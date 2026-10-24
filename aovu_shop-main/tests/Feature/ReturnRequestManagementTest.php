<?php

namespace Tests\Feature;

use App\Models\ReturnRequest;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ReturnRequestManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('don_hangs', function (Blueprint $table) {
            $table->increments('id');
            $table->string('ma_don_hang')->nullable();
            $table->string('ten_nguoi_nhan')->nullable();
            $table->decimal('tong_tien', 12, 0)->nullable();
        });
        Schema::create('chi_tiet_don_hangs', function (Blueprint $table) {
            $table->increments('id');
            $table->string('ten_san_pham')->nullable();
            $table->decimal('don_gia', 12, 0)->nullable();
            $table->unsignedInteger('so_luong')->nullable();
        });
        Schema::create('tai_khoans', function (Blueprint $table) {
            $table->increments('id');
            $table->string('ho_ten')->nullable();
            $table->string('email')->nullable();
        });
    }

    public function test_return_request_list_shows_joined_details_and_filters(): void
    {
        $this->seedOrderDetails();
        $requestId = $this->createRequest(
            ReturnRequest::TYPE_RETURN,
            ReturnRequest::STATUS_PENDING,
            'Đường may bị lỗi'
        );
        $this->createRequest(
            ReturnRequest::TYPE_EXCHANGE,
            ReturnRequest::STATUS_PROCESSING,
            'Cần đổi sang kích thước lớn hơn'
        );

        $this->get(route('admin.returns.index', [
            'q' => 'ORD-100',
            'type' => (string) ReturnRequest::TYPE_RETURN,
            'status' => (string) ReturnRequest::STATUS_PENDING,
        ]))
            ->assertOk()
            ->assertSee('#DT-'.str_pad((string) $requestId, 6, '0', STR_PAD_LEFT))
            ->assertSee('ORD-100')
            ->assertSee('Khách hàng thử nghiệm')
            ->assertSee('Áo Linen')
            ->assertSee('Đường may bị lỗi')
            ->assertDontSee('Cần đổi sang kích thước lớn hơn');
    }

    public function test_staff_can_process_approve_and_record_refund_for_return_request(): void
    {
        $this->seedOrderDetails();
        $requestId = $this->createRequest(ReturnRequest::TYPE_RETURN, ReturnRequest::STATUS_PENDING);

        $this->put(route('admin.returns.update', $requestId), [
            'trang_thai' => ReturnRequest::STATUS_PROCESSING,
            'phan_hoi' => 'Đã tiếp nhận yêu cầu và đang kiểm tra.',
        ])->assertRedirect(route('admin.returns.show', $requestId));

        $this->assertDatabaseHas('yeu_cau_doi_tras', [
            'id' => $requestId,
            'trang_thai' => ReturnRequest::STATUS_PROCESSING,
            'phan_hoi' => 'Đã tiếp nhận yêu cầu và đang kiểm tra.',
        ]);

        $this->put(route('admin.returns.update', $requestId), [
            'trang_thai' => ReturnRequest::STATUS_APPROVED,
            'phan_hoi' => 'Sản phẩm đạt điều kiện trả hàng.',
        ])->assertRedirect(route('admin.returns.show', $requestId));

        $this->put(route('admin.returns.update', $requestId), [
            'trang_thai' => ReturnRequest::STATUS_REFUNDED,
            'phan_hoi' => 'Đã hoàn tiền theo quy trình kế toán.',
        ])->assertRedirect(route('admin.returns.show', $requestId));

        $this->get(route('admin.returns.show', $requestId))
            ->assertOk()
            ->assertSee('Đã ghi nhận hoàn tiền')
            ->assertSee('Đã hoàn tiền theo quy trình kế toán.')
            ->assertSee('Việc chuyển tiền thực tế');

        $this->assertDatabaseHas('yeu_cau_doi_tras', [
            'id' => $requestId,
            'trang_thai' => ReturnRequest::STATUS_REFUNDED,
        ]);
        $this->get(route('admin.returns.index'))
            ->assertOk()
            ->assertSee('Đã ghi nhận hoàn tiền')
            ->assertSee('>0</span>', false);
    }

    public function test_rejection_requires_customer_feedback(): void
    {
        $requestId = $this->createRequest(ReturnRequest::TYPE_EXCHANGE, ReturnRequest::STATUS_PENDING);

        $this->from(route('admin.returns.show', $requestId))
            ->put(route('admin.returns.update', $requestId), [
                'trang_thai' => ReturnRequest::STATUS_REJECTED,
                'phan_hoi' => '',
            ])
            ->assertRedirect(route('admin.returns.show', $requestId))
            ->assertSessionHasErrors('phan_hoi');

        $this->assertDatabaseHas('yeu_cau_doi_tras', [
            'id' => $requestId,
            'trang_thai' => ReturnRequest::STATUS_PENDING,
        ]);

        $this->put(route('admin.returns.update', $requestId), [
            'trang_thai' => ReturnRequest::STATUS_REJECTED,
            'phan_hoi' => 'Yêu cầu nằm ngoài thời hạn đổi hàng.',
        ])->assertRedirect(route('admin.returns.show', $requestId));
    }

    public function test_status_transitions_are_restricted_and_exchange_cannot_be_refunded(): void
    {
        $exchangeId = $this->createRequest(ReturnRequest::TYPE_EXCHANGE, ReturnRequest::STATUS_APPROVED);

        $this->from(route('admin.returns.show', $exchangeId))
            ->put(route('admin.returns.update', $exchangeId), [
                'trang_thai' => ReturnRequest::STATUS_REFUNDED,
                'phan_hoi' => 'Đã hoàn tiền.',
            ])
            ->assertRedirect(route('admin.returns.show', $exchangeId))
            ->assertSessionHasErrors('trang_thai');

        $this->assertDatabaseHas('yeu_cau_doi_tras', [
            'id' => $exchangeId,
            'trang_thai' => ReturnRequest::STATUS_APPROVED,
        ]);

        $this->put(route('admin.returns.update', $exchangeId), [
            'trang_thai' => ReturnRequest::STATUS_COMPLETED,
            'phan_hoi' => 'Đã đổi sản phẩm thành công.',
        ])->assertRedirect(route('admin.returns.show', $exchangeId));

        $this->get(route('admin.returns.show', $exchangeId))
            ->assertOk()
            ->assertSee('Yêu cầu đã kết thúc');
    }

    private function seedOrderDetails(): void
    {
        DB::table('don_hangs')->insert([
            'id' => 100,
            'ma_don_hang' => 'ORD-100',
            'ten_nguoi_nhan' => 'Khách hàng thử nghiệm',
            'tong_tien' => 500000,
        ]);
        DB::table('chi_tiet_don_hangs')->insert([
            'id' => 200,
            'ten_san_pham' => 'Áo Linen',
            'don_gia' => 250000,
            'so_luong' => 2,
        ]);
        DB::table('tai_khoans')->insert([
            'id' => 300,
            'ho_ten' => 'Khách hàng thử nghiệm',
            'email' => 'customer@example.test',
        ]);
    }

    private function createRequest(int $type, int $status, string $reason = 'Sản phẩm cần đổi hoặc trả'): int
    {
        return DB::table('yeu_cau_doi_tras')->insertGetId([
            'don_hang_id' => 100,
            'chi_tiet_don_hang_id' => 200,
            'tai_khoan_id' => 300,
            'loai_yeu_cau' => $type,
            'so_luong' => 1,
            'ly_do' => $reason,
            'ngay_tao' => now(),
            'trang_thai' => $status,
        ]);
    }
}
