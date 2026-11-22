<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('san_phams') && ! Schema::hasColumn('san_phams', 'so_luong_ton')) {
            Schema::table('san_phams', function (Blueprint $table): void {
                $table->unsignedInteger('so_luong_ton')->default(0)->after('gia_khuyen_mai');
            });
        }

        if (Schema::hasTable('chi_tiet_don_hangs') && ! Schema::hasColumn('chi_tiet_don_hangs', 'san_pham_id')) {
            Schema::table('chi_tiet_don_hangs', function (Blueprint $table): void {
                $table->unsignedInteger('san_pham_id')->nullable()->index()->after('don_hang_id');
            });
        }

        // Migrate historical line references to product IDs without deleting old columns or records.
        if (Schema::hasTable('chi_tiet_don_hangs') && Schema::hasTable('product_variants')
            && Schema::hasColumn('chi_tiet_don_hangs', 'product_variant_id')
            && Schema::hasColumn('product_variants', 'product_id')) {
            DB::table('chi_tiet_don_hangs as items')
                ->join('product_variants as variants', 'variants.id', '=', 'items.product_variant_id')
                ->whereNull('items.san_pham_id')
                ->update(['items.san_pham_id' => DB::raw('variants.product_id')]);
        }

        if (Schema::hasTable('chi_tiet_don_hangs') && Schema::hasTable('bien_thes')
            && Schema::hasColumn('chi_tiet_don_hangs', 'bien_the_id')
            && Schema::hasColumn('bien_thes', 'san_pham_id')) {
            DB::table('chi_tiet_don_hangs as items')
                ->join('bien_thes as variants', 'variants.id', '=', 'items.bien_the_id')
                ->whereNull('items.san_pham_id')
                ->update(['items.san_pham_id' => DB::raw('variants.san_pham_id')]);
        }

        // Carry stock to each product. Prefer new ProductVariant rows, but fall back to the
        // legacy bien_thes rows for products that do not have newer stock rows.
        $hasProductVariantStock = Schema::hasTable('product_variants')
            && Schema::hasColumn('product_variants', 'product_id')
            && Schema::hasColumn('product_variants', 'stock');
        if (Schema::hasTable('san_phams') && $hasProductVariantStock) {
            DB::statement("UPDATE san_phams AS products SET so_luong_ton = (SELECT COALESCE(SUM(variants.stock), 0) FROM product_variants AS variants WHERE variants.product_id = products.id) WHERE EXISTS (SELECT 1 FROM product_variants AS variants WHERE variants.product_id = products.id)");
        }

        $hasLegacyStock = Schema::hasTable('bien_thes')
            && Schema::hasColumn('bien_thes', 'san_pham_id')
            && Schema::hasColumn('bien_thes', 'so_luong_ton');
        if (Schema::hasTable('san_phams') && $hasLegacyStock) {
            $legacyStockSql = "UPDATE san_phams AS products SET so_luong_ton = (SELECT COALESCE(SUM(variants.so_luong_ton), 0) FROM bien_thes AS variants WHERE variants.san_pham_id = products.id) WHERE EXISTS (SELECT 1 FROM bien_thes AS variants WHERE variants.san_pham_id = products.id)";
            if ($hasProductVariantStock) {
                $legacyStockSql .= " AND NOT EXISTS (SELECT 1 FROM product_variants AS variants WHERE variants.product_id = products.id)";
            }
            DB::statement($legacyStockSql);
        }

        if (Schema::hasTable('support_return_workflows') && ! Schema::hasColumn('support_return_workflows', 'replacement_product_id')) {
            Schema::table('support_return_workflows', function (Blueprint $table): void {
                $table->unsignedInteger('replacement_product_id')->nullable()->index();
            });
        }

        if (Schema::hasTable('support_return_workflows') && Schema::hasTable('product_variants')
            && Schema::hasColumn('support_return_workflows', 'replacement_variant_id')
            && Schema::hasColumn('product_variants', 'product_id')) {
            DB::table('support_return_workflows as workflows')
                ->join('product_variants as variants', 'variants.id', '=', 'workflows.replacement_variant_id')
                ->whereNull('workflows.replacement_product_id')
                ->update(['workflows.replacement_product_id' => DB::raw('variants.product_id')]);
        }
    }

    public function down(): void
    {
        // Intentionally preserve product stock and order-to-product references.
        // Dropping these columns would destroy live inventory and historical order links.
    }
};
