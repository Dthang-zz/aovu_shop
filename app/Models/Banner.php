<?php

namespace App\Models;

use Database\Factories\BannerFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Banner extends Model
{
    /** @use HasFactory<BannerFactory> */
    use HasFactory;

    protected $fillable = [
        'ten_banner',
        'hinh_anh',
        'lien_ket',
        'thu_tu',
        'trang_thai',
    ];

    protected function casts(): array
    {
        return [
            'thu_tu' => 'integer',
            'trang_thai' => 'boolean',
        ];
    }
}
