<?php

namespace App\Http\Controllers;

use App\Models\ChucVu;
use App\Models\TaiKhoan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(): View
    {
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ], [
            'email.required' => 'Vui lòng nhập email.',
            'email.email' => 'Email không hợp lệ.',
            'password.required' => 'Vui lòng nhập mật khẩu.',
        ]);

        if (! Auth::attempt([
            'email' => $credentials['email'],
            'password' => $credentials['password'],
            'trang_thai' => 1,
        ])) {
            return back()
                ->withErrors(['email' => 'Email hoặc mật khẩu không chính xác, hoặc tài khoản đã bị khóa.'])
                ->onlyInput('email');
        }

        $request->session()->regenerate();

        $account = Auth::user();

        return redirect()->route($account instanceof TaiKhoan && $account->isAdmin()
            ? 'admin.dashboard'
            : 'storefront.index');
    }

    public function showRegistration(): View
    {
        return view('auth.register');
    }

    public function register(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ho_ten' => ['required', 'string', 'max:50'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:tai_khoans,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'ho_ten.required' => 'Vui lòng nhập họ và tên.',
            'ho_ten.max' => 'Họ và tên không được vượt quá 50 ký tự.',
            'email.required' => 'Vui lòng nhập email.',
            'email.email' => 'Email không hợp lệ.',
            'email.unique' => 'Email này đã được đăng ký.',
            'password.min' => 'Mật khẩu phải có ít nhất 8 ký tự.',
            'password.confirmed' => 'Mật khẩu xác nhận không khớp.',
        ]);

        $account = DB::transaction(function () use ($validated): TaiKhoan {
            $customerRole = ChucVu::query()->firstOrCreate(
                ['ten_chuc_vu' => 'Khách hàng'],
                ['mo_ta' => 'Tài khoản khách hàng đăng ký trên cửa hàng.'],
            );

            $account = new TaiKhoan([
                'ho_ten' => $validated['ho_ten'],
                'email' => $validated['email'],
                'mat_khau' => $validated['password'],
            ]);
            $account->chuc_vu_id = $customerRole->getKey();
            $account->trang_thai = 1;
            $account->ngay_tao = now();
            $account->save();

            return $account;
        });

        Auth::login($account);
        $request->session()->regenerate();

        return redirect()->route('storefront.index');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('storefront.index');
    }
}
