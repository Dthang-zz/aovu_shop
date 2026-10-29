<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Post extends Model
{
    protected $table = 'bai_viets';

    protected $fillable = [
        'tieu_de', 'slug', 'mo_ta_ngan', 'noi_dung', 'anh_dai_dien',
        'danh_muc_bai_viet_id', 'tac_gia', 'tu_khoa_seo', 'mo_ta_seo',
        'trang_thai', 'luot_xem', 'published_at',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'luot_xem' => 'integer',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(NewsCategory::class, 'danh_muc_bai_viet_id');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('trang_thai', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }
}