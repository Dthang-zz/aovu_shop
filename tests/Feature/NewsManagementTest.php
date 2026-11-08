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
            'content' => '<p>Nội dung đã được cập nhật.</p>',
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
            'content' => '<p>Nội dung nháp</p><script>alert("xss")</script>',
            'status' => NewsArticle::STATUS_DRAFT,
        ]);

        $this->get(route('admin.news.preview', $article))
            ->assertOk()
            ->assertSee('Bản xem trước')
            ->assertSee('<p>Nội dung nháp</p>', false)
            ->assertDontSee('alert("xss")', false);

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

    public function test_article_form_uses_the_rich_text_editor(): void
    {
        $article = $this->createArticle(null, ['content' => "Dòng một\nDòng hai\n\nĐoạn mới"]);

        $this->get(route('admin.news.create'))
            ->assertOk()
            ->assertSee('summernote-bs4.min.js')
            ->assertSee('summernote-bs4.min.css')
            ->assertSee('data-article-editor', false)
            ->assertSee('data-upload-url="'.route('admin.news.images.store').'"', false);
        $this->get(route('admin.news.edit', $article))
            ->assertOk()
            ->assertSee('&lt;p&gt;Dòng một&lt;br&gt;'."\n".'Dòng hai&lt;/p&gt;&lt;p&gt;Đoạn mới&lt;/p&gt;', false);
    }

    public function test_editor_html_is_kept_formatted_and_stripped_of_scripts_when_saved_and_shown(): void
    {
        $this->post(route('admin.news.store'), [
            'title' => 'Bài có định dạng',
            'content' => '<h2 style="text-align: center; position: fixed">Tiêu đề</h2>'
                .'<p><strong>Đậm</strong> và <a href="https://aovu.vn" target="_blank" onclick="steal()">liên kết</a></p>'
                .'<p><img src="/storage/news/content/a.jpg" onerror="alert(1)" style="width: 50%"></p>'
                .'<p><a href="javascript:alert(1)">xấu</a></p><script>alert(2)</script><iframe src="https://evil.test"></iframe>',
            'status' => NewsArticle::STATUS_PUBLISHED,
        ])->assertRedirect(route('admin.news.index'));

        $article = NewsArticle::query()->firstOrFail();
        $this->assertSame(
            '<h2 style="text-align: center">Tiêu đề</h2>'
            .'<p><strong>Đậm</strong> và <a href="https://aovu.vn" target="_blank" rel="noopener noreferrer">liên kết</a></p>'
            .'<p><img src="/storage/news/content/a.jpg" style="width: 50%"></p>'
            .'<p><a>xấu</a></p>',
            $article->content,
        );

        $this->get(route('news.show', $article->slug))
            ->assertOk()
            ->assertSee('<strong>Đậm</strong>', false)
            ->assertSee('<img src="/storage/news/content/a.jpg" style="width: 50%">', false)
            ->assertDontSee('onerror', false)
            ->assertDontSee('evil.test', false)
            ->assertDontSee('javascript:alert', false);
    }

    public function test_legacy_plain_text_and_stored_unsafe_html_are_rendered_safely(): void
    {
        $category = NewsCategory::create(['name' => 'Tin', 'slug' => 'tin', 'is_active' => true]);
        $plain = $this->createArticle($category, ['slug' => 'bai-cu', 'content' => "Dòng một\nDòng <hai>\n\nĐoạn mới", 'status' => NewsArticle::STATUS_PUBLISHED, 'published_at' => now()]);
        $unsafe = $this->createArticle($category, ['slug' => 'bai-loi', 'content' => '<p onmouseover="x()">Chữ</p><img src="x" onerror="alert(1)"><svg onload="alert(1)"></svg>', 'status' => NewsArticle::STATUS_PUBLISHED, 'published_at' => now()]);

        $this->get(route('news.show', $plain->slug))
            ->assertOk()
            ->assertSee('<p>Dòng một<br>'."\n".'Dòng &lt;hai&gt;</p><p>Đoạn mới</p>', false);
        $this->get(route('news.show', $unsafe->slug))
            ->assertOk()
            ->assertSee('<p>Chữ</p><img src="x">', false)
            ->assertDontSee('onmouseover', false)
            ->assertDontSee('onerror', false)
            ->assertDontSee('<svg', false);
    }

    public function test_title_excerpt_and_seo_fields_use_compact_editors(): void
    {
        $this->get(route('admin.news.create'))
            ->assertOk()
            ->assertSee('id="title" name="title" rows="1" class="form-control " data-inline-editor data-single-line data-max-length="255"', false)
            ->assertSee('id="excerpt" name="excerpt" rows="3" class="form-control " data-inline-editor data-max-length="500"', false)
            ->assertSee('id="seo_title" name="seo_title" rows="1" class="form-control " data-inline-editor data-single-line', false)
            ->assertSee('id="seo_description" name="seo_description" rows="3" class="form-control " data-inline-editor', false);
    }

    public function test_short_fields_keep_inline_formatting_on_the_page_but_meta_tags_and_slug_get_plain_text(): void
    {
        $this->post(route('admin.news.store'), [
            'title' => '<p>Bộ sưu tập <b>Thu</b> <a href="https://evil.test">mới</a><script>x()</script></p>',
            'excerpt' => '<p><i>Dòng một</i></p><p>Dòng <span style="color: red; position: fixed">hai</span></p><p><img src="/a.png"></p>',
            'seo_title' => '<p><b>SEO</b> &amp; thời trang</p>',
            'seo_description' => '<p>Mô tả <u>SEO</u></p><p>dòng 2</p>',
            'content' => '<p>Nội dung</p>',
            'status' => NewsArticle::STATUS_PUBLISHED,
        ])->assertRedirect(route('admin.news.index'));

        $article = NewsArticle::query()->firstOrFail();
        $this->assertSame('Bộ sưu tập <b>Thu</b> mới', $article->title);
        $this->assertSame('<i>Dòng một</i><br>Dòng <span style="color: red">hai</span>', $article->excerpt);
        $this->assertSame('bo-suu-tap-thu-moi', $article->slug);

        $this->get(route('news.show', $article->slug))
            ->assertOk()
            ->assertSee('<title>SEO &amp; thời trang | AOVU Shop</title>', false)
            ->assertSee('<meta name="description" content="Mô tả SEO dòng 2">', false)
            ->assertSee('<h1 class="mt-2 mb-2">Bộ sưu tập <b>Thu</b> mới</h1>', false)
            ->assertSee('<p class="lead"><i>Dòng một</i><br>Dòng <span style="color: red">hai</span></p>', false)
            ->assertDontSee('evil.test', false);
        $this->get(route('news.index'))
            ->assertSee('Bộ sưu tập <b>Thu</b> mới</a>', false);
    }

    public function test_short_field_limits_count_visible_text_and_formatted_blank_title_is_rejected(): void
    {
        $this->from(route('admin.news.create'))
            ->post(route('admin.news.store'), [
                'title' => '<p><br></p>',
                'seo_title' => '<b>'.str_repeat('a', 256).'</b>',
                'excerpt' => '<p>'.str_repeat('b', 249).'</p><p><strong>'.str_repeat('c', 250).'</strong></p>',
                'content' => '<p>Nội dung</p>',
                'status' => NewsArticle::STATUS_DRAFT,
            ])
            ->assertRedirect(route('admin.news.create'))
            ->assertSessionHasErrors([
                'title' => 'Vui lòng nhập tiêu đề bài viết.',
                'seo_title' => 'Tiêu đề SEO không được vượt quá 255 ký tự (hiện có 256).',
            ])
            ->assertSessionDoesntHaveErrors('excerpt');

        $this->assertDatabaseCount('news_articles', 0);
    }

    public function test_meta_tags_fall_back_to_the_plain_title_and_excerpt(): void
    {
        $article = $this->createArticle(null, [
            'slug' => 'khong-seo',
            'title' => 'Tiêu đề <b>đậm</b>',
            'excerpt' => '<i>Mô tả</i> ngắn',
            'status' => NewsArticle::STATUS_PUBLISHED,
            'published_at' => now(),
        ]);

        $this->get(route('news.show', $article->slug))
            ->assertSee('<title>Tiêu đề đậm | AOVU Shop</title>', false)
            ->assertSee('<meta name="description" content="Mô tả ngắn">', false);
    }

    public function test_empty_editor_content_is_rejected(): void
    {
        $this->from(route('admin.news.create'))
            ->post(route('admin.news.store'), [
                'title' => 'Bài trống',
                'content' => '<p><br></p><p>&nbsp;</p>',
                'status' => NewsArticle::STATUS_DRAFT,
            ])
            ->assertRedirect(route('admin.news.create'))
            ->assertSessionHasErrors(['content' => 'Vui lòng nhập nội dung bài viết.']);

        $this->assertDatabaseCount('news_articles', 0);
    }

    public function test_editor_image_upload_stores_the_image_and_rejects_other_files(): void
    {
        Storage::fake('public');

        $response = $this->postJson(route('admin.news.images.store'), [
            'image' => UploadedFile::fake()->image('ao-thu.png'),
        ])->assertCreated();

        $url = $response->json('url');
        $this->assertStringStartsWith('/storage/news/content/', $url);
        Storage::disk('public')->assertExists(str_replace('/storage/', '', $url));

        $this->postJson(route('admin.news.images.store'), [
            'image' => UploadedFile::fake()->create('script.php', 10, 'application/x-php'),
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['image' => 'Tệp tải lên phải là hình ảnh.']);
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
