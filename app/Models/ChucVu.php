<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChucVu extends Model
{
    protected $table = 'chuc_vus';

    protected $fillable = [
        'ten_chuc_vu',
        'mo_ta',
    ];

    public $timestamps = false;

    public function taiKhoans(): HasMany
    {
        return $this->hasMany(TaiKhoan::class, 'chuc_vu_id');
    }
}
