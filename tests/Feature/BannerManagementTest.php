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

    public function test_admin_can_create_a_banner_with_an_image_and_optional_link(): void
    {
        Storage::fake('public');

        $this->post('/admin/banners', [
            'title' => 'Ưu đãi cuối mùa',
            'link_url' => 'https://example.com/uu-dai',
            'sort_order' => 2,
            'is_active' => '1',
            'image' => UploadedFile::fake()->image('sale-banner.jpg', 1600, 600),
        ])
            ->assertRedirect(route('admin.banners.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('banners', [
            'title' => 'Ưu đãi cuối mùa',
            'link_url' => 'https://example.com/uu-dai',
            'sort_order' => 2,
            'is_active' => 1,
        ]);

        $banner = Banner::query()->firstOrFail();
        Storage::disk('public')->assertExists($banner->image_path);
    }

    public function test_admin_can_view_banner_details(): void
    {
        Storage::fake('public');
        $banner = Banner::query()->create([
            'title' => 'Bộ sưu tập thu',
            'image_path' => 'banners/autumn.jpg',
            'link_url' => 'https://example.com/thu',
            'sort_order' => 3,
            'is_active' => false,
        ]);

        $this->get(route('admin.banners.index'))
            ->assertOk()
            ->assertSee(route('admin.banners.show', $banner));
        $this->get(route('admin.banners.show', $banner))
            ->assertOk()
            ->assertSee('Bộ sưu tập thu')
            ->assertSee('https://example.com/thu')
            ->assertSee(Storage::disk('public')->url('banners/autumn.jpg'))
            ->assertSee('Đang ẩn khỏi cửa hàng')
            ->assertSee(route('admin.banners.edit', $banner));
    }

    public function test_banner_detail_returns_not_found_for_missing_banner(): void
    {
        $this->get(route('admin.banners.show', 999))->assertNotFound();
    }

    public function test_storefront_shows_only_active_banners_in_display_order(): void
    {
        $hidden = Banner::query()->create([
            'title' => 'Banner đang ẩn',
            'image_path' => 'banners/hidden.jpg',
            'sort_order' => 0,
            'is_active' => false,
        ]);
        $second = Banner::query()->create([
            'title' => 'Ưu đãi thứ hai',
            'image_path' => 'banners/second.jpg',
            'sort_order' => 2,
            'is_active' => true,
        ]);
        $first = Banner::query()->create([
            'title' => 'Ưu đãi đầu tiên',
            'image_path' => 'banners/first.jpg',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $this->get(route('shop.products.index'))
            ->assertOk()
            ->assertSeeInOrder([$first->title, $second->title])
            ->assertDontSee($hidden->title);
    }

    public function test_admin_can_replace_and_delete_a_banner_image(): void
    {
        Storage::fake('public');
        $oldPath = UploadedFile::fake()->image('old-banner.jpg')->store('banners', 'public');
        $banner = Banner::query()->create([
            'title' => 'Banner cũ',
            'image_path' => $oldPath,
            'sort_order' => 0,
            'is_active' => true,
        ]);

        $this->put(route('admin.banners.update', $banner), [
            'title' => 'Banner mới',
            'link_url' => '',
            'sort_order' => 3,
            'is_active' => '0',
            'image' => UploadedFile::fake()->image('new-banner.webp'),
        ])->assertRedirect(route('admin.banners.index'));

        $banner->refresh();
        $this->assertSame('Banner mới', $banner->title);
        $this->assertFalse($banner->is_active);
        Storage::disk('public')->assertMissing($oldPath);
        Storage::disk('public')->assertExists($banner->image_path);

        $newPath = $banner->image_path;
        $this->delete(route('admin.banners.destroy', $banner))
            ->assertRedirect(route('admin.banners.index'));

        $this->assertDatabaseMissing('banners', ['id' => $banner->id]);
        Storage::disk('public')->assertMissing($newPath);
    }
}
