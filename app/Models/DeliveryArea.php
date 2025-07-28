<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DeliveryArea extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'description',
        'base_fee',
        'is_active',
        'sort_order',
        'landmarks',
    ];

    protected $casts = [
        'base_fee' => 'decimal:2',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'landmarks' => 'array',
    ];

    /**
     * Get orders for this delivery area
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'delivery_area', 'code');
    }

    /**
     * Scope for active delivery areas
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for ordering by sort_order
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    /**
     * Get formatted fee
     */
    public function getFormattedFeeAttribute(): string
    {
        return '₵' . number_format($this->base_fee, 2);
    }

    /**
     * Check if area has pending orders
     */
    public function hasPendingOrders(): bool
    {
        return $this->orders()
            ->whereIn('status', ['placed', 'delivering'])
            ->exists();
    }

    /**
     * Get pending orders count
     */
    public function getPendingOrdersCountAttribute(): int
    {
        return $this->orders()
            ->whereIn('status', ['placed', 'delivering'])
            ->count();
    }
}
