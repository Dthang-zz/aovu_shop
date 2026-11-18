<?php

namespace Tests\Feature;

use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CategoryManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_category_list_shows_saved_categories_and_counts(): void
    {
        $parent = Category::create([
            'ten_danh_muc' => 'Thời trang nữ',
            'trang_thai' => true,
        ]);
        $child = Category::create([
            'ten_danh_muc' => 'Đầm váy',
            'danh_muc_cha_id' => $parent->id,
            'trang_thai' => false,
        ]);
        Category::create([
            'ten_danh_muc' => 'Đầm dự tiệc',
            'danh_muc_cha_id' => $child->id,
            'trang_thai' => true,
        ]);

        $this->get(route('admin.categories.index'))
            ->assertOk()
            ->assertSee('Thời trang nữ')
            ->assertSee('Đầm váy')
            ->assertSeeInOrder(['Thời trang nữ', 'Đầm váy', 'Đầm dự tiệc'])
            ->assertSee('padding-left: 1.25rem')
            ->assertSee('padding-left: 2.5rem')
            ->assertSee('Đang ẩn')
            ->assertSee('Tổng danh mục');
    }

    public function test_category_details_show_the_number_of_products(): void
    {
        $category = Category::create([
            'ten_danh_muc' => 'Áo nữ',
            'trang_thai' => true,
        ]);
        $child = Category::create([
            'ten_danh_muc' => 'Áo sơ mi',
            'danh_muc_cha_id' => $category->id,
            'trang_thai' => true,
        ]);

        DB::table('san_phams')->insert([
            ['ma_san_pham' => 'AO-NU-001', 'danh_muc_id' => $category->id],
            ['ma_san_pham' => 'AO-NU-002', 'danh_muc_id' => $category->id],
            ['ma_san_pham' => 'AO-SM-001', 'danh_muc_id' => $child->id],
        ]);

        $this->get(route('admin.categories.show', $category))
            ->assertOk()
            ->assertSee('Áo nữ')
            ->assertSeeText('2')
            ->assertSee('Sản phẩm trong danh mục')
            ->assertSee('Áo sơ mi');
    }

    public function test_deleting_parent_category_keeps_children_without_a_parent(): void
    {
        $parent = Category::create([
            'ten_danh_muc' => 'Thời trang nữ',
            'trang_thai' => true,
        ]);
        $child = Category::create([
            'ten_danh_muc' => 'Đầm váy',
            'danh_muc_cha_id' => $parent->id,
            'trang_thai' => true,
        ]);

        $this->delete(route('admin.categories.destroy', $parent))
            ->assertRedirect(route('admin.categories.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('danh_mucs', ['id' => $parent->id]);
        $this->assertDatabaseHas('danh_mucs', [
            'id' => $child->id,
            'danh_muc_cha_id' => null,
        ]);
    }

    public function test_category_can_be_created_updated_and_deleted(): void
    {
        $this->post(route('admin.categories.store'), [
            'ten_danh_muc' => 'Áo nữ',
            'danh_muc_cha_id' => '',
            'mo_ta' => 'Các mẫu áo dành cho nữ.',
            'trang_thai' => '1',
        ])->assertRedirect(route('admin.categories.index'));

        $category = Category::where('ten_danh_muc', 'Áo nữ')->firstOrFail();

        $this->put(route('admin.categories.update', $category), [
            'ten_danh_muc' => 'Áo nữ mới',
            'danh_muc_cha_id' => '',
            'mo_ta' => 'Mô tả đã cập nhật.',
            'trang_thai' => '0',
        ])->assertRedirect(route('admin.categories.index'));

        $this->assertDatabaseHas('danh_mucs', [
            'id' => $category->id,
            'ten_danh_muc' => 'Áo nữ mới',
            'trang_thai' => 0,
        ]);

        $this->delete(route('admin.categories.destroy', $category))
            ->assertRedirect(route('admin.categories.index'));

        $this->assertDatabaseMissing('danh_mucs', ['id' => $category->id]);
    }

    public function test_category_validation_rejects_missing_name_and_invalid_parent(): void
    {
        $this->from(route('admin.categories.create'))
            ->post(route('admin.categories.store'), [
                'ten_danh_muc' => '',
                'danh_muc_cha_id' => 9999,
                'trang_thai' => '1',
            ])
            ->assertRedirect(route('admin.categories.create'))
            ->assertSessionHasErrors(['ten_danh_muc', 'danh_muc_cha_id']);

        $this->assertDatabaseCount('danh_mucs', 0);
    }

    public function test_category_cannot_be_moved_under_its_descendant(): void
    {
        $parent = Category::create(['ten_danh_muc' => 'Thời trang', 'trang_thai' => true]);
        $child = Category::create([
            'ten_danh_muc' => 'Áo nữ',
            'danh_muc_cha_id' => $parent->id,
            'trang_thai' => true,
        ]);

        $this->from(route('admin.categories.edit', $parent))
            ->put(route('admin.categories.update', $parent), [
                'ten_danh_muc' => 'Thời trang',
                'danh_muc_cha_id' => $child->id,
                'trang_thai' => '1',
            ])
            ->assertRedirect(route('admin.categories.edit', $parent))
            ->assertSessionHasErrors('danh_muc_cha_id');

        $this->assertDatabaseHas('danh_mucs', [
            'id' => $parent->id,
            'danh_muc_cha_id' => null,
        ]);
    }

    public function test_category_in_use_cannot_be_deleted(): void
    {
        $category = Category::create(['ten_danh_muc' => 'Áo nữ', 'trang_thai' => true]);
        DB::table('san_phams')->insert([
            'ma_san_pham' => 'AO-NU-001',
            'danh_muc_id' => $category->id,
        ]);

        $this->delete(route('admin.categories.destroy', $category))
            ->assertRedirect(route('admin.categories.index'))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('danh_mucs', ['id' => $category->id]);
    }

    public function test_category_search_and_status_filter_work(): void
    {
        Category::create(['ten_danh_muc' => 'Áo nữ', 'trang_thai' => true]);
        Category::create(['ten_danh_muc' => 'Áo nam', 'trang_thai' => false]);
        Category::create(['ten_danh_muc' => 'Đầm váy', 'trang_thai' => true]);

        $this->get(route('admin.categories.index', ['q' => 'Áo', 'status' => 'active']))
            ->assertOk()
            ->assertSee('Áo nữ')
            ->assertDontSee('Áo nam')
            ->assertDontSee('Đầm váy');
    }
}
