<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductVariant extends Model
{
    protected $table = 'product_variants';

    protected $fillable = [
        'product_id', 'sku', 'color', 'size', 'price', 'stock', 'status', 'attribute_values',
    ];

    protected function casts(): array
    {
        return [
            'product_id' => 'integer',
            'price' => 'integer',
            'stock' => 'integer',
            'attribute_values' => 'array',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function getAttributeLabelAttribute(): string
    {
        $attributes = $this->attribute_values ?? [];
        return collect($attributes)->map(fn ($value, $name) => $name . ': ' . $value)->implode(' / ');
    }
}
