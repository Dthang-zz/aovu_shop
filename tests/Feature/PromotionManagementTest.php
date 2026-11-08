<?php

namespace Tests\Feature;

use App\Models\Voucher;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PromotionManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('vouchers', function (Blueprint $table) {
            $table->increments('id');
            $table->string('ma_voucher', 50)->unique();
            $table->string('mo_ta')->nullable();
            $table->unsignedTinyInteger('loai_giam')->nullable();
            $table->decimal('gia_tri_giam', 12, 0)->nullable();
            $table->decimal('giam_toi_da', 12, 0)->nullable();
            $table->decimal('don_toi_thieu', 12, 0)->nullable();
            $table->unsignedInteger('so_luong')->nullable();
            $table->unsignedInteger('da_su_dung')->nullable();
            $table->dateTime('ngay_bat_dau')->nullable();
            $table->dateTime('ngay_ket_thuc')->nullable();
            $table->boolean('trang_thai')->nullable();
        });
        Schema::create('don_hangs', function (Blueprint $table) {
            $table->increments('id');
            $table->string('ma_don_hang');
            $table->string('ten_nguoi_nhan')->nullable();
            $table->dateTime('ngay_dat')->nullable();
            $table->unsignedInteger('voucher_id')->nullable();
            $table->decimal('tien_giam_voucher', 12, 0)->nullable();
            $table->decimal('tong_tien', 12, 0)->nullable();
            $table->unsignedTinyInteger('trang_thai')->nullable();
        });
    }

    public function test_admin_can_create_percentage_code_with_cap_and_minimum_order(): void
    {
        $this->get(route('admin.promotions.create'))
            ->assertOk()
            ->assertSee('name="ma_voucher"', false)
            ->assertDontSee('san_pham_ids');

        $this->post(route('admin.promotions.store'), [
            ...$this->codePayload(),
            'ma_voucher' => ' sale10 ',
            'mo_ta' => 'Giảm 10% đơn từ 300k',
            'loai_giam' => Voucher::TYPE_PERCENT,
            'gia_tri_giam' => 10,
            'giam_toi_da' => 50000,
            'don_toi_thieu' => 300000,
            'so_luong' => 100,
        ])->assertRedirect(route('admin.promotions.index'))
            ->assertSessionHas('success', 'Đã tạo mã giảm giá SALE10.');

        $this->assertDatabaseHas('vouchers', [
            'ma_voucher' => 'SALE10',
            'loai_giam' => Voucher::TYPE_PERCENT,
            'gia_tri_giam' => 10,
            'giam_toi_da' => 50000,
            'don_toi_thieu' => 300000,
            'so_luong' => 100,
            'da_su_dung' => 0,
            'trang_thai' => 1,
        ]);
        $this->get(route('admin.promotions.index'))
            ->assertSee('SALE10')
            ->assertSee('tối đa 50.000 ₫ · đơn từ 300.000 ₫')
            ->assertSee('0 / 100')
            ->assertSee('Sắp diễn ra');
    }

    public function test_fixed_amount_code_ignores_the_percentage_cap_and_allows_unlimited_uses(): void
    {
        $this->post(route('admin.promotions.store'), [
            ...$this->codePayload(),
            'ma_voucher' => 'GIAM50K',
            'loai_giam' => Voucher::TYPE_AMOUNT,
            'gia_tri_giam' => 50000,
            'giam_toi_da' => 10000,
            'so_luong' => '',
        ])->assertRedirect(route('admin.promotions.index'));

        $this->assertDatabaseHas('vouchers', [
            'ma_voucher' => 'GIAM50K',
            'loai_giam' => Voucher::TYPE_AMOUNT,
            'gia_tri_giam' => 50000,
            'giam_toi_da' => null,
            'so_luong' => null,
        ]);
        $this->get(route('admin.promotions.index'))->assertSee('50.000 ₫')->assertSee('0 / ∞');
    }

    public function test_code_fields_are_validated(): void
    {
        $this->createVoucher(['ma_voucher' => 'TAKEN']);

        $this->from(route('admin.promotions.create'))
            ->post(route('admin.promotions.store'), [
                ...$this->codePayload(),
                'ma_voucher' => 'taken',
                'loai_giam' => Voucher::TYPE_PERCENT,
                'gia_tri_giam' => 101,
                'giam_toi_da' => 0,
                'don_toi_thieu' => -1,
                'ngay_ket_thuc' => now()->subDay()->format('Y-m-d\TH:i'),
            ])
            ->assertRedirect(route('admin.promotions.create'))
            ->assertSessionHasErrors(['ma_voucher', 'gia_tri_giam', 'giam_toi_da', 'don_toi_thieu', 'ngay_ket_thuc']);

        $this->post(route('admin.promotions.store'), [...$this->codePayload(), 'ma_voucher' => 'CÓ DẤU', 'gia_tri_giam' => 12.5])
            ->assertSessionHasErrors(['ma_voucher', 'gia_tri_giam']);
        $this->post(route('admin.promotions.store'), [...$this->codePayload(), 'loai_giam' => 3])
            ->assertSessionHasErrors('loai_giam');

        $this->assertDatabaseCount('vouchers', 1);
    }

    public function test_admin_can_update_a_code_but_not_below_its_used_count(): void
    {
        $voucher = $this->createVoucher(['ma_voucher' => 'OLD', 'so_luong' => 10, 'da_su_dung' => 4]);

        $this->get(route('admin.promotions.edit', $voucher))
            ->assertOk()
            ->assertSee('Đã dùng 4 lượt.');
        $this->put(route('admin.promotions.update', $voucher), [...$this->codePayload(), 'ma_voucher' => 'OLD', 'so_luong' => 3])
            ->assertSessionHasErrors(['so_luong' => 'Số lượt sử dụng không được nhỏ hơn số lượt đã dùng (4).']);

        $this->put(route('admin.promotions.update', $voucher), [...$this->codePayload(), 'ma_voucher' => 'new', 'so_luong' => 4, 'trang_thai' => '0'])
            ->assertRedirect(route('admin.promotions.index'));

        $this->assertDatabaseHas('vouchers', ['id' => $voucher->id, 'ma_voucher' => 'NEW', 'so_luong' => 4, 'da_su_dung' => 4, 'trang_thai' => 0]);
    }

    public function test_detail_page_lists_orders_that_used_the_code(): void
    {
        $voucher = $this->createVoucher(['ma_voucher' => 'DETAIL', 'da_su_dung' => 1]);
        $orderId = DB::table('don_hangs')->insertGetId([
            'ma_don_hang' => 'ORD-VOUCHER', 'ten_nguoi_nhan' => 'Khách dùng mã', 'ngay_dat' => now(),
            'voucher_id' => $voucher->id, 'tien_giam_voucher' => 30000, 'tong_tien' => 270000, 'trang_thai' => 0,
        ]);
        DB::table('don_hangs')->insert(['ma_don_hang' => 'ORD-NO-CODE', 'ngay_dat' => now(), 'tong_tien' => 100000, 'trang_thai' => 0]);

        $this->get(route('admin.promotions.show', $voucher))
            ->assertOk()
            ->assertSee('Mã DETAIL')
            ->assertSee('Đơn hàng đã dùng mã (1)')
            ->assertSee(route('admin.orders.show', $orderId))
            ->assertSee('-30.000 ₫')
            ->assertDontSee('ORD-NO-CODE');
    }

    public function test_code_used_by_an_order_cannot_be_deleted_but_an_unused_one_can(): void
    {
        $usedVoucher = $this->createVoucher(['ma_voucher' => 'USED']);
        $unusedVoucher = $this->createVoucher(['ma_voucher' => 'UNUSED']);
        DB::table('don_hangs')->insert(['ma_don_hang' => 'ORD-1', 'voucher_id' => $usedVoucher->id]);

        $this->delete(route('admin.promotions.destroy', $usedVoucher))
            ->assertRedirect(route('admin.promotions.index'))
            ->assertSessionHas('error');
        $this->delete(route('admin.promotions.destroy', $unusedVoucher))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('vouchers', ['id' => $usedVoucher->id]);
        $this->assertDatabaseMissing('vouchers', ['id' => $unusedVoucher->id]);
    }

    public function test_product_promotion_tables_are_gone(): void
    {
        $this->assertFalse(Schema::hasTable('promotions'));
        $this->assertFalse(Schema::hasTable('promotion_products'));
    }

    /**
     * @return array{ma_voucher: string, loai_giam: int, gia_tri_giam: int, ngay_bat_dau: string, ngay_ket_thuc: string, trang_thai: string}
     */
    private function codePayload(): array
    {
        return [
            'ma_voucher' => 'CODE'.uniqid(),
            'loai_giam' => Voucher::TYPE_PERCENT,
            'gia_tri_giam' => 10,
            'ngay_bat_dau' => now()->addDay()->format('Y-m-d\TH:i'),
            'ngay_ket_thuc' => now()->addDays(3)->format('Y-m-d\TH:i'),
            'trang_thai' => '1',
        ];
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createVoucher(array $attributes = []): Voucher
    {
        return Voucher::query()->create([
            'ma_voucher' => 'CODE'.uniqid(),
            'loai_giam' => Voucher::TYPE_PERCENT,
            'gia_tri_giam' => 10,
            'da_su_dung' => 0,
            'ngay_bat_dau' => now()->subDay(),
            'ngay_ket_thuc' => now()->addDay(),
            'trang_thai' => true,
            ...$attributes,
        ]);
    }
}
