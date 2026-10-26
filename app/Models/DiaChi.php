<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DiaChi extends Model
{
    protected $table = 'dia_chis';

    public $timestamps = false;

    protected $fillable = [
        'tai_khoan_id',
        'ten_nguoi_nhan',
        'sdt_nguoi_nhan',
        'tinh_thanh',
        'quan_huyen',
        'phuong_xa',
        'dia_chi_chi_tiet',
        'mac_dinh',
    ];

    protected function casts(): array
    {
        return [
            'mac_dinh' => 'boolean',
            'tai_khoan_id' => 'integer',
        ];
    }

    public function taiKhoan(): BelongsTo
    {
        return $this->belongsTo(TaiKhoan::class, 'tai_khoan_id');
    }

    /**
     * Địa chỉ đầy đủ
     */
    public function getDiaChiDayDuAttribute(): string
    {
        $parts = array_filter([
            $this->dia_chi_chi_tiet,
            $this->phuong_xa,
            $this->quan_huyen,
            $this->tinh_thanh,
        ]);

        return implode(', ', $parts);
    }
}
