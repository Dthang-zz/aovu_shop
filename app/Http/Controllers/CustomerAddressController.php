<?php

namespace App\Http\Controllers;

use App\Models\DiaChi;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class CustomerAddressController extends Controller
{
    public function index(): View
    {
        $customerId = (int) Session::get('customer_id');

        $addresses = DiaChi::query()
            ->where('tai_khoan_id', $customerId)
            ->orderByDesc('mac_dinh')
            ->orderByDesc('id')
            ->get();

        return view('storefront.customer.addresses', [
            'addresses' => $addresses,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $customerId = (int) Session::get('customer_id');

        $data = $request->validate([
            'ten_nguoi_nhan' => ['required', 'string', 'max:100'],
            'sdt_nguoi_nhan' => ['required', 'string', 'max:15'],
            'tinh_thanh' => ['required', 'string', 'max:100'],
            'quan_huyen' => ['required', 'string', 'max:100'],
            'phuong_xa' => ['required', 'string', 'max:100'],
            'dia_chi_chi_tiet' => ['required', 'string', 'max:255'],
            'mac_dinh' => ['nullable', 'boolean'],
        ], [
            'ten_nguoi_nhan.required' => 'Vui lòng nhập tên người nhận hàng.',
            'sdt_nguoi_nhan.required' => 'Vui lòng nhập số điện thoại người nhận.',
            'tinh_thanh.required' => 'Vui lòng nhập Tỉnh / Thành phố.',
            'quan_huyen.required' => 'Vui lòng nhập Quận / Huyện.',
            'phuong_xa.required' => 'Vui lòng nhập Phường / Xã.',
            'dia_chi_chi_tiet.required' => 'Vui lòng nhập địa chỉ chi tiết (số nhà, tên đường).',
        ]);

        $hasAddresses = DiaChi::query()->where('tai_khoan_id', $customerId)->exists();
        $isDefault = ! empty($data['mac_dinh']) || ! $hasAddresses;

        if ($isDefault) {
            DiaChi::query()
                ->where('tai_khoan_id', $customerId)
                ->update(['mac_dinh' => false]);
        }

        DiaChi::query()->create([
            'tai_khoan_id' => $customerId,
            'ten_nguoi_nhan' => trim($data['ten_nguoi_nhan']),
            'sdt_nguoi_nhan' => trim($data['sdt_nguoi_nhan']),
            'tinh_thanh' => trim($data['tinh_thanh']),
            'quan_huyen' => trim($data['quan_huyen']),
            'phuong_xa' => trim($data['phuong_xa']),
            'dia_chi_chi_tiet' => trim($data['dia_chi_chi_tiet']),
            'mac_dinh' => $isDefault,
        ]);

        return redirect()->route('customer.addresses.index')
            ->with('success', 'Đã thêm địa chỉ nhận hàng mới.');
    }

    public function update(Request $request, DiaChi $address): RedirectResponse
    {
        $customerId = (int) Session::get('customer_id');
        abort_unless((int) $address->tai_khoan_id === $customerId, 403);

        $data = $request->validate([
            'ten_nguoi_nhan' => ['required', 'string', 'max:100'],
            'sdt_nguoi_nhan' => ['required', 'string', 'max:15'],
            'tinh_thanh' => ['required', 'string', 'max:100'],
            'quan_huyen' => ['required', 'string', 'max:100'],
            'phuong_xa' => ['required', 'string', 'max:100'],
            'dia_chi_chi_tiet' => ['required', 'string', 'max:255'],
            'mac_dinh' => ['nullable', 'boolean'],
        ], [
            'ten_nguoi_nhan.required' => 'Vui lòng nhập tên người nhận hàng.',
            'sdt_nguoi_nhan.required' => 'Vui lòng nhập số điện thoại người nhận.',
            'tinh_thanh.required' => 'Vui lòng nhập Tỉnh / Thành phố.',
            'quan_huyen.required' => 'Vui lòng nhập Quận / Huyện.',
            'phuong_xa.required' => 'Vui lòng nhập Phường / Xã.',
            'dia_chi_chi_tiet.required' => 'Vui lòng nhập địa chỉ chi tiết.',
        ]);

        $isDefault = ! empty($data['mac_dinh']);

        if ($isDefault) {
            DiaChi::query()
                ->where('tai_khoan_id', $customerId)
                ->where('id', '!=', $address->id)
                ->update(['mac_dinh' => false]);
        }

        $address->update([
            'ten_nguoi_nhan' => trim($data['ten_nguoi_nhan']),
            'sdt_nguoi_nhan' => trim($data['sdt_nguoi_nhan']),
            'tinh_thanh' => trim($data['tinh_thanh']),
            'quan_huyen' => trim($data['quan_huyen']),
            'phuong_xa' => trim($data['phuong_xa']),
            'dia_chi_chi_tiet' => trim($data['dia_chi_chi_tiet']),
            'mac_dinh' => $isDefault ? true : $address->mac_dinh,
        ]);

        return redirect()->route('customer.addresses.index')
            ->with('success', 'Đã cập nhật thông tin địa chỉ nhận hàng.');
    }

    public function destroy(DiaChi $address): RedirectResponse
    {
        $customerId = (int) Session::get('customer_id');
        abort_unless((int) $address->tai_khoan_id === $customerId, 403);

        $wasDefault = $address->mac_dinh;
        $address->delete();

        if ($wasDefault) {
            $remaining = DiaChi::query()
                ->where('tai_khoan_id', $customerId)
                ->first();
            if ($remaining) {
                $remaining->update(['mac_dinh' => true]);
            }
        }

        return redirect()->route('customer.addresses.index')
            ->with('success', 'Đã xóa địa chỉ nhận hàng.');
    }

    public function setDefault(DiaChi $address): RedirectResponse
    {
        $customerId = (int) Session::get('customer_id');
        abort_unless((int) $address->tai_khoan_id === $customerId, 403);

        DiaChi::query()
            ->where('tai_khoan_id', $customerId)
            ->update(['mac_dinh' => false]);

        $address->update(['mac_dinh' => true]);

        return redirect()->route('customer.addresses.index')
            ->with('success', 'Đã đặt địa chỉ làm mặc định.');
    }
}
