<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductImage extends Model
{
    protected $table = 'hinh_anh_san_phams';

    public $timestamps = false;

    protected $fillable = [
        'san_pham_id',
        'link_hinh_anh',
        'thu_tu',
    ];

    protected function casts(): array
    {
        return [
            'san_pham_id' => 'integer',
            'thu_tu' => 'integer',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'san_pham_id');
    }
}
