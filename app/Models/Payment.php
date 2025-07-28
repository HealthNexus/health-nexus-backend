<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'user_id',
        'payment_reference',
        'paystack_reference',
        'access_code',
        'amount',
        'currency',
        'status',
        'payment_method',
        'channel',
        'gateway_response',
        'gateway_metadata',
        'fees',
        'authorization_code',
        'last4',
        'exp_month',
        'exp_year',
        'card_type',
        'bank',
        'paid_at',
        'failed_at',
        'refunded_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'fees' => 'decimal:2',
        'gateway_response' => 'array',
        'gateway_metadata' => 'array',
        'paid_at' => 'datetime',
        'failed_at' => 'datetime',
        'refunded_at' => 'datetime',
    ];

    // Relationships
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(PaymentLog::class);
    }

    // Methods
    public function generatePaymentReference(): string
    {
        do {
            $reference = 'HN-PAY-' . date('Ymd') . '-' . strtoupper(\Illuminate\Support\Str::random(8));
        } while (self::where('payment_reference', $reference)->exists());

        return $reference;
    }

    public function markAsPaid(array $gatewayData = []): bool
    {
        $updateData = [
            'status' => 'success',
            'paid_at' => now(),
            'gateway_response' => $gatewayData['gateway_response'] ?? null,
            'gateway_metadata' => $gatewayData,
            'fees' => $gatewayData['fees'] ?? null,
            'authorization_code' => $gatewayData['authorization']['authorization_code'] ?? null,
            'last4' => $gatewayData['authorization']['last4'] ?? null,
            'exp_month' => $gatewayData['authorization']['exp_month'] ?? null,
            'exp_year' => $gatewayData['authorization']['exp_year'] ?? null,
            'card_type' => $gatewayData['authorization']['card_type'] ?? null,
            'bank' => $gatewayData['authorization']['bank'] ?? null,
            'channel' => $gatewayData['channel'] ?? null,
        ];

        return $this->update($updateData);
    }

    public function markAsFailed(?string $reason = null): bool
    {
        return $this->update([
            'status' => 'failed',
            'failed_at' => now(),
            'gateway_response' => $reason,
        ]);
    }

    public function markAsRefunded(): bool
    {
        return $this->update([
            'status' => 'refunded',
            'refunded_at' => now(),
        ]);
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isSuccessful(): bool
    {
        return $this->status === 'success';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function isRefunded(): bool
    {
        return $this->status === 'refunded';
    }

    public function getFormattedAmountAttribute(): string
    {
        return config('payment.currency_symbol') . number_format($this->amount, 2);
    }

    public function getFormattedFeesAttribute(): string
    {
        return $this->fees ? config('payment.currency_symbol') . number_format($this->fees, 2) : config('payment.currency_symbol') . '0.00';
    }

    // Scopes
    public function scopeSuccessful($query)
    {
        return $query->where('status', 'success');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }
}
