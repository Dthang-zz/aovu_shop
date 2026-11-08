<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Voucher;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * "Khuyến mãi": discount codes customers enter in the cart/checkout (legacy `vouchers` table).
 */
class PromotionController extends Controller
{
    public function index(Request $request): View
    {
        $term = trim((string) $request->query('q', ''));

        return view('admin.promotions.index', [
            'vouchers' => Voucher::query()
                ->when($term !== '', fn ($query) => $query->where(function ($query) use ($term): void {
                    $query->where('ma_voucher', 'like', "%{$term}%")
                        ->orWhere('mo_ta', 'like', "%{$term}%");
                }))
                ->orderByDesc('id')
                ->paginate(15)
                ->withQueryString(),
        ]);
    }

    public function show(Voucher $promotion): View
    {
        $orders = Schema::hasColumn('don_hangs', 'voucher_id')
            ? DB::table('don_hangs')
                ->where('voucher_id', $promotion->getKey())
                ->orderByDesc('ngay_dat')
                ->orderByDesc('id')
                ->paginate(10, array_values(array_filter(
                    ['id', 'ma_don_hang', 'ten_nguoi_nhan', 'ngay_dat', 'tong_tien', 'tien_giam_voucher', 'trang_thai'],
                    fn (string $column): bool => Schema::hasColumn('don_hangs', $column),
                )))
            : null;

        return view('admin.promotions.show', [
            'voucher' => $promotion,
            'orders' => $orders,
            'orderStatuses' => Order::statuses(),
        ]);
    }

