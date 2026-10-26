<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $table = 'san_phams';

    public $timestamps = false;

    protected $fillable = [
        'ma_san_pham',
        'ten_san_pham',
        'gia_san_pham',
        'gia_khuyen_mai',
        'hinh_anh',
        'mo_ta',
        'chat_lieu',
        'huong_dan_su_dung',
        'noi_bat',
        'ngay_nhap',
        'danh_muc_id',
        'trang_thai',
    ];

    protected function casts(): array
    {
        return [
            'gia_san_pham' => 'integer',
            'gia_khuyen_mai' => 'integer',
            'noi_bat' => 'boolean',
            'ngay_nhap' => 'datetime',
            'danh_muc_id' => 'integer',
            'trang_thai' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'danh_muc_id');
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class, 'san_pham_id')->orderBy('thu_tu');
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class, 'product_id', 'id');
    }

    public function promotions(): BelongsToMany
    {
        return $this->belongsToMany(Promotion::class, 'promotion_products', 'product_id', 'promotion_id');
    }
}
