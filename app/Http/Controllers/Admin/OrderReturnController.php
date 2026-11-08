<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\ProductVariant;
use App\Models\ReturnRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class OrderReturnController extends Controller
{
    /**
     * Validation errors of the return forms use their own bag so they don't mix with the order status form.
     */
    public const ERROR_BAG = 'returns';

    public function store(Request $request, int $order): RedirectResponse
    {
        $data = $request->validateWithBag(self::ERROR_BAG, [
            'chi_tiet_don_hang_id' => ['required', 'integer'],
            'loai_yeu_cau' => ['required', 'integer', Rule::in(array_keys(ReturnRequest::types()))],
            'so_luong' => ['required', 'integer', 'min:1'],
            'ly_do' => ['required', 'string', 'max:5000'],
        ], [
            'chi_tiet_don_hang_id.required' => 'Vui lòng chọn sản phẩm cần đổi/trả.',
            'loai_yeu_cau.required' => 'Vui lòng chọn loại yêu cầu.',
            'loai_yeu_cau.in' => 'Loại yêu cầu không hợp lệ.',
            'so_luong.required' => 'Vui lòng nhập số lượng.',
            'so_luong.min' => 'Số lượng phải ít nhất là 1.',
            'ly_do.required' => 'Vui lòng nhập lý do đổi/trả.',
            'ly_do.max' => 'Lý do không được vượt quá 5000 ký tự.',
        ]);

        $returnRequest = DB::transaction(function () use ($data, $order): ReturnRequest {
            $orderRecord = DB::table('don_hangs')
                ->where('id', $order)
                ->lockForUpdate()
                ->first(array_values(array_filter(
                    ['id', 'tai_khoan_id', 'trang_thai', 'ngay_dat', 'ngay_hoan_thanh'],
                    fn (string $column): bool => Schema::hasColumn('don_hangs', $column),
                )));
            abort_if($orderRecord === null, 404);

            if ((int) $orderRecord->trang_thai !== Order::STATUS_COMPLETED) {
                $this->fail('chi_tiet_don_hang_id', 'Chỉ được tạo yêu cầu đổi/trả cho đơn hàng đã hoàn thành.');
            }

            if (! ReturnRequest::isWithinReturnWindow($orderRecord)) {
                $this->fail(
                    'chi_tiet_don_hang_id',
                    'Đơn hàng đã quá hạn đổi/trả '.ReturnRequest::RETURN_WINDOW_DAYS.' ngày.',
                );
            }

            $orderItem = DB::table('chi_tiet_don_hangs')
                ->where('id', $data['chi_tiet_don_hang_id'])
                ->where('don_hang_id', $orderRecord->id)
                ->lockForUpdate()
                ->first(['id', 'so_luong']);
            if ($orderItem === null) {
                $this->fail('chi_tiet_don_hang_id', 'Sản phẩm không thuộc đơn hàng này.');
            }

            $alreadyRequested = (int) ReturnRequest::query()
                ->where('chi_tiet_don_hang_id', $orderItem->id)
                ->where('trang_thai', '!=', ReturnRequest::STATUS_REJECTED)
                ->sum('so_luong');
            $availableQuantity = max(0, (int) ($orderItem->so_luong ?? 0) - $alreadyRequested);
            if ((int) $data['so_luong'] > $availableQuantity) {
                $this->fail(
                    'so_luong',
                    'Số lượng vượt quá số sản phẩm còn có thể đổi/trả ('.$availableQuantity.').',
                );
            }

            return ReturnRequest::query()->create([
                'don_hang_id' => $orderRecord->id,
                'chi_tiet_don_hang_id' => $orderItem->id,
                'tai_khoan_id' => $orderRecord->tai_khoan_id ?? null,
                'loai_yeu_cau' => (int) $data['loai_yeu_cau'],
                'so_luong' => (int) $data['so_luong'],
                'ly_do' => trim($data['ly_do']),
                'ngay_tao' => now(),
                'trang_thai' => ReturnRequest::STATUS_PENDING,
            ]);
        });

        return $this->backToOrder($order, 'Đã tạo yêu cầu đổi/trả '.self::code($returnRequest->getKey()).'.');
    }

    public function update(Request $request, int $order, int $returnRequest): RedirectResponse
    {
        $data = $request->validateWithBag(self::ERROR_BAG, [
            'thao_tac' => ['required', 'string', Rule::in([
                ReturnRequest::ACTION_APPROVE,
                ReturnRequest::ACTION_REJECT,
                ReturnRequest::ACTION_RECEIVE,
                ReturnRequest::ACTION_COMPLETE,
            ])],
            'phan_hoi' => ['nullable', 'string', 'max:5000'],
            'so_tien_hoan' => ['nullable', 'integer', 'min:1', 'max:999999999999'],
            'ma_giao_dich_hoan' => ['nullable', 'string', 'max:100'],
            'bien_the_thay_the_id' => ['nullable', 'integer'],
        ], [
            'thao_tac.required' => 'Thao tác không hợp lệ.',
            'thao_tac.in' => 'Thao tác không hợp lệ.',
            'phan_hoi.max' => 'Phản hồi không được vượt quá 5000 ký tự.',
            'so_tien_hoan.integer' => 'Số tiền hoàn phải là số nguyên dương.',
            'so_tien_hoan.min' => 'Số tiền hoàn phải lớn hơn 0.',
            'ma_giao_dich_hoan.max' => 'Mã giao dịch không được vượt quá 100 ký tự.',
        ]);

        $message = DB::transaction(function () use ($data, $order, $returnRequest): string {
            $record = ReturnRequest::query()
                ->whereKey($returnRequest)
                ->where('don_hang_id', $order)
                ->lockForUpdate()
                ->firstOrFail();
            $action = $data['thao_tac'];

            if (! in_array($action, ReturnRequest::allowedActions((int) $record->trang_thai), true)) {
                $this->fail('thao_tac', 'Không thể thực hiện thao tác này ở trạng thái hiện tại của yêu cầu.');
            }

            $staffReply = trim((string) ($data['phan_hoi'] ?? ''));
            $changes = $staffReply !== '' ? ['phan_hoi' => $staffReply] : [];

            switch ($action) {
                case ReturnRequest::ACTION_APPROVE:
                    $changes['trang_thai'] = ReturnRequest::STATUS_APPROVED;
                    $message = 'Đã duyệt yêu cầu';
                    break;

                case ReturnRequest::ACTION_REJECT:
                    if ($staffReply === '') {
                        $this->fail('phan_hoi', 'Vui lòng nhập lý do từ chối.');
                    }
                    $changes['trang_thai'] = ReturnRequest::STATUS_REJECTED;
                    $message = 'Đã từ chối yêu cầu';
                    break;

                case ReturnRequest::ACTION_RECEIVE:
                    $changes['trang_thai'] = ReturnRequest::STATUS_RETURN_RECEIVED;
                    $changes['ngay_nhan_hang'] = now();
                    $message = 'Đã ghi nhận nhận hàng cho yêu cầu';
                    break;

                default:
                    $changes = [...$changes, ...$this->completionChanges($record, $data)];
                    $this->restockReturnedItem($record);
                    $changes['trang_thai'] = ReturnRequest::STATUS_COMPLETED;
                    $changes['ngay_hoan_tat'] = now();
                    $message = 'Đã hoàn tất yêu cầu';
            }

            $record->update($changes);

            return $message.' '.self::code($record->getKey()).'.';
        });

        return $this->backToOrder($order, $message);
    }

    /**
     * Old return/refund-wallet list URLs now point to the orders that still have returns waiting for approval.
     */
    public function legacyIndex(): RedirectResponse
    {
        return redirect()->route('admin.orders.index', ['return_status' => 'pending']);
    }

    /**
     * Old return detail/edit URLs now open the order the request belongs to.
     */
    public function legacyShow(int $returnRequest): RedirectResponse
    {
        $orderId = ReturnRequest::query()->whereKey($returnRequest)->value('don_hang_id');
        abort_if($orderId === null, 404);

        return redirect()->to(route('admin.orders.show', $orderId).'#returns');
    }

    /**
     * Display code of a return request, e.g. #DT-000012.
     */
    public static function code(int $returnRequestId): string
    {
        return '#DT-'.str_pad((string) $returnRequestId, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Refund details for a return, or the replacement variant (with its stock taken) for an exchange.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function completionChanges(ReturnRequest $record, array $data): array
    {
        if ((int) $record->loai_yeu_cau === ReturnRequest::TYPE_RETURN) {
            if (empty($data['so_tien_hoan'])) {
                $this->fail('so_tien_hoan', 'Vui lòng nhập số tiền đã hoàn cho khách.');
            }

            $refundableAmount = $this->refundableAmount($record);
            if ((int) $data['so_tien_hoan'] > $refundableAmount) {
                $this->fail(
                    'so_tien_hoan',
                    'Số tiền hoàn vượt quá giá trị hàng trả ('.number_format($refundableAmount, 0, ',', '.').' ₫).',
                );
            }

            $reference = trim((string) ($data['ma_giao_dich_hoan'] ?? ''));

            return [
                'so_tien_hoan' => (int) $data['so_tien_hoan'],
                'ma_giao_dich_hoan' => $reference !== '' ? $reference : null,
            ];
        }

        if (empty($data['bien_the_thay_the_id'])) {
            $this->fail('bien_the_thay_the_id', 'Vui lòng chọn biến thể thay thế để gửi cho khách.');
        }

        $quantity = (int) $record->so_luong;
        $replacement = ProductVariant::query()
            ->whereKey($data['bien_the_thay_the_id'])
            ->where('status', ProductVariant::STATUS_ACTIVE)
            ->lockForUpdate()
            ->first(['id', 'stock']);
        if ($replacement === null || (int) $replacement->stock < $quantity) {
            $this->fail('bien_the_thay_the_id', 'Biến thể thay thế không còn bán hoặc không đủ tồn kho.');
        }

        $replacement->decrement('stock', $quantity);

        return ['bien_the_thay_the_id' => $replacement->getKey()];
    }

    /**
     * Amount the customer paid for the returned units, net of their share of the order voucher.
     */
    private function refundableAmount(ReturnRequest $record): int
    {
        $item = DB::table('chi_tiet_don_hangs')
            ->where('id', $record->chi_tiet_don_hang_id)
            ->first(['don_gia']);
        $voucherDiscount = Schema::hasColumn('don_hangs', 'tien_giam_voucher')
            ? (int) DB::table('don_hangs')->where('id', $record->don_hang_id)->value('tien_giam_voucher')
            : 0;
        $orderSubtotal = Schema::hasColumn('chi_tiet_don_hangs', 'thanh_tien')
            ? (int) DB::table('chi_tiet_don_hangs')->where('don_hang_id', $record->don_hang_id)->sum('thanh_tien')
            : 0;

        return ReturnRequest::refundableAmount(
            (int) ($item->don_gia ?? 0),
            (int) $record->so_luong,
            $voucherDiscount,
            $orderSubtotal,
        );
    }

    /**
     * Put the units the customer sent back into the stock of the variant they originally bought.
     */
    private function restockReturnedItem(ReturnRequest $record): void
    {
        if (! Schema::hasColumn('chi_tiet_don_hangs', 'product_variant_id')) {
            return;
        }

        $variantId = DB::table('chi_tiet_don_hangs')
            ->where('id', $record->chi_tiet_don_hang_id)
            ->value('product_variant_id');

        if ($variantId !== null) {
            ProductVariant::query()->whereKey($variantId)->increment('stock', (int) $record->so_luong);
        }
    }

    private function fail(string $field, string $message): never
    {
        throw ValidationException::withMessages([$field => $message])->errorBag(self::ERROR_BAG);
    }

    private function backToOrder(int $order, string $message): RedirectResponse
    {
        return redirect()
            ->to(route('admin.orders.show', $order).'#returns')
            ->with('return_success', $message);
    }
}
