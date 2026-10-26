<?php

namespace Tests\Feature;

use App\Models\NewsArticle;
use App\Models\NewsCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class NewsManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_filter_articles_by_search_category_and_status(): void
    {
        $category = NewsCategory::create([
            'name' => 'Mẹo phối đồ',
            'slug' => 'meo-phoi-do',
            'is_active' => true,
        ]);
        $matchingArticle = $this->createArticle($category, [
            'title' => 'Bí quyết phối áo linen',
            'slug' => 'bi-quyet-phoi-ao-linen',
            'status' => NewsArticle::STATUS_DRAFT,
        ]);
        $this->createArticle($category, [
            'title' => 'Tin mới của cửa hàng',
            'slug' => 'tin-moi-cua-hang',
            'status' => NewsArticle::STATUS_PUBLISHED,
        ]);
        $otherCategory = NewsCategory::create([
            'name' => 'Tin cửa hàng',
            'slug' => 'tin-cua-hang',
            'is_active' => true,
        ]);
        $this->createArticle($otherCategory, [
            'title' => 'Bí quyết phối màu',
            'slug' => 'bi-quyet-phoi-mau',
            'status' => NewsArticle::STATUS_DRAFT,
        ]);

        $this->get(route('admin.news.index', [
            'q' => 'Bí quyết',
            'category_id' => $category->id,
            'status' => NewsArticle::STATUS_DRAFT,
        ]))
            ->assertOk()
            ->assertSee($matchingArticle->title)
            ->assertDontSee('Tin mới của cửa hàng')
            ->assertDontSee('Bí quyết phối màu');
    }

    public function test_admin_can_create_and_update_article_content_image_and_seo(): void
    {
        Storage::fake('public');
        $this->get(route('admin.news.create'))
            ->assertOk()
            ->assertSee('Nội dung bài viết');
        $category = NewsCategory::create([
            'name' => 'Tin thời trang',
            'slug' => 'tin-thoi-trang',
            'is_active' => true,
        ]);

        $this->post(route('admin.news.store'), [
            'title' => 'Xu hướng thời trang mùa thu',
            'slug' => '',
            'excerpt' => 'Những xu hướng nổi bật trong mùa thu.',
            'content' => 'Nội dung bài viết mùa thu.',
            'category_id' => $category->id,
            'status' => NewsArticle::STATUS_PUBLISHED,
            'seo_title' => 'Xu hướng mùa thu 2026',
            'seo_description' => 'Cập nhật các xu hướng thời trang mùa thu.',
            'featured_image' => UploadedFile::fake()->image('autumn.jpg'),
        ])->assertRedirect(route('admin.news.index'))
            ->assertSessionHas('success');

        $article = NewsArticle::query()->firstOrFail();
        $originalImage = $article->featured_image;
        $this->assertSame('xu-huong-thoi-trang-mua-thu', $article->slug);
        $this->assertSame(NewsArticle::STATUS_PUBLISHED, $article->status);
        $this->assertNotNull($article->published_at);
        Storage::disk('public')->assertExists($originalImage);
        $this->get(route('admin.news.edit', $article))
            ->assertOk()
            ->assertSee('Thông tin SEO');

        $this->put(route('admin.news.update', $article), [
            'title' => 'Xu hướng thời trang mùa đông',
            'slug' => 'xu-huong-mua-dong',
            'excerpt' => 'Xu hướng nổi bật mùa đông.',
            'content' => 'Nội dung đã được cập nhật.',
            'category_id' => $category->id,
            'status' => NewsArticle::STATUS_DRAFT,
            'seo_title' => 'SEO mùa đông',
            'seo_description' => 'Mô tả SEO mới.',
            'featured_image' => UploadedFile::fake()->image('winter.jpg'),
        ])->assertRedirect(route('admin.news.index'))
            ->assertSessionHas('success');

        $article->refresh();
        $this->assertDatabaseHas('news_articles', [
            'id' => $article->id,
            'title' => 'Xu hướng thời trang mùa đông',
            'slug' => 'xu-huong-mua-dong',
            'content' => 'Nội dung đã được cập nhật.',
            'status' => NewsArticle::STATUS_DRAFT,
            'seo_title' => 'SEO mùa đông',
            'seo_description' => 'Mô tả SEO mới.',
        ]);
        Storage::disk('public')->assertMissing($originalImage);
        Storage::disk('public')->assertExists($article->featured_image);
    }

    public function test_admin_can_preview_draft_article_but_draft_is_not_public(): void
    {
        $article = $this->createArticle(null, [
            'title' => 'Bản nháp nội bộ',
            'slug' => 'ban-nhap-noi-bo',
            'content' => '<script>alert("xss")</script>',
            'status' => NewsArticle::STATUS_DRAFT,
        ]);

        $this->get(route('admin.news.preview', $article))
            ->assertOk()
            ->assertSee('Bản xem trước')
            ->assertSee('&lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;', false)
            ->assertDontSee('<script>alert("xss")</script>', false);

        $this->get(route('news.show', $article->slug))->assertNotFound();
        $this->get(route('news.index'))->assertDontSee($article->title);
    }

    public function test_public_news_shows_published_articles_and_categories_only(): void
    {
        $category = NewsCategory::create([
            'name' => 'Tin cửa hàng',
            'slug' => 'tin-cua-hang',
            'is_active' => true,
        ]);
        $publishedArticle = $this->createArticle($category, [
            'title' => 'Khai trương bộ sưu tập mới',
            'slug' => 'khai-truong-bo-suu-tap-moi',
            'excerpt' => 'Bộ sưu tập mới đã có mặt.',
            'content' => 'Thông tin về bộ sưu tập.',
            'status' => NewsArticle::STATUS_PUBLISHED,
        ]);
        $this->createArticle($category, [
            'title' => 'Tin nháp không công khai',
            'slug' => 'tin-nhap-khong-cong-khai',
            'status' => NewsArticle::STATUS_DRAFT,
        ]);
        $hiddenArticle = $this->createArticle($category, [
            'title' => 'Tin đang ẩn',
            'slug' => 'tin-dang-an',
            'status' => NewsArticle::STATUS_HIDDEN,
        ]);

        $this->get(route('news.index'))
            ->assertOk()
            ->assertSee($publishedArticle->title)
            ->assertSee('Tin cửa hàng')
            ->assertDontSee('Tin nháp không công khai')
            ->assertDontSee($hiddenArticle->title);

        $this->get(route('news.show', $publishedArticle->slug))
            ->assertOk()
            ->assertSee('Thông tin về bộ sưu tập.');
        $this->get(route('news.show', $hiddenArticle->slug))->assertNotFound();
    }

    public function test_admin_can_manage_news_categories_and_cannot_delete_a_category_in_use(): void
    {
        $this->get(route('admin.news-categories.create'))
            ->assertOk()
            ->assertSee('Thông tin danh mục');
        $this->post(route('admin.news-categories.store'), [
            'name' => 'Mẹo phối đồ',
            'slug' => '',
            'description' => 'Gợi ý phối đồ hằng ngày.',
            'is_active' => '1',
        ])->assertRedirect(route('admin.news-categories.index'))
            ->assertSessionHas('success');

        $category = NewsCategory::query()->firstOrFail();
        $this->assertSame('meo-phoi-do', $category->slug);
        $this->get(route('admin.news-categories.edit', $category))
            ->assertOk()
            ->assertSee('Mẹo phối đồ');

        $this->put(route('admin.news-categories.update', $category), [
            'name' => 'Mẹo mặc đẹp',
            'slug' => 'meo-mac-dep',
            'description' => 'Mô tả đã sửa.',
            'is_active' => '0',
        ])->assertRedirect(route('admin.news-categories.index'));

        $this->assertDatabaseHas('news_categories', [
            'id' => $category->id,
            'name' => 'Mẹo mặc đẹp',
            'slug' => 'meo-mac-dep',
            'is_active' => 0,
        ]);

        $article = $this->createArticle($category, [
            'title' => 'Bài vẫn sử dụng danh mục',
            'slug' => 'bai-van-su-dung-danh-muc',
        ]);
        $this->delete(route('admin.news-categories.destroy', $category))
            ->assertRedirect(route('admin.news-categories.index'))
            ->assertSessionHas('error');
        $this->assertDatabaseHas('news_articles', ['id' => $article->id]);

        $article->delete();
        $this->delete(route('admin.news-categories.destroy', $category))
            ->assertRedirect(route('admin.news-categories.index'))
            ->assertSessionHas('success');
        $this->assertDatabaseMissing('news_categories', ['id' => $category->id]);
    }

    public function test_article_validation_rejects_missing_fields_and_duplicate_slug(): void
    {
        $this->from(route('admin.news.create'))
            ->post(route('admin.news.store'), [
                'title' => '',
                'content' => '',
                'status' => 'invalid',
            ])
            ->assertRedirect(route('admin.news.create'))
            ->assertSessionHasErrors(['title', 'content', 'status']);

        $this->createArticle(null, [
            'title' => 'Bài viết đầu tiên',
            'slug' => 'bai-viet-trung-slug',
        ]);

        $this->from(route('admin.news.create'))
            ->post(route('admin.news.store'), [
                'title' => 'Bài viết thứ hai',
                'slug' => 'bai-viet-trung-slug',
                'content' => 'Nội dung hợp lệ.',
                'status' => NewsArticle::STATUS_DRAFT,
            ])
            ->assertRedirect(route('admin.news.create'))
            ->assertSessionHasErrors('slug');

        $this->assertDatabaseCount('news_articles', 1);
    }

    public function test_article_validation_errors_are_displayed_in_vietnamese(): void
    {
        $this->from(route('admin.news.create'))
            ->post(route('admin.news.store'), [
                'title' => 'Bài viết thử',
                'slug' => 'duong dan sai',
                'content' => 'Nội dung hợp lệ.',
                'status' => NewsArticle::STATUS_DRAFT,
            ])
            ->assertRedirect(route('admin.news.create'))
            ->assertSessionHasErrors([
                'slug' => 'đường dẫn (slug) chỉ được chứa chữ cái, chữ số, dấu gạch ngang và dấu gạch dưới.',
            ]);

        $this->from(route('admin.news.create'))
            ->post(route('admin.news.store'), [
                'title' => 'Bài viết thử',
                'slug' => 'bai-viet-thu',
                'content' => 'Nội dung hợp lệ.',
                'status' => NewsArticle::STATUS_DRAFT,
                'featured_image' => UploadedFile::fake()->image('qua-lon.jpg')->size(5121),
            ])
            ->assertRedirect(route('admin.news.create'))
            ->assertSessionHasErrors([
                'featured_image' => 'ảnh đại diện không được lớn hơn 5120 kilobyte.',
            ]);
    }

    public function test_deleting_article_removes_its_featured_image_after_confirmation(): void
    {
        Storage::fake('public');
        $image = UploadedFile::fake()->image('article.jpg');
        $path = $image->store('news', 'public');
        $article = $this->createArticle(null, [
            'title' => 'Bài cần xóa',
            'slug' => 'bai-can-xoa',
            'featured_image' => $path,
        ]);

        $this->get(route('admin.news.index'))
            ->assertOk()
            ->assertSee('target="_blank" rel="noopener"', false)
            ->assertSee('confirm(', false);

        $this->delete(route('admin.news.destroy', $article))
            ->assertRedirect(route('admin.news.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('news_articles', ['id' => $article->id]);
        Storage::disk('public')->assertMissing($path);
    }

    private function createArticle(?NewsCategory $category, array $attributes = []): NewsArticle
    {
        return NewsArticle::query()->create(array_merge([
            'category_id' => $category?->id,
            'title' => 'Bài viết thử nghiệm',
            'slug' => 'bai-viet-thu-nghiem',
            'excerpt' => 'Mô tả ngắn.',
            'content' => 'Nội dung bài viết.',
            'status' => NewsArticle::STATUS_DRAFT,
        ], $attributes));
    }
}
