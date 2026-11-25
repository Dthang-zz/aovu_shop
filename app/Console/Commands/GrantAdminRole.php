<?php

namespace App\Console\Commands;

use App\Models\ChucVu;
use App\Models\TaiKhoan;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

#[Signature('account:grant-admin {email : Email của tài khoản cần cấp quyền quản trị}')]
#[Description('Cấp quyền quản trị cho một tài khoản hiện có')]
class GrantAdminRole extends Command
{
    public function handle(): int
    {
        $account = TaiKhoan::query()
            ->where('email', $this->argument('email'))
            ->first();

        if ($account === null) {
            $this->components->error('Không tìm thấy tài khoản với email đã nhập.');

            return self::FAILURE;
        }

        DB::transaction(function () use ($account): void {
            $adminRole = ChucVu::query()->firstOrCreate(
                ['ten_chuc_vu' => 'Quản trị viên'],
                ['mo_ta' => 'Tài khoản quản trị hệ thống.'],
            );

            $account->chuc_vu_id = $adminRole->getKey();
            $account->save();
        });

        $this->components->info("Đã cấp quyền quản trị cho {$account->email}.");

        return self::SUCCESS;
    }
}
