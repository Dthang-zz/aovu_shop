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
        if (! Schema::hasTable('danh_gias')) {
            return;
        }

        $columnsToAdd = [];

        foreach (['hinh_anh', 'phan_hoi'] as $column) {
            if (! Schema::hasColumn('danh_gias', $column)) {
                $columnsToAdd[] = $column;
            }
        }

        if ($columnsToAdd !== []) {
            Schema::table('danh_gias', function (Blueprint $table) use ($columnsToAdd): void {
                foreach ($columnsToAdd as $column) {
                    $table->text($column)->nullable();
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('danh_gias')) {
            return;
        }

        $columnsToDrop = [];

        foreach (['hinh_anh', 'phan_hoi'] as $column) {
            if (Schema::hasColumn('danh_gias', $column)) {
                $columnsToDrop[] = $column;
            }
        }

        if ($columnsToDrop !== []) {
            Schema::table('danh_gias', function (Blueprint $table) use ($columnsToDrop): void {
                $table->dropColumn($columnsToDrop);
            });
        }
    }
};
