<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Builder;

class Drug extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'price' => 'decimal:2',
        'stock' => 'integer',
        'expiry_date' => 'date',
    ];

    //Disease and Drug relationship
    public function diseases(): BelongsToMany
    {
        return $this->belongsToMany(Disease::class)->withTimestamps();
    }

    //Drug and DrugCategory relationship
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(DrugCategory::class)->withTimestamps();
    }

    // Scopes
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopeInStock(Builder $query): Builder
    {
        return $query->where('stock', '>', 0);
    }

    public function scopeAvailable(Builder $query): Builder
    {
        return $query->active()->inStock();
    }

    public function scopeExpired(Builder $query): Builder
    {
        return $query->where('expiry_date', '<', now());
    }

    public function scopeExpiringSoon(Builder $query, int $days = 30): Builder
    {
        return $query->whereBetween('expiry_date', [now(), now()->addDays($days)]);
    }

    // Methods
    public function isAvailable(): bool
    {
        return $this->status === 'active' && $this->stock > 0;
    }

    public function isInStock(int $quantity = 1): bool
    {
        return $this->stock >= $quantity;
    }

    public function decrementStock(int $quantity): bool
    {
        if ($this->stock >= $quantity) {
            $this->decrement('stock', $quantity);

            // Update status if out of stock
            if ($this->stock <= 0) {
                $this->update(['status' => 'out_of_stock']);
            }

            return true;
        }

        return false;
    }

    public function incrementStock(int $quantity): void
    {
        $this->increment('stock', $quantity);

        // Reactivate if was out of stock
        if ($this->status === 'out_of_stock' && $this->stock > 0) {
            $this->update(['status' => 'active']);
        }
    }

    public function getFormattedPriceAttribute(): string
    {
        return config('payment.currency_symbol') . number_format($this->price, 2);
    }

    public function getIsExpiredAttribute(): bool
    {
        return $this->expiry_date && $this->expiry_date->isPast();
    }
}
