<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Services\PromotionPricingService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use RuntimeException;
use Throwable;

class ProductController extends Controller
{
    public function index(Request $request, PromotionPricingService $pricing): View
    {
        $query = Product::query()->with('category')->withCount('images');

        if ($request->filled('q')) {
            $term = trim((string) $request->query('q'));
            $query->where(fn (Builder $query) => $query
                ->where('ten_san_pham', 'like', "%{$term}%")
                ->orWhere('ma_san_pham', 'like', "%{$term}%"));
        }
        if ($request->query('status') === 'active') {
            $query->where('trang_thai', true);
        } elseif ($request->query('status') === 'hidden') {
            $query->where('trang_thai', false);
        }
        if ($request->filled('category_id')) {
            $query->where('danh_muc_id', $request->query('category_id'));
        }
        if ($request->query('featured') === '1') {
            $query->where('noi_bat', true);
        }

        $products = $query->orderByDesc('ngay_nhap')->orderByDesc('id')->paginate(10)->withQueryString();

        return view('admin.products.index', [
            'products' => $products,
            'promotionQuotes' => $pricing->quotes($products->getCollection()),
            'categories' => Category::query()->orderBy('ten_danh_muc')->get(),
            'totalCount' => Product::count(),
            'activeCount' => Product::where('trang_thai', true)->count(),
            'hiddenCount' => Product::where('trang_thai', false)->count(),
            'featuredCount' => Product::where('noi_bat', true)->count(),
        ]);
    }

    public function create(): View
    {
        return view('admin.products.form', [
            'product' => new Product(['trang_thai' => true, 'noi_bat' => false, 'so_luong_ton' => 0]),
            'categories' => Category::query()->orderBy('ten_danh_muc')->get(),
            'attributeGroups' => [],
            'variantRows' => [],
            'formTitle' => 'Thêm sản phẩm',
        ]);
    }

