<?php

namespace Tests\Feature;

use App\Models\ChucVu;
use App\Models\TaiKhoan;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('chuc_vus', function (Blueprint $table) {
            $table->increments('id');
            $table->string('ten_chuc_vu')->nullable();
            $table->text('mo_ta')->nullable();
        });
        Schema::create('tai_khoans', function (Blueprint $table) {
            $table->increments('id');
            $table->string('ho_ten', 50)->nullable();
            $table->string('anh_dai_dien')->nullable();
            $table->date('ngay_sinh')->nullable();
            $table->string('email')->unique();
            $table->string('so_dien_thoai', 15)->nullable();
            $table->unsignedTinyInteger('gioi_tinh')->nullable();
            $table->string('mat_khau')->nullable();
            $table->unsignedInteger('chuc_vu_id')->nullable();
            $table->boolean('trang_thai')->nullable();
            $table->dateTime('ngay_tao')->nullable();
        });
        Schema::create('don_hangs', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('tai_khoan_id')->nullable();
            $table->decimal('tong_tien', 12, 0)->nullable();
        });
        foreach (['danh_gias', 'dia_chis', 'gio_hangs', 'yeu_thichs'] as $tableName) {
            Schema::create($tableName, function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('tai_khoan_id')->nullable();
            });
        }
        Schema::create('lich_su_trang_thai_don_hangs', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('nguoi_cap_nhat_id')->nullable();
        });
    }

    public function test_user_list_filters_by_search_role_status_and_gender(): void
    {
        $role = ChucVu::create(['ten_chuc_vu' => 'Khách hàng']);
        $this->createUser([
            'ho_ten' => 'Nguyễn Minh Anh',
            'email' => 'minhanh@example.test',
            'chuc_vu_id' => $role->id,
            'gioi_tinh' => 0,
        ]);
        $this->createUser([
            'ho_ten' => 'Trần Hoàng Nam',
            'email' => 'nam@example.test',
            'trang_thai' => false,
            'gioi_tinh' => 1,
        ]);

        $this->get(route('admin.users.index', [
            'q' => 'Minh',
            'chuc_vu_id' => $role->id,
            'status' => 'active',
            'gioi_tinh' => '0',
        ]))
            ->assertOk()
            ->assertSee('Nguyễn Minh Anh')
            ->assertSee('Khách hàng')
            ->assertDontSee('Trần Hoàng Nam');
    }

    public function test_user_can_be_created_and_updated_without_exposing_password(): void
    {
        Storage::fake('public');
        $role = ChucVu::create(['ten_chuc_vu' => 'Khách hàng']);

        $this->post(route('admin.users.store'), [
            'ho_ten' => 'Lê Thu Hà',
            'email' => 'ha@example.test',
            'so_dien_thoai' => '0912345678',
            'ngay_sinh' => '1995-04-10',
            'gioi_tinh' => '0',
            'anh_dai_dien' => UploadedFile::fake()->image('avatar.jpg'),
            'mat_khau' => 'secret123',
            'chuc_vu_id' => $role->id,
            'trang_thai' => '1',
        ])->assertRedirect(route('admin.users.index'));

        $user = TaiKhoan::where('email', 'ha@example.test')->firstOrFail();
        $avatarPath = $user->anh_dai_dien;
        $this->assertModelExists($user);
        $this->assertSame(true, Hash::check('secret123', $user->mat_khau));
        Storage::disk('public')->assertExists($avatarPath);
        $this->assertDatabaseHas('tai_khoans', [
            'id' => $user->id,
            'anh_dai_dien' => $avatarPath,
            'chuc_vu_id' => $role->id,
            'trang_thai' => 1,
        ]);

        $this->put(route('admin.users.update', $user), [
            'ho_ten' => 'Lê Thu Hà mới',
            'email' => 'ha@example.test',
            'mat_khau' => '',
            'chuc_vu_id' => $role->id,
            'trang_thai' => '0',
        ])->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseHas('tai_khoans', [
            'id' => $user->id,
            'ho_ten' => 'Lê Thu Hà mới',
            'trang_thai' => 0,
        ]);
        $this->assertSame(true, Hash::check('secret123', $user->fresh()->mat_khau));
        $this->assertSame($avatarPath, $user->fresh()->anh_dai_dien);
        Storage::disk('public')->assertExists($avatarPath);
        $this->get(route('admin.users.show', $user))
            ->assertOk()
            ->assertDontSee('secret123')
            ->assertDontSee($user->mat_khau);
    }

    public function test_user_creation_time_uses_vietnam_timezone_when_saved_and_displayed(): void
    {
        $this->travelTo(Carbon::parse('2026-10-21 18:00:00', 'Asia/Ho_Chi_Minh'));

        $this->post(route('admin.users.store'), [
            'ho_ten' => 'Người dùng mới',
            'email' => 'new-user@example.test',
            'mat_khau' => 'secret123',
            'trang_thai' => '1',
        ])->assertRedirect(route('admin.users.index'));

        $user = TaiKhoan::where('email', 'new-user@example.test')->firstOrFail();

        $this->assertDatabaseHas('tai_khoans', [
            'id' => $user->id,
            'ngay_tao' => '2026-10-21 18:00:00',
        ]);
        $this->get(route('admin.users.show', $user))
            ->assertOk()
            ->assertSee('21/10/2026 18:00');
    }

    public function test_user_form_rejects_duplicate_email_unknown_role_and_non_image_avatar(): void
    {
        $this->createUser(['email' => 'existing@example.test']);

        $this->from(route('admin.users.create'))
            ->post(route('admin.users.store'), [
                'ho_ten' => 'Người dùng mới',
                'email' => 'existing@example.test',
                'mat_khau' => 'secret123',
                'chuc_vu_id' => 999,
                'anh_dai_dien' => UploadedFile::fake()->create('avatar.txt', 10, 'text/plain'),
                'trang_thai' => '1',
            ])
            ->assertRedirect(route('admin.users.create'))
            ->assertSessionHasErrors(['email', 'chuc_vu_id', 'anh_dai_dien']);

        $this->assertDatabaseCount('tai_khoans', 1);
    }

    public function test_uploading_new_avatar_replaces_and_removes_the_previous_file(): void
    {
        Storage::fake('public');
        $oldAvatarPath = UploadedFile::fake()->image('old.jpg')->store('avatars', 'public');
        $user = $this->createUser(['anh_dai_dien' => $oldAvatarPath]);

        $this->put(route('admin.users.update', $user), [
            'ho_ten' => $user->ho_ten,
            'email' => $user->email,
            'anh_dai_dien' => UploadedFile::fake()->image('new.png'),
            'mat_khau' => '',
            'trang_thai' => '1',
        ])->assertRedirect(route('admin.users.index'));

        $newAvatarPath = $user->fresh()->anh_dai_dien;
        $this->assertNotSame($oldAvatarPath, $newAvatarPath);
        Storage::disk('public')->assertMissing($oldAvatarPath);
        Storage::disk('public')->assertExists($newAvatarPath);
    }

    public function test_user_list_escapes_names_and_rejects_invalid_filters(): void
    {
        $dangerousName = '<script>alert("x")</script>';
        $this->createUser(['ho_ten' => $dangerousName]);

        $this->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee($dangerousName)
            ->assertDontSee($dangerousName, false);

        $this->from(route('admin.users.index'))
            ->get(route('admin.users.index', ['gioi_tinh' => '9']))
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHasErrors('gioi_tinh');
    }

    public function test_user_detail_shows_role_and_order_statistics(): void
    {
        $role = ChucVu::create(['ten_chuc_vu' => 'Quản trị viên']);
        $user = $this->createUser(['chuc_vu_id' => $role->id]);
        DB::table('don_hangs')->insert([
            ['tai_khoan_id' => $user->id, 'tong_tien' => 200000],
            ['tai_khoan_id' => $user->id, 'tong_tien' => 300000],
        ]);

        $this->get(route('admin.users.show', $user))
            ->assertOk()
            ->assertSee('Quản trị viên')
            ->assertSee('Tổng đơn hàng')
            ->assertSee('2')
            ->assertSee('500.000');
    }

    public function test_user_status_can_be_locked_and_reactivated(): void
    {
        $user = $this->createUser();

        $this->patch(route('admin.users.toggle-status', $user))
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHas('success', 'Đã khóa tài khoản.');
        $this->assertDatabaseHas('tai_khoans', ['id' => $user->id, 'trang_thai' => 0]);

        $this->patch(route('admin.users.toggle-status', $user))
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHas('success', 'Đã mở khóa tài khoản.');
        $this->assertDatabaseHas('tai_khoans', ['id' => $user->id, 'trang_thai' => 1]);
    }

    public function test_user_with_related_records_cannot_be_deleted(): void
    {
        $user = $this->createUser();
        DB::table('yeu_thichs')->insert(['tai_khoan_id' => $user->id]);

        $this->delete(route('admin.users.destroy', $user))
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHas('error');

        $this->assertModelExists($user);
    }

    public function test_user_without_related_records_can_be_deleted(): void
    {
        $user = $this->createUser();

        $this->delete(route('admin.users.destroy', $user))
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHas('success', 'Đã xóa người dùng.');

        $this->assertModelMissing($user);
    }

    private function createUser(array $attributes = []): TaiKhoan
    {
        return TaiKhoan::create(array_merge([
            'ho_ten' => 'Khách hàng thử nghiệm',
            'email' => 'user-'.uniqid().'@example.test',
            'mat_khau' => 'secret123',
            'trang_thai' => true,
            'ngay_tao' => now(),
        ], $attributes));
    }
}
