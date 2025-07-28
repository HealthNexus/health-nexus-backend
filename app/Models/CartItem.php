<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CartItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'cart_id',
        'drug_id',
        'quantity',
        'unit_price',
        'total_price',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
    ];

    // Relationships
    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    public function drug(): BelongsTo
    {
        return $this->belongsTo(Drug::class);
    }

    // Methods
    public function updateQuantity(int $quantity): bool
    {
        if ($quantity <= 0) {
            return $this->delete();
        }

        return $this->update([
            'quantity' => $quantity,
            'total_price' => $this->unit_price * $quantity,
        ]);
    }

    public function incrementQuantity(int $amount = 1): bool
    {
        return $this->updateQuantity($this->quantity + $amount);
    }

    public function decrementQuantity(int $amount = 1): bool
    {
        return $this->updateQuantity($this->quantity - $amount);
    }

    public function getFormattedUnitPriceAttribute(): string
    {
        return '₦' . number_format($this->unit_price, 2);
    }

    public function getFormattedTotalPriceAttribute(): string
    {
        return '₦' . number_format($this->total_price, 2);
    }
}
