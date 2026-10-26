<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ChucVu;
use App\Models\DiaChi;
use App\Models\DonHang;
use App\Models\ReturnRequest;
use App\Models\TaiKhoan;
use App\Models\Voucher;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class UserController extends Controller
{
    /**
     * Danh sách người dùng & Tìm kiếm, Lọc
     */
    public function index(Request $request): View
    {
        $query = $this->buildFilteredQuery($request);

        $users = $query->orderBy('ngay_tao', 'desc')
            ->paginate(6)
            ->withQueryString();

        $chucVus = ChucVu::orderBy('id')->get();

        // Danh sách các nhãn khách hàng có trong hệ thống
        $availableTags = TaiKhoan::whereNotNull('nhan_khach_hang')
            ->where('nhan_khach_hang', '!=', '')
            ->distinct()
            ->pluck('nhan_khach_hang');

        return view('admin.users.index', [
            'users'         => $users,
            'chucVus'       => $chucVus,
            'availableTags' => $availableTags,
            'totalCount'    => TaiKhoan::count(),
            'activeCount'   => TaiKhoan::where('trang_thai', true)->count(),
            'lockedCount'   => TaiKhoan::where('trang_thai', false)->count(),
            'vipCount'      => TaiKhoan::where('nhan_khach_hang', 'like', '%VIP%')->count(),
        ]);
    }

    /**
     * Chi tiết người dùng: Hồ sơ thời trang, Địa chỉ, Đơn hàng, Đổi trả, Voucher, CRM
     */
    public function show(TaiKhoan $user): View
    {
        $user->load([
            'chucVu',
            'diaChis',
            'donHangs',
            'returnRequests',
        ]);

        // Thống kê đơn hàng chi tiết
        $orderStats = (object) [
            'tong_don'      => $user->donHangs->count(),
            'tong_chi_tieu' => $user->donHangs->where('trang_thai', '!=', DonHang::STATUS_CANCELLED)->sum('tong_tien'),
            'don_hoan_thanh'=> $user->donHangs->where('trang_thai', DonHang::STATUS_COMPLETED)->count(),
            'don_dang_giao' => $user->donHangs->where('trang_thai', DonHang::STATUS_SHIPPING)->count(),
            'don_huy'       => $user->donHangs->where('trang_thai', DonHang::STATUS_CANCELLED)->count(),
        ];

        // Danh sách voucher đang có hiệu lực trong hệ thống
        $availableVouchers = Voucher::where('trang_thai', true)
            ->where(function ($q) {
                $q->whereNull('ngay_ket_thuc')->orWhere('ngay_ket_thuc', '>=', now());
            })
            ->get();

        return view('admin.users.show', [
            'user'              => $user,
            'orderStats'        => $orderStats,
            'availableVouchers' => $availableVouchers,
        ]);
    }

    /**
     * Form tạo người dùng
     */
    public function create(): View
    {
        return view('admin.users.form', [
            'user'      => new TaiKhoan(['trang_thai' => true, 'ngay_tao' => now()]),
            'chucVus'   => ChucVu::orderBy('id')->get(),
            'formTitle' => 'Thêm người dùng',
        ]);
    }

    /**
     * Lưu người dùng mới
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedData($request);

        if ($request->hasFile('avatar_file')) {
            $data['anh_dai_dien'] = $request->file('avatar_file')->store('avatars', 'public');
        }
        unset($data['avatar_file']);

        if (empty($data['mat_khau'])) {
            unset($data['mat_khau']);
        }

        $data['ngay_tao'] = now();

        TaiKhoan::create($data);

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'Đã thêm người dùng mới thành công.');
    }

    /**
     * Form chỉnh sửa
     */
    public function edit(TaiKhoan $user): View
    {
        return view('admin.users.form', [
            'user'      => $user,
            'chucVus'   => ChucVu::orderBy('id')->get(),
            'formTitle' => 'Chỉnh sửa người dùng',
        ]);
    }

    /**
     * Cập nhật thông tin
     */
    public function update(Request $request, TaiKhoan $user): RedirectResponse
    {
        // RBAC: Bảo vệ tài khoản Super Admin (ID 1) không bị hạ chức vụ bởi form
        if ($user->id === 1 && (int)$request->input('chuc_vu_id') !== 1) {
            return redirect()
                ->back()
                ->with('error', 'Không thể thay đổi chức vụ của Quản trị viên tối cao.');
        }

        $data = $this->validatedData($request, $user);

        // Xóa ảnh cũ hoặc tải ảnh mới
        if ($request->boolean('xoa_anh')) {
            if ($user->anh_dai_dien && !str_starts_with($user->anh_dai_dien, 'http')) {
                Storage::disk('public')->delete($user->anh_dai_dien);
            }
            $data['anh_dai_dien'] = null;
        } elseif ($request->hasFile('avatar_file')) {
            if ($user->anh_dai_dien && !str_starts_with($user->anh_dai_dien, 'http')) {
                Storage::disk('public')->delete($user->anh_dai_dien);
            }
            $data['anh_dai_dien'] = $request->file('avatar_file')->store('avatars', 'public');
        }
        unset($data['avatar_file']);

        if (empty($data['mat_khau'])) {
            unset($data['mat_khau']);
        }

        $user->update($data);

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'Đã cập nhật thông tin người dùng.');
    }

    /**
     * Xóa người dùng (có kiểm tra ràng buộc & RBAC)
     */
    public function destroy(TaiKhoan $user): RedirectResponse
    {
        // RBAC: Không được xóa Super Admin (ID 1)
        if ($user->id === 1) {
            return redirect()
                ->route('admin.users.index')
                ->with('error', 'Không được phép xóa tài khoản Quản trị viên tối cao.');
        }

        // Kiểm tra nếu có đơn hàng
        if (\Illuminate\Support\Facades\Schema::hasTable('don_hangs')
            && DB::table('don_hangs')->where('tai_khoan_id', $user->getKey())->exists()) {
            return redirect()
                ->route('admin.users.index')
                ->with('error', 'Không thể xóa người dùng đang có đơn hàng trong hệ thống.');
        }

        try {
            if ($user->anh_dai_dien && !str_starts_with($user->anh_dai_dien, 'http')) {
                Storage::disk('public')->delete($user->anh_dai_dien);
            }
            $user->delete();

            return redirect()
                ->route('admin.users.index')
                ->with('success', 'Đã xóa người dùng thành công.');
        } catch (\Throwable $e) {
            return redirect()
                ->route('admin.users.index')
                ->with('error', 'Không thể xóa người dùng do có dữ liệu liên quan.');
        }
    }

    /**
     * Khóa / Mở khóa tài khoản (1-click toggle)
     */
    public function toggleStatus(TaiKhoan $user): RedirectResponse
    {
        // RBAC: Không được khóa Super Admin (ID 1)
        if ($user->id === 1 && $user->trang_thai) {
            return redirect()
                ->route('admin.users.index')
                ->with('error', 'Không thể khóa tài khoản Quản trị viên tối cao.');
        }

        $user->update(['trang_thai' => !$user->trang_thai]);

        $msg = $user->trang_thai
            ? 'Đã mở khóa tài khoản thành công.'
            : 'Đã khóa tài khoản thành công.';

        return redirect()
            ->back()
            ->with('success', $msg);
    }

    /**
     * Nghiệp vụ 5: Cấp lại mật khẩu nhanh (Reset password)
     */
    public function resetPassword(Request $request, TaiKhoan $user): RedirectResponse
    {
        $newPassword = $request->filled('custom_password')
            ? trim((string)$request->input('custom_password'))
            : 'Aovu@' . Str::random(6);

        if (strlen($newPassword) < 6) {
            return redirect()
                ->back()
                ->with('error', 'Mật khẩu mới phải có tối thiểu 6 ký tự.');
        }

        $user->update([
            'mat_khau' => Hash::make($newPassword),
        ]);

        return redirect()
            ->back()
            ->with('success', 'Đã cấp lại mật khẩu cho tài khoản ' . $user->email . '!')
            ->with('new_password_generated', $newPassword);
    }

    /**
     * Nghiệp vụ 2: Cập nhật nhanh Ghi chú nội bộ & Nhãn CRM
     */
    public function updateAdminNote(Request $request, TaiKhoan $user): RedirectResponse
    {
        $user->update([
            'ghi_chu_admin'   => $request->input('ghi_chu_admin'),
            'nhan_khach_hang' => $request->input('nhan_khach_hang'),
        ]);

        return redirect()
            ->back()
            ->with('success', 'Đã cập nhật ghi chú CRM và nhãn khách hàng.');
    }

    /**
     * Nghiệp vụ 1: Thêm địa chỉ nhận hàng cho khách
     */
    public function storeAddress(Request $request, TaiKhoan $user): RedirectResponse
    {
        $validated = $request->validate([
            'ten_nguoi_nhan'   => 'required|string|max:100',
            'sdt_nguoi_nhan'   => 'required|string|max:15',
            'tinh_thanh'       => 'required|string|max:100',
            'quan_huyen'       => 'required|string|max:100',
            'phuong_xa'        => 'nullable|string|max:100',
            'dia_chi_chi_tiet' => 'required|string|max:255',
            'mac_dinh'         => 'nullable|boolean',
        ], [
            'ten_nguoi_nhan.required'   => 'Vui lòng nhập tên người nhận.',
            'sdt_nguoi_nhan.required'   => 'Vui lòng nhập số điện thoại nhận hàng.',
            'tinh_thanh.required'       => 'Vui lòng chọn/nhập Tỉnh/Thành phố.',
            'quan_huyen.required'       => 'Vui lòng chọn/nhập Quận/Huyện.',
            'dia_chi_chi_tiet.required' => 'Vui lòng nhập số nhà, tên đường.',
        ]);

        $isDefault = $request->boolean('mac_dinh');
        if ($isDefault || $user->diaChis()->count() === 0) {
            $user->diaChis()->update(['mac_dinh' => false]);
            $validated['mac_dinh'] = true;
        }

        $validated['tai_khoan_id'] = $user->id;
        DiaChi::create($validated);

        return redirect()
            ->back()
            ->with('success', 'Đã thêm địa chỉ giao hàng mới cho khách.');
    }

    /**
     * Nghiệp vụ 1: Xóa địa chỉ nhận hàng
     */
    public function destroyAddress(TaiKhoan $user, DiaChi $address): RedirectResponse
    {
        if ($address->tai_khoan_id !== $user->id) {
            abort(403);
        }

        $wasDefault = $address->mac_dinh;
        $address->delete();

        // Nếu xóa địa chỉ mặc định, chọn địa chỉ đầu tiên còn lại làm mặc định
        if ($wasDefault) {
            $user->diaChis()->first()?->update(['mac_dinh' => true]);
        }

        return redirect()
            ->back()
            ->with('success', 'Đã xóa địa chỉ giao hàng.');
    }

    /**
     * Nghiệp vụ 1: Đặt làm địa chỉ mặc định
     */
    public function setDefaultAddress(TaiKhoan $user, DiaChi $address): RedirectResponse
    {
        if ($address->tai_khoan_id !== $user->id) {
            abort(403);
        }

        $user->diaChis()->update(['mac_dinh' => false]);
        $address->update(['mac_dinh' => true]);

        return redirect()
            ->back()
            ->with('success', 'Đã đặt địa chỉ làm mặc định.');
    }

    /**
     * Nghiệp vụ 5: Thao tác hàng loạt (Bulk Actions: Khóa / Mở khóa)
     */
    public function bulkAction(Request $request): RedirectResponse
    {
        $action = $request->input('action');
        $userIds = $request->input('user_ids', []);

        if (empty($userIds) || !is_array($userIds)) {
            return redirect()
                ->back()
                ->with('error', 'Vui lòng chọn ít nhất một người dùng để thực hiện thao tác.');
        }

        // Loại trừ Super Admin (ID 1)
        $userIds = array_values(array_filter($userIds, fn($id) => (int)$id !== 1));

        if ($action === 'lock') {
            TaiKhoan::whereIn('id', $userIds)->update(['trang_thai' => false]);
            $msg = 'Đã khóa ' . count($userIds) . ' tài khoản được chọn.';
        } elseif ($action === 'unlock') {
            TaiKhoan::whereIn('id', $userIds)->update(['trang_thai' => true]);
            $msg = 'Đã mở khóa ' . count($userIds) . ' tài khoản được chọn.';
        } else {
            return redirect()->back()->with('error', 'Thao tác không hợp lệ.');
        }

        return redirect()->back()->with('success', $msg);
    }

    /**
     * Nghiệp vụ 5: Xuất danh sách người dùng ra CSV/Excel (UTF-8 BOM)
     */
    public function export(Request $request): StreamedResponse
    {
        $query = $this->buildFilteredQuery($request);
        $users = $query->orderBy('id', 'asc')->get();

        $fileName = 'Danh_sach_nguoi_dung_' . date('Ymd_His') . '.csv';

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0',
        ];

        return response()->stream(function () use ($users) {
            $handle = fopen('php://output', 'w');

            // Ghi UTF-8 BOM để Excel tự động nhận diện tiếng Việt có dấu
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            // Dòng tiêu đề
            fputcsv($handle, [
                'ID',
                'Họ và tên',
                'Email',
                'Số điện thoại',
                'Giới tính',
                'Ngày sinh',
                'Size thời trang',
                'Chiều cao (cm)',
                'Cân nặng (kg)',
                'Phong cách',
                'Chức vụ',
                'Nhãn khách hàng',
                'Hạng thành viên',
                'Tổng chi tiêu (VNĐ)',
                'Số đơn hàng',
                'Trạng thái',
                'Ngày đăng ký',
                'Đăng nhập cuối',
            ]);

            foreach ($users as $u) {
                $totalSpent = $u->donHangs()->sum('tong_tien');
                $orderCount = $u->donHangs()->count();
                $gender = match ($u->gioi_tinh) {
                    1 => 'Nam',
                    0 => 'Nữ',
                    default => 'Khác',
                };

                fputcsv($handle, [
                    $u->id,
                    $u->ho_ten,
                    $u->email,
                    $u->so_dien_thoai ?? '',
                    $gender,
                    $u->ngay_sinh ? $u->ngay_sinh->format('d/m/Y') : '',
                    $u->size_thuong_mac ?? '',
                    $u->chieu_cao ?? '',
                    $u->can_nang ?? '',
                    $u->phong_cach ?? '',
                    $u->chucVu?->ten_chuc_vu ?? 'Khách hàng',
                    $u->nhan_khach_hang ?? 'Bình thường',
                    $u->loyalty_tier['name'],
                    number_format($totalSpent, 0, ',', '.'),
                    $orderCount,
                    $u->trang_thai ? 'Hoạt động' : 'Đã khóa',
                    $u->ngay_tao ? $u->ngay_tao->format('d/m/Y H:i') : '',
                    $u->dang_nhap_cuoi ? $u->dang_nhap_cuoi->format('d/m/Y H:i') : 'Chưa đăng nhập',
                ]);
            }

            fclose($handle);
        }, 200, $headers);
    }

    /**
     * Query chung dùng cho index và export
     */
    private function buildFilteredQuery(Request $request)
    {
        $query = TaiKhoan::query()->with('chucVu');

        // Tìm kiếm
        if ($request->filled('q')) {
            $term = trim((string) $request->query('q'));
            $query->where(function ($q) use ($term) {
                $q->where('ho_ten', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%")
                    ->orWhere('so_dien_thoai', 'like', "%{$term}%")
                    ->orWhere('nhan_khach_hang', 'like', "%{$term}%");
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

        // Lọc nhãn
        if ($request->filled('tag')) {
            $query->where('nhan_khach_hang', $request->query('tag'));
        }

        return $query;
    }

    /**
     * Validation dữ liệu
     *
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
            'ho_ten'          => ['required', 'string', 'max:50'],
            'email'           => $emailRule,
            'so_dien_thoai'   => ['nullable', 'string', 'max:15'],
            'ngay_sinh'       => ['nullable', 'date', 'before:today'],
            'gioi_tinh'       => ['nullable', 'in:0,1,2'],
            'size_thuong_mac' => ['nullable', 'string', 'max:20'],
            'chieu_cao'       => ['nullable', 'integer', 'min:50', 'max:250'],
            'can_nang'        => ['nullable', 'integer', 'min:20', 'max:250'],
            'phong_cach'      => ['nullable', 'string', 'max:100'],
            'avatar_file'     => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp,gif', 'max:2048'],
            'anh_dai_dien'    => ['nullable', 'string', 'max:255'],
            'mat_khau'        => $matKhauRule,
            'chuc_vu_id'      => ['nullable', 'integer', Rule::exists('chuc_vus', 'id')],
            'nhan_khach_hang' => ['nullable', 'string', 'max:50'],
            'ghi_chu_admin'   => ['nullable', 'string'],
            'trang_thai'      => ['required', 'boolean'],
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
            'avatar_file.image'     => 'Tệp tải lên phải là hình ảnh.',
            'avatar_file.mimes'     => 'Ảnh đại diện phải có định dạng: jpeg, png, jpg, webp, gif.',
            'avatar_file.max'       => 'Dung lượng ảnh tối đa 2MB.',
            'mat_khau.required'     => 'Vui lòng nhập mật khẩu.',
            'mat_khau.min'          => 'Mật khẩu tối thiểu 6 ký tự.',
            'chuc_vu_id.exists'     => 'Chức vụ không tồn tại.',
            'trang_thai.required'   => 'Vui lòng chọn trạng thái.',
        ])->validate();
    }
}
