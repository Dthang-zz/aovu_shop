<?php

namespace App\Models;

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
}
