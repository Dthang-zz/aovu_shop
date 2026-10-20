<?php

namespace App\Http\Controllers;

use App\OrderStatus;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderTrackingController extends Controller
{
    public function show(): View
    {
        return view('orders.track', [
            'order' => null,
            'searchAttempted' => false,
        ]);
    }

    public function lookup(Request $request): View
    {
        $data = $request->validate([
            'ma_don_hang' => ['required', 'string', 'max:50'],
            'lien_he' => ['required', 'string', 'max:255'],
        ], [
            'ma_don_hang.required' => 'Vui lòng nhập mã đơn hàng.',
            'lien_he.required' => 'Vui lòng nhập email hoặc số điện thoại đặt hàng.',
        ]);

        $contact = trim($data['lien_he']);
        $order = DB::table('don_hangs')
            ->where('ma_don_hang', trim($data['ma_don_hang']))
            ->where(function ($query) use ($contact): void {
                $query->where('email_nguoi_nhan', $contact)
                    ->orWhere('sdt_nguoi_nhan', $contact);
            })
            ->first();

        if ($order === null) {
            return view('orders.track', [
                'order' => null,
                'searchAttempted' => true,
            ]);
        }

        $status = OrderStatus::tryFrom((int) $order->trang_thai);
        $order->status_label = $status?->label() ?? 'Không xác định';
        $order->status_badge = $status?->badge() ?? 'secondary';
        $items = DB::table('chi_tiet_don_hangs')
            ->where('don_hang_id', $order->id)
            ->orderBy('id')
            ->get();
        $history = DB::table('lich_su_trang_thai_don_hangs')
            ->where('don_hang_id', $order->id)
            ->orderBy('ngay_cap_nhat')
            ->orderBy('id')
            ->get()
            ->map(function ($entry) {
                $status = OrderStatus::tryFrom((int) $entry->trang_thai);
                $entry->status_label = $status?->label() ?? 'Không xác định';

                return $entry;
            });

        if ($history->isEmpty()) {
            $history->push((object) [
                'status_label' => $order->status_label,
                'ngay_cap_nhat' => $order->ngay_dat,
                'ghi_chu' => null,
            ]);
        }

        return view('orders.track', [
            'order' => $order,
            'items' => $items,
            'history' => $history,
            'searchAttempted' => true,
        ]);
    }
}