    public function show(Product $product, PromotionPricingService $pricing): View
    {
        $product->load(['category', 'images', 'variants']);
        $completedProductItems = DB::table('chi_tiet_don_hangs as order_items')
            ->join('don_hangs as orders', 'orders.id', '=', 'order_items.don_hang_id')
            ->where('order_items.san_pham_id', $product->getKey())
            ->where('orders.trang_thai', Order::STATUS_COMPLETED);

        $salesSummary = (clone $completedProductItems)
            ->selectRaw('COALESCE(SUM(COALESCE(order_items.thanh_tien, order_items.don_gia * order_items.so_luong)), 0) as revenue')
            ->selectRaw('COALESCE(SUM(order_items.so_luong), 0) as units_sold')
            ->selectRaw('COUNT(DISTINCT orders.id) as orders_count')
            ->first();

        $orders = (clone $completedProductItems)
            ->select(['orders.id', 'orders.ma_don_hang', 'orders.ngay_dat', 'orders.trang_thai'])
            ->selectRaw('SUM(order_items.so_luong) as units_sold')
            ->selectRaw('SUM(COALESCE(order_items.thanh_tien, order_items.don_gia * order_items.so_luong)) as revenue')
            ->groupBy('orders.id', 'orders.ma_don_hang', 'orders.ngay_dat', 'orders.trang_thai')
            ->orderByDesc('orders.ngay_dat')->orderByDesc('orders.id')->paginate(10)->withQueryString();

        return view('admin.products.show', [
            'product' => $product,
            'promotionQuote' => $pricing->quotes(collect([$product]))[$product->id],
            'salesSummary' => $salesSummary,
            'orders' => $orders,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedData($request);
        $variants = $data['variants'] ?? [];
        unset($data['variants']);
        if ($variants !== []) {
            $data['so_luong_ton'] = array_sum(array_map(fn ($variant) => (int) $variant['stock'], $variants));
        }
        $imagePaths = $this->storeImages($request->file('hinh_anh', []));
        $data['hinh_anh'] = $imagePaths[0] ?? null;
        $data['ngay_nhap'] = now();
        $data['luot_xem'] = 0;
        $data['da_ban'] = 0;

        try {
            DB::transaction(function () use ($data, $imagePaths, $variants): void {
                $product = Product::create($data);
                $this->syncVariants($product, $variants);
                $this->attachImages($product, $imagePaths);
            });
        } catch (Throwable $exception) {
            Storage::disk('public')->delete($imagePaths);
            throw $exception;
        }

        return redirect()->route('admin.products.index')->with('success', 'Đã thêm sản phẩm mới.');
    }

    public function edit(Product $product): View
    {
        $product->load(['images', 'variants']);
        [$attributeGroups, $variantRows] = $this->variantFormData($product);
        return view('admin.products.form', [
            'product' => $product,
            'categories' => Category::query()->orderBy('ten_danh_muc')->get(),
            'attributeGroups' => $attributeGroups,
            'variantRows' => $variantRows,
            'formTitle' => 'Chỉnh sửa sản phẩm',
        ]);
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $data = $this->validatedData($request, $product);
        $variants = $data['variants'] ?? [];
        unset($data['variants']);
        if ($variants !== []) {
            $data['so_luong_ton'] = array_sum(array_map(fn ($variant) => (int) $variant['stock'], $variants));
        }
        $newImagePaths = $this->storeImages($request->file('hinh_anh', []));
        $removedImages = $product->images()->whereIn('id', $data['xoa_hinh_anh_ids'] ?? [])->get();
        unset($data['xoa_hinh_anh_ids']);
        $remainingImages = $product->images()->whereNotIn('id', $removedImages->modelKeys())->get();
        $data['hinh_anh'] = $remainingImages->first()?->link_hinh_anh ?? $newImagePaths[0] ?? null;

        try {
            DB::transaction(function () use ($product, $data, $removedImages, $newImagePaths, $remainingImages, $variants): void {
                if ($removedImages->isNotEmpty()) {
                    ProductImage::query()->whereIn('id', $removedImages->modelKeys())->delete();
                }
                $product->update($data);
                $this->syncVariants($product, $variants);
                $this->attachImages($product, $newImagePaths, ((int) $remainingImages->max('thu_tu')) + 1);
            });
        } catch (Throwable $exception) {
            Storage::disk('public')->delete($newImagePaths);
            throw $exception;
        }

        $removedPaths = $removedImages->pluck('link_hinh_anh')->all();
        if ($removedPaths !== []) {
            Storage::disk('public')->delete($removedPaths);
        }

        return redirect()->route('admin.products.index')->with('success', 'Đã cập nhật sản phẩm.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        if (Schema::hasTable('chi_tiet_don_hangs')
            && Schema::hasColumn('chi_tiet_don_hangs', 'san_pham_id')
            && DB::table('chi_tiet_don_hangs')->where('san_pham_id', $product->getKey())->exists()) {
            return redirect()->route('admin.products.index')
                ->with('error', 'Không thể xóa sản phẩm đã phát sinh đơn hàng. Bạn có thể ẩn sản phẩm thay thế.');
        }

        foreach (['danh_gias', 'yeu_thichs'] as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'san_pham_id')
                && DB::table($table)->where('san_pham_id', $product->getKey())->exists()) {
                return redirect()->route('admin.products.index')
                    ->with('error', 'Không thể xóa sản phẩm đang có đánh giá hoặc lượt yêu thích liên quan.');
            }
        }

        $imagePaths = $product->images()->pluck('link_hinh_anh')->all();
        if ($product->hinh_anh) {
            $imagePaths[] = $product->hinh_anh;
        }
        DB::transaction(function () use ($product): void {
            $product->images()->delete();
            $product->delete();
        });
        Storage::disk('public')->delete(array_unique($imagePaths));

        return redirect()->route('admin.products.index')->with('success', 'Đã xóa sản phẩm.');
    }

