<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cart extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'subtotal',
        'tax_amount',
        'total_amount',
        'total_items',
        'metadata'
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'total_items' => 'integer',
        'metadata' => 'array',
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    // Methods
    public function addItem(Drug $drug, int $quantity = 1): CartItem
    {
        // Check if item already exists
        $existingItem = $this->items()->where('drug_id', $drug->id)->first();

        if ($existingItem) {
            return $this->updateItemQuantity($existingItem, $existingItem->quantity + $quantity);
        }

        // Create new cart item
        $cartItem = $this->items()->create([
            'drug_id' => $drug->id,
            'quantity' => $quantity,
            'unit_price' => $drug->price,
            'total_price' => $drug->price * $quantity,
        ]);

        $this->recalculateTotals();

        return $cartItem;
    }

    public function updateItemQuantity(CartItem $item, int $quantity): ?CartItem
    {
        if ($quantity <= 0) {
            $this->removeItem($item);
            return null;
        }

        $item->update([
            'quantity' => $quantity,
            'total_price' => $item->unit_price * $quantity,
        ]);

        $this->recalculateTotals();

        return $item->fresh();
    }

    public function removeItem(CartItem $item): bool
    {
        $removed = $item->delete();

        if ($removed) {
            $this->recalculateTotals();
        }

        return $removed;
    }

    public function clear(): bool
    {
        $this->items()->delete();
        $this->update([
            'subtotal' => 0,
            'tax_amount' => 0,
            'total_amount' => 0,
            'total_items' => 0,
        ]);

        return true;
    }

    public function recalculateTotals(): void
    {
        $this->load('items');

        $subtotal = $this->items->sum('total_price');
        $totalItems = $this->items->sum('quantity');
        $taxRate = config('cart.tax_rate', 0.075); // 7.5% VAT
        $taxAmount = $subtotal * $taxRate;
        $totalAmount = $subtotal + $taxAmount;

        $this->update([
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'total_amount' => $totalAmount,
            'total_items' => $totalItems,
        ]);
    }

    public function isEmpty(): bool
    {
        return $this->total_items === 0;
    }

    public function hasItem(Drug $drug): bool
    {
        return $this->items()->where('drug_id', $drug->id)->exists();
    }

    public function getItemQuantity(Drug $drug): int
    {
        $item = $this->items()->where('drug_id', $drug->id)->first();
        return $item ? $item->quantity : 0;
    }

    public function mergeWithCart(Cart $otherCart): void
    {
        foreach ($otherCart->items as $item) {
            $this->addItem($item->drug, $item->quantity);
        }

        $otherCart->delete();
    }
}
