<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add fixed-amount promotions and the cap / minimum product price for percentage promotions.
     */
    public function up(): void
    {
        Schema::table('promotions', function (Blueprint $table): void {
            $table->string('loai_giam', 20)->default('percent')->after('ten_chuong_trinh');
            $table->decimal('so_tien_giam', 12, 0)->nullable()->after('phan_tram_giam');
            $table->decimal('giam_toi_da', 12, 0)->nullable()->after('so_tien_giam');
            $table->decimal('gia_toi_thieu', 12, 0)->nullable()->after('giam_toi_da');
            $table->decimal('phan_tram_giam', 5, 2)->nullable()->change();
        });
    }

    /**
     * Fixed-amount promotions have no percentage to fall back to, so they are removed on rollback.
     */
    public function down(): void
    {
        DB::table('promotions')->where('loai_giam', '!=', 'percent')->delete();

        Schema::table('promotions', function (Blueprint $table): void {
            $table->decimal('phan_tram_giam', 5, 2)->nullable(false)->change();
            $table->dropColumn(['loai_giam', 'so_tien_giam', 'giam_toi_da', 'gia_toi_thieu']);
        });
    }
};
