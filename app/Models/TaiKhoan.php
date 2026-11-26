<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TaiKhoan extends Authenticatable
{
    use Notifiable;

    protected $table = 'tai_khoans';
    protected $fillable = [
        'ho_ten', 'anh_dai_dien', 'ngay_sinh', 'email', 'so_dien_thoai',
        'gioi_tinh', 'mat_khau', 'chuc_vu_id', 'trang_thai', 'ngay_tao',
    ];
    protected $hidden = ['mat_khau'];
    protected $rememberTokenName = null;
    public $timestamps = false;


    protected function casts(): array
    {
        return [
            'ngay_sinh' => 'date', 'ngay_tao' => 'datetime', 'trang_thai' => 'boolean',
            'gioi_tinh' => 'integer', 'mat_khau' => 'hashed',
        ];
    }


    public function getAuthPasswordName(): string { return 'mat_khau'; }
    public function getAuthPassword(): string { return (string) $this->mat_khau; }
    public function getRememberToken(): ?string { return null; }
    public function setRememberToken($value): void { }

    public function chucVu(): BelongsTo { return $this->belongsTo(ChucVu::class, 'chuc_vu_id'); }

    public function isAdmin(): bool
    {
        $role = Str::lower(trim((string) $this->chucVu?->ten_chuc_vu));
        return in_array($role, ['admin', 'administrator', 'quản trị', 'quản trị viên'], true);
    }

    public function avatarUrl(): ?string
    {
        if (! $this->anh_dai_dien) return null;
        return Str::startsWith($this->anh_dai_dien, ['http://', 'https://'])
            ? $this->anh_dai_dien : Storage::disk('public')->url($this->anh_dai_dien);
    }
}
