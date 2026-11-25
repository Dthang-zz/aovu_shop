<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Str;

class TaiKhoan extends Authenticatable
{
    protected $table = 'tai_khoans';

    public $timestamps = false;

    protected $fillable = [
        'ho_ten',
        'email',
        'mat_khau',
    ];

    protected $hidden = [
        'mat_khau',
    ];

    protected function casts(): array
    {
        return [
            'mat_khau' => 'hashed',
            'ngay_tao' => 'datetime',
        ];
    }

    public function getAuthPasswordName(): string
    {
        return 'mat_khau';
    }

    public function chucVu(): BelongsTo
    {
        return $this->belongsTo(ChucVu::class, 'chuc_vu_id');
    }

    public function isAdmin(): bool
    {
        return in_array(Str::lower(trim((string) $this->chucVu?->ten_chuc_vu)), [
            'admin',
            'administrator',
            'quản trị',
            'quản trị viên',
        ], true);
    }
}
