<?php

namespace App\Http\Controllers\Auth;


use App\Http\Controllers\Controller;
use App\Models\ChucVu;
use App\Models\TaiKhoan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function loginForm() { return view('auth.login'); }
    public function registerForm() { return view('auth.register'); }

    public function profile() { return view('auth.profile', ['account' => auth()->user()]); }

    public function updateProfile(Request $request)
    {
        $account = auth()->user();
        $data = $request->validate([
            'ho_ten' => ['required','string','max:50'],
            'email' => ['required','email','max:255', \Illuminate\Validation\Rule::unique('tai_khoans','email')->ignore($account->getKey())],
            'so_dien_thoai' => ['nullable','string','max:15'],
            'ngay_sinh' => ['nullable','date','before:today'],
            'gioi_tinh' => ['nullable', \Illuminate\Validation\Rule::in(['0','1','2'])],
        ]);
        $account->update($data);
        return redirect()->route('account.profile')->with('success','Đã cập nhật hồ sơ.');
    }


    public function updatePassword(Request $request)
    {
        $data = $request->validate([
            'password_current' => ['required','string'],
            'password' => ['required','string','min:8','confirmed'],
        ]);
        $account = auth()->user();
        if (! Hash::check($data['password_current'], $account->mat_khau)) {
            throw ValidationException::withMessages(['password_current' => 'Mật khẩu hiện tại không đúng.']);
        }
        $account->update(['mat_khau' => $data['password']]);
        return redirect()->route('account.profile')->with('success','Đã đổi mật khẩu.');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate(['email' => ['required','email'], 'password' => ['required','string']]);
        $account = TaiKhoan::where('email', Str::lower(trim($credentials['email'])))->first();
        if (! $account || ! $account->trang_thai || ! Auth::attempt(['email' => $account->email, 'password' => $credentials['password']])) {
            throw ValidationException::withMessages(['email' => 'Email, mật khẩu không đúng hoặc tài khoản đã bị khóa.']);
        }
        $request->session()->regenerate();
        return redirect()->to($account->isAdmin() ? route('admin.dashboard') : route('shop.products.index'));
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'ho_ten' => ['required','string','max:50'],
            'email' => ['required','email','max:255','unique:tai_khoans,email'],
            'so_dien_thoai' => ['nullable','string','max:15'],
            'ngay_sinh' => ['nullable','date','before:today'],
            'gioi_tinh' => ['nullable', \Illuminate\Validation\Rule::in(['0','1','2'])],
            'password' => ['required','string','min:8','confirmed'],
            'terms' => ['accepted'],
        ], [
            'email.unique' => 'Email này đã được đăng ký.',
            'password.confirmed' => 'Mật khẩu xác nhận không khớp.',
            'terms.accepted' => 'Bạn cần đồng ý với điều khoản để đăng ký.',
        ]);
        $customerRole = ChucVu::firstOrCreate(['ten_chuc_vu' => 'Khách hàng'], ['mo_ta' => 'Tài khoản khách hàng cửa hàng']);
        $account = TaiKhoan::create([
            'ho_ten' => trim($data['ho_ten']),
            'email' => Str::lower(trim($data['email'])),
            'so_dien_thoai' => $data['so_dien_thoai'] ?? null,
            'ngay_sinh' => $data['ngay_sinh'] ?? null,
            'gioi_tinh' => $data['gioi_tinh'] ?? null,
            'mat_khau' => $data['password'],
            'chuc_vu_id' => $customerRole->id,
            'trang_thai' => true,
            'ngay_tao' => now(),
        ]);
        Auth::login($account);
        $request->session()->regenerate();
        return redirect()->route('shop.products.index')->with('success', 'Đăng ký thành công. Chào mừng bạn đến AOVU Shop!');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('shop.products.index')->with('success', 'Bạn đã đăng xuất.');
    }
}
