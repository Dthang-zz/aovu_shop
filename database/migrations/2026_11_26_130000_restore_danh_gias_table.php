<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('danh_gias')) {
            Schema::create('danh_gias', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('san_pham_id')->nullable()->index();
                $table->unsignedInteger('tai_khoan_id')->nullable()->index();
                $table->unsignedInteger('chi_tiet_don_hang_id')->nullable()->index();
                $table->unsignedTinyInteger('so_sao')->nullable();
                $table->text('noi_dung')->nullable();
                $table->dateTime('ngay_dang')->nullable()->index();
                $table->unsignedTinyInteger('trang_thai')->nullable()->index();
                $table->text('hinh_anh')->nullable();
                $table->text('phan_hoi')->nullable();
            });
            return;
        }
        $missing = [];
        foreach ([
            'san_pham_id' => 'unsignedInteger',
            'tai_khoan_id' => 'unsignedInteger',
            'chi_tiet_don_hang_id' => 'unsignedInteger',
            'so_sao' => 'unsignedTinyInteger',
            'noi_dung' => 'text',
            'ngay_dang' => 'dateTime',
            'trang_thai' => 'unsignedTinyInteger',
            'hinh_anh' => 'text',
            'phan_hoi' => 'text',
        ] as $column => $type) {
            if (! Schema::hasColumn('danh_gias', $column)) $missing[$column] = $type;
        }
        if ($missing) Schema::table('danh_gias', function (Blueprint $table) use ($missing) {
            foreach ($missing as $column => $type) $table->{$type}($column)->nullable();
        });
    }
    public function down(): void
    {
        // Deliberately non-destructive: this table may contain customer review data.
    }
};
