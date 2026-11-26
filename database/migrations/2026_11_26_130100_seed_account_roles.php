<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('chuc_vus')) return;
        foreach ([
            ['ten_chuc_vu' => 'Admin', 'mo_ta' => 'Quản trị hệ thống'],
            ['ten_chuc_vu' => 'Người dùng', 'mo_ta' => 'Tài khoản người dùng'],
            ['ten_chuc_vu' => 'Khách hàng', 'mo_ta' => 'Tài khoản khách hàng mua sắm'],
        ] as $role) {
            DB::table('chuc_vus')->updateOrInsert(['ten_chuc_vu' => $role['ten_chuc_vu']], ['mo_ta' => $role['mo_ta']]);
        }
    }
    public function down(): void { /* Keep role records to avoid orphaning account permissions. */ }
};
