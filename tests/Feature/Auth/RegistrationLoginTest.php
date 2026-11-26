<?php

namespace Tests\Feature\Auth;

use App\Models\ChucVu;
use App\Models\TaiKhoan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_creates_customer_and_authenticates(): void
    {
        $response = $this->post(route('register.store'), [
            'ho_ten' => 'Nguyen Van Test',
            'email' => 'customer@example.test',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'terms' => '1',
        ]);

        $response->assertRedirect(route('shop.products.index'));
        $this->assertDatabaseHas('tai_khoans', ['email' => 'customer@example.test']);
        $this->assertAuthenticated();
        $account = TaiKhoan::where('email', 'customer@example.test')->firstOrFail();
        $this->assertSame('Khách hàng', $account->chucVu->ten_chuc_vu);
    }


    public function test_customer_cannot_access_admin_routes(): void
    {
        $role = ChucVu::firstOrCreate(['ten_chuc_vu' => 'Khách hàng']);
        $account = TaiKhoan::create([
            'ho_ten' => 'Customer Test', 'email' => 'customer2@example.test',
            'mat_khau' => 'Password123!', 'chuc_vu_id' => $role->id,
            'trang_thai' => true, 'ngay_tao' => now(),
        ]);
        $this->actingAs($account)->get(route('admin.users.index'))->assertForbidden();
    }


    public function test_admin_can_access_admin_routes(): void
    {
        $role = ChucVu::firstOrCreate(['ten_chuc_vu' => 'Admin']);
        $account = TaiKhoan::create([
            'ho_ten' => 'Admin Test', 'email' => 'admin@example.test',
            'mat_khau' => 'Password123!', 'chuc_vu_id' => $role->id,
            'trang_thai' => true, 'ngay_tao' => now(),
        ]);
        $this->actingAs($account)->get(route('admin.users.index'))->assertOk();
    }
}
