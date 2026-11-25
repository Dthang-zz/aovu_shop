<?php

namespace Tests\Feature;

use App\Models\ChucVu;
use App\Models\TaiKhoan;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AuthFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('chuc_vus', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('ten_chuc_vu');
            $table->text('mo_ta')->nullable();
        });

        Schema::create('tai_khoans', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('ho_ten', 50)->nullable();
            $table->string('email')->unique();
            $table->string('mat_khau')->nullable();
            $table->unsignedInteger('chuc_vu_id')->nullable();
            $table->unsignedTinyInteger('trang_thai')->nullable();
            $table->dateTime('ngay_tao')->nullable();
        });
    }

    public function test_guest_can_view_login_and_registration_forms(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Chào mừng trở lại')
            ->assertSee(route('register'));

        $this->get(route('register'))
            ->assertOk()
            ->assertSee('Tạo tài khoản')
            ->assertSee(route('login'));
    }

    public function test_registration_always_creates_a_customer_account(): void
    {
        $adminRole = ChucVu::query()->create(['ten_chuc_vu' => 'Quản trị viên']);

        $response = $this->post(route('register'), [
            'ho_ten' => 'Nguyễn An',
            'email' => 'an@example.test',
            'password' => 'secure-password',
            'password_confirmation' => 'secure-password',
            'chuc_vu_id' => $adminRole->getKey(),
        ]);

        $response->assertRedirect(route('storefront.index'));

        $account = TaiKhoan::query()->where('email', 'an@example.test')->firstOrFail();
        $this->assertSame('Khách hàng', $account->chucVu->ten_chuc_vu);
        $this->assertFalse($account->isAdmin());
        $this->assertTrue(Hash::check('secure-password', $account->mat_khau));
        $this->assertAuthenticatedAs($account);
        $this->assertDatabaseHas('tai_khoans', [
            'email' => 'an@example.test',
            'trang_thai' => 1,
        ]);
    }

    public function test_admin_role_can_be_granted_to_an_existing_account_from_artisan(): void
    {
        $account = $this->createAccount('Khách hàng', 'promote@example.test');

        $this->artisan('account:grant-admin', ['email' => 'promote@example.test'])
            ->assertSuccessful();

        $account->refresh();

        $this->assertTrue($account->isAdmin());
    }

    public function test_admin_login_redirects_to_the_protected_admin_area(): void
    {
        $admin = $this->createAccount('Quản trị viên', 'admin@example.test');

        $this->post(route('login'), [
            'email' => 'admin@example.test',
            'password' => 'secure-password',
        ])->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($admin);
        $this->get(route('admin.resource', ['section' => 'users']))->assertOk();
    }

    public function test_customer_cannot_open_admin_pages(): void
    {
        $customer = $this->createAccount('Khách hàng', 'customer@example.test');
        $this->actingAs($customer);

        $this->get(route('admin.dashboard'))->assertForbidden();
    }

    public function test_guest_is_redirected_to_login_from_admin_pages(): void
    {
        $this->get(route('admin.dashboard'))
            ->assertRedirect(route('login'));
    }

    public function test_inactive_account_cannot_log_in(): void
    {
        $this->createAccount('Khách hàng', 'inactive@example.test', 0);

        $this->post(route('login'), [
            'email' => 'inactive@example.test',
            'password' => 'secure-password',
        ])
            ->assertRedirect()
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_customer_login_redirects_to_the_storefront(): void
    {
        $customer = $this->createAccount('Khách hàng', 'customer@example.test');

        $this->post(route('login'), [
            'email' => 'customer@example.test',
            'password' => 'secure-password',
        ])->assertRedirect(route('storefront.index'));

        $this->assertAuthenticatedAs($customer);
    }

    public function test_logout_ends_the_authenticated_session(): void
    {
        $this->actingAs($this->createAccount('Khách hàng', 'customer@example.test'));

        $this->post(route('logout'))
            ->assertRedirect(route('storefront.index'));

        $this->assertGuest();
    }

    public function test_registration_rejects_mismatched_password_confirmation(): void
    {
        $this->post(route('register'), [
            'ho_ten' => 'Nguyễn An',
            'email' => 'an@example.test',
            'password' => 'secure-password',
            'password_confirmation' => 'different-password',
        ])
            ->assertRedirect()
            ->assertSessionHasErrors('password');

        $this->assertDatabaseMissing('tai_khoans', ['email' => 'an@example.test']);
    }

    private function createAccount(string $roleName, string $email, int $status = 1): TaiKhoan
    {
        $role = ChucVu::query()->firstOrCreate(['ten_chuc_vu' => $roleName]);

        $account = new TaiKhoan([
            'ho_ten' => 'Tài khoản kiểm thử',
            'email' => $email,
            'mat_khau' => 'secure-password',
        ]);
        $account->chuc_vu_id = $role->getKey();
        $account->trang_thai = $status;
        $account->save();

        return $account;
    }
}
