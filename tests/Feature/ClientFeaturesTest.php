<?php

namespace Tests\Feature;

use App\Models\ChucVu;
use App\Models\DiaChi;
use App\Models\Order;
use App\Models\Product;
use App\Models\SupportConversation;
use App\Models\SupportMessage;
use App\Models\TaiKhoan;
use App\Models\YeuThich;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ClientFeaturesTest extends TestCase
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
            $table->boolean('trang_thai')->nullable()->default(true);
            $table->dateTime('ngay_tao')->nullable();
        });

        Schema::create('danh_mucs', function (Blueprint $table) {
            $table->increments('id');
            $table->string('ten_danh_muc');
            $table->unsignedInteger('danh_muc_cha_id')->nullable();
            $table->text('mo_ta')->nullable();
            $table->boolean('trang_thai')->default(true);
        });

        Schema::create('san_phams', function (Blueprint $table) {
            $table->increments('id');
            $table->string('ma_san_pham')->nullable();
            $table->string('ten_san_pham');
            $table->unsignedInteger('gia_san_pham')->default(0);
            $table->unsignedInteger('gia_khuyen_mai')->nullable();
            $table->string('hinh_anh')->nullable();
            $table->text('mo_ta')->nullable();
            $table->string('chat_lieu')->nullable();
            $table->text('huong_dan_su_dung')->nullable();
            $table->boolean('noi_bat')->default(false);
            $table->dateTime('ngay_nhap')->nullable();
            $table->unsignedInteger('danh_muc_id')->nullable();
            $table->boolean('trang_thai')->default(true);
        });

        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('product_id');
            $table->string('sku')->unique();
            $table->string('color')->nullable();
            $table->string('size')->nullable();
            $table->unsignedInteger('price')->nullable();
            $table->unsignedInteger('stock')->default(0);
            $table->string('status')->default('active');
            $table->timestamps();
        });

        Schema::create('dia_chis', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('tai_khoan_id')->nullable();
            $table->string('ten_nguoi_nhan', 100)->nullable();
            $table->string('sdt_nguoi_nhan', 15)->nullable();
            $table->string('tinh_thanh', 100)->nullable();
            $table->string('quan_huyen', 100)->nullable();
            $table->string('phuong_xa', 100)->nullable();
            $table->string('dia_chi_chi_tiet', 255)->nullable();
            $table->boolean('mac_dinh')->default(false);
        });

        Schema::create('yeu_thichs', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('tai_khoan_id')->nullable();
            $table->unsignedInteger('san_pham_id')->nullable();
            $table->dateTime('ngay_them')->nullable();
        });

        Schema::create('don_hangs', function (Blueprint $table) {
            $table->increments('id');
            $table->string('ma_don_hang')->nullable();
            $table->unsignedInteger('tai_khoan_id')->nullable();
            $table->string('ten_nguoi_nhan')->nullable();
            $table->string('email_nguoi_nhan')->nullable();
            $table->string('sdt_nguoi_nhan')->nullable();
            $table->string('dia_chi_nguoi_nhan')->nullable();
            $table->dateTime('ngay_dat')->nullable();
            $table->decimal('tien_hang', 12, 0)->nullable();
            $table->decimal('phi_van_chuyen', 12, 0)->nullable();
            $table->unsignedInteger('voucher_id')->nullable();
            $table->decimal('tien_giam', 12, 0)->nullable();
            $table->decimal('tong_tien', 12, 0)->nullable();
            $table->text('ghi_chu')->nullable();
            $table->unsignedTinyInteger('trang_thai_thanh_toan')->nullable();
            $table->unsignedTinyInteger('trang_thai')->nullable();
        });

        Schema::create('chi_tiet_don_hangs', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('don_hang_id')->nullable();
            $table->unsignedInteger('bien_the_id')->nullable();
            $table->unsignedBigInteger('product_variant_id')->nullable();
            $table->string('ten_san_pham')->nullable();
            $table->decimal('don_gia', 12, 0)->nullable();
            $table->unsignedInteger('so_luong')->nullable();
            $table->decimal('thanh_tien', 12, 0)->nullable();
        });

        Schema::create('promotions', function (Blueprint $table) {
            $table->id();
            $table->string('ten_chuong_trinh');
            $table->unsignedInteger('phan_tram_giam');
            $table->dateTime('ngay_bat_dau');
            $table->dateTime('ngay_ket_thuc');
            $table->boolean('trang_thai')->default(true);
            $table->timestamps();
        });

        Schema::create('promotion_products', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('promotion_id');
            $table->unsignedInteger('product_id');
            $table->timestamps();
        });

        Schema::create('banners', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('image_path');
            $table->string('link_url')->nullable();
            $table->unsignedInteger('display_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('support_conversations', function (Blueprint $table) {
            $table->id();
            $table->string('visitor_token_hash')->index();
            $table->string('customer_name')->nullable();
            $table->string('customer_email')->nullable();
            $table->string('status')->default('open');
            $table->timestamps();
        });

        Schema::create('support_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id');
            $table->string('sender_type');
            $table->string('sender_name')->nullable();
            $table->text('body');
            $table->timestamps();
        });

        ChucVu::create(['ten_chuc_vu' => 'Khách hàng']);
    }

    public function test_cskh_page_renders_successfully(): void
    {
        $response = $this->get(route('cskh.index'));
        $response->assertOk();
        $response->assertSee('Trung tâm Chăm sóc & Hỗ trợ Khách hàng');
        $response->assertSee('Tra cứu tình trạng đơn hàng');
        $response->assertSee('Câu hỏi thường gặp');
    }

    public function test_cskh_order_tracking_finds_order(): void
    {
        $orderId = DB::table('don_hangs')->insertGetId([
            'ma_don_hang' => 'DH-TEST999',
            'ten_nguoi_nhan' => 'Nguyễn Khách Hàng',
            'sdt_nguoi_nhan' => '0912345678',
            'dia_chi_nguoi_nhan' => '123 Cầu Giấy, Hà Nội',
            'ngay_dat' => now(),
            'tong_tien' => 350000,
            'trang_thai' => 1,
            'trang_thai_thanh_toan' => 0,
        ]);

        DB::table('chi_tiet_don_hangs')->insert([
            'don_hang_id' => $orderId,
            'ten_san_pham' => 'Áo Thun Basic Cotton',
            'so_luong' => 1,
            'don_gia' => 350000,
            'thanh_tien' => 350000,
        ]);

        $response = $this->get(route('cskh.index', ['ma_don_hang' => 'DH-TEST999']));
        $response->assertOk();
        $response->assertSee('Đơn hàng #DH-TEST999');
        $response->assertSee('Nguyễn Khách Hàng');
        $response->assertSee('Áo Thun Basic Cotton');
    }

    public function test_cskh_inquiry_creates_support_conversation(): void
    {
        $response = $this->post(route('cskh.inquiry.store'), [
            'ho_ten' => 'Lê Minh Anh',
            'email' => 'minhanh@example.test',
            'so_dien_thoai' => '0987654321',
            'chu_de' => 'Tư vấn chọn size & sản phẩm',
            'noi_dung' => 'Em cao 1m68 nặng 60kg thì nên mặc áo phông size nào ạ?',
        ]);

        $response->assertRedirect(route('cskh.index'));
        $response->assertSessionHas('success');
        $response->assertSessionHas('open_support_chat', true);

        $this->assertDatabaseHas('support_conversations', [
            'customer_name' => 'Lê Minh Anh',
            'customer_email' => 'minhanh@example.test',
        ]);
        $this->assertDatabaseHas('support_messages', [
            'sender_type' => SupportMessage::SENDER_CUSTOMER,
        ]);
    }

    public function test_customer_can_register_and_login(): void
    {
        $registerResponse = $this->post(route('customer.register.store'), [
            'ho_ten' => 'Trần Văn Nam',
            'email' => 'namtran@example.test',
            'so_dien_thoai' => '0901112233',
            'mat_khau' => 'matkhau123',
            'mat_khau_confirmation' => 'matkhau123',
        ]);

        $registerResponse->assertRedirect(route('customer.profile'));
        $this->assertDatabaseHas('tai_khoans', [
            'email' => 'namtran@example.test',
            'ho_ten' => 'Trần Văn Nam',
        ]);

        // Đăng xuất
        $logoutResponse = $this->post(route('customer.logout'));
        $logoutResponse->assertRedirect(route('shop.products.index'));
        $this->assertNull(session('customer_id'));

        // Đăng nhập lại
        $loginResponse = $this->post(route('customer.login.store'), [
            'email' => 'namtran@example.test',
            'mat_khau' => 'matkhau123',
        ]);
        $loginResponse->assertRedirect(route('customer.profile'));
        $this->assertNotNull(session('customer_id'));
    }

    public function test_customer_can_update_profile_and_password(): void
    {
        $customer = TaiKhoan::create([
            'ho_ten' => 'Phạm Hoàng',
            'email' => 'hoang@example.test',
            'so_dien_thoai' => '0912000111',
            'mat_khau' => 'oldpassword123',
            'trang_thai' => true,
        ]);

        $response = $this->withSession(['customer_id' => $customer->id])
            ->put(route('customer.profile.update'), [
                'ho_ten' => 'Phạm Hoàng Cập Nhật',
                'so_dien_thoai' => '0988776655',
                'ngay_sinh' => '2000-05-15',
                'gioi_tinh' => '1',
            ]);

        $response->assertRedirect(route('customer.profile'));
        $customer->refresh();
        $this->assertSame('Phạm Hoàng Cập Nhật', $customer->ho_ten);
        $this->assertSame('0988776655', $customer->so_dien_thoai);
        $this->assertSame(1, $customer->gioi_tinh);

        // Đổi mật khẩu
        $pwdResponse = $this->withSession(['customer_id' => $customer->id])
            ->put(route('customer.password.update'), [
                'mat_khau_cu' => 'oldpassword123',
                'mat_khau' => 'newsecret456',
                'mat_khau_confirmation' => 'newsecret456',
            ]);

        $pwdResponse->assertRedirect(route('customer.profile'));
        $customer->refresh();
        $this->assertTrue(Hash::check('newsecret456', $customer->mat_khau));
    }

    public function test_customer_address_management_crud(): void
    {
        $customer = TaiKhoan::create([
            'ho_ten' => 'Vũ Thị Lan',
            'email' => 'lan@example.test',
            'mat_khau' => 'password123',
            'trang_thai' => true,
        ]);

        // 1. Thêm địa chỉ mới
        $storeResponse = $this->withSession(['customer_id' => $customer->id])
            ->post(route('customer.addresses.store'), [
                'ten_nguoi_nhan' => 'Vũ Thị Lan',
                'sdt_nguoi_nhan' => '0933221100',
                'tinh_thanh' => 'Hà Nội',
                'quan_huyen' => 'Cầu Giấy',
                'phuong_xa' => 'Dịch Vọng',
                'dia_chi_chi_tiet' => 'Số 10 ngõ 50 đường Cầu Giấy',
                'mac_dinh' => '1',
            ]);

        $storeResponse->assertRedirect(route('customer.addresses.index'));
        $this->assertDatabaseHas('dia_chis', [
            'tai_khoan_id' => $customer->id,
            'ten_nguoi_nhan' => 'Vũ Thị Lan',
            'mac_dinh' => 1,
        ]);

        $address = DiaChi::where('tai_khoan_id', $customer->id)->first();
        $this->assertNotNull($address);
        $this->assertSame('Số 10 ngõ 50 đường Cầu Giấy, Dịch Vọng, Cầu Giấy, Hà Nội', $address->diaChiDayDu());

        // 2. Cập nhật địa chỉ
        $updateResponse = $this->withSession(['customer_id' => $customer->id])
            ->put(route('customer.addresses.update', $address), [
                'ten_nguoi_nhan' => 'Vũ Thị Lan (Cơ quan)',
                'sdt_nguoi_nhan' => '0933221100',
                'tinh_thanh' => 'Hà Nội',
                'quan_huyen' => 'Nam Từ Liêm',
                'phuong_xa' => 'Mỹ Đình 1',
                'dia_chi_chi_tiet' => 'Tòa nhà FPT Tower',
                'mac_dinh' => '1',
            ]);

        $updateResponse->assertRedirect(route('customer.addresses.index'));
        $address->refresh();
        $this->assertSame('Vũ Thị Lan (Cơ quan)', $address->ten_nguoi_nhan);
        $this->assertSame('Mỹ Đình 1', $address->phuong_xa);

        // 3. Xóa địa chỉ
        $deleteResponse = $this->withSession(['customer_id' => $customer->id])
            ->delete(route('customer.addresses.destroy', $address));

        $deleteResponse->assertRedirect(route('customer.addresses.index'));
        $this->assertDatabaseMissing('dia_chis', ['id' => $address->id]);
    }

    public function test_wishlist_toggle_for_guest_and_customer(): void
    {
        $product = Product::create([
            'ma_san_pham' => 'SP-AO-01',
            'ten_san_pham' => 'Áo Polo Thể Thao Nam',
            'gia_san_pham' => 280000,
            'trang_thai' => true,
        ]);

        // 1. Khách vãng lai toggle yêu thích qua AJAX
        $guestResponse = $this->postJson(route('shop.wishlist.toggle', $product));
        $guestResponse->assertOk();
        $guestResponse->assertJson([
            'success' => true,
            'is_favorited' => true,
            'count' => 1,
        ]);

        // Toggle lần 2 -> gỡ khỏi yêu thích
        $guestToggleResponse = $this->postJson(route('shop.wishlist.toggle', $product));
        $guestToggleResponse->assertOk();
        $guestToggleResponse->assertJson([
            'success' => true,
            'is_favorited' => false,
            'count' => 0,
        ]);

        // 2. Khách đã đăng nhập lưu vào DB
        $customer = TaiKhoan::create([
            'ho_ten' => 'Khách Yêu Thích',
            'email' => 'yeuthich@example.test',
            'mat_khau' => '123456',
            'trang_thai' => true,
        ]);

        $authResponse = $this->withSession(['customer_id' => $customer->id])
            ->postJson(route('shop.wishlist.toggle', $product));

        $authResponse->assertOk();
        $authResponse->assertJson([
            'success' => true,
            'is_favorited' => true,
            'count' => 1,
        ]);

        $this->assertDatabaseHas('yeu_thichs', [
            'tai_khoan_id' => $customer->id,
            'san_pham_id' => $product->id,
        ]);

        // Xem trang yêu thích
        $wishlistView = $this->withSession(['customer_id' => $customer->id])
            ->get(route('shop.wishlist.index'));
        $wishlistView->assertOk();
        $wishlistView->assertSee('Áo Polo Thể Thao Nam');
    }
}
