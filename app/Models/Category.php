<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    protected $table = 'danh_mucs';

    public $timestamps = false;

    protected $fillable = [
        'ten_danh_muc',
        'danh_muc_cha_id',
        'mo_ta',
        'trang_thai',
    ];

    protected function casts(): array
    {
        return [
            'danh_muc_cha_id' => 'integer',
            'trang_thai' => 'boolean',
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'danh_muc_cha_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'danh_muc_cha_id');
    }
}
