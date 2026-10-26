<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Promotion;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PromotionController extends Controller
{
    public function index(): View
    {
        return view('admin.promotions.index', [
            'promotions' => Promotion::query()
                ->with('products:id,ten_san_pham')
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->paginate(15),
        ]);
    }

    public function show(Promotion $promotion): View
    {
        return view('admin.promotions.show', [
            'promotion' => $promotion,
            'products' => $promotion->products()
                ->select('san_phams.id', 'san_phams.ma_san_pham', 'san_phams.ten_san_pham', 'san_phams.gia_san_pham', 'san_phams.trang_thai')
                ->orderBy('san_phams.ten_san_pham')
                ->paginate(15),
        ]);
    }

    public function create(): View
    {
        return view('admin.promotions.form', [
            'promotion' => new Promotion(['trang_thai' => true]),
            'products' => Product::query()->orderBy('ten_san_pham')->get(['id', 'ten_san_pham', 'ma_san_pham']),
            'selectedProductIds' => [],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedData($request);

        DB::transaction(function () use ($data): void {
            $promotion = Promotion::create($data['promotion']);
            $promotion->products()->sync($data['san_pham_ids']);
        });

        return redirect()
            ->route('admin.promotions.index')
            ->with('success', 'Đã tạo chương trình khuyến mãi.');
    }

    public function edit(Promotion $promotion): View
    {
        $promotion->load('products:id');

        return view('admin.promotions.form', [
            'promotion' => $promotion,
            'products' => Product::query()->orderBy('ten_san_pham')->get(['id', 'ten_san_pham', 'ma_san_pham']),
            'selectedProductIds' => $promotion->products->modelKeys(),
        ]);
    }

    public function update(Request $request, Promotion $promotion): RedirectResponse
    {
        $data = $this->validatedData($request);

        DB::transaction(function () use ($data, $promotion): void {
            $promotion->update($data['promotion']);
            $promotion->products()->sync($data['san_pham_ids']);
        });

        return redirect()
            ->route('admin.promotions.index')
            ->with('success', 'Đã cập nhật chương trình khuyến mãi.');
    }

    public function destroy(Promotion $promotion): RedirectResponse
    {
        $promotion->delete();

        return redirect()
            ->route('admin.promotions.index')
            ->with('success', 'Đã xóa chương trình khuyến mãi.');
    }

    /**
     * @return array{promotion: array<string, mixed>, san_pham_ids: array<int, int>}
     */
    private function validatedData(Request $request): array
    {
        $data = $request->validate([
            'ten_chuong_trinh' => ['required', 'string', 'max:255'],
            'phan_tram_giam' => ['required', 'numeric', 'gt:0', 'lte:100'],
            'san_pham_ids' => ['required', 'array', 'min:1'],
            'san_pham_ids.*' => ['required', 'integer', 'distinct', 'exists:san_phams,id'],
            'ngay_bat_dau' => ['required', 'date'],
            'ngay_ket_thuc' => ['required', 'date', 'after:ngay_bat_dau'],
            'trang_thai' => ['required', 'boolean'],
        ], [
            'ten_chuong_trinh.required' => 'Vui lòng nhập tên chương trình.',
            'phan_tram_giam.required' => 'Vui lòng nhập phần trăm giảm.',
            'phan_tram_giam.gt' => 'Phần trăm giảm phải lớn hơn 0.',
            'phan_tram_giam.lte' => 'Phần trăm giảm không được vượt quá 100%.',
            'san_pham_ids.required' => 'Vui lòng chọn ít nhất một sản phẩm.',
            'san_pham_ids.min' => 'Vui lòng chọn ít nhất một sản phẩm.',
            'san_pham_ids.*.exists' => 'Một sản phẩm đã chọn không tồn tại.',
            'ngay_bat_dau.required' => 'Vui lòng chọn thời gian bắt đầu.',
            'ngay_ket_thuc.after' => 'Thời gian kết thúc phải sau thời gian bắt đầu.',
        ]);

        $productIds = array_map('intval', $data['san_pham_ids']);
        if (Product::query()->whereIn('id', $productIds)->count() !== count(array_unique($productIds))) {
            throw ValidationException::withMessages([
                'san_pham_ids' => 'Danh sách sản phẩm áp dụng không hợp lệ.',
            ]);
        }

        return [
            'promotion' => [
                'ten_chuong_trinh' => trim($data['ten_chuong_trinh']),
                'phan_tram_giam' => $data['phan_tram_giam'],
                'ngay_bat_dau' => $data['ngay_bat_dau'],
                'ngay_ket_thuc' => $data['ngay_ket_thuc'],
                'trang_thai' => (bool) $data['trang_thai'],
            ],
            'san_pham_ids' => $productIds,
        ];
    }
}
