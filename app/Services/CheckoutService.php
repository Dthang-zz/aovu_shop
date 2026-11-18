<?php

namespace App\Services;

use App\Models\ChucVu;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\TaiKhoan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CheckoutService
{
    public const VOUCHER_PERCENTAGE = 1;

    public function __construct(
        private PromotionPricingService $promotionPricing
    ) {}

    /**
     * Tính giá giỏ hàng, khuyến mãi và voucher.
     */
    public function quote(array $cart, ?string $voucherCode = null, bool $lockRows = false): array
    {
        if ($cart === []) {
            throw ValidationException::withMessages(['cart' => 'Giỏ hàng đang trống.']);
        }

        $cartLines = [];
        $productIds = [];
        foreach ($cart as $item) {
            $productId = filter_var($item['product_id'] ?? null, FILTER_VALIDATE_INT);
            $quantity = filter_var($item['quantity'] ?? null, FILTER_VALIDATE_INT);
            $variantId = filter_var($item['variant_id'] ?? null, FILTER_VALIDATE_INT);
            if ($productId === false || $productId < 1 || $quantity === false || $quantity < 1) {
                throw ValidationException::withMessages(['cart' => 'Thông tin sản phẩm trong giỏ hàng không hợp lệ.']);
            }
            $variantId = ($variantId !== false && $variantId > 0) ? (int) $variantId : null;
            $key = $variantId ? 'variant-'.$variantId : 'product-'.$productId;
            $cartLines[$key] = [
                'product_id' => (int) $productId,
                'variant_id' => $variantId,
                'quantity' => (int) (($cartLines[$key]['quantity'] ?? 0) + $quantity),
            ];
            if ($cartLines[$key]['quantity'] > 100) {
                throw ValidationException::withMessages(['cart' => 'Mỗi phân loại chỉ được mua tối đa 100 sản phẩm trong một đơn hàng.']);
            }
            $productIds[] = (int) $productId;
        }

        $productQuery = Product::query()->where('trang_thai', true)->whereIn('id', array_unique($productIds));
        if ($lockRows) $productQuery->lockForUpdate();
        $products = $productQuery->get()->keyBy('id');
        if ($products->count() !== count(array_unique($productIds))) {
            throw ValidationException::withMessages(['cart' => 'Một sản phẩm trong giỏ hàng không còn kinh doanh.']);
        }

        $variantIds = collect($cartLines)->pluck('variant_id')->filter()->unique()->values();
        $variants = collect();
        if ($variantIds->isNotEmpty()) {
            $variantQuery = ProductVariant::query()
                ->whereIn('id', $variantIds)
                ->where('status', 'active');
            if ($lockRows) $variantQuery->lockForUpdate();
            $variants = $variantQuery->get()->keyBy('id');
            if ($variants->count() !== $variantIds->count()) {
                throw ValidationException::withMessages(['cart' => 'Một phân loại sản phẩm không còn khả dụng. Vui lòng chọn lại.']);
            }
        }

        $promotions = $this->promotionPricing->promotionsFor($products->values());
        $lines = [];
        $grossTotal = 0;
        $promotionDiscount = 0;
        foreach ($cartLines as $key => $cartLine) {
            $product = $products->get($cartLine['product_id']);
            $variant = $cartLine['variant_id'] ? $variants->get($cartLine['variant_id']) : null;
            if ($variant && (int) $variant->product_id !== (int) $product->id) {
                throw ValidationException::withMessages(['cart' => 'Phân loại sản phẩm không khớp với sản phẩm trong giỏ hàng.']);
            }
            if ($variant === null && $cartLine['variant_id'] !== null) {
                throw ValidationException::withMessages(['cart' => 'Phân loại không tồn tại.']);
            }
            if (! $variant && $cartLine['variant_id'] === null
                && $product->variants()->where('status', 'active')->exists()) {
                throw ValidationException::withMessages(['cart' => 'Vui lòng chọn thuộc tính cho sản phẩm "'.$product->ten_san_pham.'" rồi thêm lại vào giỏ hàng.']);
            }
            $quantity = $cartLine['quantity'];
            $availableStock = $variant ? (int) $variant->stock : (int) $product->so_luong_ton;
            if ($availableStock < $quantity) {
                throw ValidationException::withMessages([
                    'cart' => 'Sản phẩm "'.$product->ten_san_pham.'" không còn đủ số lượng cho phân loại đã chọn.',
                ]);
            }

            $basePrice = ($variant && $variant->price !== null)
                ? (int) $variant->price
                : (int) $product->gia_san_pham;
            $price = $this->promotionPricing->price($basePrice, $promotions[$product->id] ?? null);
            $lineGross = $price['original_price'] * $quantity;
            $lineDiscount = $price['discount_amount'] * $quantity;
            $lineSubtotal = $price['sale_price'] * $quantity;
            $grossTotal += $lineGross;
            $promotionDiscount += $lineDiscount;
            $attributes = $variant?->attribute_values ?? [];
            if ($variant && $attributes === []) {
                if ($variant->color) $attributes['Màu sắc'] = $variant->color;
                if ($variant->size) $attributes['Kích thước'] = $variant->size;
                if ($attributes === []) $attributes['Phân loại'] = $variant->sku;
            }
            $variantLabel = collect($attributes)->map(fn ($value, $name) => $name.': '.$value)->implode(' / ');

            $lines[] = [
                'cart_key' => $key,
                'product' => $product,
                'variant' => $variant,
                'variant_id' => $variant?->id,
                'variant_label' => $variantLabel,
                'quantity' => $quantity,
                'original_price' => $price['original_price'],
                'unit_price' => $price['sale_price'],
                'line_gross' => $lineGross,
                'promotion_discount' => $lineDiscount,
                'line_total' => $lineSubtotal,
                'promotion' => $price['promotion'],
            ];
        }

        if ($grossTotal > 999_999_999_999) {
            throw ValidationException::withMessages(['cart' => 'Tổng giá trị giỏ hàng vượt quá giới hạn cho phép.']);
        }

        $subtotal = $grossTotal - $promotionDiscount;
        $voucher = $this->findValidVoucher($voucherCode, $subtotal, $lockRows);
        $voucherDiscount = $this->voucherDiscount($voucher, $subtotal);

        return [
            'lines' => $lines,
            'gross_total' => $grossTotal,
            'promotion_discount' => $promotionDiscount,
            'subtotal' => $subtotal,
            'voucher_discount' => $voucherDiscount,
            'total_discount' => $promotionDiscount + $voucherDiscount,
            'total' => max(0, $subtotal - $voucherDiscount),
            'voucher' => $voucher,
            'voucher_code' => $voucher?->ma_voucher,
        ];
    }

    /**
     * Tạo đơn hàng và lưu phương thức thanh toán.
     */
    public function placeOrder(
        array $customer,
        array $cart,
        ?string $voucherCode,
        int $paymentMethodId
    ): array {
        return DB::transaction(function () use (
            $customer,
            $cart,
            $voucherCode,
            $paymentMethodId
        ): array {

            // Kiểm tra phương thức thanh toán còn hoạt động.
            $paymentMethod = DB::table('phuong_thuc_thanh_toans')
                ->where('id', $paymentMethodId)
                ->where('trang_thai', true)
                ->lockForUpdate()
                ->first();

            if ($paymentMethod === null) {
                throw ValidationException::withMessages([
                    'phuong_thuc_thanh_toan_id' =>
                    'Phương thức thanh toán không khả dụng.',
                ]);
            }

            // Tính lại giá và kiểm tra tồn kho trước khi đặt hàng.
            $quote = $this->quote(
                $cart,
                $voucherCode,
                true
            );

            $orderCode = 'AO'
                . now()->format('ymd')
                . Str::upper(Str::random(8));

            $orderData = [
                'ma_don_hang' => $orderCode,

                'tai_khoan_id' => $this->resolveCustomerAccountId(
                    $customer
                ),

                'ten_nguoi_nhan' => $customer['ten_nguoi_nhan'],
                'email_nguoi_nhan' => $customer['email_nguoi_nhan'],
                'sdt_nguoi_nhan' => $customer['sdt_nguoi_nhan'],
                'dia_chi_nguoi_nhan' => $customer['dia_chi_nguoi_nhan'],

                'ghi_chu' => $customer['ghi_chu'] ?? null,

                'ngay_dat' => now(),

                'tien_hang' => $quote['gross_total'],
                'phi_van_chuyen' => 0,

                'voucher_id' => $quote['voucher']?->id,

                'tien_giam' => $quote['total_discount'],
                'tong_tien' => $quote['total'],

                // Phương thức thanh toán khách đã chọn.
                'phuong_thuc_thanh_toan_id' => $paymentMethodId,

                // Chưa xác nhận đã thanh toán.
                'trang_thai_thanh_toan' => Order::PAYMENT_UNPAID,

                'trang_thai' => Order::STATUS_PENDING,
            ];

            // Lưu thêm các cột khuyến mãi nếu database có.
            if (
                Schema::hasColumn(
                    'don_hangs',
                    'tien_giam_khuyen_mai'
                )
            ) {
                $orderData['tien_giam_khuyen_mai'] =
                    $quote['promotion_discount'];
            }

            if (
                Schema::hasColumn(
                    'don_hangs',
                    'tien_giam_voucher'
                )
            ) {
                $orderData['tien_giam_voucher'] =
                    $quote['voucher_discount'];
            }

            // Tạo đơn hàng.
            $orderId = DB::table('don_hangs')->insertGetId(
                $orderData
            );

            // Save order lines and decrement the selected variant (or product stock when no variants exist).
            foreach ($quote['lines'] as $line) {
                $productId = (int) $line['product']->id;
                if ($line['variant']) {
                    $updated = DB::table('product_variants')
                        ->where('id', $line['variant']->id)
                        ->where('product_id', $productId)
                        ->where('status', 'active')
                        ->where('stock', '>=', $line['quantity'])
                        ->decrement('stock', $line['quantity']);
                    if ($updated === 0) {
                        throw ValidationException::withMessages(['cart' => 'Biến thể vừa hết hàng. Vui lòng kiểm tra lại giỏ hàng.']);
                    }
                    $productStockUpdated = Product::query()
                        ->whereKey($productId)
                        ->where('so_luong_ton', '>=', $line['quantity'])
                        ->decrement('so_luong_ton', $line['quantity']);
                    if ($productStockUpdated === 0) {
                        throw ValidationException::withMessages(['cart' => 'Tồn kho tổng của sản phẩm không còn đủ số lượng. Vui lòng kiểm tra lại.']);
                    }
                } else {
                    $updated = Product::query()
                        ->whereKey($productId)
                        ->where('so_luong_ton', '>=', $line['quantity'])
                        ->decrement('so_luong_ton', $line['quantity']);
                    if ($updated === 0) {
                        throw ValidationException::withMessages(['cart' => 'Sản phẩm vừa hết hàng hoặc không còn đủ số lượng. Vui lòng kiểm tra lại giỏ hàng.']);
                    }
                }

                $productLineName = $line['product']->ten_san_pham
                    . ($line['variant_label'] !== '' ? ' ('.$line['variant_label'].')' : '');
                $itemData = [
                    'don_hang_id' => $orderId,
                    'san_pham_id' => $productId,
                    'bien_the_id' => null,
                    'product_variant_id' => $line['variant_id'],
                    'ten_san_pham' => $productLineName,
                    'don_gia' => $line['unit_price'],
                    'so_luong' => $line['quantity'],
                    'thanh_tien' => $line['line_total'],
                    'gia_goc' => $line['original_price'],
                    'tien_giam_khuyen_mai' => $line['promotion_discount'],
                    'khuyen_mai_id' => $line['promotion']?->id,
                    'ten_khuyen_mai' => $line['promotion']?->ten_chuong_trinh,
                    'phan_tram_giam' => $line['promotion']?->phan_tram_giam,
                ];

                $availableItemData = array_filter(
                    $itemData,
                    fn (string $column): bool => Schema::hasColumn('chi_tiet_don_hangs', $column),
                    ARRAY_FILTER_USE_KEY
                );
                DB::table('chi_tiet_don_hangs')->insert($availableItemData);
            }

            // Tăng lượt sử dụng voucher.
            if ($quote['voucher'] !== null) {
                DB::table('vouchers')
                    ->where('id', $quote['voucher']->id)
                    ->update([
                        'da_su_dung' => DB::raw(
                            'COALESCE(da_su_dung, 0) + 1'
                        ),
                    ]);
            }

            return [
                'order_code' => $orderCode,
                'total' => $quote['total'],
                'payment_method' => $paymentMethod->ten_phuong_thuc,
            ];
        });
    }

    /**
     * Liên kết đơn hàng với tài khoản khách hàng.
     */
    private function resolveCustomerAccountId(
        array $customer
    ): ?int {
        if (! Schema::hasTable('tai_khoans')) {
            return null;
        }

        $email = Str::lower(
            trim((string) $customer['email_nguoi_nhan'])
        );

        $accountId = TaiKhoan::query()
            ->whereRaw('LOWER(email) = ?', [$email])
            ->value('id');

        if ($accountId !== null) {
            return (int) $accountId;
        }

        $customerRoleId = Schema::hasTable('chuc_vus')
            ? ChucVu::query()
            ->where('ten_chuc_vu', 'Khách hàng')
            ->value('id')
            : null;

        return (int) TaiKhoan::query()->create([
            'ho_ten' => mb_substr(
                trim((string) $customer['ten_nguoi_nhan']),
                0,
                50
            ),

            'email' => $email,

            'so_dien_thoai' => $customer['sdt_nguoi_nhan'],

            'chuc_vu_id' => $customerRoleId,

            'trang_thai' => true,

            'ngay_tao' => now(),
        ])->getKey();
    }

    /**
     * Kiểm tra voucher hợp lệ.
     */
    private function findValidVoucher(
        ?string $voucherCode,
        int $subtotal,
        bool $lockRows
    ): ?object {
        $voucherCode = trim((string) $voucherCode);

        if ($voucherCode === '') {
            return null;
        }

        if (! Schema::hasTable('vouchers')) {
            throw ValidationException::withMessages([
                'voucher_code' => 'Mã voucher không hợp lệ.',
            ]);
        }

        $voucherQuery = DB::table('vouchers')
            ->where(
                'ma_voucher',
                Str::upper($voucherCode)
            );

        if ($lockRows) {
            $voucherQuery->lockForUpdate();
        }

        $voucher = $voucherQuery->first();

        if (
            $voucher === null ||
            ! (bool) $voucher->trang_thai ||
            (
                $voucher->ngay_bat_dau &&
                now()->lt($voucher->ngay_bat_dau)
            ) ||
            (
                $voucher->ngay_ket_thuc &&
                now()->gt($voucher->ngay_ket_thuc)
            ) ||
            (
                $voucher->so_luong !== null &&
                (int) $voucher->da_su_dung >= (int) $voucher->so_luong
            )
        ) {
            throw ValidationException::withMessages([
                'voucher_code' =>
                'Voucher không hoạt động, đã hết hạn hoặc đã hết lượt sử dụng.',
            ]);
        }

        if (
            $voucher->don_toi_thieu !== null &&
            $subtotal < (int) $voucher->don_toi_thieu
        ) {
            throw ValidationException::withMessages([
                'voucher_code' =>
                'Giá trị đơn hàng chưa đạt mức tối thiểu để sử dụng voucher.',
            ]);
        }

        return $voucher;
    }

    /**
     * Tính số tiền giảm từ voucher.
     */
    private function voucherDiscount(
        ?object $voucher,
        int $subtotal
    ): int {
        if ($voucher === null) {
            return 0;
        }

        $discount = (int) $voucher->loai_giam
            === self::VOUCHER_PERCENTAGE

            ? (int) round(
                $subtotal * (float) $voucher->gia_tri_giam / 100
            )

            : (int) $voucher->gia_tri_giam;

        if (
            $voucher->giam_toi_da !== null &&
            (int) $voucher->giam_toi_da > 0
        ) {
            $discount = min(
                $discount,
                (int) $voucher->giam_toi_da
            );
        }

        return min(
            $subtotal,
            max(0, $discount)
        );
    }
}
