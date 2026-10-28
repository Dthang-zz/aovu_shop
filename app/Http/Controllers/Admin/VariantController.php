<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class VariantController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
            'product_id' => ['nullable', 'integer'],
            'color' => ['nullable', 'string', 'max:255'],
            'size' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::in(array_keys(ProductVariant::statuses()))],
        ]);
        $query = ProductVariant::query()->with('product');

        if (filled($filters['q'] ?? null)) {
            $term = trim($filters['q']);
            $query->where(function ($query) use ($term): void {
                $query->where('sku', 'like', "%{$term}%")
                    ->orWhere('color', 'like', "%{$term}%")
                    ->orWhere('size', 'like', "%{$term}%")
                    ->orWhereHas('product', function ($productQuery) use ($term): void {
                        $productQuery->where('ten_san_pham', 'like', "%{$term}%")
                            ->orWhere('ma_san_pham', 'like', "%{$term}%");
                    });
            });
        }

        if (filled($filters['product_id'] ?? null)) {
            $query->where('product_id', (int) $filters['product_id']);
        }

        if (filled($filters['color'] ?? null)) {
            $query->where('color', $filters['color']);
        }

        if (filled($filters['size'] ?? null)) {
            $query->where('size', $filters['size']);
        }

        if (filled($filters['status'] ?? null)) {
            $query->where('status', $filters['status']);
        }

        return view('admin.variants.index', [
            'variants' => $query->orderByDesc('id')->paginate(10)->withQueryString(),
            'totalVariants' => ProductVariant::count(),
            'colorsCount' => ProductVariant::query()
                ->whereNotNull('color')
                ->where('color', '!=', '')
                ->distinct()
                ->count('color'),
            'sizesCount' => ProductVariant::query()
                ->whereNotNull('size')
                ->where('size', '!=', '')
                ->distinct()
                ->count('size'),
            'products' => Product::query()->orderBy('ten_san_pham')->get(['id', 'ma_san_pham', 'ten_san_pham']),
            'colors' => ProductVariant::query()
                ->whereNotNull('color')
                ->where('color', '!=', '')
                ->distinct()
                ->orderBy('color')
                ->pluck('color'),
            'sizes' => ProductVariant::query()
                ->whereNotNull('size')
                ->where('size', '!=', '')
                ->distinct()
                ->orderBy('size')
                ->pluck('size'),
            'statuses' => ProductVariant::statuses(),
            'statusBadges' => [
                ProductVariant::STATUS_ACTIVE => 'success',
                ProductVariant::STATUS_INACTIVE => 'secondary',
                ProductVariant::STATUS_OUT_OF_STOCK => 'danger',
            ],
        ]);
    }

    public function create(): View
    {
        return view('admin.variants.create', [
            'products' => Product::query()->orderBy('ten_san_pham')->get(),
            'statuses' => ProductVariant::statuses(),
        ]);
    }

    public function show(ProductVariant $variant): View
    {
        $variant->load('product');

        return view('admin.variants.show', [
            'variant' => $variant,
            'statusLabel' => ProductVariant::statuses()[$variant->status] ?? 'Không xác định',
            'statusBadge' => match ($variant->status) {
                ProductVariant::STATUS_ACTIVE => 'success',
                ProductVariant::STATUS_INACTIVE => 'secondary',
                ProductVariant::STATUS_OUT_OF_STOCK => 'danger',
                default => 'secondary',
            },
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedData($request);
        $product = Product::query()->findOrFail($data['product_id']);
        $data['sku'] = $this->normalizedSku($data['sku'] ?? null)
            ?: $this->generateSku($product, $data['color'] ?? null, $data['size'] ?? null);

        ProductVariant::create($data);

        return redirect()
            ->route('admin.variants.index')
            ->with('success', 'Đã thêm biến thể sản phẩm.');
    }

    public function edit(ProductVariant $variant): View
    {
        return view('admin.variants.edit', [
            'variant' => $variant,
            'products' => Product::query()->orderBy('ten_san_pham')->get(),
            'statuses' => ProductVariant::statuses(),
        ]);
    }

    public function update(Request $request, ProductVariant $variant): RedirectResponse
    {
        $data = $this->validatedData($request, $variant);
        $product = Product::query()->findOrFail($data['product_id']);
        $data['sku'] = $this->normalizedSku($data['sku'] ?? null)
            ?: $this->generateSku($product, $data['color'] ?? null, $data['size'] ?? null);

        $variant->update($data);

        return redirect()
            ->route('admin.variants.index')
            ->with('success', 'Đã cập nhật biến thể sản phẩm.');
    }

    public function destroy(ProductVariant $variant): RedirectResponse
    {
        $variant->delete();

        return redirect()
            ->route('admin.variants.index')
            ->with('success', 'Đã xóa biến thể sản phẩm.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedData(Request $request, ?ProductVariant $variant = null): array
    {
        $data = $request->validate([
            'product_id' => ['required', 'integer', Rule::exists('san_phams', 'id')],
            'sku' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('product_variants', 'sku')->ignore($variant?->getKey()),
            ],
            'color' => ['nullable', 'string', 'max:255'],
            'size' => ['nullable', 'string', 'max:255'],
            'price' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
            'stock' => ['required', 'integer', 'min:0', 'max:2147483647'],
            'status' => ['required', Rule::in(array_keys(ProductVariant::statuses()))],
        ], [
            'product_id.required' => 'Vui lòng chọn sản phẩm.',
            'product_id.exists' => 'Sản phẩm đã chọn không tồn tại.',
            'sku.unique' => 'Mã SKU này đã tồn tại.',
            'color.max' => 'Tên màu sắc không được vượt quá 255 ký tự.',
            'size.max' => 'Tên kích thước không được vượt quá 255 ký tự.',
            'price.numeric' => 'Giá riêng của biến thể phải là số.',
            'price.min' => 'Giá riêng của biến thể không được nhỏ hơn 0.',
            'stock.required' => 'Vui lòng nhập số lượng tồn kho.',
            'stock.integer' => 'Số lượng tồn kho phải là số nguyên.',
            'stock.min' => 'Số lượng tồn kho không được nhỏ hơn 0.',
            'status.required' => 'Vui lòng chọn trạng thái kinh doanh.',
            'status.in' => 'Trạng thái kinh doanh không hợp lệ.',
        ]);

        $sku = $this->normalizedSku($data['sku'] ?? null);
        if ($sku !== '') {
            $skuQuery = ProductVariant::query()->whereRaw('LOWER(sku) = ?', [Str::lower($sku)]);
            if ($variant !== null) {
                $skuQuery->whereKeyNot($variant->getKey());
            }

            if ($skuQuery->exists()) {
                throw ValidationException::withMessages([
                    'sku' => 'Mã SKU này đã tồn tại.',
                ]);
            }
        }

        $data['sku'] = $sku ?: null;
        $data['color'] = filled($data['color'] ?? null) ? trim($data['color']) : null;
        $data['size'] = filled($data['size'] ?? null) ? trim($data['size']) : null;

        return $data;
    }

    private function normalizedSku(?string $sku): string
    {
        return Str::upper(trim($sku ?? ''));
    }

    private function generateSku(Product $product, ?string $color, ?string $size): string
    {
        $parts = [
            Str::upper(Str::substr(Str::slug($product->ma_san_pham ?: 'SP'.$product->getKey(), '-'), 0, 50)),
        ];
        foreach ([$color, $size] as $attribute) {
            if (filled($attribute)) {
                $parts[] = Str::upper(Str::substr(Str::slug($attribute, ''), 0, 20));
            }
        }

        do {
            $sku = implode('-', array_filter($parts)).'-'.Str::upper(Str::random(6));
        } while (ProductVariant::query()->where('sku', $sku)->exists());

        return $sku;
    }
}
