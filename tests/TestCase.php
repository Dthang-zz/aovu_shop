<?php

namespace Tests;

use App\Models\ChucVu;
use App\Models\TaiKhoan;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

abstract class TestCase extends BaseTestCase
{
    protected function signInAsAdmin(): TaiKhoan
    {
        if (! Schema::hasTable('chuc_vus')) {
            Schema::create('chuc_vus', function (Blueprint $table): void {
                $table->increments('id');
                $table->string('ten_chuc_vu')->nullable();
                $table->text('mo_ta')->nullable();
            });
        }

        if (! Schema::hasTable('tai_khoans')) {
            Schema::create('tai_khoans', function (Blueprint $table): void {
                $table->increments('id');
                $table->string('ho_ten', 50)->nullable();
                $table->string('email')->unique();
                $table->string('mat_khau')->nullable();
                $table->unsignedInteger('chuc_vu_id')->nullable();
                $table->unsignedTinyInteger('trang_thai')->nullable();
                $table->dateTime('ngay_tao')->nullable();
            });
        } else {
            $columns = [
                'ho_ten' => fn (Blueprint $table) => $table->string('ho_ten', 50)->nullable(),
                'email' => fn (Blueprint $table) => $table->string('email')->nullable(),
                'mat_khau' => fn (Blueprint $table) => $table->string('mat_khau')->nullable(),
                'chuc_vu_id' => fn (Blueprint $table) => $table->unsignedInteger('chuc_vu_id')->nullable(),
                'trang_thai' => fn (Blueprint $table) => $table->unsignedTinyInteger('trang_thai')->nullable(),
                'ngay_tao' => fn (Blueprint $table) => $table->dateTime('ngay_tao')->nullable(),
            ];

            foreach ($columns as $column => $definition) {
                if (! Schema::hasColumn('tai_khoans', $column)) {
                    Schema::table('tai_khoans', $definition);
                }
            }
        }

        $role = ChucVu::query()->create([
            'ten_chuc_vu' => 'Quản trị viên',
            'mo_ta' => 'Vai trò quản trị dành cho bài kiểm thử.',
        ]);
        $account = new TaiKhoan([
            'ho_ten' => 'Quản trị viên kiểm thử',
            'email' => Str::uuid().'@example.test',
            'mat_khau' => 'password',
        ]);
        $account->chuc_vu_id = $role->getKey();
        $account->trang_thai = 1;
        $account->ngay_tao = '2000-01-01 00:00:00';
        $account->save();

        $this->actingAs($account);

        return $account;
    }
}
