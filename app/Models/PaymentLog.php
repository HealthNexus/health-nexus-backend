<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'payment_id',
        'event_type',
        'status',
        'request_data',
        'response_data',
        'error_message',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'request_data' => 'array',
        'response_data' => 'array',
    ];

    // Relationships
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }
}
