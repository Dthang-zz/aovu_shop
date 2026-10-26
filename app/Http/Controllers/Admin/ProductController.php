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
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use RuntimeException;
use Throwable;

class ProductController extends Controller
{
    public function index(Request $request, PromotionPricingService $pricing): View
    {
        $query = Product::query()->with('category')->withCount(['images', 'variants']);

        if ($request->filled('q')) {
            $term = trim((string) $request->query('q'));
            $query->where(function (Builder $query) use ($term) {
                $query->where('ten_san_pham', 'like', "%{$term}%")
                    ->orWhere('ma_san_pham', 'like', "%{$term}%");
            });
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
            'product' => new Product(['trang_thai' => true, 'noi_bat' => false]),
            'categories' => Category::query()->orderBy('ten_danh_muc')->get(),
            'variants' => collect(),
            'variantStatuses' => ProductVariant::statuses(),
            'formTitle' => 'Thêm sản phẩm',
        ]);
    }

    public function show(Product $product, PromotionPricingService $pricing): View
    {
        $product->load(['category', 'images', 'variants']);

        $completedProductItems = DB::table('chi_tiet_don_hangs as order_items')
            ->join('bien_thes as legacy_variants', 'legacy_variants.id', '=', 'order_items.bien_the_id')
            ->join('don_hangs as orders', 'orders.id', '=', 'order_items.don_hang_id')
            ->where('legacy_variants.san_pham_id', $product->getKey())
            ->where('orders.trang_thai', Order::STATUS_COMPLETED);

        $salesSummary = (clone $completedProductItems)
            ->selectRaw('COALESCE(SUM(COALESCE(order_items.thanh_tien, order_items.don_gia * order_items.so_luong)), 0) as revenue')
            ->selectRaw('COALESCE(SUM(order_items.so_luong), 0) as units_sold')
            ->selectRaw('COUNT(DISTINCT orders.id) as orders_count')
            ->first();

        $orders = (clone $completedProductItems)
            ->select([
                'orders.id',
                'orders.ma_don_hang',
                'orders.ngay_dat',
                'orders.trang_thai',
            ])
            ->selectRaw('SUM(order_items.so_luong) as units_sold')
            ->selectRaw('SUM(COALESCE(order_items.thanh_tien, order_items.don_gia * order_items.so_luong)) as revenue')
            ->groupBy('orders.id', 'orders.ma_don_hang', 'orders.ngay_dat', 'orders.trang_thai')
            ->orderByDesc('orders.ngay_dat')
            ->orderByDesc('orders.id')
            ->paginate(10)
            ->withQueryString();

        return view('admin.products.show', [
            'product' => $product,
            'promotionQuote' => $pricing->quotes(collect([$product]))[$product->id],
            'salesSummary' => $salesSummary,
            'orders' => $orders,
            'variantStatuses' => ProductVariant::statuses(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedData($request);
        $variants = $data['variants'];
        unset($data['variants']);
        $imagePaths = $this->storeImages($request->file('hinh_anh', []));
        $data['hinh_anh'] = $imagePaths[0] ?? null;
        $data['ngay_nhap'] = now();
        $data['luot_xem'] = 0;
        $data['da_ban'] = 0;

        try {
            DB::transaction(function () use ($data, $imagePaths, $variants) {
                $product = Product::create($data);
                $this->attachImages($product, $imagePaths);
                $this->syncVariants($product, $variants);
            });
        } catch (Throwable $exception) {
            Storage::disk('public')->delete($imagePaths);
            throw $exception;
        }

        return redirect()
            ->route('admin.products.index')
            ->with('success', 'Đã thêm sản phẩm mới.');
    }

    public function edit(Product $product): View
    {
        $product->load('images');
        $variants = $product->variants()->orderBy('id')->get();

        return view('admin.products.form', [
            'product' => $product,
            'categories' => Category::query()->orderBy('ten_danh_muc')->get(),
            'variants' => $variants,
            'variantStatuses' => ProductVariant::statuses(),
            'formTitle' => 'Chỉnh sửa sản phẩm',
        ]);
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $data = $this->validatedData($request, $product);
        $variants = $data['variants'];
        unset($data['variants']);
        $newImagePaths = $this->storeImages($request->file('hinh_anh', []));
        $removedImages = $product->images()
            ->whereIn('id', $data['xoa_hinh_anh_ids'] ?? [])
            ->get();
        unset($data['xoa_hinh_anh_ids']);
        $remainingImages = $product->images()
            ->whereNotIn('id', $removedImages->modelKeys())
            ->get();
        $coverImage = $remainingImages->first()?->link_hinh_anh
            ?? $newImagePaths[0]
            ?? null;
        $data['hinh_anh'] = $coverImage;

        try {
            DB::transaction(function () use ($product, $data, $removedImages, $newImagePaths, $remainingImages, $variants) {
                if ($removedImages->isNotEmpty()) {
                    ProductImage::query()->whereIn('id', $removedImages->modelKeys())->delete();
                }

                $product->update($data);
                $this->attachImages(
                    $product,
                    $newImagePaths,
                    ((int) $remainingImages->max('thu_tu')) + 1
                );
                $this->syncVariants($product, $variants);
            });
        } catch (Throwable $exception) {
            Storage::disk('public')->delete($newImagePaths);
            throw $exception;
        }

        $removedPaths = $removedImages
            ->pluck('link_hinh_anh')
            ->all();
        if ($removedPaths !== []) {
            Storage::disk('public')->delete($removedPaths);
        }

        return redirect()
            ->route('admin.products.index')
            ->with('success', 'Đã cập nhật sản phẩm.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        foreach ([
            'bien_thes' => 'san_pham_id',
            'danh_gias' => 'san_pham_id',
            'yeu_thichs' => 'san_pham_id',
        ] as $table => $column) {
            if (Schema::hasTable($table)
                && DB::table($table)->where($column, $product->getKey())->exists()) {
                return redirect()
                    ->route('admin.products.index')
                    ->with('error', 'Không thể xóa sản phẩm đang có biến thể, đánh giá hoặc lượt yêu thích liên quan.');
            }
        }

        $imagePaths = $product->images()->pluck('link_hinh_anh')->all();
        if ($product->hinh_anh) {
            $imagePaths[] = $product->hinh_anh;
        }

        DB::transaction(function () use ($product) {
            $product->images()->delete();
            $product->variants()->delete();
            $product->delete();
        });

        Storage::disk('public')->delete(array_unique($imagePaths));

        return redirect()
            ->route('admin.products.index')
            ->with('success', 'Đã xóa sản phẩm.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedData(Request $request, ?Product $product = null): array
    {
        $validator = validator($request->all(), [
            'ma_san_pham' => [
                'required',
                'string',
                'max:50',
                Rule::unique('san_phams', 'ma_san_pham')->ignore($product?->getKey()),
            ],
            'ten_san_pham' => ['required', 'string', 'max:255'],
            'danh_muc_id' => ['nullable', 'integer', Rule::exists('danh_mucs', 'id')],
            'gia_san_pham' => ['required', 'numeric', 'integer', 'min:0', 'max:999999999999'],
            'gia_khuyen_mai' => ['nullable', 'numeric', 'integer', 'min:0', 'max:999999999999'],
            'mo_ta' => ['nullable', 'string', 'max:20000'],
            'chat_lieu' => ['nullable', 'string', 'max:10000'],
            'huong_dan_su_dung' => ['nullable', 'string', 'max:10000'],
            'hinh_anh' => ['nullable', 'array', 'max:10'],
            'hinh_anh.*' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'trang_thai' => ['required', 'boolean'],
            'noi_bat' => ['nullable', 'boolean'],
            'xoa_hinh_anh' => ['nullable', 'boolean'],
            'xoa_hinh_anh_ids' => ['nullable', 'array'],
            'xoa_hinh_anh_ids.*' => [
                'integer',
                Rule::exists('hinh_anh_san_phams', 'id')->where('san_pham_id', $product?->getKey()),
            ],
            'variants' => ['nullable', 'array', 'max:100'],
            'variants.*' => ['array'],
            'variants.*.id' => $product
                ? ['nullable', 'integer', Rule::exists('product_variants', 'id')->where('product_id', $product->getKey())]
                : ['prohibited'],
            'variants.*.sku' => ['nullable', 'string', 'max:255'],
            'variants.*.color' => ['nullable', 'string', 'max:255'],
            'variants.*.size' => ['nullable', 'string', 'max:255'],
            'variants.*.price' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
            'variants.*.stock' => ['required', 'integer', 'min:0', 'max:2147483647'],
            'variants.*.status' => ['required', 'string', Rule::in(array_keys(ProductVariant::statuses()))],
        ], [
            'ma_san_pham.required' => 'Vui lòng nhập mã sản phẩm.',
            'ma_san_pham.max' => 'Mã sản phẩm không được vượt quá 50 ký tự.',
            'ma_san_pham.unique' => 'Mã sản phẩm đã được sử dụng.',
            'ten_san_pham.required' => 'Vui lòng nhập tên sản phẩm.',
            'ten_san_pham.max' => 'Tên sản phẩm không được vượt quá 255 ký tự.',
            'danh_muc_id.exists' => 'Danh mục đã chọn không tồn tại.',
            'gia_san_pham.required' => 'Vui lòng nhập giá bán.',
            'gia_san_pham.numeric' => 'Giá bán phải là số.',
            'gia_san_pham.integer' => 'Giá bán phải là số nguyên theo đơn vị đồng.',
            'gia_san_pham.min' => 'Giá bán không được nhỏ hơn 0.',
            'gia_khuyen_mai.integer' => 'Giá khuyến mãi phải là số nguyên theo đơn vị đồng.',
            'gia_khuyen_mai.min' => 'Giá khuyến mãi không được nhỏ hơn 0.',
            'mo_ta.max' => 'Mô tả không được vượt quá 20.000 ký tự.',
            'chat_lieu.max' => 'Thông tin chất liệu không được vượt quá 10.000 ký tự.',
            'huong_dan_su_dung.max' => 'Hướng dẫn sử dụng không được vượt quá 10.000 ký tự.',
            'hinh_anh.array' => 'Danh sách ảnh sản phẩm không hợp lệ.',
            'hinh_anh.max' => 'Mỗi sản phẩm được tải lên tối đa 10 ảnh cùng lúc.',
            'hinh_anh.*.image' => 'Mỗi tệp tải lên phải là hình ảnh.',
            'hinh_anh.*.mimes' => 'Ảnh sản phẩm phải có định dạng JPG, PNG hoặc WebP.',
            'hinh_anh.*.max' => 'Mỗi ảnh sản phẩm không được vượt quá 5 MB.',
            'xoa_hinh_anh_ids.*.exists' => 'Ảnh cần xóa không thuộc sản phẩm này.',
            'variants.array' => 'Danh sách biến thể không hợp lệ.',
            'variants.max' => 'Mỗi sản phẩm được cấu hình tối đa 100 biến thể.',
            'variants.*.id.exists' => 'Biến thể không thuộc sản phẩm đang chỉnh sửa.',
            'variants.*.sku.max' => 'Mã SKU biến thể không được vượt quá 255 ký tự.',
            'variants.*.color.max' => 'Tên màu sắc không được vượt quá 255 ký tự.',
            'variants.*.size.max' => 'Tên kích thước không được vượt quá 255 ký tự.',
            'variants.*.price.numeric' => 'Giá riêng của biến thể phải là số.',
            'variants.*.price.min' => 'Giá riêng của biến thể không được nhỏ hơn 0.',
            'variants.*.stock.required' => 'Vui lòng nhập số lượng tồn kho cho từng biến thể.',
            'variants.*.stock.integer' => 'Số lượng tồn kho phải là số nguyên.',
            'variants.*.stock.min' => 'Số lượng tồn kho không được nhỏ hơn 0.',
            'variants.*.status.required' => 'Vui lòng chọn trạng thái kinh doanh cho từng biến thể.',
            'variants.*.status.in' => 'Trạng thái kinh doanh của biến thể không hợp lệ.',
            'trang_thai.required' => 'Vui lòng chọn trạng thái sản phẩm.',
            'trang_thai.boolean' => 'Trạng thái sản phẩm không hợp lệ.',
        ]);

        $validator->after(function ($validator) use ($request) {
            $regularPrice = $request->input('gia_san_pham');
            $salePrice = $request->input('gia_khuyen_mai');

            if ($salePrice !== null && $salePrice !== '' && is_numeric($regularPrice)
                && is_numeric($salePrice) && (float) $salePrice >= (float) $regularPrice) {
                $validator->errors()->add(
                    'gia_khuyen_mai',
                    'Giá khuyến mãi phải thấp hơn giá bán.'
                );
            }

            $seenSkus = [];
            $seenIds = [];
            $submittedVariants = $request->input('variants', []);
            if (! is_array($submittedVariants)) {
                return;
            }

            foreach ($submittedVariants as $index => $variant) {
                if (! is_array($variant)) {
                    continue;
                }

                $variantId = $variant['id'] ?? null;
                if ($variantId !== null && $variantId !== '') {
                    if (isset($seenIds[$variantId])) {
                        $validator->errors()->add(
                            "variants.{$index}.id",
                            'Mỗi biến thể chỉ được chọn một lần.'
                        );
                    }
                    $seenIds[$variantId] = true;
                }

                $sku = trim((string) ($variant['sku'] ?? ''));
                if ($sku === '') {
                    continue;
                }

                $sku = Str::upper($sku);
                if (isset($seenSkus[$sku])) {
                    $validator->errors()->add(
                        "variants.{$index}.sku",
                        'Mã SKU biến thể bị trùng trong danh sách.'
                    );
                }
                $seenSkus[$sku] = true;

                $skuQuery = ProductVariant::query()
                    ->whereRaw('LOWER(sku) = ?', [Str::lower($sku)]);
                if ($variantId !== null && $variantId !== '') {
                    $skuQuery->where('id', '<>', (int) $variantId);
                }

                if ($skuQuery->exists()) {
                    $validator->errors()->add(
                        "variants.{$index}.sku",
                        'Mã SKU biến thể đã được sử dụng.'
                    );
                }
            }
        });

        $data = $validator->validate();
        unset($data['xoa_hinh_anh']);
        unset($data['hinh_anh']);
        $data['variants'] = array_values($data['variants'] ?? []);
        if (! $product) {
            unset($data['xoa_hinh_anh_ids']);
        }
        $data['noi_bat'] = $request->boolean('noi_bat');

        return $data;
    }

    /**
     * @param  array<int, array<string, mixed>>  $variants
     */
    private function syncVariants(Product $product, array $variants): void
    {
        $submittedIds = collect($variants)
            ->pluck('id')
            ->filter()
            ->map(fn (mixed $id): int => (int) $id)
            ->all();

        if ($submittedIds === []) {
            $product->variants()->delete();
        } else {
            $product->variants()->whereNotIn('id', $submittedIds)->delete();
        }

        $generatedSkus = [];
        foreach ($variants as $variantData) {
            $sku = Str::upper(trim((string) ($variantData['sku'] ?? '')));
            if ($sku === '') {
                $sku = $this->generateVariantSku($product, $variantData, $generatedSkus);
            }
            $generatedSkus[$sku] = true;

            $attributes = [
                'sku' => $sku,
                'color' => filled($variantData['color'] ?? null) ? trim($variantData['color']) : null,
                'size' => filled($variantData['size'] ?? null) ? trim($variantData['size']) : null,
                'price' => $variantData['price'] ?? null,
                'stock' => (int) $variantData['stock'],
                'status' => $variantData['status'],
            ];

            if (! empty($variantData['id'])) {
                $product->variants()->whereKey($variantData['id'])->firstOrFail()->update($attributes);
            } else {
                $product->variants()->create($attributes);
            }
        }
    }

    /**
     * @param  array<string, mixed>  $variant
     * @param  array<string, bool>  $reservedSkus
     */
    private function generateVariantSku(Product $product, array $variant, array $reservedSkus): string
    {
        $parts = [
            Str::upper(Str::substr(Str::slug($product->ma_san_pham ?: 'SP'.$product->getKey(), '-'), 0, 50)),
        ];
        foreach (['color', 'size'] as $attribute) {
            if (filled($variant[$attribute] ?? null)) {
                $parts[] = Str::upper(Str::substr(Str::slug($variant[$attribute], ''), 0, 20));
            }
        }

        do {
            $sku = implode('-', array_filter($parts)).'-'.Str::upper(Str::random(6));
            $exists = isset($reservedSkus[$sku])
                || ProductVariant::query()->where('sku', $sku)->exists();
        } while ($exists);

        return $sku;
    }

    /**
     * @param  array<int, UploadedFile>|null  $images
     * @return array<int, string>
     */
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

    /**
     * @param  array<int, string>  $paths
     */
    private function attachImages(Product $product, array $paths, int $startOrder = 0): void
    {
        foreach ($paths as $offset => $path) {
            $product->images()->create([
                'link_hinh_anh' => $path,
                'thu_tu' => $startOrder + $offset,
            ]);
        }
    }
}
