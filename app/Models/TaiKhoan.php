<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

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
            'ngay_sinh' => 'date',
            'ngay_tao' => 'datetime',
            'trang_thai' => 'boolean',
            'gioi_tinh' => 'integer',
            'mat_khau' => 'hashed',
        ];
    }

    public $timestamps = false;

    // ─── Relationships ──────────────────────────────────────────

    public function chucVu(): BelongsTo
    {
        return $this->belongsTo(ChucVu::class, 'chuc_vu_id');
    }

    public function diaChis(): HasMany
    {
        return $this->hasMany(DiaChi::class, 'tai_khoan_id');
    }

    public function diaChiMacDinh(): HasOne
    {
        return $this->hasOne(DiaChi::class, 'tai_khoan_id')->where('mac_dinh', true);
    }

    public function yeuThichs(): HasMany
    {
        return $this->hasMany(YeuThich::class, 'tai_khoan_id');
    }

    public function sanPhamYeuThichs(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'yeu_thichs', 'tai_khoan_id', 'san_pham_id')
            ->withPivot('ngay_them');
    }

    public function donHangs(): HasMany
    {
        return $this->hasMany(Order::class, 'tai_khoan_id');
    }

    public function avatarUrl(): ?string
    {
        if (! $this->anh_dai_dien) {
            return null;
        }

        return Str::startsWith($this->anh_dai_dien, ['http://', 'https://'])
            ? $this->anh_dai_dien
            : Storage::disk('public')->url($this->anh_dai_dien);
    }
}
