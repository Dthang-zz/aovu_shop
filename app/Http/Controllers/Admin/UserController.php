<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ChucVu;
use App\Models\Order;
use App\Models\ReturnRequest;
use App\Models\TaiKhoan;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use RuntimeException;
use Throwable;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::in(['active', 'locked'])],
            'chuc_vu_id' => ['nullable', 'integer', Rule::exists('chuc_vus', 'id')],
            'gioi_tinh' => ['nullable', Rule::in(['0', '1', '2'])],
        ]);

        $query = TaiKhoan::query()->with('chucVu');

        if (! empty($filters['q'])) {
            $term = trim($filters['q']);
            $query->where(function ($q) use ($term) {
                $q->where('ho_ten', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%")
                    ->orWhere('so_dien_thoai', 'like', "%{$term}%");
            });
        }

        if (($filters['status'] ?? null) === 'active') {
            $query->where('trang_thai', true);
        } elseif (($filters['status'] ?? null) === 'locked') {
            $query->where(function ($query) {
                $query->where('trang_thai', false)->orWhereNull('trang_thai');
            });
        }

        if (! empty($filters['chuc_vu_id'])) {
            $query->where('chuc_vu_id', $filters['chuc_vu_id']);
        }

        if (isset($filters['gioi_tinh']) && $filters['gioi_tinh'] !== '') {
            $query->where('gioi_tinh', (int) $filters['gioi_tinh']);
        }

        $users = $query->orderBy('ngay_tao', 'desc')
            ->paginate(15)
            ->withQueryString();

        $chucVus = ChucVu::orderBy('ten_chuc_vu')->get();

        return view('admin.users.index', [
            'users' => $users,
            'chucVus' => $chucVus,
            'totalCount' => TaiKhoan::count(),
            'activeCount' => TaiKhoan::where('trang_thai', true)->count(),
            'lockedCount' => TaiKhoan::where(function ($query) {
                $query->where('trang_thai', false)->orWhereNull('trang_thai');
            })->count(),
        ]);
    }

    public function show(TaiKhoan $user): View
    {
        $user->load('chucVu');

        $orders = collect();
        $orderStats = (object) ['tong_don' => 0, 'tong_chi_tieu' => 0];
        if (Schema::hasTable('don_hangs')) {
            $orderStats = DB::table('don_hangs')
                ->where('tai_khoan_id', $user->getKey())
                ->selectRaw('COUNT(*) as tong_don, SUM(tong_tien) as tong_chi_tieu')
                ->first();

            $orders = DB::table('don_hangs')
                ->where('tai_khoan_id', $user->getKey())
                ->orderByDesc('ngay_dat')
                ->orderByDesc('id')
                ->get(['id', 'ma_don_hang', 'ngay_dat', 'tong_tien', 'trang_thai'])
                ->map(function (object $order): object {
                    $status = (int) $order->trang_thai;
                    $order->status_label = Order::statuses()[$status] ?? 'Không xác định';
                    $order->status_badge = Order::statusBadges()[$status] ?? 'secondary';

                    return $order;
                });
        }

        $reviews = Schema::hasTable('danh_gias')
            ? DB::table('danh_gias as reviews')
                ->leftJoin('san_phams as products', 'products.id', '=', 'reviews.san_pham_id')
                ->where('reviews.tai_khoan_id', $user->getKey())
                ->orderByDesc('reviews.ngay_dang')
                ->orderByDesc('reviews.id')
                ->get([
                    'reviews.so_sao',
                    'reviews.noi_dung',
                    'reviews.ngay_dang',
                    'reviews.trang_thai',
                    'products.ten_san_pham',
                ])
            : collect();

        return view('admin.users.show', [
            'user' => $user,
            'orderStats' => $orderStats,
            'orders' => $orders,
            'reviews' => $reviews,
        ]);
    }

    public function create(): View
    {
        return view('admin.users.form', [
            'user' => new TaiKhoan(['trang_thai' => true, 'ngay_tao' => now()]),
            'chucVus' => ChucVu::orderBy('ten_chuc_vu')->get(),
            'formTitle' => 'Thêm khách hàng',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedData($request);
        $avatarPath = $this->storeAvatar($data['anh_dai_dien'] ?? null);
        unset($data['anh_dai_dien']);

        if (empty($data['mat_khau'])) {
            unset($data['mat_khau']);
        }

        if ($avatarPath !== null) {
            $data['anh_dai_dien'] = $avatarPath;
        }

        $data['ngay_tao'] = now();

        try {
            TaiKhoan::create($data);
        } catch (Throwable $exception) {
            if ($avatarPath !== null) {
                Storage::disk('public')->delete($avatarPath);
            }

            throw $exception;
        }

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'Đã thêm khách hàng mới.');
    }

    public function edit(TaiKhoan $user): View
    {
        return view('admin.users.form', [
            'user' => $user,
            'chucVus' => ChucVu::orderBy('ten_chuc_vu')->get(),
            'formTitle' => 'Chỉnh sửa khách hàng',
        ]);
    }

    public function update(Request $request, TaiKhoan $user): RedirectResponse
    {
        $data = $this->validatedData($request, $user);
        $avatar = $data['anh_dai_dien'] ?? null;
        unset($data['anh_dai_dien']);

        if (empty($data['mat_khau'])) {
            unset($data['mat_khau']);
        }

        $avatarPath = $this->storeAvatar($avatar);
        $oldAvatarPath = $user->anh_dai_dien;

        if ($avatarPath !== null) {
            $data['anh_dai_dien'] = $avatarPath;
        }

        try {
            $user->update($data);
        } catch (Throwable $exception) {
            if ($avatarPath !== null) {
                Storage::disk('public')->delete($avatarPath);
            }

            throw $exception;
        }

        if ($avatarPath !== null && $oldAvatarPath && ! Str::startsWith($oldAvatarPath, ['http://', 'https://'])) {
            Storage::disk('public')->delete($oldAvatarPath);
        }

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'Đã cập nhật thông tin khách hàng.');
    }

    public function destroy(TaiKhoan $user): RedirectResponse
    {
        $blockMessage = DB::transaction(function () use ($user): ?string {
            $lockedUser = DB::table('tai_khoans')
                ->where('id', $user->getKey())
                ->lockForUpdate()
                ->first();

            if ($lockedUser === null) {
                return 'Khách hàng không còn tồn tại.';
            }

            if (Schema::hasTable('don_hangs')
                && DB::table('don_hangs')->where('tai_khoan_id', $user->getKey())->exists()) {
                return 'Khách hàng đã có đơn hàng nên không thể xóa trực tiếp. Hãy khóa tài khoản để giữ lịch sử giao dịch.';
            }

            if (Schema::hasTable('yeu_cau_doi_tras')
                && Schema::hasColumn('yeu_cau_doi_tras', 'tai_khoan_id')
                && Schema::hasColumn('yeu_cau_doi_tras', 'trang_thai')
                && DB::table('yeu_cau_doi_tras')
                    ->where('tai_khoan_id', $user->getKey())
                    ->whereIn('trang_thai', [
                        ReturnRequest::STATUS_PENDING,
                        ReturnRequest::STATUS_PROCESSING,
                        ReturnRequest::STATUS_APPROVED,
                        ReturnRequest::STATUS_AWAITING_RETURN,
                        ReturnRequest::STATUS_RETURN_RECEIVED,
                        ReturnRequest::STATUS_INSPECTED,
                        ReturnRequest::STATUS_REFUNDED,
                        ReturnRequest::STATUS_EXCHANGE_SHIPPED,
                    ])
                    ->exists()) {
                return 'Khách hàng có yêu cầu đổi/trả đang xử lý nên không thể xóa. Hãy xử lý yêu cầu hoặc khóa tài khoản.';
            }

            foreach (['danh_gias', 'dia_chis', 'gio_hangs', 'yeu_thichs'] as $table) {
                if (Schema::hasTable($table) && Schema::hasColumn($table, 'tai_khoan_id')) {
                    DB::table($table)->where('tai_khoan_id', $user->getKey())->delete();
                }
            }

            if (Schema::hasTable('yeu_cau_doi_tras')
                && Schema::hasColumn('yeu_cau_doi_tras', 'tai_khoan_id')) {
                DB::table('yeu_cau_doi_tras')
                    ->where('tai_khoan_id', $user->getKey())
                    ->update(['tai_khoan_id' => null]);
            }

            if (Schema::hasTable('lich_su_trang_thai_don_hangs')
                && Schema::hasColumn('lich_su_trang_thai_don_hangs', 'nguoi_cap_nhat_id')) {
                DB::table('lich_su_trang_thai_don_hangs')
                    ->where('nguoi_cap_nhat_id', $user->getKey())
                    ->update(['nguoi_cap_nhat_id' => null]);
            }

            $user->delete();

            return null;
        });

        if ($blockMessage !== null) {
            return redirect()
                ->route('admin.users.index')
                ->with('error', $blockMessage);
        }

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'Đã xóa khách hàng.');
    }

    public function toggleStatus(TaiKhoan $user): RedirectResponse
    {
        $user->update(['trang_thai' => ! $user->trang_thai]);

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
        $uniqueEmailRule = Rule::unique('tai_khoans', 'email');
        if ($user) {
            $uniqueEmailRule->ignore($user->getKey());
        }
        $emailRules = ['required', 'email', 'max:255', $uniqueEmailRule];

        $passwordRules = $user
            ? ['nullable', 'string', 'min:6', 'max:255']
            : ['required', 'string', 'min:6', 'max:255'];

        return $request->validate([
            'ho_ten' => ['required', 'string', 'max:50'],
            'email' => $emailRules,
            'so_dien_thoai' => ['nullable', 'string', 'max:15'],
            'ngay_sinh' => ['nullable', 'date', 'before:today'],
            'gioi_tinh' => ['nullable', Rule::in(['0', '1', '2'])],
            'anh_dai_dien' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'mat_khau' => $passwordRules,
            'chuc_vu_id' => ['nullable', 'integer', Rule::exists('chuc_vus', 'id')],
            'trang_thai' => ['required', 'boolean'],
        ], [
            'ho_ten.required' => 'Vui lòng nhập họ tên.',
            'ho_ten.max' => 'Họ tên không vượt quá 50 ký tự.',
            'email.required' => 'Vui lòng nhập email.',
            'email.email' => 'Email không đúng định dạng.',
            'email.unique' => 'Email này đã được sử dụng.',
            'so_dien_thoai.max' => 'Số điện thoại không vượt quá 15 ký tự.',
            'ngay_sinh.date' => 'Ngày sinh không đúng định dạng.',
            'ngay_sinh.before' => 'Ngày sinh phải trước ngày hôm nay.',
            'gioi_tinh.in' => 'Giới tính không hợp lệ.',
            'anh_dai_dien.image' => 'Ảnh đại diện phải là tệp hình ảnh.',
            'anh_dai_dien.mimes' => 'Ảnh đại diện chỉ chấp nhận định dạng JPG, PNG hoặc WebP.',
            'anh_dai_dien.max' => 'Ảnh đại diện không được vượt quá 2 MB.',
            'mat_khau.required' => 'Vui lòng nhập mật khẩu.',
            'mat_khau.min' => 'Mật khẩu tối thiểu 6 ký tự.',
            'chuc_vu_id.exists' => 'Chức vụ không tồn tại.',
            'trang_thai.required' => 'Vui lòng chọn trạng thái.',
        ]);
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
