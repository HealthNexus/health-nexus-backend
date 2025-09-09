<?php

namespace App\Models;

use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_number',
        'user_id',
        'subtotal',
        'tax_amount',
        'total_amount',
        'total_items',
        'status',
        'status_updated_at',
        'status_updated_by',
        'phone_number',
        'delivery_notes',
        // Delivery fields for in-city delivery
        'delivery_area',
        'delivery_address',
        'landmark',
        'delivery_fee',
        'payment_status',
        'payment_method',
        'payment_reference',
        'placed_at',
        'delivered_at',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'delivery_fee' => 'decimal:2',
        'total_items' => 'integer',
        'status' => OrderStatus::class,
        'status_updated_at' => 'datetime',
        'placed_at' => 'datetime',
        'delivered_at' => 'datetime',
    ];

    // Boot method to generate order number
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($order) {
            if (empty($order->order_number)) {
                $order->order_number = $order->generateOrderNumber();
            }

            if (empty($order->placed_at)) {
                $order->placed_at = now();
            }
        });
    }

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function statusUpdatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'status_updated_by');
    }

    // Methods
    public function generateOrderNumber(): string
    {
        do {
            $orderNumber = 'HN-' . date('Ymd') . '-' . strtoupper(Str::random(6));
        } while (self::where('order_number', $orderNumber)->exists());

        return $orderNumber;
    }

    public function updateStatus(OrderStatus $newStatus, ?User $updatedBy = null): bool
    {
        if (!$this->status->canTransitionTo($newStatus)) {
            throw new \InvalidArgumentException("Cannot transition from {$this->status->value} to {$newStatus->value}");
        }

        $updateData = [
            'status' => $newStatus,
            'status_updated_at' => now(),
            'status_updated_by' => $updatedBy?->id,
        ];

        // Set delivered_at when status becomes delivered
        if ($newStatus === OrderStatus::DELIVERED) {
            $updateData['delivered_at'] = now();
        }

        return $this->update($updateData);
    }

    public function canBeDelivered(): bool
    {
        return $this->status === OrderStatus::DELIVERING;
    }

    public function canBeShipped(): bool
    {
        return $this->status === OrderStatus::PLACED && $this->payment_status === 'paid';
    }

    public function isDelivered(): bool
    {
        return $this->status === OrderStatus::DELIVERED;
    }

    public function getFormattedTotalAttribute(): string
    {
        return config('payment.currency_symbol') . number_format($this->total_amount, 2);
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            OrderStatus::PLACED => 'blue',
            OrderStatus::DELIVERING => 'orange',
            OrderStatus::DELIVERED => 'green',
            OrderStatus::CANCELLED => 'red',
        };
    }

    public function getDaysOldAttribute(): int
    {
        return $this->placed_at->diffInDays(now());
    }

    // Scopes
    public function scopeByStatus($query, OrderStatus $status)
    {
        return $query->where('status', $status);
    }

    public function scopePending($query)
    {
        return $query->where('status', OrderStatus::PLACED);
    }

    public function scopeDelivering($query)
    {
        return $query->where('status', OrderStatus::DELIVERING);
    }

    public function scopeDelivered($query)
    {
        return $query->where('status', OrderStatus::DELIVERED);
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', OrderStatus::CANCELLED);
    }

    // Delivery-specific methods for in-city delivery
    public function scopeByDeliveryArea($query, string $area)
    {
        return $query->where('delivery_area', $area);
    }

    /**
     * Get the delivery area details
     */
    public function deliveryAreaDetails(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(DeliveryArea::class, 'delivery_area', 'code');
    }

    public function getFullDeliveryAddressAttribute(): string
    {
        $address = $this->delivery_address;

        if ($this->landmark) {
            $address .= ' (Near: ' . $this->landmark . ')';
        }

        if ($this->delivery_area) {
            $areaDetails = $this->deliveryAreaDetails;
            if ($areaDetails) {
                $address .= ', ' . $areaDetails->name;
            } else {
                $address .= ', ' . $this->delivery_area;
            }
        }

        return $address;
    }

    public function getTotalWithDeliveryAttribute(): float
    {
        return $this->total_amount + $this->delivery_fee;
    }

    /**
     * Scope a query to only include orders placed within the last X days.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  int  $days
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeRecent($query, $days = 7)
    {
        return $query->where('placed_at', '>=', now()->subDays($days));
    }
}
