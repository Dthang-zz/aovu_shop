<?php

namespace Tests\Feature;

use App\Models\Banner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BannerManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_banner_list_can_be_searched_and_filtered_by_status(): void
    {
        Banner::factory()->create([
            'ten_banner' => 'Bộ sưu tập mùa hè',
            'trang_thai' => true,
        ]);
        Banner::factory()->create([
            'ten_banner' => 'Khuyến mãi cuối năm',
            'trang_thai' => false,
        ]);
        Banner::factory()->create([
            'ten_banner' => 'Banner khác',
            'trang_thai' => true,
        ]);

        $this->get(route('admin.banners.index', ['q' => 'mùa hè', 'status' => 'active']))
            ->assertOk()
            ->assertSee('Bộ sưu tập mùa hè')
            ->assertDontSee('Khuyến mãi cuối năm')
            ->assertDontSee('Banner khác')
            ->assertSee('Quản lý banner')
            ->assertSee(route('admin.banners.index'));
    }

    public function test_banner_can_be_created_updated_and_deleted_with_its_image(): void
    {
        Storage::fake('public');

        $this->get(route('admin.banners.create'))
            ->assertOk()
            ->assertSee('enctype="multipart/form-data"', false);

        $this->post(route('admin.banners.store'), [
            'ten_banner' => 'Bộ sưu tập mùa hè',
            'lien_ket' => '/collections/summer',
            'thu_tu' => 2,
            'trang_thai' => '1',
            'hinh_anh' => UploadedFile::fake()->image('summer.jpg'),
        ])->assertRedirect(route('admin.banners.index'));

        $banner = Banner::where('ten_banner', 'Bộ sưu tập mùa hè')->firstOrFail();
        $originalImagePath = $banner->hinh_anh;

        $this->assertDatabaseHas('banners', [
            'id' => $banner->id,
            'lien_ket' => '/collections/summer',
            'thu_tu' => 2,
            'trang_thai' => 1,
        ]);
        Storage::disk('public')->assertExists($originalImagePath);
        $this->get(route('admin.banners.edit', $banner))
            ->assertOk()
            ->assertSee('/storage/'.$originalImagePath)
            ->assertSee('Bộ sưu tập mùa hè');

        $this->put(route('admin.banners.update', $banner), [
            'ten_banner' => 'Bộ sưu tập mùa thu',
            'lien_ket' => '/collections/autumn',
            'thu_tu' => 1,
            'trang_thai' => '0',
            'hinh_anh' => UploadedFile::fake()->image('autumn.webp'),
        ])->assertRedirect(route('admin.banners.index'));

        $banner->refresh();
        $replacementImagePath = $banner->hinh_anh;

        $this->assertDatabaseHas('banners', [
            'id' => $banner->id,
            'ten_banner' => 'Bộ sưu tập mùa thu',
            'lien_ket' => '/collections/autumn',
            'thu_tu' => 1,
            'trang_thai' => 0,
        ]);
        Storage::disk('public')->assertMissing($originalImagePath);
        Storage::disk('public')->assertExists($replacementImagePath);

        $this->put(route('admin.banners.update', $banner), [
            'ten_banner' => 'Bộ sưu tập mùa thu mới',
            'lien_ket' => '/collections/autumn',
            'thu_tu' => 1,
            'trang_thai' => '0',
        ])->assertRedirect(route('admin.banners.index'));

        $banner->refresh();
        $this->assertSame($replacementImagePath, $banner->hinh_anh);
        Storage::disk('public')->assertExists($replacementImagePath);

        $this->delete(route('admin.banners.destroy', $banner))
            ->assertRedirect(route('admin.banners.index'));

        $this->assertDatabaseMissing('banners', ['id' => $banner->id]);
        Storage::disk('public')->assertMissing($replacementImagePath);
    }

    public function test_banner_validation_requires_a_name_and_a_valid_image(): void
    {
        Storage::fake('public');

        $this->from(route('admin.banners.create'))
            ->post(route('admin.banners.store'), [
                'ten_banner' => '',
                'thu_tu' => -1,
                'trang_thai' => '1',
                'hinh_anh' => UploadedFile::fake()->create('banner.pdf', 10, 'application/pdf'),
            ])
            ->assertRedirect(route('admin.banners.create'))
            ->assertSessionHasErrors(['ten_banner', 'thu_tu', 'hinh_anh']);

        $this->assertDatabaseCount('banners', 0);
    }

    public function test_banner_image_is_required_when_creating(): void
    {
        $this->from(route('admin.banners.create'))
            ->post(route('admin.banners.store'), [
                'ten_banner' => 'Banner chưa có ảnh',
                'thu_tu' => 0,
                'trang_thai' => '1',
            ])
            ->assertRedirect(route('admin.banners.create'))
            ->assertSessionHasErrors('hinh_anh');

        $this->assertDatabaseCount('banners', 0);
    }
}
