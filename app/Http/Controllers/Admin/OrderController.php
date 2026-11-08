<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\ProductVariant;
use App\Models\ReturnRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class OrderController extends Controller
{
    public function index(Request $request): View
    {
        $query = $this->orderQuery();

        if ($request->filled('q')) {
            $term = trim((string) $request->query('q'));
            $query->where(function ($query) use ($term) {
                $query->where('orders.ma_don_hang', 'like', "%{$term}%")
                    ->orWhere('orders.ten_nguoi_nhan', 'like', "%{$term}%")
                    ->orWhere('orders.email_nguoi_nhan', 'like', "%{$term}%")
                    ->orWhere('orders.sdt_nguoi_nhan', 'like', "%{$term}%")
                    ->orWhere('accounts.ho_ten', 'like', "%{$term}%");

                if (Schema::hasColumn('tai_khoans', 'email')) {
                    $query->orWhere('accounts.email', 'like', "%{$term}%");
                }
            });
        }

        if ($request->filled('status') && is_numeric($request->query('status'))) {
            $query->where('orders.trang_thai', (int) $request->query('status'));
        }

        if ($request->filled('payment_status') && is_numeric($request->query('payment_status'))) {
            $query->where('orders.trang_thai_thanh_toan', (int) $request->query('payment_status'));
        }

        $returnsAvailable = Schema::hasTable('yeu_cau_doi_tras');
        if ($returnsAvailable) {
            $query->selectSub(
                DB::table('yeu_cau_doi_tras as returns')
                    ->selectRaw('COUNT(*)')
                    ->whereColumn('returns.don_hang_id', 'orders.id'),
                'return_count',
            )->selectSub(
                DB::table('yeu_cau_doi_tras as returns')
                    ->selectRaw('COUNT(*)')
                    ->whereColumn('returns.don_hang_id', 'orders.id')
                    ->where('returns.trang_thai', ReturnRequest::STATUS_PENDING),
                'pending_return_count',
            );

            if ($request->query('return_status') === 'pending') {
                $query->whereExists(fn ($exists) => $exists
                    ->selectRaw('1')
                    ->from('yeu_cau_doi_tras as returns')
                    ->whereColumn('returns.don_hang_id', 'orders.id')
                    ->where('returns.trang_thai', ReturnRequest::STATUS_PENDING));
            }
        }

        $counts = Order::query()
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN trang_thai = ? THEN 1 ELSE 0 END) as pending', [Order::STATUS_PENDING])
            ->selectRaw('SUM(CASE WHEN trang_thai = ? THEN 1 ELSE 0 END) as processing', [Order::STATUS_PROCESSING])
            ->selectRaw('SUM(CASE WHEN trang_thai = ? THEN 1 ELSE 0 END) as shipping', [Order::STATUS_SHIPPING])
            ->first();

        return view('admin.orders.index', [
            'orders' => $query->orderByDesc('orders.ngay_dat')
                ->orderByDesc('orders.id')
                ->paginate(10)
                ->withQueryString(),
            'statuses' => Order::statuses(),
            'statusBadges' => Order::statusBadges(),
            'counts' => $counts,
        ]);
    }

    public function show(int $order): View
    {
        $order = $this->findOrderForDisplay($order);
        $items = DB::table('chi_tiet_don_hangs')
            ->where('don_hang_id', $order->id)
            ->orderBy('id')
            ->get();

        return view('admin.orders.show', [
            'order' => $order,
            'items' => $items,
            'history' => $this->statusHistory($order->id),
            'statuses' => Order::statuses(),
            'statusBadges' => Order::statusBadges(),
            'allowedStatuses' => Order::allowedNextStatuses((int) $order->trang_thai),
            'promotionDiscountsAvailable' => Schema::hasColumn('don_hangs', 'tien_giam_khuyen_mai')
                && Schema::hasColumn('don_hangs', 'tien_giam_voucher'),
            ...$this->returnDetails($order, $items),
        ]);
    }

    public function update(Request $request, int $order): RedirectResponse
    {
        $data = $request->validate([
            'trang_thai' => ['required', 'integer', Rule::in(array_keys(Order::statuses()))],
            'ghi_chu' => ['nullable', 'string', 'max:255'],
        ], [
            'trang_thai.required' => 'Vui lòng chọn trạng thái đơn hàng.',
            'trang_thai.in' => 'Trạng thái đơn hàng không hợp lệ.',
            'ghi_chu.max' => 'Ghi chú không được vượt quá 255 ký tự.',
        ]);

        DB::transaction(function () use ($order, $data) {
            $record = Order::query()->whereKey($order)->lockForUpdate()->firstOrFail();
            $targetStatus = (int) $data['trang_thai'];

            if (! in_array($targetStatus, Order::allowedNextStatuses((int) $record->trang_thai), true)) {
                throw ValidationException::withMessages([
                    'trang_thai' => 'Không thể chuyển đơn hàng từ trạng thái hiện tại sang trạng thái đã chọn.',
                ]);
            }

            $changes = ['trang_thai' => $targetStatus];

            if ($targetStatus === Order::STATUS_COMPLETED) {
                if (Schema::hasColumn('don_hangs', 'ngay_hoan_thanh')) {
                    $changes['ngay_hoan_thanh'] = now();
                }

                if ((int) $record->trang_thai_thanh_toan !== Order::PAYMENT_PAID) {
                    $changes['trang_thai_thanh_toan'] = Order::PAYMENT_PAID;
                }
            }

            if ($targetStatus === Order::STATUS_CANCELLED) {
                $this->restockCancelledOrder($record->getKey());
            }

            $record->update($changes);

            if (Schema::hasTable('lich_su_trang_thai_don_hangs')) {
                DB::table('lich_su_trang_thai_don_hangs')->insert([
                    'don_hang_id' => $record->getKey(),
                    'trang_thai' => $targetStatus,
                    'ghi_chu' => trim((string) ($data['ghi_chu'] ?? '')) ?: null,
                    'nguoi_cap_nhat_id' => null,
                    'ngay_cap_nhat' => now(),
                ]);
            }
        });

        return redirect()
            ->route('admin.orders.show', $order)
            ->with('success', 'Đã cập nhật trạng thái đơn hàng.');
    }

    /**
     * Return/exchange data shown on the order detail page.
     *
     * @param  Collection<int, object>  $items
     * @return array{returnRequests: Collection<int, ReturnRequest>, returnableItems: Collection<int, object>, returnDeadline: ?Carbon, canCreateReturn: bool, replacementVariants: Collection<int, ProductVariant>}
     */
    private function returnDetails(object $order, Collection $items): array
    {
        $details = [
            'returnRequests' => collect(),
            'returnableItems' => collect(),
            'returnDeadline' => null,
            'canCreateReturn' => false,
            'replacementVariants' => collect(),
        ];

        if (! Schema::hasTable('yeu_cau_doi_tras')) {
            return $details;
        }

        $itemsById = $items->keyBy('id');
        $orderSubtotal = (int) $items->sum(fn (object $item): float => (float) ($item->thanh_tien ?? 0));
        $voucherDiscount = (int) ($order->tien_giam_voucher ?? 0);

        $returnRequests = ReturnRequest::query()
            ->where('don_hang_id', $order->id)
            ->orderByDesc('ngay_tao')
            ->orderByDesc('id')
            ->get();
        $replacementLabels = ProductVariant::query()
            ->with('product:id,ten_san_pham')
            ->whereIn('id', $returnRequests->pluck('bien_the_thay_the_id')->filter())
            ->get()
            ->mapWithKeys(fn (ProductVariant $variant): array => [$variant->id => $this->variantLabel($variant)]);

        $returnRequests->each(function (ReturnRequest $returnRequest) use ($itemsById, $replacementLabels, $voucherDiscount, $orderSubtotal): void {
            $item = $itemsById->get($returnRequest->chi_tiet_don_hang_id);
            $returnRequest->product_name = $item->ten_san_pham ?? 'Sản phẩm';
            $returnRequest->replacement_label = $replacementLabels->get($returnRequest->bien_the_thay_the_id);
            $returnRequest->refundable_amount = ReturnRequest::refundableAmount(
                (int) ($item->don_gia ?? 0),
                (int) $returnRequest->so_luong,
                $voucherDiscount,
                $orderSubtotal,
            );
        });

        $requestedQuantities = $returnRequests
            ->where('trang_thai', '!=', ReturnRequest::STATUS_REJECTED)
            ->groupBy('chi_tiet_don_hang_id')
            ->map(fn (Collection $requests): int => (int) $requests->sum('so_luong'));
        $returnableItems = $items
            ->map(function (object $item) use ($requestedQuantities): object {
                $item->available_return_quantity = max(
                    0,
                    (int) ($item->so_luong ?? 0) - (int) ($requestedQuantities[$item->id] ?? 0),
                );

                return $item;
            })
            ->filter(fn (object $item): bool => $item->available_return_quantity > 0)
            ->values();

        $isCompleted = (int) $order->trang_thai === Order::STATUS_COMPLETED;
        $needsReplacement = $returnRequests->contains(fn (ReturnRequest $returnRequest): bool => (int) $returnRequest->loai_yeu_cau === ReturnRequest::TYPE_EXCHANGE
            && (int) $returnRequest->trang_thai === ReturnRequest::STATUS_RETURN_RECEIVED);

        return [
            'returnRequests' => $returnRequests,
            'returnableItems' => $returnableItems,
            'returnDeadline' => $isCompleted ? ReturnRequest::returnDeadline($order) : null,
            'canCreateReturn' => $isCompleted
                && ReturnRequest::isWithinReturnWindow($order)
                && $returnableItems->isNotEmpty(),
            'replacementVariants' => $needsReplacement
                ? ProductVariant::query()
                    ->with('product:id,ten_san_pham')
                    ->where('status', ProductVariant::STATUS_ACTIVE)
                    ->where('stock', '>', 0)
                    ->orderBy('product_id')
                    ->orderBy('sku')
                    ->get()
                : collect(),
        ];
    }

    private function variantLabel(ProductVariant $variant): string
    {
        $options = collect([$variant->color, $variant->size])->filter()->implode(' / ');

        return trim(($variant->product?->ten_san_pham ?: 'Sản phẩm').' · '.$variant->sku.($options !== '' ? ' ('.$options.')' : ''));
    }

    private function findOrderForDisplay(int $orderId): object
    {
        $order = $this->orderQuery()
            ->where('orders.id', $orderId)
            ->firstOrFail();
        $order->status_label = Order::statuses()[$order->trang_thai] ?? 'Không xác định';
        $order->status_badge = Order::statusBadges()[$order->trang_thai] ?? 'secondary';
        $order->payment_status_label = Order::paymentStatusLabel(
            $order->trang_thai_thanh_toan === null ? null : (int) $order->trang_thai_thanh_toan
        );

        return $order;
    }

    private function statusHistory(int $orderId): Collection
    {
        return Schema::hasTable('lich_su_trang_thai_don_hangs')
            ? DB::table('lich_su_trang_thai_don_hangs')
                ->where('don_hang_id', $orderId)
                ->orderByDesc('ngay_cap_nhat')
                ->orderByDesc('id')
                ->get()
            : collect();
    }

    private function restockCancelledOrder(int $orderId): void
    {
        if (! Schema::hasColumn('chi_tiet_don_hangs', 'product_variant_id')) {
            return;
        }

        DB::table('chi_tiet_don_hangs')
            ->where('don_hang_id', $orderId)
            ->whereNotNull('product_variant_id')
            ->get(['product_variant_id', 'so_luong'])
            ->each(function (object $item): void {
                ProductVariant::query()
                    ->whereKey($item->product_variant_id)
                    ->increment('stock', (int) $item->so_luong);
            });
    }

    private function orderQuery()
    {
        return DB::table('don_hangs as orders')
            ->leftJoin('tai_khoans as accounts', 'accounts.id', '=', 'orders.tai_khoan_id')
            ->leftJoin(
                'phuong_thuc_thanh_toans as payment_methods',
                'payment_methods.id',
                '=',
                'orders.phuong_thuc_thanh_toan_id'
            )
            ->select([
                'orders.*',
                'accounts.ho_ten as account_name',
                Schema::hasColumn('tai_khoans', 'email')
                    ? 'accounts.email as account_email'
                    : DB::raw('NULL as account_email'),
                Schema::hasColumn('tai_khoans', 'so_dien_thoai')
                    ? 'accounts.so_dien_thoai as account_phone'
                    : DB::raw('NULL as account_phone'),
                'payment_methods.ten_phuong_thuc as payment_method_name',
            ]);
    }
}
