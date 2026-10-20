<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\OrderStatus;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class OrderController extends Controller
{
    public function index(Request $request): View
    {
        $data = $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::in(array_keys(OrderStatus::labels()))],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
        ]);

        $query = DB::table('don_hangs');

        if (isset($data['q']) && trim($data['q']) !== '') {
            $term = trim($data['q']);
            $query->where(function ($query) use ($term): void {
                $query->where('ma_don_hang', 'like', "%{$term}%")
                    ->orWhere('ten_nguoi_nhan', 'like', "%{$term}%")
                    ->orWhere('email_nguoi_nhan', 'like', "%{$term}%")
                    ->orWhere('sdt_nguoi_nhan', 'like', "%{$term}%");
            });
        }

        if (isset($data['status']) && $data['status'] !== '') {
            $query->where('trang_thai', (int) $data['status']);
        }

        if (! empty($data['from'])) {
            $query->whereDate('ngay_dat', '>=', $data['from']);
        }

        if (! empty($data['to'])) {
            $query->whereDate('ngay_dat', '<=', $data['to']);
        }

        return view('admin.orders.index', [
            'orders' => $query->orderByDesc('ngay_dat')
                ->orderByDesc('id')
                ->paginate(10)
                ->withQueryString(),
            'statuses' => OrderStatus::labels(),
            'statusBadges' => OrderStatus::badges(),
            'counts' => [
                'total' => DB::table('don_hangs')->count(),
                'pending' => DB::table('don_hangs')->where('trang_thai', OrderStatus::Pending->value)->count(),
                'shipping' => DB::table('don_hangs')->where('trang_thai', OrderStatus::Shipping->value)->count(),
                'cancelled' => DB::table('don_hangs')->where('trang_thai', OrderStatus::Cancelled->value)->count(),
            ],
        ]);
    }

    public function show(int $order): View
    {
        $order = DB::table('don_hangs')->where('id', $order)->firstOrFail();
        $status = OrderStatus::tryFrom((int) $order->trang_thai);
        $order->status_label = $status?->label() ?? 'Không xác định';
        $order->status_badge = $status?->badge() ?? 'secondary';
        $order->allowed_statuses = $status?->nextStatuses() ?? [];
        $order->items = DB::table('chi_tiet_don_hangs')
            ->where('don_hang_id', $order->id)
            ->orderBy('id')
            ->get();
        $order->status_history = DB::table('lich_su_trang_thai_don_hangs')
            ->where('don_hang_id', $order->id)
            ->orderBy('ngay_cap_nhat')
            ->orderBy('id')
            ->get()
            ->map(function ($entry) {
                $status = OrderStatus::tryFrom((int) $entry->trang_thai);
                $entry->status_label = $status?->label() ?? 'Không xác định';
                $entry->status_badge = $status?->badge() ?? 'secondary';

                return $entry;
            });

        return view('admin.orders.show', [
            'order' => $order,
            'statuses' => OrderStatus::labels(),
        ]);
    }

    public function update(Request $request, int $order): RedirectResponse
    {
        $data = $request->validate([
            'trang_thai' => ['required', 'integer', Rule::in(array_keys(OrderStatus::labels()))],
            'ghi_chu' => ['nullable', 'string', 'max:255'],
        ], [
            'trang_thai.required' => 'Vui lòng chọn trạng thái đơn hàng.',
            'trang_thai.in' => 'Trạng thái đơn hàng không hợp lệ.',
            'ghi_chu.max' => 'Ghi chú không được vượt quá 255 ký tự.',
        ]);

        DB::transaction(function () use ($data, $order): void {
            $record = DB::table('don_hangs')
                ->where('id', $order)
                ->lockForUpdate()
                ->firstOrFail();

            $currentStatus = OrderStatus::tryFrom((int) $record->trang_thai);
            $targetStatus = OrderStatus::from((int) $data['trang_thai']);
            $allowedStatuses = $currentStatus?->nextStatuses() ?? [];

            if (! in_array($targetStatus, $allowedStatuses, true)) {
                throw ValidationException::withMessages([
                    'trang_thai' => 'Không thể chuyển đơn hàng từ trạng thái hiện tại sang trạng thái đã chọn.',
                ]);
            }

            $note = trim((string) ($data['ghi_chu'] ?? ''));
            if ($targetStatus === OrderStatus::Cancelled && $note === '') {
                throw ValidationException::withMessages([
                    'ghi_chu' => 'Vui lòng ghi rõ lý do hủy đơn hàng.',
                ]);
            }

            DB::table('don_hangs')
                ->where('id', $record->id)
                ->update(['trang_thai' => $targetStatus->value]);

            DB::table('lich_su_trang_thai_don_hangs')->insert([
                'don_hang_id' => $record->id,
                'trang_thai' => $targetStatus->value,
                'ghi_chu' => $note !== '' ? $note : null,
                'ngay_cap_nhat' => now(),
            ]);
        });

        return redirect()
            ->route('admin.orders.show', $order)
            ->with('success', 'Đã cập nhật trạng thái đơn hàng.');
    }
}
