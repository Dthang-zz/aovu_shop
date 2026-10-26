<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CheckoutService
{
    public const VOUCHER_PERCENTAGE = 1;

    public function __construct(private PromotionPricingService $promotionPricing) {}

    /**
     * @param  array<string, array{product_id: mixed, variant_id: mixed, quantity: mixed}>  $cart
     * @return array{lines: array<int, array<string, mixed>>, gross_total: int, promotion_discount: int, subtotal: int, voucher_discount: int, total_discount: int, total: int, voucher: object|null, voucher_code: string|null}
     */
    public function quote(array $cart, ?string $voucherCode = null, bool $lockRows = false): array
    {
        if ($cart === []) {
            throw ValidationException::withMessages(['cart' => 'Giỏ hàng đang trống.']);
        }

        $cartItems = [];
        foreach ($cart as $item) {
            $productId = filter_var($item['product_id'] ?? null, FILTER_VALIDATE_INT);
            $quantity = filter_var($item['quantity'] ?? null, FILTER_VALIDATE_INT);
            $variantId = ($item['variant_id'] ?? null) === null
                ? null
                : filter_var($item['variant_id'], FILTER_VALIDATE_INT);

            if ($productId === false || $quantity === false || $quantity < 1 || $quantity > 100
                || ($item['variant_id'] ?? null) !== null && $variantId === false) {
                throw ValidationException::withMessages(['cart' => 'Thông tin sản phẩm trong giỏ hàng không hợp lệ.']);
            }

            $cartItems[] = [
                'product_id' => $productId,
                'variant_id' => $variantId,
                'quantity' => $quantity,
            ];
        }

        $productIds = array_values(array_unique(array_column($cartItems, 'product_id')));
        $products = Product::query()
            ->where('trang_thai', true)
            ->whereIn('id', $productIds)
            ->get()
            ->keyBy('id');

        if ($products->count() !== count($productIds)) {
            throw ValidationException::withMessages(['cart' => 'Một sản phẩm trong giỏ hàng không còn kinh doanh.']);
        }

        $variants = [];
        $variantPrices = [];
        $variantQuantities = [];
        foreach ($cartItems as $item) {
            if ($item['variant_id'] !== null) {
                $variantQuantities[$item['variant_id']] = ($variantQuantities[$item['variant_id']] ?? 0) + $item['quantity'];
            }
        }

        foreach ($cartItems as $item) {
            $product = $products->get($item['product_id']);
            if ($item['variant_id'] === null) {
                if ($product->variants()->exists()) {
                    throw ValidationException::withMessages(['cart' => 'Vui lòng chọn đầy đủ phân loại sản phẩm.']);
                }

                $variantPrices[] = (int) $product->gia_san_pham;

                continue;
            }

            $variantQuery = ProductVariant::query()
                ->whereKey($item['variant_id'])
                ->where('product_id', $product->id)
                ->where('status', ProductVariant::STATUS_ACTIVE);
            if ($lockRows) {
                $variantQuery->lockForUpdate();
            }
            $variant = $variantQuery->first();

            if ($variant === null || $variant->stock < $variantQuantities[$item['variant_id']]) {
                throw ValidationException::withMessages([
                    'cart' => 'Phân loại sản phẩm không còn đủ số lượng trong kho.',
                ]);
            }

            $variants[$item['variant_id']] = $variant;
            $variantPrices[] = (int) round((float) ($variant->price ?? $product->gia_san_pham));
        }

        $promotions = $this->promotionPricing->promotionsFor($products->values());
        $lines = [];
        $grossTotal = 0;
        $promotionDiscount = 0;
        foreach ($cartItems as $index => $item) {
            $product = $products->get($item['product_id']);
            $variant = $item['variant_id'] === null ? null : $variants[$item['variant_id']];
            $price = $this->promotionPricing->price(
                $variantPrices[$index],
                $promotions[$product->id] ?? null,
            );
            $lineGross = $price['original_price'] * $item['quantity'];
            $lineDiscount = $price['discount_amount'] * $item['quantity'];
            $lineSubtotal = $price['sale_price'] * $item['quantity'];
            $promotion = $price['promotion'];

            $grossTotal += $lineGross;
            $promotionDiscount += $lineDiscount;
            $lines[] = [
                'product' => $product,
                'variant' => $variant,
                'quantity' => $item['quantity'],
                'original_price' => $price['original_price'],
                'unit_price' => $price['sale_price'],
                'line_gross' => $lineGross,
                'promotion_discount' => $lineDiscount,
                'line_total' => $lineSubtotal,
                'promotion' => $promotion,
                'variant_label' => $variant
                    ? trim(implode(' / ', array_filter([$variant->color, $variant->size])))
                    : null,
            ];
        }

        if ($grossTotal > 999_999_999_999) {
            throw ValidationException::withMessages([
                'cart' => 'Tổng giá trị giỏ hàng vượt quá giới hạn cho phép.',
            ]);
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
            'voucher_code' => $voucher ? $voucher->ma_voucher : null,
        ];
    }

    /**
     * @param  array<string, mixed>  $customer
     * @param  array<string, array{product_id: mixed, variant_id: mixed, quantity: mixed}>  $cart
     * @return array{order_code: string, total: int}
     */
    public function placeOrder(array $customer, array $cart, ?string $voucherCode): array
    {
        return DB::transaction(function () use ($customer, $cart, $voucherCode): array {
            $quote = $this->quote($cart, $voucherCode, true);
            $orderCode = 'AO'.now()->format('ymd').Str::upper(Str::random(8));
            $orderData = [
                'ma_don_hang' => $orderCode,
                'tai_khoan_id' => null,
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
                'trang_thai_thanh_toan' => Order::PAYMENT_UNPAID,
                'trang_thai' => Order::STATUS_PENDING,
            ];

            if (Schema::hasColumn('don_hangs', 'tien_giam_khuyen_mai')) {
                $orderData['tien_giam_khuyen_mai'] = $quote['promotion_discount'];
            }
            if (Schema::hasColumn('don_hangs', 'tien_giam_voucher')) {
                $orderData['tien_giam_voucher'] = $quote['voucher_discount'];
            }

            $orderId = DB::table('don_hangs')->insertGetId($orderData);

            foreach ($quote['lines'] as $line) {
                $variant = $line['variant'];
                if ($variant !== null) {
                    ProductVariant::query()
                        ->whereKey($variant->id)
                        ->decrement('stock', $line['quantity']);
                }

                $variantDescription = $line['variant_label']
                    ? ' ('.$line['variant_label'].')'
                    : '';
                $itemData = [
                    'don_hang_id' => $orderId,
                    'bien_the_id' => null,
                    'ten_san_pham' => $line['product']->ten_san_pham.$variantDescription,
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
                    ARRAY_FILTER_USE_KEY,
                );
                DB::table('chi_tiet_don_hangs')->insert($availableItemData);
            }

            if ($quote['voucher'] !== null) {
                DB::table('vouchers')
                    ->where('id', $quote['voucher']->id)
                    ->update(['da_su_dung' => DB::raw('COALESCE(da_su_dung, 0) + 1')]);
            }

            return ['order_code' => $orderCode, 'total' => $quote['total']];
        });
    }

    private function findValidVoucher(?string $voucherCode, int $subtotal, bool $lockRows): ?object
    {
        $voucherCode = trim((string) $voucherCode);
        if ($voucherCode === '') {
            return null;
        }

        if (! Schema::hasTable('vouchers')) {
            throw ValidationException::withMessages(['voucher_code' => 'Mã voucher không hợp lệ.']);
        }

        $voucherQuery = DB::table('vouchers')->where('ma_voucher', Str::upper($voucherCode));
        if ($lockRows) {
            $voucherQuery->lockForUpdate();
        }
        $voucher = $voucherQuery->first();

        if ($voucher === null || ! (bool) $voucher->trang_thai
            || $voucher->ngay_bat_dau && now()->lt($voucher->ngay_bat_dau)
            || $voucher->ngay_ket_thuc && now()->gt($voucher->ngay_ket_thuc)
            || $voucher->so_luong !== null && (int) $voucher->da_su_dung >= (int) $voucher->so_luong) {
            throw ValidationException::withMessages(['voucher_code' => 'Voucher không hoạt động, đã hết hạn hoặc đã hết lượt sử dụng.']);
        }

        if ($voucher->don_toi_thieu !== null && $subtotal < (int) $voucher->don_toi_thieu) {
            throw ValidationException::withMessages([
                'voucher_code' => 'Giá trị đơn hàng chưa đạt mức tối thiểu để sử dụng voucher.',
            ]);
        }

        return $voucher;
    }

    private function voucherDiscount(?object $voucher, int $subtotal): int
    {
        if ($voucher === null) {
            return 0;
        }

        $discount = (int) $voucher->loai_giam === self::VOUCHER_PERCENTAGE
            ? (int) round($subtotal * (float) $voucher->gia_tri_giam / 100)
            : (int) $voucher->gia_tri_giam;

        if ($voucher->giam_toi_da !== null && (int) $voucher->giam_toi_da > 0) {
            $discount = min($discount, (int) $voucher->giam_toi_da);
        }

        return min($subtotal, max(0, $discount));
    }
}
