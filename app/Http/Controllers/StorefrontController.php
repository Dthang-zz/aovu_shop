<?php

namespace App\Http\Controllers;

use App\Models\Banner;
use App\Models\Product;
use App\Services\CheckoutService;
use App\Services\PromotionPricingService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class StorefrontController extends Controller
{
    /**
     * Trang danh sách sản phẩm.
     */
    public function index(PromotionPricingService $pricing): View
    {
        $products = Product::query()
            ->where('trang_thai', true)
            ->orderByDesc('noi_bat')
            ->orderByDesc('id')
            ->get();

        return view('storefront.products.index', [
            'banners' => Banner::query()->active()->ordered()->get(),
            'products' => $products,
            'quotes' => $pricing->quotes($products),
        ]);
    }

    /**
     * Chi tiết sản phẩm.
     */
    public function show(Product $product, PromotionPricingService $pricing): View
    {
        abort_unless($product->trang_thai, 404);

        $promotion = $pricing->promotionsFor(collect([$product]))[$product->id] ?? null;
        $variants = $product->variants()->where('status', 'active')->orderBy('id')->get();
        $variantAttributes = [];
        foreach ($variants as $variant) {
            $attributes = $variant->attribute_values ?? [];
            if ($attributes === []) {
                if ($variant->color) $attributes['Màu sắc'] = $variant->color;
                if ($variant->size) $attributes['Kích thước'] = $variant->size;
                if ($attributes === []) $attributes['Phân loại'] = $variant->sku;
            }
            $variant->setAttribute('display_attributes', $attributes);
            foreach ($attributes as $name => $value) {
                $variantAttributes[$name][$value] = true;
            }
        }
        $variantAttributes = collect($variantAttributes)->map(fn ($values) => array_keys($values))->all();
        $reviews = collect();
        $reviewSummary = (object) ['total' => 0, 'average_rating' => null];

        if (Schema::hasTable('danh_gias')) {
            $reviewSummary = DB::table('danh_gias')
                ->where('san_pham_id', $product->id)
                ->selectRaw('COUNT(*) as total, AVG(so_sao) as average_rating')
                ->first();

            $reviewColumns = [
                'reviews.so_sao',
                'reviews.noi_dung',
                'reviews.ngay_dang',
                'accounts.ho_ten as customer_name',
            ];
            $reviewColumns[] = Schema::hasColumn('danh_gias', 'phan_hoi')
                ? 'reviews.phan_hoi'
                : DB::raw('NULL as phan_hoi');

            $reviews = DB::table('danh_gias as reviews')
                ->leftJoin('tai_khoans as accounts', 'accounts.id', '=', 'reviews.tai_khoan_id')
                ->where('reviews.san_pham_id', $product->id)
                ->orderByDesc('reviews.ngay_dang')
                ->orderByDesc('reviews.id')
                ->limit(10)
                ->get($reviewColumns);
        }

        return view('storefront.products.show', [
            'product' => $product,
            'variants' => $variants,
            'variantAttributes' => $variantAttributes,
            'quote' => $pricing->price((int) $product->gia_san_pham, $promotion),
            'reviews' => $reviews,
            'reviewSummary' => $reviewSummary,
        ]);
    }

    /**
     * Thêm sản phẩm vào giỏ hàng.
     */
    public function addToCart(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'product_id' => ['required', 'integer', 'exists:san_phams,id'],
            'variant_id' => ['nullable', 'integer'],
            'quantity' => ['required', 'integer', 'min:1', 'max:100'],
        ]);

        $product = Product::query()->where('trang_thai', true)->findOrFail($data['product_id']);
        $availableVariants = $product->variants()->where('status', 'active')->exists();
        $variant = null;
        if ($availableVariants) {
            if (empty($data['variant_id'])) {
                throw ValidationException::withMessages(['variant_id' => 'Vui lòng chọn đầy đủ thuộc tính sản phẩm.']);
            }
            $variant = $product->variants()->where('status', 'active')->find($data['variant_id']);
            if (! $variant) {
                throw ValidationException::withMessages(['variant_id' => 'Biến thể sản phẩm không hợp lệ hoặc đã ngừng kinh doanh.']);
            }
        } elseif (! empty($data['variant_id'])) {
            throw ValidationException::withMessages(['variant_id' => 'Sản phẩm này không có biến thể hợp lệ.']);
        }

        $cart = $this->normalizeCart(Session::get('storefront_cart', []));
        $key = $variant ? 'variant-'.$variant->id : 'product-'.$product->id;
        $nextQuantity = (int) ($cart[$key]['quantity'] ?? 0) + (int) $data['quantity'];
        $availableStock = $variant ? (int) $variant->stock : (int) $product->so_luong_ton;

        if ($nextQuantity > $availableStock) {
            return back()->withErrors([
                'quantity' => 'Số lượng vượt quá tồn kho hiện tại của phân loại đã chọn.',
            ])->withInput();
        }

        $cart[$key] = [
            'product_id' => $product->id,
            'variant_id' => $variant?->id,
            'quantity' => $nextQuantity,
        ];
        Session::put('storefront_cart', $cart);

        return redirect()->route('shop.cart')->with('success', 'Đã thêm sản phẩm vào giỏ hàng.');
    }

    /**
     * Trang giỏ hàng.
     */
    public function cart(CheckoutService $checkout): View|RedirectResponse
    {
        $cart = $this->normalizeCart(Session::get('storefront_cart', []));
        Session::put('storefront_cart', $cart);

        if ($cart === []) {
            return redirect()
                ->route('shop.products.index')
                ->with('info', 'Giỏ hàng đang trống.');
        }

        return view('storefront.cart', [
            'quote' => $checkout->quote($cart),
        ]);
    }

    /**
     * Cập nhật số lượng trong giỏ hàng.
     */
    public function updateCart(Request $request, string $item): RedirectResponse
    {
        $data = $request->validate([
            'quantity' => ['required', 'integer', 'min:1', 'max:100'],
        ]);

        $cart = $this->normalizeCart(Session::get('storefront_cart', []));
        if (! isset($cart[$item])) {
            abort(404);
        }

        $product = Product::query()->findOrFail($cart[$item]['product_id']);
        $variant = ! empty($cart[$item]['variant_id'])
            ? $product->variants()->where('status', 'active')->find($cart[$item]['variant_id'])
            : null;
        if (! empty($cart[$item]['variant_id']) && ! $variant) {
            return back()->withErrors(['quantity' => 'Phân loại này hiện không còn kinh doanh.']);
        }
        $quantity = (int) $data['quantity'];
        $availableStock = $variant ? (int) $variant->stock : (int) $product->so_luong_ton;
        if ($quantity > $availableStock) {
            return back()->withErrors([
                'quantity' => 'Số lượng vượt quá tồn kho hiện tại của phân loại đã chọn.',
            ])->withInput();
        }

        $cart[$item]['quantity'] = $quantity;
        Session::put('storefront_cart', $cart);

        return redirect()->route('shop.cart')->with('success', 'Đã cập nhật giỏ hàng.');
    }

    /**
     * Xóa sản phẩm khỏi giỏ hàng.
     */
    public function removeFromCart(string $item): RedirectResponse
    {
        $cart = $this->normalizeCart(Session::get('storefront_cart', []));

        if (! isset($cart[$item])) {
            abort(404);
        }

        unset($cart[$item]);

        Session::put('storefront_cart', $cart);

        return redirect()
            ->route('shop.cart')
            ->with('success', 'Đã xóa sản phẩm khỏi giỏ hàng.');
    }

    /**
     * Trang thanh toán.
     */
    public function checkout(
        Request $request,
        CheckoutService $checkout
    ): View|RedirectResponse {
        $cart = $this->normalizeCart(Session::get('storefront_cart', []));

        if ($cart === []) {
            return redirect()
                ->route('shop.products.index')
                ->with('info', 'Giỏ hàng đang trống.');
        }

        // Chỉ lấy voucher tại bước thanh toán
        $voucherCode = $request->query(
            'voucher_code',
            Session::get('checkout_voucher_code')
        );

        $voucherCode = is_string($voucherCode)
            ? trim($voucherCode)
            : null;

        // Tính tổng tiền và giảm giá voucher tại checkout
        $quote = $checkout->quote($cart, $voucherCode);

        // Lưu mã voucher hợp lệ trong session checkout
        if ($quote['voucher_code'] !== null) {
            Session::put(
                'checkout_voucher_code',
                $quote['voucher_code']
            );

            $voucherCode = $quote['voucher_code'];
        }

        $paymentMethods = DB::table('phuong_thuc_thanh_toans')
            ->where('trang_thai', true)
            ->orderBy('id')
            ->get();

        return view('storefront.checkout', [
            'quote' => $quote,
            'customer' => [],
            'paymentMethods' => $paymentMethods,
            'voucherCode' => $voucherCode,
            'bankTransfer' => config('aovu_payment.bank_transfer'),
        ]);
    }

    /**
     * Áp dụng voucher tại bước thanh toán.
     */
    public function applyVoucher(
        Request $request,
        CheckoutService $checkout
    ): JsonResponse|RedirectResponse {
        $data = $request->validate([
            'voucher_code' => ['required', 'string', 'max:50'],
        ], [
            'voucher_code.required' => 'Vui lòng nhập mã giảm giá.',
            'voucher_code.max' => 'Mã giảm giá không được vượt quá 50 ký tự.',
        ]);

        $cart = $this->normalizeCart(Session::get('storefront_cart', []));

        if ($cart === []) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Giỏ hàng đang trống.',
                ], 422);
            }

            return redirect()
                ->route('shop.products.index')
                ->with('info', 'Giỏ hàng đang trống.');
        }

        $voucherCode = Str::upper(trim($data['voucher_code']));

        // quote() kiểm tra trạng thái, thời hạn, lượt dùng, đơn tối thiểu
        // và tính lại tổng tiền trước khi lưu mã vào session.
        $quote = $checkout->quote($cart, $voucherCode);

        if (empty($quote['voucher_code'])) {
            throw ValidationException::withMessages([
                'voucher_code' => 'Không thể áp dụng mã giảm giá này.',
            ]);
        }

        Session::put('checkout_voucher_code', $quote['voucher_code']);

        // AJAX: chỉ trả dữ liệu giảm giá để checkout cập nhật tại chỗ,
        // không redirect và không làm mất thông tin người nhận đang nhập.
        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Áp dụng mã giảm giá thành công.',
                'voucher_code' => $quote['voucher_code'],
                'gross_total' => (int) $quote['gross_total'],
                'promotion_discount' => (int) $quote['promotion_discount'],
                'voucher_discount' => (int) $quote['voucher_discount'],
                'total' => (int) $quote['total'],
            ]);
        }

        return redirect()
            ->route('shop.checkout')
            ->with('success', 'Áp dụng mã giảm giá thành công.');
    }

    /**
     * Gỡ voucher đang áp dụng tại bước thanh toán.
     */
    public function removeVoucher(
        Request $request,
        CheckoutService $checkout
    ): JsonResponse|RedirectResponse {
        Session::forget('checkout_voucher_code');

        $cart = $this->normalizeCart(Session::get('storefront_cart', []));

        if ($cart === []) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Giỏ hàng đang trống.',
                ], 422);
            }

            return redirect()
                ->route('shop.products.index')
                ->with('info', 'Giỏ hàng đang trống.');
        }

        // Tính lại tổng tiền không có voucher, vẫn giữ các khuyến mãi sản phẩm.
        $quote = $checkout->quote($cart, null);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Đã gỡ mã giảm giá.',
                'voucher_code' => null,
                'gross_total' => (int) $quote['gross_total'],
                'promotion_discount' => (int) $quote['promotion_discount'],
                'voucher_discount' => (int) $quote['voucher_discount'],
                'total' => (int) $quote['total'],
            ]);
        }

        return redirect()
            ->route('shop.checkout')
            ->with('success', 'Đã gỡ mã giảm giá.');
    }

    /**
     * Xử lý đặt hàng.
     */
    public function placeOrder(
        Request $request,
        CheckoutService $checkout
    ): RedirectResponse {
        $customer = $request->validate([
            'ten_nguoi_nhan' => [
                'required',
                'string',
                'max:100',
            ],
            'email_nguoi_nhan' => [
                'required',
                'email',
                'max:255',
            ],
            'sdt_nguoi_nhan' => [
                'required',
                'string',
                'max:15',
            ],
            'dia_chi_nguoi_nhan' => [
                'required',
                'string',
                'max:255',
            ],
            'ghi_chu' => [
                'nullable',
                'string',
                'max:2000',
            ],
            'voucher_code' => [
                'nullable',
                'string',
                'max:50',
            ],
            'phuong_thuc_thanh_toan_id' => [
                'required',
                'integer',
                'exists:phuong_thuc_thanh_toans,id',
            ],
        ]);

        $paymentMethod = DB::table('phuong_thuc_thanh_toans')
            ->where(
                'id',
                $customer['phuong_thuc_thanh_toan_id']
            )
            ->where('trang_thai', true)
            ->first();

        if (! $paymentMethod) {
            throw ValidationException::withMessages([
                'phuong_thuc_thanh_toan_id' =>
                'Phương thức thanh toán không khả dụng.',
            ]);
        }

        $cart = $this->normalizeCart(Session::get('storefront_cart', []));

        if ($cart === []) {
            return redirect()
                ->route('shop.products.index')
                ->with('info', 'Giỏ hàng đang trống.');
        }

        $confirmation = $checkout->placeOrder(
            $customer,
            $cart,
            $customer['voucher_code'] ?? null,
            (int) $customer['phuong_thuc_thanh_toan_id']
        );

        // Nếu khách chọn chuyển khoản, tạo QR VietQR theo đúng mã đơn và số tiền.
        $paymentMethodName = Str::lower((string) ($paymentMethod->ten_phuong_thuc ?? ''));
        $isBankTransfer = Str::contains($paymentMethodName, [
            'chuyển khoản',
            'ngân hàng',
            'bank transfer',
        ]);

        if ($isBankTransfer) {
            $bank = config('aovu_payment.bank_transfer');
            $transferContent = 'AOVU ' . $confirmation['order_code'];

            $qrUrl = 'https://img.vietqr.io/image/'
                . rawurlencode((string) $bank['bank_bin'])
                . '-'
                . rawurlencode((string) $bank['account_number'])
                . '-'
                . rawurlencode((string) ($bank['qr_template'] ?? 'compact2'))
                . '.svg?'
                . http_build_query([
                    'amount' => (int) $confirmation['total'],
                    'addInfo' => $transferContent,
                    'accountName' => $bank['account_name'],
                ], '', '&', PHP_QUERY_RFC3986);

            $confirmation['bank_transfer'] = [
                'bank_name' => $bank['bank_name'],
                'bank_bin' => $bank['bank_bin'],
                'account_number' => $bank['account_number'],
                'account_name' => $bank['account_name'],
                'transfer_content' => $transferContent,
                'amount' => (int) $confirmation['total'],
                'qr_url' => $qrUrl,
            ];

            // Đơn chỉ được đánh dấu đã thanh toán sau khi cửa hàng đối soát giao dịch.
            $confirmation['payment_status_label'] = 'Chưa thanh toán';
        }

        Session::forget([
            'storefront_cart',
            'checkout_voucher_code',
        ]);

        Session::put(
            'last_order_confirmation',
            $confirmation
        );

        return redirect()
            ->route('shop.checkout.success')
            ->with('success', 'Đơn hàng của bạn đã được ghi nhận.');
    }

    /**
     * Trang xác nhận đơn hàng.
     */
    public function orderConfirmation(): View|RedirectResponse
    {
        $confirmation = Session::pull(
            'last_order_confirmation'
        );

        if (! is_array($confirmation)) {
            return redirect()->route('shop.products.index');
        }

        if (! empty($confirmation['bank_transfer'])) {
            return view('storefront.checkout-bank-transfer', [
                'confirmation' => $confirmation,
            ]);
        }

        return view('storefront.checkout-success', [
            'confirmation' => $confirmation,
        ]);
    }

    /** Normalize and merge cart rows while keeping each selected variant separate. */
    private function normalizeCart(array $cart): array
    {
        $normalized = [];
        foreach ($cart as $item) {
            $productId = filter_var($item['product_id'] ?? null, FILTER_VALIDATE_INT);
            $quantity = filter_var($item['quantity'] ?? null, FILTER_VALIDATE_INT);
            $variantId = filter_var($item['variant_id'] ?? null, FILTER_VALIDATE_INT);
            if ($productId === false || $quantity === false || $productId < 1 || $quantity < 1) {
                continue;
            }
            $variantId = ($variantId !== false && $variantId > 0) ? (int) $variantId : null;
            $key = $variantId ? 'variant-'.$variantId : 'product-'.$productId;
            $normalized[$key] = [
                'product_id' => (int) $productId,
                'variant_id' => $variantId,
                'quantity' => (int) (($normalized[$key]['quantity'] ?? 0) + $quantity),
            ];
        }
        return $normalized;
    }

}
