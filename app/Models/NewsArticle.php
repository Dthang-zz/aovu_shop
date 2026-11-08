<?php

namespace App\Models;

use App\Services\ArticleHtmlSanitizer;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NewsArticle extends Model
{
    use HasFactory;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_PUBLISHED = 'published';

    public const STATUS_HIDDEN = 'hidden';

    public const STATUSES = [
        self::STATUS_DRAFT => 'Bản nháp',
        self::STATUS_PUBLISHED => 'Đã xuất bản',
        self::STATUS_HIDDEN => 'Đang ẩn',
    ];

    protected $fillable = [
        'category_id',
        'title',
        'slug',
        'excerpt',
        'content',
        'featured_image',
        'seo_title',
        'seo_description',
        'status',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (NewsArticle $article): void {
            if ($article->status === self::STATUS_PUBLISHED && $article->published_at === null) {
                $article->published_at = now();
            }
        });
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(NewsCategory::class);
    }

    /**
     * Article body as safe HTML: editor content is cleaned, legacy plain text becomes paragraphs.
     */
    public function contentHtml(): string
    {
        return app(ArticleHtmlSanitizer::class)->sanitize($this->content);
    }

    /**
     * Title with its inline formatting, safe to print unescaped (headings, lists).
     */
    public function titleHtml(): string
    {
        return app(ArticleHtmlSanitizer::class)->sanitizeInline($this->title);
    }

    /**
     * Excerpt with its inline formatting and line breaks, safe to print unescaped.
     */
    public function excerptHtml(): string
    {
        return app(ArticleHtmlSanitizer::class)->sanitizeInline($this->excerpt, multiline: true);
    }

    /**
     * Title without formatting, for alt texts and anywhere only text is allowed.
     */
    public function plainTitle(): string
    {
        return app(ArticleHtmlSanitizer::class)->plainText($this->title);
    }

    /**
     * Text for the page <title>: the SEO title, or the article title.
     */
    public function metaTitle(): string
    {
        $sanitizer = app(ArticleHtmlSanitizer::class);

        return $sanitizer->plainText($this->seo_title) ?: $sanitizer->plainText($this->title);
    }

    /**
     * Text for <meta name="description">: the SEO description, or the excerpt.
     */
    public function metaDescription(): string
    {
        $sanitizer = app(ArticleHtmlSanitizer::class);

        return $sanitizer->plainText($this->seo_description) ?: $sanitizer->plainText($this->excerpt);
    }
}
