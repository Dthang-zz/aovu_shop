<?php

namespace App\Models;

use Database\Factories\SupportMessageFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupportMessage extends Model
{
    /** @use HasFactory<SupportMessageFactory> */
    use HasFactory;

    public const SENDER_CUSTOMER = 'customer';

    public const SENDER_STAFF = 'staff';

    protected $fillable = [
        'sender_type',
        'sender_name',
        'body',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(SupportConversation::class);
    }
}
