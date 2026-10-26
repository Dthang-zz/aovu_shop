<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\CheckoutService;
use App\Services\PromotionPricingService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class StorefrontController extends Controller
{
    public function index(PromotionPricingService $pricing): View
    {
        $products = Product::query()
            ->where('trang_thai', true)
            ->orderByDesc('noi_bat')
            ->orderByDesc('id')
            ->get();

        return view('storefront.products.index', [
            'products' => $products,
            'quotes' => $pricing->quotes($products),
        ]);
    }

    public function show(Product $product, PromotionPricingService $pricing): View
    {
        abort_unless($product->trang_thai, 404);
        $variants = $product->variants()
            ->where('status', ProductVariant::STATUS_ACTIVE)
            ->orderBy('color')
            ->orderBy('size')
            ->get();
        $promotion = $pricing->promotionsFor(collect([$product]))[$product->id] ?? null;
        $variantQuotes = [];
        foreach ($variants as $variant) {
            $variantQuotes[$variant->id] = $pricing->price(
                (int) round((float) ($variant->price ?? $product->gia_san_pham)),
                $promotion,
            );
        }

        return view('storefront.products.show', [
            'product' => $product,
            'variants' => $variants,
            'quote' => $pricing->price((int) $product->gia_san_pham, $promotion),
            'variantQuotes' => $variantQuotes,
        ]);
    }

    public function addToCart(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'product_id' => ['required', 'integer', 'exists:san_phams,id'],
            'variant_id' => ['nullable', 'integer', 'exists:product_variants,id'],
            'quantity' => ['required', 'integer', 'min:1', 'max:100'],
        ]);
        $product = Product::query()
            ->where('trang_thai', true)
            ->findOrFail($data['product_id']);
        $variant = null;

        if (! empty($data['variant_id'])) {
            $variant = ProductVariant::query()
                ->whereKey($data['variant_id'])
                ->where('product_id', $product->id)
                ->where('status', ProductVariant::STATUS_ACTIVE)
                ->firstOrFail();
            if ($variant->stock < (int) $data['quantity']) {
                return back()->withErrors(['quantity' => 'Số lượng vượt quá tồn kho của phân loại đã chọn.']);
            }
        } elseif ($product->variants()->exists()) {
            return back()->withErrors(['variant_id' => 'Vui lòng chọn màu sắc và kích thước sản phẩm.']);
        }

        $cart = Session::get('storefront_cart', []);
        $key = $variant ? 'variant-'.$variant->id : 'product-'.$product->id;
        $nextQuantity = (int) ($cart[$key]['quantity'] ?? 0) + (int) $data['quantity'];

        if ($variant && $nextQuantity > $variant->stock) {
            return back()->withErrors(['quantity' => 'Số lượng trong giỏ vượt quá tồn kho.']);
        }

        $cart[$key] = [
            'product_id' => $product->id,
            'variant_id' => $variant?->id,
            'quantity' => $nextQuantity,
        ];
        Session::put('storefront_cart', $cart);

        return redirect()
            ->route('shop.cart')
            ->with('success', 'Đã thêm sản phẩm vào giỏ hàng.');
    }

    public function cart(CheckoutService $checkout): View|RedirectResponse
    {
        $cart = Session::get('storefront_cart', []);
        if ($cart === []) {
            return redirect()->route('shop.products.index')->with('info', 'Giỏ hàng đang trống.');
        }

        return view('storefront.cart', [
            'quote' => $checkout->quote($cart),
        ]);
    }

    public function updateCart(Request $request, string $item): RedirectResponse
    {
        $data = $request->validate([
            'quantity' => ['required', 'integer', 'min:1', 'max:100'],
        ]);
        $cart = Session::get('storefront_cart', []);
        if (! isset($cart[$item])) {
            abort(404);
        }

        $cart[$item]['quantity'] = (int) $data['quantity'];
        Session::put('storefront_cart', $cart);

        return redirect()->route('shop.cart');
    }

    public function removeFromCart(string $item): RedirectResponse
    {
        $cart = Session::get('storefront_cart', []);
        if (! isset($cart[$item])) {
            abort(404);
        }

        unset($cart[$item]);
        Session::put('storefront_cart', $cart);

        return redirect()->route('shop.cart')->with('success', 'Đã xóa sản phẩm khỏi giỏ hàng.');
    }

    public function checkout(Request $request, CheckoutService $checkout): View|RedirectResponse
    {
        $cart = Session::get('storefront_cart', []);
        if ($cart === []) {
            return redirect()->route('shop.products.index')->with('info', 'Giỏ hàng đang trống.');
        }

        $voucherCode = $request->query('voucher_code', Session::get('checkout_voucher_code'));
        $quote = $checkout->quote($cart, is_string($voucherCode) ? $voucherCode : null);
        if ($quote['voucher_code'] !== null) {
            Session::put('checkout_voucher_code', $quote['voucher_code']);
        }

        return view('storefront.checkout', [
            'quote' => $quote,
            'customer' => [],
        ]);
    }

    public function placeOrder(Request $request, CheckoutService $checkout): RedirectResponse
    {
        $customer = $request->validate([
            'ten_nguoi_nhan' => ['required', 'string', 'max:100'],
            'email_nguoi_nhan' => ['required', 'email', 'max:255'],
            'sdt_nguoi_nhan' => ['required', 'string', 'max:15'],
            'dia_chi_nguoi_nhan' => ['required', 'string', 'max:255'],
            'ghi_chu' => ['nullable', 'string', 'max:2000'],
            'voucher_code' => ['nullable', 'string', 'max:50'],
        ]);

        $confirmation = $checkout->placeOrder(
            $customer,
            Session::get('storefront_cart', []),
            $customer['voucher_code'] ?? null,
        );

        Session::forget(['storefront_cart', 'checkout_voucher_code']);
        Session::put('last_order_confirmation', $confirmation);

        return redirect()
            ->route('shop.checkout.success')
            ->with('success', 'Đơn hàng của bạn đã được ghi nhận.');
    }

    public function orderConfirmation(): View|RedirectResponse
    {
        $confirmation = Session::pull('last_order_confirmation');
        if (! is_array($confirmation)) {
            return redirect()->route('shop.products.index');
        }

        return view('storefront.checkout-success', ['confirmation' => $confirmation]);
    }
}
