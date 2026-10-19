<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class VariantController extends Controller
{
    public function index()
    {
        $variants = ProductVariant::with('product')->paginate(10);
        $totalVariants = ProductVariant::count();
        $colorsCount = ProductVariant::whereNotNull('color')->where('color', '!=', '')->distinct('color')->count('color');
        $sizesCount = ProductVariant::whereNotNull('size')->where('size', '!=', '')->distinct('size')->count('size');

        return view('admin.variants.index', compact('variants', 'totalVariants', 'colorsCount', 'sizesCount'));
    }

    public function create()
    {
        $products = Product::all();
        return view('admin.variants.create', compact('products'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'product_id' => 'required',
            'sku'        => 'nullable|unique:product_variants,sku',
            'price'      => 'nullable|numeric',
            'stock'      => 'required|integer',
        ], [
            'product_id.required' => 'Vui lòng chọn sản phẩm.',
            'sku.unique'          => 'Mã SKU này đã tồn tại.',
            'stock.required'       => 'Vui lòng nhập số lượng tồn kho.',
        ]);

        $product = Product::findOrFail($request->product_id);

        // Tự động sinh SKU nếu người dùng để trống
        $sku = $request->sku;
        if (empty($sku)) {
            $sku = $this->generateSku($product, $request->color, $request->size);
        }

        ProductVariant::create([
            'product_id' => $request->product_id,
            'sku'        => strtoupper($sku),
            'color'      => $request->color,
            'size'       => $request->size,
            'price'      => $request->price,
            'stock'      => $request->stock,
            'status'     => $request->status ?? 'active',
        ]);

        return redirect()->route('admin.variants.index')->with('success', 'Thêm biến thể thành công!');
    }

    public function edit(ProductVariant $variant)
    {
        $products = Product::all();
        return view('admin.variants.edit', compact('variant', 'products'));
    }

    public function update(Request $request, ProductVariant $variant)
    {
        $request->validate([
            'product_id' => 'required',
            'sku'        => 'nullable|unique:product_variants,sku,' . $variant->id,
            'price'      => 'nullable|numeric',
            'stock'      => 'required|integer',
        ], [
            'product_id.required' => 'Vui lòng chọn sản phẩm.',
            'sku.unique'          => 'Mã SKU này đã tồn tại.',
            'stock.required'       => 'Vui lòng nhập số lượng tồn kho.',
        ]);

        $product = Product::findOrFail($request->product_id);

        // Tự động sinh SKU nếu người dùng để trống khi sửa
        $sku = $request->sku;
        if (empty($sku)) {
            $sku = $this->generateSku($product, $request->color, $request->size);
        }

        $variant->update([
            'product_id' => $request->product_id,
            'sku'        => strtoupper($sku),
            'color'      => $request->color,
            'size'       => $request->size,
            'price'      => $request->price,
            'stock'      => $request->stock,
            'status'     => $request->status,
        ]);

        return redirect()->route('admin.variants.index')->with('success', 'Cập nhật biến thể thành công!');
    }

    public function destroy(ProductVariant $variant)
    {
        $variant->delete();
        return redirect()->route('admin.variants.index')->with('success', 'Đã xóa biến thể thành công!');
    }

    /**
     * Hàm phụ trợ tự động tạo SKU
     */
    private function generateSku($product, $color = null, $size = null)
    {
        // Lấy mã SP hoặc tên SP gạch nối
        $prefix = $product->ma_san_pham ?? Str::slug($product->ten_san_pham, '-');

        $parts = [$prefix];

        if (!empty($color)) {
            $parts[] = Str::slug($color, '');
        }

        if (!empty($size)) {
            $parts[] = Str::slug($size, '');
        }

        // Thêm 4 ký tự ngẫu nhiên để đảm bảo duy nhất
        $parts[] = strtoupper(Str::random(4));

        return implode('-', $parts);
    }
}
