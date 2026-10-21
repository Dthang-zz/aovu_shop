<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ChucVu;
use App\Models\TaiKhoan;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $query = TaiKhoan::query()->with('chucVu');

        // Tìm kiếm
        if ($request->filled('q')) {
            $term = trim((string) $request->query('q'));
            $query->where(function ($q) use ($term) {
                $q->where('ho_ten', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%")
                    ->orWhere('so_dien_thoai', 'like', "%{$term}%");
            });
        }

        // Lọc trạng thái
        if ($request->query('status') === 'active') {
            $query->where('trang_thai', true);
        } elseif ($request->query('status') === 'locked') {
            $query->where('trang_thai', false);
        }

        // Lọc chức vụ
        if ($request->filled('chuc_vu_id')) {
            $query->where('chuc_vu_id', $request->query('chuc_vu_id'));
        }

        // Lọc giới tính
        if ($request->query('gioi_tinh') !== null && $request->query('gioi_tinh') !== '') {
            $query->where('gioi_tinh', (int) $request->query('gioi_tinh'));
        }

        $users = $query->orderBy('ngay_tao', 'desc')
            ->paginate(15)
            ->withQueryString();

        $chucVus = ChucVu::orderBy('ten_chuc_vu')->get();

        return view('admin.users.index', [
            'users'       => $users,
            'chucVus'     => $chucVus,
            'totalCount'  => TaiKhoan::count(),
            'activeCount' => TaiKhoan::where('trang_thai', true)->count(),
            'lockedCount' => TaiKhoan::where('trang_thai', false)->count(),
        ]);
    }

    public function show(TaiKhoan $user): View
    {
        $user->load('chucVu');

        // Thống kê đơn hàng
        $orderStats = [];
        if (\Illuminate\Support\Facades\Schema::hasTable('don_hangs')) {
            $orderStats = DB::table('don_hangs')
                ->where('tai_khoan_id', $user->getKey())
                ->selectRaw('COUNT(*) as tong_don, SUM(tong_tien) as tong_chi_tieu')
                ->first();
        }

        return view('admin.users.show', [
            'user'       => $user,
            'orderStats' => $orderStats,
        ]);
    }

    public function create(): View
    {
        return view('admin.users.form', [
            'user'      => new TaiKhoan(['trang_thai' => true, 'ngay_tao' => now()]),
            'chucVus'   => ChucVu::orderBy('ten_chuc_vu')->get(),
            'formTitle' => 'Thêm người dùng',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedData($request);

        if (empty($data['mat_khau'])) {
            unset($data['mat_khau']);
        }

        $data['ngay_tao'] = now();

        TaiKhoan::create($data);

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'Đã thêm người dùng mới.');
    }

    public function edit(TaiKhoan $user): View
    {
        return view('admin.users.form', [
            'user'      => $user,
            'chucVus'   => ChucVu::orderBy('ten_chuc_vu')->get(),
            'formTitle' => 'Chỉnh sửa người dùng',
        ]);
    }

    public function update(Request $request, TaiKhoan $user): RedirectResponse
    {
        $data = $this->validatedData($request, $user);

        if (empty($data['mat_khau'])) {
            unset($data['mat_khau']);
        }

        $user->update($data);

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'Đã cập nhật thông tin người dùng.');
    }

    public function destroy(TaiKhoan $user): RedirectResponse
    {
        // Kiểm tra nếu người dùng có đơn hàng
        if (\Illuminate\Support\Facades\Schema::hasTable('don_hangs')
            && DB::table('don_hangs')->where('tai_khoan_id', $user->getKey())->exists()) {
            return redirect()
                ->route('admin.users.index')
                ->with('error', 'Không thể xóa người dùng đang có đơn hàng.');
        }

        $user->delete();

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'Đã xóa người dùng.');
    }

    /**
     * Toggle trạng thái khóa / mở khóa tài khoản
     */
    public function toggleStatus(TaiKhoan $user): RedirectResponse
    {
        $user->update(['trang_thai' => !$user->trang_thai]);

        $msg = $user->trang_thai
            ? 'Đã mở khóa tài khoản.'
            : 'Đã khóa tài khoản.';

        return redirect()
            ->route('admin.users.index')
            ->with('success', $msg);
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedData(Request $request, ?TaiKhoan $user = null): array
    {
        $emailRule = ['required', 'email', 'max:255'];
        if ($user) {
            $emailRule[] = Rule::unique('tai_khoans', 'email')->ignore($user->getKey());
        } else {
            $emailRule[] = Rule::unique('tai_khoans', 'email');
        }

        $matKhauRule = $user
            ? ['nullable', 'string', 'min:6', 'max:255']
            : ['required', 'string', 'min:6', 'max:255'];

        return validator($request->all(), [
            'ho_ten'       => ['required', 'string', 'max:50'],
            'email'        => $emailRule,
            'so_dien_thoai' => ['nullable', 'string', 'max:15'],
            'ngay_sinh'    => ['nullable', 'date', 'before:today'],
            'gioi_tinh'    => ['nullable', 'in:0,1,2'],
            'mat_khau'     => $matKhauRule,
            'chuc_vu_id'   => ['nullable', 'integer', Rule::exists('chuc_vus', 'id')],
            'trang_thai'   => ['required', 'boolean'],
        ], [
            'ho_ten.required'       => 'Vui lòng nhập họ tên.',
            'ho_ten.max'            => 'Họ tên không vượt quá 50 ký tự.',
            'email.required'        => 'Vui lòng nhập email.',
            'email.email'           => 'Email không đúng định dạng.',
            'email.unique'          => 'Email này đã được sử dụng.',
            'so_dien_thoai.max'     => 'Số điện thoại không vượt quá 15 ký tự.',
            'ngay_sinh.date'        => 'Ngày sinh không đúng định dạng.',
            'ngay_sinh.before'      => 'Ngày sinh phải trước ngày hôm nay.',
            'gioi_tinh.in'          => 'Giới tính không hợp lệ.',
            'mat_khau.required'     => 'Vui lòng nhập mật khẩu.',
            'mat_khau.min'          => 'Mật khẩu tối thiểu 6 ký tự.',
            'chuc_vu_id.exists'     => 'Chức vụ không tồn tại.',
            'trang_thai.required'   => 'Vui lòng chọn trạng thái.',
        ])->validate();
    }
}
