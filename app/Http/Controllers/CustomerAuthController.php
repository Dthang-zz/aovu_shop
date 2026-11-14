<?php

namespace App\Http\Controllers;

use App\Models\ChucVu;
use App\Models\TaiKhoan;
use App\Models\YeuThich;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rule;

class CustomerAuthController extends Controller
{
    public function showLoginForm(): View|RedirectResponse
    {
        if (Session::has('customer_id')) {
            return redirect()->route('customer.profile');
        }

        $demoCustomers = collect();
        if (Schema::hasTable('tai_khoans')) {
            $demoCustomers = TaiKhoan::query()
                ->where('trang_thai', true)
                ->where(function ($q) {
                    if (Schema::hasTable('chuc_vus')) {
                        $customerRoleId = ChucVu::query()->where('ten_chuc_vu', 'Khách hàng')->value('id');
                        if ($customerRoleId) {
                            $q->where('chuc_vu_id', $customerRoleId)->orWhereNull('chuc_vu_id');
                        }
                    }
                })
                ->orderBy('id', 'asc')
                ->limit(4)
                ->get();
        }

        return view('storefront.auth.login', [
            'demoCustomers' => $demoCustomers,
        ]);
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'mat_khau' => ['required', 'string'],
        ], [
            'email.required' => 'Vui lòng nhập địa chỉ email.',
            'email.email' => 'Địa chỉ email không đúng định dạng.',
            'mat_khau.required' => 'Vui lòng nhập mật khẩu.',
        ]);

        $customer = TaiKhoan::query()
            ->whereRaw('LOWER(email) = ?', [strtolower(trim($credentials['email']))])
            ->first();

        if (! $customer) {
            return back()->withInput()->withErrors([
                'email' => 'Tài khoản không tồn tại trong hệ thống.',
            ]);
        }

        if ($customer->trang_thai === false) {
            return back()->withInput()->withErrors([
                'email' => 'Tài khoản của bạn hiện đang bị tạm khóa.',
            ]);
        }

        // Kiểm tra mật khẩu (hỗ trợ hash và mật khẩu text nếu tài khoản cũ chưa mã hóa)
        $passwordMatches = Hash::check($credentials['mat_khau'], (string) $customer->mat_khau)
            || (string) $customer->mat_khau === $credentials['mat_khau'];

        if (! $passwordMatches && ! empty($customer->mat_khau)) {
            return back()->withInput()->withErrors([
                'mat_khau' => 'Mật khẩu đăng nhập không chính xác.',
            ]);
        }

        // Nếu tài khoản được tạo tự động khi checkout chưa có mật khẩu, đặt mật khẩu nhập lần đầu
        if (empty($customer->mat_khau)) {
            $customer->mat_khau = $credentials['mat_khau'];
            $customer->save();
        }

        Session::put('customer_id', $customer->id);

        $this->syncGuestWishlistToDatabase($customer->id);

        return redirect()->intended(route('customer.profile'))
            ->with('success', 'Đăng nhập thành công! Chào mừng bạn, ' . $customer->ho_ten . '.');
    }

    public function showRegisterForm(): View|RedirectResponse
    {
        if (Session::has('customer_id')) {
            return redirect()->route('customer.profile');
        }

        return view('storefront.auth.register');
    }

    public function register(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'ho_ten' => ['required', 'string', 'max:50'],
            'email' => ['required', 'email', 'max:255', Rule::unique('tai_khoans', 'email')],
            'so_dien_thoai' => ['required', 'string', 'max:15'],
            'mat_khau' => ['required', 'string', 'min:6', 'confirmed'],
        ], [
            'ho_ten.required' => 'Vui lòng nhập họ tên.',
            'ho_ten.max' => 'Họ tên không được vượt quá 50 ký tự.',
            'email.required' => 'Vui lòng nhập địa chỉ email.',
            'email.email' => 'Địa chỉ email không đúng định dạng.',
            'email.unique' => 'Địa chỉ email này đã được sử dụng.',
            'so_dien_thoai.required' => 'Vui lòng nhập số điện thoại.',
            'so_dien_thoai.max' => 'Số điện thoại không quá 15 ký tự.',
            'mat_khau.required' => 'Vui lòng nhập mật khẩu.',
            'mat_khau.min' => 'Mật khẩu phải chứa ít nhất 6 ký tự.',
            'mat_khau.confirmed' => 'Xác nhận mật khẩu không trùng khớp.',
        ]);

        $customerRoleId = Schema::hasTable('chuc_vus')
            ? ChucVu::query()->where('ten_chuc_vu', 'Khách hàng')->value('id')
            : null;

        $customer = TaiKhoan::query()->create([
            'ho_ten' => trim($data['ho_ten']),
            'email' => strtolower(trim($data['email'])),
            'so_dien_thoai' => trim($data['so_dien_thoai']),
            'mat_khau' => $data['mat_khau'],
            'chuc_vu_id' => $customerRoleId,
            'trang_thai' => true,
            'ngay_tao' => now(),
        ]);

        Session::put('customer_id', $customer->id);

        $this->syncGuestWishlistToDatabase($customer->id);

        return redirect()->route('customer.profile')
            ->with('success', 'Đăng ký tài khoản thành công! Chào mừng bạn gia nhập AOVU Shop.');
    }

    public function demoLogin(int $id): RedirectResponse
    {
        $customer = TaiKhoan::query()->where('trang_thai', true)->findOrFail($id);
        Session::put('customer_id', $customer->id);

        $this->syncGuestWishlistToDatabase($customer->id);

        return redirect()->route('customer.profile')
            ->with('success', 'Đã đăng nhập tài khoản thử nghiệm: ' . $customer->ho_ten);
    }

    public function logout(Request $request): RedirectResponse
    {
        $request->session()->forget('customer_id');

        return redirect()->route('shop.products.index')
            ->with('info', 'Bạn đã đăng xuất tài khoản.');
    }

    /**
     * Đồng bộ các sản phẩm đã thích từ session (khi là khách) vào cơ sở dữ liệu.
     */
    private function syncGuestWishlistToDatabase(int $customerId): void
    {
        if (! Schema::hasTable('yeu_thichs')) {
            return;
        }

        $guestWishlist = Session::get('storefront_wishlist', []);
        if (empty($guestWishlist) || ! is_array($guestWishlist)) {
            return;
        }

        foreach ($guestWishlist as $productId) {
            $productId = (int) $productId;
            if ($productId <= 0) {
                continue;
            }

            $exists = YeuThich::query()
                ->where('tai_khoan_id', $customerId)
                ->where('san_pham_id', $productId)
                ->exists();

            if (! $exists) {
                YeuThich::query()->create([
                    'tai_khoan_id' => $customerId,
                    'san_pham_id' => $productId,
                    'ngay_them' => now(),
                ]);
            }
        }

        Session::forget('storefront_wishlist');
    }
}
