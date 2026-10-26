<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
            });
        }

        if ($request->filled('status') && is_numeric($request->query('status'))) {
            $query->where('orders.trang_thai', (int) $request->query('status'));
        }

        if ($request->filled('payment_status') && is_numeric($request->query('payment_status'))) {
            $query->where('orders.trang_thai_thanh_toan', (int) $request->query('payment_status'));
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
        $order = $this->orderQuery()
            ->where('orders.id', $order)
            ->firstOrFail();
        $order->status_label = Order::statuses()[$order->trang_thai] ?? 'Không xác định';
        $order->status_badge = Order::statusBadges()[$order->trang_thai] ?? 'secondary';
        $order->payment_status_label = Order::paymentStatusLabel(
            $order->trang_thai_thanh_toan === null ? null : (int) $order->trang_thai_thanh_toan
        );

        $items = DB::table('chi_tiet_don_hangs')
            ->where('don_hang_id', $order->id)
            ->orderBy('id')
            ->get();

        $history = Schema::hasTable('lich_su_trang_thai_don_hangs')
            ? DB::table('lich_su_trang_thai_don_hangs')
                ->where('don_hang_id', $order->id)
                ->orderByDesc('ngay_cap_nhat')
                ->orderByDesc('id')
                ->get()
            : collect();

        return view('admin.orders.show', [
            'order' => $order,
            'items' => $items,
            'history' => $history,
            'statuses' => Order::statuses(),
            'statusBadges' => Order::statusBadges(),
            'allowedStatuses' => Order::allowedNextStatuses((int) $order->trang_thai),
            'promotionDiscountsAvailable' => Schema::hasColumn('don_hangs', 'tien_giam_khuyen_mai')
                && Schema::hasColumn('don_hangs', 'tien_giam_voucher'),
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

            $record->update(['trang_thai' => $targetStatus]);

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
                'payment_methods.ten_phuong_thuc as payment_method_name',
            ]);
    }
}
