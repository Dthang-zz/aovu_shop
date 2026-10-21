<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TaiKhoan extends Model
{
    protected $table = 'tai_khoans';

    protected $fillable = [
        'ho_ten',
        'anh_dai_dien',
        'ngay_sinh',
        'email',
        'so_dien_thoai',
        'gioi_tinh',
        'mat_khau',
        'chuc_vu_id',
        'trang_thai',
        'ngay_tao',
    ];

    protected $hidden = [
        'mat_khau',
    ];

    protected function casts(): array
    {
        return [
            'ngay_sinh'  => 'date',
            'ngay_tao'   => 'datetime',
            'trang_thai' => 'boolean',
            'gioi_tinh'  => 'integer',
            'mat_khau'   => 'hashed',
        ];
    }

    public $timestamps = false;

    // ─── Relationships ──────────────────────────────────────────

    public function chucVu(): BelongsTo
    {
        return $this->belongsTo(ChucVu::class, 'chuc_vu_id');
    }
}
