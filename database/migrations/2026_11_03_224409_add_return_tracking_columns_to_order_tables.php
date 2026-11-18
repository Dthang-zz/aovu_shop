<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('don_hangs')) {
            Schema::table('don_hangs', function (Blueprint $table) {
                if (! Schema::hasColumn('don_hangs', 'ngay_hoan_thanh')) {
                    $table->dateTime('ngay_hoan_thanh')->nullable();
                }
            });
        }

        if (Schema::hasTable('chi_tiet_don_hangs')) {
            Schema::table('chi_tiet_don_hangs', function (Blueprint $table) {
                if (! Schema::hasColumn('chi_tiet_don_hangs', 'product_variant_id')) {
                    $table->unsignedBigInteger('product_variant_id')->nullable()->index();
                }
            });
        }

        Schema::table('support_return_workflows', function (Blueprint $table) {
            if (! Schema::hasColumn('support_return_workflows', 'restocked_at')) {
                $table->dateTime('restocked_at')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('support_return_workflows', function (Blueprint $table) {
            $table->dropColumn('restocked_at');
        });

        // Keep the order snapshot columns after rollback to preserve historical transaction data.
    }
};
