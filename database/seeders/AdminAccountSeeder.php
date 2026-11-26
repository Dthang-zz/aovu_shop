<?php

namespace Database\Seeders;

use App\Models\ChucVu;
use App\Models\TaiKhoan;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AdminAccountSeeder extends Seeder
{
    public function run(): void
    {
        $email = Str::lower(trim((string) env('ADMIN_EMAIL')));
        $password = (string) env('ADMIN_PASSWORD');
        if ($email === '' || $password === '') {
            $this->command?->warn('AdminAccountSeeder skipped: set ADMIN_EMAIL and ADMIN_PASSWORD in .env first.');
            return;
        }
        $role = ChucVu::firstOrCreate(['ten_chuc_vu' => 'Admin'], ['mo_ta' => 'Quản trị hệ thống']);
        $account = TaiKhoan::firstOrNew(['email' => $email]);
        if ($account->exists && $account->chuc_vu_id !== $role->id) {
            $this->command?->warn('An account already exists with this email; role was not changed automatically.');
            return;
        }
        $account->fill([
            'ho_ten' => env('ADMIN_NAME', 'Quản trị viên AOVU'),
            'mat_khau' => $password,
            'chuc_vu_id' => $role->id,
            'trang_thai' => true,
            'ngay_tao' => $account->ngay_tao ?: now(),
        ])->save();
        $this->command?->info('Admin account configured: '.$email);
    }
}
