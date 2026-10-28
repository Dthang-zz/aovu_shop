<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReturnRequestWorkflow extends Model
{
    protected $table = 'support_return_workflows';

    protected $fillable = [
        'return_request_id',
        'conversation_id',
        'customer_agreed',
        'customer_agreed_at',
        'policy_reviewed',
        'policy_notes',
        'collection_waived',
        'received_at',
        'inspection_notes',
        'replacement_variant_id',
        'replacement_name',
        'replacement_price',
        'price_difference',
        'price_difference_settled',
        'price_difference_note',
        'tracking_code',
        'delivered_at',
        'refund_amount',
        'refund_method',
        'refund_reference',
        'refunded_at',
    ];

    protected function casts(): array
    {
        return [
            'customer_agreed' => 'boolean',
            'customer_agreed_at' => 'datetime',
            'policy_reviewed' => 'boolean',
            'collection_waived' => 'boolean',
            'received_at' => 'datetime',
            'replacement_price' => 'integer',
            'price_difference' => 'integer',
            'price_difference_settled' => 'boolean',
            'delivered_at' => 'datetime',
            'refund_amount' => 'integer',
            'refunded_at' => 'datetime',
        ];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(SupportConversation::class);
    }

    public function returnRequest(): BelongsTo
    {
        return $this->belongsTo(ReturnRequest::class, 'return_request_id');
    }
}
