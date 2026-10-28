<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReturnRequestHistory extends Model
{
    protected $table = 'support_return_histories';

    public $timestamps = false;

    protected $fillable = [
        'return_request_id',
        'from_status',
        'to_status',
        'actor_name',
        'note',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'from_status' => 'integer',
            'to_status' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    public function returnRequest(): BelongsTo
    {
        return $this->belongsTo(ReturnRequest::class, 'return_request_id');
    }
}
