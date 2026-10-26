<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Voucher extends Model
{
    protected $table = 'vouchers';

    public $timestamps = false;

    // Loại giảm: 1 = Phần trăm (%), 2 = Tiền cố định (VNĐ)
    public const TYPE_PERCENT = 1;
    public const TYPE_FIXED = 2;

    protected $fillable = [
        'ma_voucher',
        'mo_ta',
        'loai_giam',
        'gia_tri_giam',
        'giam_toi_da',
        'don_toi_thieu',
        'so_luong',
        'da_su_dung',
        'ngay_bat_dau',
        'ngay_ket_thuc',
        'trang_thai',
    ];

    protected function casts(): array
    {
        return [
            'loai_giam' => 'integer',
            'gia_tri_giam' => 'decimal:0',
            'giam_toi_da' => 'decimal:0',
            'don_toi_thieu' => 'decimal:0',
            'so_luong' => 'integer',
            'da_su_dung' => 'integer',
            'ngay_bat_dau' => 'datetime',
            'ngay_ket_thuc' => 'datetime',
            'trang_thai' => 'boolean',
        ];
    }

    public function getGiaTriFormatAttribute(): string
    {
        if ($this->loai_giam === self::TYPE_PERCENT) {
            return (int)$this->gia_tri_giam . '%';
        }

        return '₫ ' . number_format($this->gia_tri_giam);
    }

    public function isValid(): bool
    {
        if (!$this->trang_thai) return false;
        if ($this->ngay_bat_dau && now()->lt($this->ngay_bat_dau)) return false;
        if ($this->ngay_ket_thuc && now()->gt($this->ngay_ket_thuc)) return false;
        if ($this->so_luong > 0 && $this->da_su_dung >= $this->so_luong) return false;

        return true;
    }
}
