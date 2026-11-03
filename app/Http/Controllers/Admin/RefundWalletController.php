<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ReturnRequest;
use App\Models\ReturnRequestHistory;
use App\Models\ReturnRequestWorkflow;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class RefundWalletController extends Controller
{
    /**
     * Return statuses that cannot be paid yet, with the step CSKH still has to complete.
     *
     * @var array<int, string>
     */
    private const REFUND_PREREQUISITE_STEPS = [
        ReturnRequest::STATUS_PENDING => 'CSKH tiếp nhận yêu cầu',
        ReturnRequest::STATUS_PROCESSING => 'Duyệt yêu cầu (khách đồng ý, kiểm tra chính sách)',
        ReturnRequest::STATUS_APPROVED => 'Chờ khách gửi hàng hoặc miễn thu hồi để kiểm tra',
        ReturnRequest::STATUS_AWAITING_RETURN => 'Xác nhận đã nhận hàng hoàn',
        ReturnRequest::STATUS_RETURN_RECEIVED => 'Kiểm tra hàng và ghi kết quả',
    ];

    public function index(): View
    {
        $returnDetails = $this->returnDetailsQuery()
            ->where('returns.loai_yeu_cau', ReturnRequest::TYPE_RETURN);

        $pendingRefunds = (clone $returnDetails)
            ->where('returns.trang_thai', ReturnRequest::STATUS_INSPECTED)
            ->orderByDesc('returns.ngay_tao')
            ->orderByDesc('returns.id')
            ->paginate(10, ['*'], 'pending_page');
        $pendingRefunds->getCollection()->each(function (object $refund): void {
            $refund->refundable_amount = $this->refundableAmount($refund);
        });

        $inProgressStatuses = array_keys(self::REFUND_PREREQUISITE_STEPS);
        $inProgressRefunds = (clone $returnDetails)
            ->whereIn('returns.trang_thai', $inProgressStatuses)
            ->orderByDesc('returns.ngay_tao')
            ->orderByDesc('returns.id')
            ->paginate(10, ['*'], 'progress_page');
        $inProgressRefunds->getCollection()->each(function (object $refund): void {
            $refund->refundable_amount = $this->refundableAmount($refund);
            $refund->status_label = ReturnRequest::statuses()[$refund->trang_thai] ?? 'Không xác định';
            $refund->status_badge = ReturnRequest::statusBadges()[$refund->trang_thai] ?? 'secondary';
            $refund->next_step = self::REFUND_PREREQUISITE_STEPS[$refund->trang_thai];
        });

        $refundHistory = (clone $returnDetails)
            ->whereNotNull('workflow.refunded_at')
            ->orderByDesc('workflow.refunded_at')
            ->orderByDesc('returns.id')
            ->paginate(10, ['*'], 'history_page');

        $refundedThisMonth = DB::table('support_return_workflows as workflow')
            ->join('yeu_cau_doi_tras as returns', 'returns.id', '=', 'workflow.return_request_id')
            ->where('returns.loai_yeu_cau', ReturnRequest::TYPE_RETURN)
            ->whereNotNull('refunded_at')
            ->whereBetween('workflow.refunded_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->sum('workflow.refund_amount');

        return view('admin.finance.refunds.index', [
            'pendingRefunds' => $pendingRefunds,
            'inProgressRefunds' => $inProgressRefunds,
            'inProgressCount' => (clone $returnDetails)
                ->whereIn('returns.trang_thai', $inProgressStatuses)
                ->count(),
            'refundHistory' => $refundHistory,
            'pendingCount' => (clone $returnDetails)
                ->where('returns.trang_thai', ReturnRequest::STATUS_INSPECTED)
                ->count(),
            'refundedThisMonth' => (int) $refundedThisMonth,
        ]);
    }

    public function confirmManualPayment(Request $request, int $returnRequest): RedirectResponse
    {
        $data = $request->validate([
            'refund_amount' => ['required', 'integer', 'min:1', 'max:999999999999'],
            'refund_reference' => ['nullable', 'string', 'max:100'],
            'confirmed' => ['accepted'],
        ], [
            'refund_amount.required' => 'Vui lòng nhập số tiền đã chuyển.',
            'refund_amount.integer' => 'Số tiền hoàn phải là số nguyên dương.',
            'refund_amount.min' => 'Số tiền hoàn phải lớn hơn 0.',
            'refund_reference.max' => 'Mã giao dịch không được vượt quá 100 ký tự.',
            'confirmed.accepted' => 'Vui lòng xác nhận bạn đã chuyển khoản cho khách hàng.',
        ]);

        DB::transaction(function () use ($data, $returnRequest): void {
            $refund = ReturnRequest::query()->lockForUpdate()->findOrFail($returnRequest);

            abort_unless($refund->loai_yeu_cau === ReturnRequest::TYPE_RETURN, 404);

            if ($refund->trang_thai !== ReturnRequest::STATUS_INSPECTED) {
                throw ValidationException::withMessages([
                    'refund' => 'Chỉ có thể xác nhận chuyển khoản cho phiếu trả hàng đã kiểm tra.',
                ]);
            }

            $workflow = ReturnRequestWorkflow::query()
                ->where('return_request_id', $refund->getKey())
                ->lockForUpdate()
                ->first();

            if ($workflow === null) {
                $workflow = ReturnRequestWorkflow::query()->create([
                    'return_request_id' => $refund->getKey(),
                ]);
            }

            if ($workflow->refunded_at !== null) {
                throw ValidationException::withMessages([
                    'refund' => 'Phiếu này đã được xác nhận hoàn tiền.',
                ]);
            }

            $refundableAmount = $this->refundableAmount(
                $this->returnDetailsQuery()->where('returns.id', $refund->getKey())->first(),
            );
            if ((int) $data['refund_amount'] > $refundableAmount) {
                throw ValidationException::withMessages([
                    'refund_amount' => 'Số tiền hoàn vượt quá giá trị hàng trả ('
                        .number_format($refundableAmount, 0, ',', '.').' ₫).',
                ]);
            }

            $paidAt = now();
            $reference = trim((string) ($data['refund_reference'] ?? ''));
            $workflow->update([
                'refund_amount' => $data['refund_amount'],
                'refund_method' => 'manual_bank_transfer',
                'refund_reference' => $reference !== '' ? $reference : null,
                'refunded_at' => $paidAt,
            ]);
            $refund->update(['trang_thai' => ReturnRequest::STATUS_REFUNDED]);
            ReturnRequestHistory::query()->create([
                'return_request_id' => $refund->getKey(),
                'from_status' => ReturnRequest::STATUS_INSPECTED,
                'to_status' => ReturnRequest::STATUS_REFUNDED,
                'actor_name' => 'Tài chính',
                'note' => 'Đã xác nhận chuyển khoản thủ công'.($reference !== '' ? ': '.$reference : '.'),
                'created_at' => $paidAt,
            ]);
        });

        return redirect()
            ->route('admin.finance.refunds.index')
            ->with('success', 'Đã xác nhận khách hàng được hoàn tiền bằng chuyển khoản thủ công.');
    }

    private function returnDetailsQuery(): Builder
    {
        return DB::table('yeu_cau_doi_tras as returns')
            ->leftJoin('don_hangs as orders', 'orders.id', '=', 'returns.don_hang_id')
            ->leftJoin('chi_tiet_don_hangs as order_items', 'order_items.id', '=', 'returns.chi_tiet_don_hang_id')
            ->leftJoin('tai_khoans as accounts', 'accounts.id', '=', 'returns.tai_khoan_id')
            ->leftJoin('support_return_workflows as workflow', 'workflow.return_request_id', '=', 'returns.id')
            ->select([
                'returns.id',
                'returns.trang_thai',
                'returns.ngay_tao',
                'returns.so_luong',
                'orders.ma_don_hang as order_number',
                Schema::hasColumn('don_hangs', 'tien_giam_voucher')
                    ? 'orders.tien_giam_voucher as order_voucher_discount'
                    : DB::raw('0 as order_voucher_discount'),
                'accounts.ho_ten as customer_name',
                'order_items.ten_san_pham as product_name',
                'order_items.don_gia as item_price',
                'workflow.refund_amount',
                'workflow.refund_method',
                'workflow.refund_reference',
                'workflow.refunded_at',
            ])
            ->selectSub(
                DB::table('chi_tiet_don_hangs as order_lines')
                    ->selectRaw('COALESCE(SUM(order_lines.thanh_tien), 0)')
                    ->whereColumn('order_lines.don_hang_id', 'returns.don_hang_id'),
                'order_subtotal',
            );
    }

    private function refundableAmount(?object $refund): int
    {
        if ($refund === null) {
            return 0;
        }

        return ReturnRequest::refundableAmount(
            (int) ($refund->item_price ?? 0),
            (int) ($refund->so_luong ?? 0),
            (int) ($refund->order_voucher_discount ?? 0),
            (int) ($refund->order_subtotal ?? 0),
        );
    }
}
