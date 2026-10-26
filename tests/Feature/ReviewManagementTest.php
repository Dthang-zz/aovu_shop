<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ReviewManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('tai_khoans', function (Blueprint $table) {
            $table->increments('id');
            $table->string('ho_ten')->nullable();
            $table->string('email')->nullable();
        });
        Schema::create('don_hangs', function (Blueprint $table) {
            $table->increments('id');
            $table->string('ma_don_hang')->nullable();
            $table->dateTime('ngay_dat')->nullable();
            $table->decimal('tong_tien', 12, 0)->nullable();
        });
        Schema::create('chi_tiet_don_hangs', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('don_hang_id')->nullable();
            $table->string('ten_san_pham')->nullable();
            $table->decimal('don_gia', 12, 0)->nullable();
            $table->unsignedInteger('so_luong')->nullable();
        });
        Schema::create('danh_gias', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('san_pham_id')->nullable();
            $table->unsignedInteger('tai_khoan_id')->nullable();
            $table->unsignedInteger('chi_tiet_don_hang_id')->nullable();
            $table->unsignedTinyInteger('so_sao')->nullable();
            $table->text('noi_dung')->nullable();
            $table->dateTime('ngay_dang')->nullable();
            $table->unsignedTinyInteger('trang_thai')->nullable();
            $table->text('hinh_anh')->nullable();
            $table->text('phan_hoi')->nullable();
        });
    }

    public function test_review_migration_adds_and_removes_review_fields(): void
    {
        $migration = require database_path('migrations/2026_10_27_021528_add_review_moderation_fields_to_danh_gias_table.php');
        Schema::table('danh_gias', function (Blueprint $table): void {
            $table->dropColumn(['hinh_anh', 'phan_hoi']);
        });

        $this->assertFalse(Schema::hasColumn('danh_gias', 'hinh_anh'));
        $this->assertFalse(Schema::hasColumn('danh_gias', 'phan_hoi'));

        $migration->up();

        $this->assertTrue(Schema::hasColumn('danh_gias', 'hinh_anh'));
        $this->assertTrue(Schema::hasColumn('danh_gias', 'phan_hoi'));

        $migration->down();

        $this->assertFalse(Schema::hasColumn('danh_gias', 'hinh_anh'));
        $this->assertFalse(Schema::hasColumn('danh_gias', 'phan_hoi'));
    }

    public function test_review_list_filters_and_shows_statistics_for_all_reviews(): void
    {
        $productId = $this->createProduct('Áo Linen');
        $otherProductId = $this->createProduct('Đầm Midi');
        $customerId = $this->createCustomer('Nguyễn Minh Anh');

        $this->createReview($productId, $customerId, 5, 1, 'Đánh giá cần tìm', '2026-10-20 10:00:00');
        $this->createReview($productId, $customerId, 4, 1, 'Đánh giá khác ngày', '2026-10-19 10:00:00');
        $this->createReview($productId, $customerId, 2, 0, 'Đánh giá khác trạng thái cũ', '2026-10-20 11:00:00');
        $this->createReview($otherProductId, $customerId, 5, 1, 'Sản phẩm khác', '2026-10-20 12:00:00');

        $this->get(route('admin.reviews.index', [
            'product_id' => $productId,
            'stars' => 5,
            'from' => '2026-10-20',
            'to' => '2026-10-20',
        ]))
            ->assertOk()
            ->assertSee('Đánh giá cần tìm')
            ->assertDontSee('Đánh giá khác ngày')
            ->assertDontSee('Đánh giá khác trạng thái cũ')
            ->assertDontSee('Sản phẩm khác')
            ->assertSee('4,0')
            ->assertSee('3,7')
            ->assertSee('Áo Linen');
    }

    public function test_review_list_remains_available_when_reply_column_migration_is_pending(): void
    {
        $productId = $this->createProduct('Áo Linen');
        $customerId = $this->createCustomer('Nguyễn Minh Anh');
        $reviewId = $this->createReview($productId, $customerId, 5, 0, 'Đánh giá trước khi chạy migration.');
        Schema::table('danh_gias', function (Blueprint $table): void {
            $table->dropColumn('phan_hoi');
        });

        $this->get(route('admin.reviews.index'))
            ->assertOk()
            ->assertSee('Đánh giá trước khi chạy migration.')
            ->assertSee('0');

        $this->from(route('admin.reviews.show', $reviewId))
            ->put(route('admin.reviews.update', $reviewId), [
                'phan_hoi' => 'Cảm ơn khách hàng.',
            ])
            ->assertRedirect(route('admin.reviews.show', $reviewId))
            ->assertSessionHasErrors('phan_hoi');
    }

    public function test_legacy_review_status_does_not_filter_admin_review_list(): void
    {
        $productId = $this->createProduct('Áo Linen');
        $customerId = $this->createCustomer('Nguyễn Minh Anh');
        $this->createReview($productId, $customerId, 5, 0, 'Đánh giá cũ trạng thái 0.');
        $this->createReview($productId, $customerId, 3, 3, 'Đánh giá cũ trạng thái 3.');

        $this->get(route('admin.reviews.index', ['status' => 1]))
            ->assertOk()
            ->assertSee('Đánh giá cũ trạng thái 0.')
            ->assertSee('Đánh giá cũ trạng thái 3.')
            ->assertDontSee('name="status"', false)
            ->assertDontSee('Trạng thái</th>');
    }

    public function test_review_detail_shows_customer_product_order_and_photos_safely(): void
    {
        $productId = $this->createProduct('Áo Linen');
        $customerId = $this->createCustomer('Nguyễn Minh Anh');
        DB::table('don_hangs')->insert([
            'id' => 80,
            'ma_don_hang' => 'ORD-0080',
            'ngay_dat' => '2026-10-18 09:00:00',
            'tong_tien' => 459000,
        ]);
        DB::table('chi_tiet_don_hangs')->insert([
            'id' => 90,
            'don_hang_id' => 80,
            'ten_san_pham' => 'Áo Linen size M',
            'don_gia' => 459000,
            'so_luong' => 1,
        ]);
        $reviewId = $this->createReview(
            $productId,
            $customerId,
            5,
            0,
            '<script>alert("xss")</script>',
            '2026-10-20 10:00:00',
            90,
            json_encode(['reviews/photo.jpg', 'https://example.test/photo-2.jpg'], JSON_THROW_ON_ERROR),
        );

        $this->get(route('admin.reviews.show', $reviewId))
            ->assertOk()
            ->assertSee('Nguyễn Minh Anh')
            ->assertSee('Áo Linen size M')
            ->assertSee('ORD-0080')
            ->assertSee('/storage/reviews/photo.jpg')
            ->assertSee('https://example.test/photo-2.jpg')
            ->assertSee('&lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;', false)
            ->assertDontSee('<script>alert("xss")</script>', false)
            ->assertDontSee('name="trang_thai"', false)
            ->assertDontSee('Kiểm duyệt');
    }

    public function test_staff_can_reply_without_changing_legacy_review_status(): void
    {
        $productId = $this->createProduct('Áo Linen');
        $customerId = $this->createCustomer('Nguyễn Minh Anh');
        $reviewId = $this->createReview($productId, $customerId, 2, 0, 'Chất lượng chưa tốt.');

        $this->put(route('admin.reviews.update', $reviewId), [
            'phan_hoi' => 'Cảm ơn bạn đã góp ý.',
        ])->assertRedirect(route('admin.reviews.show', $reviewId));

        $this->assertDatabaseHas('danh_gias', [
            'id' => $reviewId,
            'trang_thai' => 0,
            'phan_hoi' => 'Cảm ơn bạn đã góp ý.',
        ]);

        $this->put(route('admin.reviews.update', $reviewId), [
            'trang_thai' => 3,
            'phan_hoi' => '',
        ])->assertRedirect(route('admin.reviews.show', $reviewId));

        $this->assertDatabaseHas('danh_gias', [
            'id' => $reviewId,
            'trang_thai' => 0,
            'phan_hoi' => null,
        ]);
    }

    public function test_storefront_displays_all_reviews_and_store_replies_immediately(): void
    {
        $productId = $this->createProduct('Áo Linen');
        $customerId = $this->createCustomer('Nguyễn Minh Anh');
        $repliedReviewId = $this->createReview($productId, $customerId, 5, 1, 'Chất vải rất thoải mái.');
        $this->createReview($productId, $customerId, 1, 0, 'Đánh giá hiển thị ngay.');
        DB::table('danh_gias')->where('id', $repliedReviewId)->update([
            'phan_hoi' => 'Cảm ơn bạn đã tin tưởng cửa hàng.',
        ]);

        $this->get(route('shop.products.show', $productId))
            ->assertOk()
            ->assertSee('Chất vải rất thoải mái.')
            ->assertSee('Cảm ơn bạn đã tin tưởng cửa hàng.')
            ->assertSee('Đánh giá hiển thị ngay.')
            ->assertSee('3,0');
    }

    public function test_staff_can_clear_existing_reply_without_changing_review_status(): void
    {
        $productId = $this->createProduct('Áo Linen');
        $customerId = $this->createCustomer('Nguyễn Minh Anh');
        $reviewId = $this->createReview($productId, $customerId, 1, 0, 'Nội dung vi phạm.');

        $this->put(route('admin.reviews.update', $reviewId), [
            'phan_hoi' => '',
        ])->assertRedirect(route('admin.reviews.show', $reviewId));

        $this->assertDatabaseHas('danh_gias', [
            'id' => $reviewId,
            'trang_thai' => 0,
            'phan_hoi' => null,
        ]);
    }

    private function createProduct(string $name): int
    {
        return DB::table('san_phams')->insertGetId([
            'ma_san_pham' => 'REVIEW-'.(DB::table('san_phams')->count() + 1),
            'ten_san_pham' => $name,
        ]);
    }

    private function createCustomer(string $name): int
    {
        return DB::table('tai_khoans')->insertGetId([
            'ho_ten' => $name,
            'email' => 'customer@example.test',
        ]);
    }

    private function createReview(
        int $productId,
        int $customerId,
        int $stars,
        int $status,
        string $content,
        string $createdAt = '2026-10-20 09:00:00',
        ?int $orderItemId = null,
        ?string $photos = null,
    ): int {
        return DB::table('danh_gias')->insertGetId([
            'san_pham_id' => $productId,
            'tai_khoan_id' => $customerId,
            'chi_tiet_don_hang_id' => $orderItemId,
            'so_sao' => $stars,
            'noi_dung' => $content,
            'ngay_dang' => $createdAt,
            'trang_thai' => $status,
            'hinh_anh' => $photos,
        ]);
    }
}
