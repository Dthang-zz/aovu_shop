<?php

namespace App\Models;

use Database\Factories\SupportConversationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class SupportConversation extends Model
{
    /** @use HasFactory<SupportConversationFactory> */
    use HasFactory;

    public const STATUS_OPEN = 'open';

    public const STATUS_CLOSED = 'closed';

    protected $fillable = [
        'visitor_token_hash',
        'customer_name',
        'customer_email',
        'status',
    ];

    public function messages(): HasMany
    {
        return $this->hasMany(SupportMessage::class);
    }

    public function latestMessage(): HasOne
    {
        return $this->hasOne(SupportMessage::class)->latestOfMany();
    }

    public function returnWorkflows(): HasMany
    {
        return $this->hasMany(ReturnRequestWorkflow::class, 'conversation_id');
    }
}
