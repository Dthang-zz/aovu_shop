<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    protected $table = 'danh_gias';

    public $timestamps = false;

    protected $fillable = [
        'phan_hoi',
    ];

    protected function casts(): array
    {
        return [
            'san_pham_id' => 'integer',
            'tai_khoan_id' => 'integer',
            'chi_tiet_don_hang_id' => 'integer',
            'so_sao' => 'integer',
            'ngay_dang' => 'datetime',
        ];
    }
}
