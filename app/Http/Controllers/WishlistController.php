<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\YeuThich;
use App\Services\PromotionPricingService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Session;

class WishlistController extends Controller
{
    public function index(PromotionPricingService $pricing): View
    {
        $productIds = $this->getWishlistProductIds();

        $products = Product::query()
            ->whereIn('id', $productIds)
            ->where('trang_thai', true)
            ->orderByDesc('id')
            ->get();

        $quotes = $pricing->quotes($products);

        return view('storefront.wishlist', [
            'products' => $products,
            'quotes' => $quotes,
        ]);
    }

    public function toggle(Request $request, Product $product): JsonResponse|RedirectResponse
    {
        $customerId = Session::get('customer_id');

        if ($customerId && Schema::hasTable('yeu_thichs')) {
            $existing = YeuThich::query()
                ->where('tai_khoan_id', $customerId)
                ->where('san_pham_id', $product->id)
                ->first();

            if ($existing) {
                $existing->delete();
                $isFavorited = false;
            } else {
                YeuThich::query()->create([
                    'tai_khoan_id' => $customerId,
                    'san_pham_id' => $product->id,
                    'ngay_them' => now(),
                ]);
                $isFavorited = true;
            }

            $count = YeuThich::query()->where('tai_khoan_id', $customerId)->count();
        } else {
            $wishlist = Session::get('storefront_wishlist', []);
            if (! is_array($wishlist)) {
                $wishlist = [];
            }

            if (in_array($product->id, $wishlist, true)) {
                $wishlist = array_values(array_diff($wishlist, [$product->id]));
                $isFavorited = false;
            } else {
                $wishlist[] = $product->id;
                $isFavorited = true;
            }

            Session::put('storefront_wishlist', array_values(array_unique($wishlist)));
            $count = count($wishlist);
        }

        $message = $isFavorited
            ? 'Đã thêm "' . $product->ten_san_pham . '" vào danh sách yêu thích.'
            : 'Đã bỏ "' . $product->ten_san_pham . '" khỏi danh sách yêu thích.';

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'is_favorited' => $isFavorited,
                'count' => $count,
                'message' => $message,
            ]);
        }

        return back()->with('success', $message);
    }

    public function destroy(Product $product): RedirectResponse
    {
        $customerId = Session::get('customer_id');

        if ($customerId && Schema::hasTable('yeu_thichs')) {
            YeuThich::query()
                ->where('tai_khoan_id', $customerId)
                ->where('san_pham_id', $product->id)
                ->delete();
        } else {
            $wishlist = Session::get('storefront_wishlist', []);
            if (is_array($wishlist)) {
                $wishlist = array_values(array_diff($wishlist, [$product->id]));
                Session::put('storefront_wishlist', $wishlist);
            }
        }

        return redirect()->route('shop.wishlist.index')
            ->with('success', 'Đã xóa sản phẩm khỏi danh sách yêu thích.');
    }

    /**
     * @return array<int, int>
     */
    private function getWishlistProductIds(): array
    {
        $customerId = Session::get('customer_id');

        if ($customerId && Schema::hasTable('yeu_thichs')) {
            return YeuThich::query()
                ->where('tai_khoan_id', $customerId)
                ->pluck('san_pham_id')
                ->map(fn ($id) => (int) $id)
                ->all();
        }

        $wishlist = Session::get('storefront_wishlist', []);

        return is_array($wishlist) ? array_map('intval', $wishlist) : [];
    }
}
