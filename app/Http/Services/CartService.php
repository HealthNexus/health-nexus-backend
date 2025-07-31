<?php

namespace App\Http\Services;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Drug;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class CartService
{
    public function getOrCreateCart(User $user): Cart
    {
        // Get or create cart for authenticated user only
        $cart = Cart::where('user_id', $user->id)->first();

        if (!$cart) {
            $cart = Cart::create(['user_id' => $user->id]);
        }

        return $cart;
    }

    public function addToCart(Drug $drug, int $quantity = 1, User $user): CartItem
    {
        // Validate stock availability
        if (!$drug->isInStock($quantity)) {
            throw new \Exception('Insufficient stock available');
        }

        // Validate drug availability
        if (!$drug->isAvailable()) {
            throw new \Exception('Drug is not available for purchase');
        }

        $cart = $this->getOrCreateCart($user);

        return $cart->addItem($drug, $quantity);
    }

    public function updateCartItem(CartItem $item, int $quantity): CartItem
    {
        // Validate stock availability
        if (!$item->drug->isInStock($quantity)) {
            throw new \Exception('Insufficient stock available');
        }

        return $item->cart->updateItemQuantity($item, $quantity);
    }

    public function removeFromCart(CartItem $item): bool
    {
        return $item->cart->removeItem($item);
    }

    public function clearCart(Cart $cart): bool
    {
        return $cart->clear();
    }

    public function getCartTotals(Cart $cart): array
    {
        return [
            'subtotal' => $cart->subtotal,
            'tax_amount' => $cart->tax_amount,
            'total_amount' => $cart->total_amount,
            'total_items' => $cart->total_items,
            'formatted_subtotal' => '₵' . number_format($cart->subtotal, 2),
            'formatted_tax_amount' => '₵' . number_format($cart->tax_amount, 2),
            'formatted_total_amount' => '₵' . number_format($cart->total_amount, 2),
        ];
    }

    public function getUserCart(User $user): Cart
    {
        return $this->getOrCreateCart($user);
    }

    public function validateCartItems(Cart $cart): array
    {
        $issues = [];

        foreach ($cart->items as $item) {
            $drug = $item->drug;

            // Check if drug is still available
            if (!$drug->isAvailable()) {
                $issues[] = [
                    'item_id' => $item->id,
                    'drug_name' => $drug->name,
                    'issue' => 'no_longer_available',
                    'message' => "{$drug->name} is no longer available"
                ];
                continue;
            }

            // Check stock availability
            if (!$drug->isInStock($item->quantity)) {
                $availableStock = $drug->stock;
                $issues[] = [
                    'item_id' => $item->id,
                    'drug_name' => $drug->name,
                    'issue' => 'insufficient_stock',
                    'requested' => $item->quantity,
                    'available' => $availableStock,
                    'message' => "Only {$availableStock} units of {$drug->name} available"
                ];
            }

            // Check if price has changed
            if ($item->unit_price != $drug->price) {
                $issues[] = [
                    'item_id' => $item->id,
                    'drug_name' => $drug->name,
                    'issue' => 'price_changed',
                    'old_price' => $item->unit_price,
                    'new_price' => $drug->price,
                    'message' => "Price of {$drug->name} has changed"
                ];
            }
        }

        return $issues;
    }
}
