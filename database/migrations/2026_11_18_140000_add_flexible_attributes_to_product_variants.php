<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('product_variants')) {
            return;
        }

        if (! Schema::hasColumn('product_variants', 'attribute_values')) {
            Schema::table('product_variants', function (Blueprint $table): void {
                $table->json('attribute_values')->nullable();
            });
        }

        // Keep legacy color/size rows usable while exposing them through the new flexible attribute map.
        if (Schema::hasColumn('product_variants', 'color') && Schema::hasColumn('product_variants', 'size')) {
            foreach (\Illuminate\Support\Facades\DB::table('product_variants')->whereNull('attribute_values')->get(['id', 'color', 'size']) as $variant) {
                $attributes = [];
                if (filled($variant->color)) {
                    $attributes['Màu sắc'] = $variant->color;
                }
                if (filled($variant->size)) {
                    $attributes['Kích thước'] = $variant->size;
                }
                if ($attributes !== []) {
                    \Illuminate\Support\Facades\DB::table('product_variants')->where('id', $variant->id)->update(['attribute_values' => json_encode($attributes, JSON_UNESCAPED_UNICODE)]);
                }
            }
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('product_variants')) {
            return;
        }
        if (Schema::hasColumn('product_variants', 'attribute_values')) {
            Schema::table('product_variants', function (Blueprint $table): void {
                $table->dropColumn('attribute_values');
            });
        }
    }
};