    /** @return array<string, mixed> */
    private function validatedData(Request $request, ?Product $product = null): array
    {
        $validator = validator($request->all(), [
            'ma_san_pham' => ['required', 'string', 'max:50', Rule::unique('san_phams', 'ma_san_pham')->ignore($product?->getKey())],
            'ten_san_pham' => ['required', 'string', 'max:255'],
            'danh_muc_id' => ['nullable', 'integer', Rule::exists('danh_mucs', 'id')],
            'gia_san_pham' => ['required', 'numeric', 'integer', 'min:0', 'max:999999999999'],
            'gia_khuyen_mai' => ['nullable', 'numeric', 'integer', 'min:0', 'max:999999999999'],
            'so_luong_ton' => ['required_without:variants', 'nullable', 'integer', 'min:0', 'max:2147483647'],
            'mo_ta' => ['nullable', 'string', 'max:20000'],
            'chat_lieu' => ['nullable', 'string', 'max:10000'],
            'huong_dan_su_dung' => ['nullable', 'string', 'max:10000'],
            'trang_thai' => ['required', 'boolean'],
            'noi_bat' => ['nullable', 'boolean'],
            'hinh_anh' => ['nullable', 'array', 'max:10'],
            'hinh_anh.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'xoa_hinh_anh_ids' => ['nullable', 'array'],
            'xoa_hinh_anh_ids.*' => [
                'integer',
                Rule::exists('hinh_anh_san_phams', 'id')->where('san_pham_id', $product?->getKey()),
            ],
            'variants' => ['nullable', 'array', 'max:100'],
            'variants.*.id' => ['nullable', 'integer'],
            'variants.*.sku' => ['required', 'string', 'max:100'],
            'variants.*.price' => ['nullable', 'integer', 'min:0', 'max:999999999999'],
            'variants.*.stock' => ['required', 'integer', 'min:0', 'max:2147483647'],
            'variants.*.attributes' => ['required', 'string', 'max:3000'],
        ], [
            'ma_san_pham.required' => 'Vui lòng nhập mã sản phẩm.',
            'ma_san_pham.max' => 'Mã sản phẩm không được vượt quá 50 ký tự.',
            'ma_san_pham.unique' => 'Mã sản phẩm đã được sử dụng.',
            'ten_san_pham.required' => 'Vui lòng nhập tên sản phẩm.',
            'gia_san_pham.required' => 'Vui lòng nhập giá bán.',
            'gia_san_pham.integer' => 'Giá bán phải là số nguyên theo đơn vị đồng.',
            'so_luong_ton.required' => 'Vui lòng nhập số lượng tồn kho.',
            'so_luong_ton.integer' => 'Số lượng tồn kho phải là số nguyên.',
            'so_luong_ton.min' => 'Số lượng tồn kho không được nhỏ hơn 0.',
            'hinh_anh.max' => 'Mỗi sản phẩm được tải lên tối đa 10 ảnh cùng lúc.',
            'hinh_anh.*.image' => 'Mỗi tệp tải lên phải là hình ảnh.',
            'hinh_anh.*.mimes' => 'Ảnh sản phẩm phải có định dạng JPG, PNG hoặc WebP.',
            'hinh_anh.*.max' => 'Mỗi ảnh sản phẩm không được vượt quá 5 MB.',
        ]);

        $validator->after(function ($validator) use ($request, $product): void {
            $regularPrice = $request->input('gia_san_pham');
            $salePrice = $request->input('gia_khuyen_mai');
            if ($salePrice !== null && $salePrice !== '' && is_numeric($regularPrice)
                && is_numeric($salePrice) && (float) $salePrice >= (float) $regularPrice) {
                $validator->errors()->add('gia_khuyen_mai', 'Giá khuyến mãi phải thấp hơn giá bán.');
            }

            $seenSkus = [];
            $seenCombinations = [];
            foreach ((array) $request->input('variants', []) as $index => $variant) {
                $sku = trim((string) ($variant['sku'] ?? ''));
                if ($sku !== '') {
                    $normalizedSku = mb_strtolower($sku);
                    if (isset($seenSkus[$normalizedSku])) {
                        $validator->errors()->add("variants.{$index}.sku", 'SKU của các biến thể phải khác nhau.');
                    }
                    $seenSkus[$normalizedSku] = true;
                    $skuQuery = ProductVariant::query()->where('sku', $sku);
                    if (! empty($variant['id'])) $skuQuery->where('id', '!=', $variant['id']);
                    if ($skuQuery->exists()) {
                        $validator->errors()->add("variants.{$index}.sku", 'SKU này đã được sử dụng cho biến thể khác.');
                    }
                }

                $attributes = json_decode((string) ($variant['attributes'] ?? ''), true);
                if (! is_array($attributes) || $attributes === []) {
                    $validator->errors()->add("variants.{$index}.attributes", 'Biến thể phải có ít nhất một thuộc tính.');
                    continue;
                }
                foreach ($attributes as $name => $value) {
                    if (! is_string($name) || trim($name) === '' || mb_strlen((string) $name) > 50
                        || ! is_scalar($value) || trim((string) $value) === '' || mb_strlen((string) $value) > 100) {
                        $validator->errors()->add("variants.{$index}.attributes", 'Tên thuộc tính tối đa 50 ký tự và giá trị tối đa 100 ký tự, không được để trống.');
                        continue 2;
                    }
                }
                ksort($attributes);
                $combo = json_encode($attributes, JSON_UNESCAPED_UNICODE);
                if (isset($seenCombinations[$combo])) {
                    $validator->errors()->add("variants.{$index}.attributes", 'Không được tạo hai biến thể có cùng tổ hợp thuộc tính.');
                }
                $seenCombinations[$combo] = true;

                if (! empty($variant['id'])) {
                    $belongsToProduct = $product && ProductVariant::query()
                        ->whereKey($variant['id'])->where('product_id', $product->getKey())->exists();
                    if (! $belongsToProduct) {
                        $validator->errors()->add("variants.{$index}.id", 'Biến thể không thuộc sản phẩm này.');
                    }
                }
            }
        });

        $data = $validator->validate();
        unset($data['xoa_hinh_anh'], $data['hinh_anh']);
        if (! $product) {
            unset($data['xoa_hinh_anh_ids']);
        }
        $data['noi_bat'] = $request->boolean('noi_bat');
        $data['so_luong_ton'] = (int) ($data['so_luong_ton'] ?? 0);

        foreach ($data['variants'] ?? [] as $variant) {
            $attributes = json_decode((string) $variant['attributes'], true);
            if (! is_array($attributes) || $attributes === []) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'variants' => 'Vui lòng tạo ít nhất một tổ hợp biến thể hợp lệ.',
                ]);
            }
        }

        return $data;
    }


    /** @return array{0: array<int, array{name:string,values:string}>, 1: array<int, array<string,mixed>>} */
    private function variantFormData(Product $product): array
    {
        $groups = [];
        $rows = [];
        foreach ($product->variants as $variant) {
            if ($variant->status !== 'active') {
                continue;
            }
            $attributes = $variant->attribute_values ?? [];
            if ($attributes === []) {
                if ($variant->color) $attributes['Màu sắc'] = $variant->color;
                if ($variant->size) $attributes['Kích thước'] = $variant->size;
                if ($attributes === []) $attributes['Phân loại'] = $variant->sku;
            }
            foreach ($attributes as $name => $value) {
                $groups[$name][$value] = true;
            }
            $rows[] = [
                'id' => $variant->id,
                'sku' => $variant->sku,
                'price' => $variant->price,
                'stock' => $variant->stock,
                'attributes' => $attributes,
            ];
        }
        $attributeGroups = [];
        foreach ($groups as $name => $values) {
            $attributeGroups[] = ['name' => $name, 'values' => implode(', ', array_keys($values))];
        }
        return [$attributeGroups, $rows];
    }

    /** @param array<int, array<string,mixed>> $submitted */
    private function syncVariants(Product $product, array $submitted): void
    {
        if (! Schema::hasTable('product_variants')) {
            if ($submitted !== []) {
                throw new RuntimeException('Chưa có bảng product_variants. Hãy chạy php artisan migrate trước.');
            }
            return;
        }

        $keptIds = [];
        foreach ($submitted as $item) {
            $attributes = json_decode((string) $item['attributes'], true) ?: [];
            $color = null;
            $size = null;
            foreach ($attributes as $name => $value) {
                $key = mb_strtolower(trim((string) $name));
                if (in_array($key, ['màu sắc', 'mau sac', 'color', 'màu'], true)) $color = (string) $value;
                if (in_array($key, ['kích thước', 'kich thuoc', 'size'], true)) $size = (string) $value;
            }
            $variantData = [
                'sku' => trim((string) $item['sku']),
                'color' => $color,
                'size' => $size,
                'price' => ($item['price'] ?? '') === '' ? null : (int) $item['price'],
                'stock' => (int) $item['stock'],
                'status' => 'active',
                'attribute_values' => $attributes,
            ];
            if (! empty($item['id'])) {
                $variant = ProductVariant::query()->where('product_id', $product->getKey())->findOrFail($item['id']);
                $variant->update($variantData);
            } else {
                $variant = $product->variants()->create($variantData);
            }
            $keptIds[] = $variant->getKey();
        }

        $removed = $product->variants()->when($keptIds !== [], fn ($query) => $query->whereNotIn('id', $keptIds))->get();
        foreach ($removed as $variant) {
            $isOrdered = Schema::hasTable('chi_tiet_don_hangs')
                && Schema::hasColumn('chi_tiet_don_hangs', 'product_variant_id')
                && DB::table('chi_tiet_don_hangs')->where('product_variant_id', $variant->getKey())->exists();
            if ($isOrdered) {
                $variant->update(['status' => 'inactive']);
            } else {
                $variant->delete();
            }
        }

        if ($submitted !== []) {
            $product->update(['so_luong_ton' => (int) $product->variants()->where('status', 'active')->sum('stock')]);
        }
    }

    /** @param array<int, UploadedFile>|null $images @return array<int, string> */
    private function storeImages(?array $images): array
    {
        $paths = [];
        try {
            foreach ($images ?? [] as $image) {
                $path = $image->store('products', 'public');
                if ($path === false) {
                    throw new RuntimeException('Không thể lưu ảnh sản phẩm vào ổ đĩa public.');
                }
                $paths[] = $path;
            }
        } catch (Throwable $exception) {
            Storage::disk('public')->delete($paths);
            throw $exception;
        }
        return $paths;
    }

    /** @param array<int, string> $paths */
    private function attachImages(Product $product, array $paths, int $startOrder = 0): void
    {
        foreach ($paths as $offset => $path) {
            $product->images()->create(['link_hinh_anh' => $path, 'thu_tu' => $startOrder + $offset]);
        }
    }
}
