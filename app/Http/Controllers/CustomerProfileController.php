<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\ReturnRequest;
use App\Models\TaiKhoan;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use RuntimeException;

class CustomerProfileController extends Controller
{
    public function profile(): View
    {
        $customer = $this->currentCustomer();

        $orderCount = 0;
        $totalSpent = 0;
        if (Schema::hasTable('don_hangs')) {
            $orderStats = DB::table('don_hangs')
                ->where('tai_khoan_id', $customer->id)
                ->selectRaw('COUNT(*) as total_orders, COALESCE(SUM(tong_tien), 0) as total_spent')
                ->first();

            $orderCount = (int) ($orderStats->total_orders ?? 0);
            $totalSpent = (int) ($orderStats->total_spent ?? 0);
        }

        $addressCount = Schema::hasTable('dia_chis')
            ? $customer->diaChis()->count()
            : 0;

        $wishlistCount = Schema::hasTable('yeu_thichs')
            ? $customer->yeuThichs()->count()
            : 0;

        return view('storefront.customer.profile', [
            'customer' => $customer,
            'orderCount' => $orderCount,
            'totalSpent' => $totalSpent,
            'addressCount' => $addressCount,
            'wishlistCount' => $wishlistCount,
        ]);
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $customer = $this->currentCustomer();

        $data = $request->validate([
            'ho_ten' => ['required', 'string', 'max:50'],
            'so_dien_thoai' => ['nullable', 'string', 'max:15'],
            'ngay_sinh' => ['nullable', 'date', 'before:today'],
            'gioi_tinh' => ['nullable', Rule::in(['0', '1', '2'])],
            'anh_dai_dien' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ], [
            'ho_ten.required' => 'Vui lòng nhập họ và tên.',
            'ho_ten.max' => 'Họ tên không được vượt quá 50 ký tự.',
            'so_dien_thoai.max' => 'Số điện thoại không được vượt quá 15 ký tự.',
            'ngay_sinh.date' => 'Ngày sinh không đúng định dạng.',
            'ngay_sinh.before' => 'Ngày sinh phải trước ngày hôm nay.',
            'gioi_tinh.in' => 'Giới tính không hợp lệ.',
            'anh_dai_dien.image' => 'Ảnh đại diện phải là tệp hình ảnh.',
            'anh_dai_dien.mimes' => 'Chỉ hỗ trợ định dạng JPG, JPEG, PNG hoặc WebP.',
            'anh_dai_dien.max' => 'Dung lượng ảnh đại diện tối đa là 2MB.',
        ]);

        if ($request->hasFile('anh_dai_dien')) {
            $avatarPath = $this->storeAvatar($request->file('anh_dai_dien'));
            if ($customer->anh_dai_dien && ! str_starts_with($customer->anh_dai_dien, 'http')) {
                Storage::disk('public')->delete($customer->anh_dai_dien);
            }
            $customer->anh_dai_dien = $avatarPath;
        }

        $customer->ho_ten = trim($data['ho_ten']);
        $customer->so_dien_thoai = filled($data['so_dien_thoai']) ? trim($data['so_dien_thoai']) : null;
        $customer->ngay_sinh = filled($data['ngay_sinh']) ? $data['ngay_sinh'] : null;
        $customer->gioi_tinh = isset($data['gioi_tinh']) && $data['gioi_tinh'] !== '' ? (int) $data['gioi_tinh'] : null;
        $customer->save();

        return redirect()->route('customer.profile')
            ->with('success', 'Cập nhật thông tin hồ sơ thành công.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $customer = $this->currentCustomer();

        $data = $request->validate([
            'mat_khau_cu' => ['required', 'string'],
            'mat_khau' => ['required', 'string', 'min:6', 'confirmed'],
        ], [
            'mat_khau_cu.required' => 'Vui lòng nhập mật khẩu hiện tại.',
            'mat_khau.required' => 'Vui lòng nhập mật khẩu mới.',
            'mat_khau.min' => 'Mật khẩu mới phải từ 6 ký tự trở lên.',
            'mat_khau.confirmed' => 'Xác nhận mật khẩu mới không trùng khớp.',
        ]);

        $matches = Hash::check($data['mat_khau_cu'], (string) $customer->mat_khau)
            || (string) $customer->mat_khau === $data['mat_khau_cu'];

        if (! $matches) {
            return back()->withErrors(['mat_khau_cu' => 'Mật khẩu hiện tại không chính xác.']);
        }

        $customer->mat_khau = $data['mat_khau'];
        $customer->save();

        return redirect()->route('customer.profile')
            ->with('success', 'Đổi mật khẩu thành công! Vui lòng ghi nhớ mật khẩu mới.');
    }

    public function orders(): View
    {
        $customer = $this->currentCustomer();

        $orders = collect();
        if (Schema::hasTable('don_hangs')) {
            $orders = Order::query()
                ->where('tai_khoan_id', $customer->id)
                ->orderByDesc('id')
                ->paginate(10);
        }

        return view('storefront.customer.orders', [
            'customer' => $customer,
            'orders' => $orders,
        ]);
    }

    public function showOrder(int $id): View|RedirectResponse
    {
        $customer = $this->currentCustomer();

        if (! Schema::hasTable('don_hangs')) {
            return redirect()->route('customer.orders');
        }

        $order = Order::query()
            ->where('tai_khoan_id', $customer->id)
            ->where('id', $id)
            ->firstOrFail();

        $items = collect();
        if (Schema::hasTable('chi_tiet_don_hangs')) {
            $items = DB::table('chi_tiet_don_hangs')
                ->where('don_hang_id', $order->id)
                ->get();
        }

        $returnRequests = collect();
        if (Schema::hasTable('yeu_cau_doi_tras')) {
            $returnRequests = ReturnRequest::query()
                ->where('don_hang_id', $order->id)
                ->get();
        }

        return view('storefront.customer.order-detail', [
            'customer' => $customer,
            'order' => $order,
            'items' => $items,
            'returnRequests' => $returnRequests,
        ]);
    }

    private function currentCustomer(): TaiKhoan
    {
        $customerId = (int) Session::get('customer_id');

        return TaiKhoan::query()->findOrFail($customerId);
    }

    private function storeAvatar(?UploadedFile $avatar): ?string
    {
        if (! $avatar) {
            return null;
        }

        $path = $avatar->store('avatars', 'public');
        if ($path === false) {
            throw new RuntimeException('Không thể lưu ảnh đại diện vào ổ đĩa public.');
        }

        return $path;
    }
}
