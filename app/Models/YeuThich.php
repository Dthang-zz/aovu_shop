<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class YeuThich extends Model
{
    protected $table = 'yeu_thichs';

    public $timestamps = false;

    protected $fillable = [
        'tai_khoan_id',
        'san_pham_id',
        'ngay_them',
    ];

    protected function casts(): array
    {
        return [
            'tai_khoan_id' => 'integer',
            'san_pham_id' => 'integer',
            'ngay_them' => 'datetime',
        ];
    }

    public function taiKhoan(): BelongsTo
    {
        return $this->belongsTo(TaiKhoan::class, 'tai_khoan_id');
    }

    public function sanPham(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'san_pham_id');
    }
}
