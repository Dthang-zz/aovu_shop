<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChucVu extends Model
{
    protected $table = 'chuc_vus';

    public $timestamps = false;

    protected $fillable = [
        'ten_chuc_vu',
        'mo_ta',
    ];
}
