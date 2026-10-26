<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PromotionPricingService
{
    /**
     * @param  Collection<int, Product>  $products
     * @param  array<int, int>  $basePrices
     * @return array<int, array{original_price: int, discount_amount: int, sale_price: int, discount_percent: float, promotion: object|null}>
     */
    public function quotes(Collection $products, array $basePrices = []): array
    {
        if ($products->isEmpty()) {
            return [];
        }

        $promotions = $this->promotionsFor($products);
        $quotes = [];
        foreach ($products as $product) {
            $productId = (int) $product->getKey();
            $quotes[$productId] = $this->price(
                max(0, (int) ($basePrices[$productId] ?? $product->gia_san_pham ?? 0)),
                $promotions[$productId] ?? null,
            );
        }

        return $quotes;
    }

    /**
     * @param  Collection<int, Product>  $products
     * @return array<int, object>
     */
    public function promotionsFor(Collection $products): array
    {
        if ($products->isEmpty()) {
            return [];
        }

        $productIds = $products->map(fn (Product $product): int => (int) $product->getKey())->all();

        return DB::table('promotions')
            ->join('promotion_products', 'promotion_products.promotion_id', '=', 'promotions.id')
            ->whereIn('promotion_products.product_id', $productIds)
            ->where('promotions.trang_thai', true)
            ->where('promotions.ngay_bat_dau', '<=', now())
            ->where('promotions.ngay_ket_thuc', '>=', now())
            ->orderBy('promotion_products.product_id')
            ->orderByDesc('promotions.phan_tram_giam')
            ->orderBy('promotions.id')
            ->get([
                'promotion_products.product_id',
                'promotions.id',
                'promotions.ten_chuong_trinh',
                'promotions.phan_tram_giam',
            ])
            ->unique('product_id')
            ->keyBy('product_id')
            ->all();
    }

    /**
     * @return array{original_price: int, discount_amount: int, sale_price: int, discount_percent: float, promotion: object|null}
     */
    public function price(int $originalPrice, ?object $promotion): array
    {
        $originalPrice = max(0, $originalPrice);
        $discountPercent = $promotion ? (float) $promotion->phan_tram_giam : 0.0;
        $discountAmount = $promotion
            ? (int) round($originalPrice * $discountPercent / 100)
            : 0;

        return [
            'original_price' => $originalPrice,
            'discount_amount' => min($originalPrice, $discountAmount),
            'sale_price' => max(0, $originalPrice - $discountAmount),
            'discount_percent' => $discountPercent,
            'promotion' => $promotion,
        ];
    }
}