    public function create(): View
    {
        return view('admin.promotions.form', [
            'voucher' => new Voucher([
                'loai_giam' => Voucher::TYPE_PERCENT,
                'trang_thai' => true,
            ]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $voucher = Voucher::query()->create([
            ...$this->validatedData($request),
            'da_su_dung' => 0,
        ]);

        return redirect()
            ->route('admin.promotions.index')
            ->with('success', 'Đã tạo mã giảm giá '.$voucher->ma_voucher.'.');
    }

    public function edit(Voucher $promotion): View
    {
        return view('admin.promotions.form', [
            'voucher' => $promotion,
        ]);
    }

    public function update(Request $request, Voucher $promotion): RedirectResponse
    {
        $promotion->update($this->validatedData($request, $promotion));

        return redirect()
            ->route('admin.promotions.index')
            ->with('success', 'Đã cập nhật mã giảm giá '.$promotion->ma_voucher.'.');
    }

    public function destroy(Voucher $promotion): RedirectResponse
    {
        $isUsedByOrders = Schema::hasColumn('don_hangs', 'voucher_id')
            && DB::table('don_hangs')->where('voucher_id', $promotion->getKey())->exists();

        if ($isUsedByOrders) {
            return redirect()
                ->route('admin.promotions.index')
                ->with('error', 'Mã '.$promotion->ma_voucher.' đã được dùng trong đơn hàng nên không thể xóa. Hãy tắt mã để ngừng áp dụng.');
        }

        $promotion->delete();

        return redirect()
            ->route('admin.promotions.index')
            ->with('success', 'Đã xóa mã giảm giá '.$promotion->ma_voucher.'.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedData(Request $request, ?Voucher $voucher = null): array
    {
        $request->merge(['ma_voucher' => Str::upper(trim((string) $request->input('ma_voucher')))]);
        $isPercent = (int) $request->input('loai_giam') === Voucher::TYPE_PERCENT;

        $data = $request->validate([
            'ma_voucher' => [
                'required', 'string', 'max:50', 'regex:/^[A-Z0-9_-]+$/',
                Rule::unique('vouchers', 'ma_voucher')->ignore($voucher?->getKey()),
            ],
            'mo_ta' => ['nullable', 'string', 'max:255'],
            'loai_giam' => ['required', 'integer', Rule::in(array_keys(Voucher::types()))],
            'gia_tri_giam' => $isPercent
                ? ['required', 'integer', 'min:1', 'max:100']
                : ['required', 'integer', 'min:1', 'max:999999999999'],
            'giam_toi_da' => $isPercent ? ['nullable', 'integer', 'min:1', 'max:999999999999'] : ['nullable'],
            'don_toi_thieu' => ['nullable', 'integer', 'min:0', 'max:999999999999'],
            'so_luong' => ['nullable', 'integer', 'min:'.max(1, (int) ($voucher?->da_su_dung ?? 0)), 'max:2147483647'],
            'ngay_bat_dau' => ['required', 'date'],
            'ngay_ket_thuc' => ['required', 'date', 'after:ngay_bat_dau'],
            'trang_thai' => ['required', 'boolean'],
        ], [
            'ma_voucher.required' => 'Vui lòng nhập mã giảm giá.',
            'ma_voucher.max' => 'Mã giảm giá không được vượt quá 50 ký tự.',
            'ma_voucher.regex' => 'Mã giảm giá chỉ gồm chữ, số, dấu gạch ngang hoặc gạch dưới, không có khoảng trắng.',
            'ma_voucher.unique' => 'Mã giảm giá này đã tồn tại.',
            'mo_ta.max' => 'Mô tả không được vượt quá 255 ký tự.',
            'loai_giam.required' => 'Vui lòng chọn hình thức giảm giá.',
            'loai_giam.in' => 'Hình thức giảm giá không hợp lệ.',
            'gia_tri_giam.required' => $isPercent ? 'Vui lòng nhập phần trăm giảm.' : 'Vui lòng nhập số tiền giảm.',
            'gia_tri_giam.integer' => $isPercent ? 'Phần trăm giảm phải là số nguyên.' : 'Số tiền giảm phải là số nguyên.',
            'gia_tri_giam.min' => $isPercent ? 'Phần trăm giảm phải lớn hơn 0.' : 'Số tiền giảm phải lớn hơn 0.',
            'gia_tri_giam.max' => $isPercent ? 'Phần trăm giảm không được vượt quá 100%.' : 'Số tiền giảm quá lớn.',
            'giam_toi_da.integer' => 'Mức giảm tối đa phải là số nguyên.',
            'giam_toi_da.min' => 'Mức giảm tối đa phải lớn hơn 0.',
            'don_toi_thieu.integer' => 'Giá trị đơn tối thiểu phải là số nguyên.',
            'don_toi_thieu.min' => 'Giá trị đơn tối thiểu không được âm.',
            'so_luong.integer' => 'Số lượt sử dụng phải là số nguyên.',
            'so_luong.min' => $voucher && $voucher->da_su_dung > 0
                ? 'Số lượt sử dụng không được nhỏ hơn số lượt đã dùng ('.$voucher->da_su_dung.').'
                : 'Số lượt sử dụng phải lớn hơn 0.',
            'ngay_bat_dau.required' => 'Vui lòng chọn thời gian bắt đầu.',
            'ngay_ket_thuc.required' => 'Vui lòng chọn thời gian kết thúc.',
            'ngay_ket_thuc.after' => 'Thời gian kết thúc phải sau thời gian bắt đầu.',
        ]);

        return [
            'ma_voucher' => $data['ma_voucher'],
            'mo_ta' => filled($data['mo_ta'] ?? null) ? trim($data['mo_ta']) : null,
            'loai_giam' => (int) $data['loai_giam'],
            'gia_tri_giam' => (int) $data['gia_tri_giam'],
            'giam_toi_da' => $isPercent && filled($data['giam_toi_da'] ?? null) ? (int) $data['giam_toi_da'] : null,
            'don_toi_thieu' => filled($data['don_toi_thieu'] ?? null) ? (int) $data['don_toi_thieu'] : null,
            'so_luong' => filled($data['so_luong'] ?? null) ? (int) $data['so_luong'] : null,
            'ngay_bat_dau' => $data['ngay_bat_dau'],
            'ngay_ket_thuc' => $data['ngay_ket_thuc'],
            'trang_thai' => (bool) $data['trang_thai'],
        ];
    }
}
