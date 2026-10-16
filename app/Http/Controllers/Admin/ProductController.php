<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
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
    public function index(Request $request): View
    {
        $query = Product::query()->with('category')->withCount('images');

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

        return view('admin.products.index', [
            'products' => $query->orderByDesc('ngay_nhap')->orderByDesc('id')->paginate(10)->withQueryString(),
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
            'formTitle' => 'Thêm sản phẩm',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedData($request);
        $imagePaths = $this->storeImages($request->file('hinh_anh', []));
        $data['hinh_anh'] = $imagePaths[0] ?? null;
        $data['ngay_nhap'] = now();
        $data['luot_xem'] = 0;
        $data['da_ban'] = 0;

        try {
            DB::transaction(function () use ($data, $imagePaths) {
                $product = Product::create($data);
                $this->attachImages($product, $imagePaths);
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

        return view('admin.products.form', [
            'product' => $product,
            'categories' => Category::query()->orderBy('ten_danh_muc')->get(),
            'formTitle' => 'Chỉnh sửa sản phẩm',
        ]);
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $data = $this->validatedData($request, $product);
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
            DB::transaction(function () use ($product, $data, $removedImages, $newImagePaths, $remainingImages) {
                if ($removedImages->isNotEmpty()) {
                    ProductImage::query()->whereIn('id', $removedImages->modelKeys())->delete();
                }

                $product->update($data);
                $this->attachImages(
                    $product,
                    $newImagePaths,
                    ((int) $remainingImages->max('thu_tu')) + 1
                );
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
            'khuyen_mai_bat_dau' => ['nullable', 'date'],
            'khuyen_mai_ket_thuc' => ['nullable', 'date', 'after_or_equal:khuyen_mai_bat_dau'],
            'xoa_hinh_anh' => ['nullable', 'boolean'],
            'xoa_hinh_anh_ids' => ['nullable', 'array'],
            'xoa_hinh_anh_ids.*' => [
                'integer',
                Rule::exists('hinh_anh_san_phams', 'id')->where('san_pham_id', $product?->getKey()),
            ],
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
            'trang_thai.required' => 'Vui lòng chọn trạng thái sản phẩm.',
            'trang_thai.boolean' => 'Trạng thái sản phẩm không hợp lệ.',
            'khuyen_mai_ket_thuc.after_or_equal' => 'Thời gian kết thúc khuyến mãi phải sau thời gian bắt đầu.',
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
        });

        $data = $validator->validate();
        unset($data['xoa_hinh_anh']);
        unset($data['hinh_anh']);
        if (!$product) {
            unset($data['xoa_hinh_anh_ids']);
        }
        $data['noi_bat'] = $request->boolean('noi_bat');
        $data['khuyen_mai_bat_dau'] = $data['khuyen_mai_bat_dau'] ?? null;
        $data['khuyen_mai_ket_thuc'] = $data['khuyen_mai_ket_thuc'] ?? null;

        return $data;
    }

    /**
     * @param array<int, UploadedFile>|null $images
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
     * @param array<int, string> $paths
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
