<?php

namespace App\Http\Controllers;

use App\Http\Resources\CartResource;
use App\Http\Resources\CartItemResource;
use App\Http\Services\CartService;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Drug;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class CartController extends Controller
{
    protected CartService $cartService;

    public function __construct(CartService $cartService)
    {
        $this->cartService = $cartService;
    }

    /**
     * Get current user's cart
     */
    public function index(Request $request): CartResource
    {
        $cart = $this->cartService->getOrCreateCart($request->user());
        $cart->load(['items.drug']);

        return new CartResource($cart);
    }

    /**
     * Add item to cart
     */
    public function addItem(Request $request): JsonResponse
    {
        $request->validate([
            'drug_id' => 'required|exists:drugs,id',
            'quantity' => 'required|integer|min:1|max:100',
        ]);

        try {
            $drug = Drug::findOrFail($request->drug_id);

            $cartItem = $this->cartService->addToCart(
                $drug,
                $request->quantity,
                $request->user()
            );

            $cart = $cartItem->cart;
            $cart->load(['items.drug']);

            return response()->json([
                'status' => 'success',
                'message' => 'Item added to cart successfully',
                'data' => [
                    'cart' => new CartResource($cart),
                    'added_item' => new CartItemResource($cartItem)
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Update cart item quantity
     */
    public function updateItem(Request $request, CartItem $cartItem): JsonResponse
    {
        // Verify cart ownership
        $userCart = $this->cartService->getOrCreateCart($request->user());

        if ($cartItem->cart_id !== $userCart->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Cart item not found'
            ], 404);
        }

        $request->validate([
            'quantity' => 'required|integer|min:1|max:100',
        ]);

        try {
            $updatedItem = $this->cartService->updateCartItem($cartItem, $request->quantity);

            $cart = $updatedItem->cart;
            $cart->load(['items.drug']);

            return response()->json([
                'status' => 'success',
                'message' => 'Cart item updated successfully',
                'data' => [
                    'cart' => new CartResource($cart),
                    'updated_item' => new CartItemResource($updatedItem)
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Remove item from cart
     */
    public function removeItem(Request $request, CartItem $cartItem): JsonResponse
    {
        // Verify cart ownership
        $userCart = $this->cartService->getOrCreateCart($request->user());

        if ($cartItem->cart_id !== $userCart->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Cart item not found'
            ], 404);
        }

        try {
            $this->cartService->removeFromCart($cartItem);

            $cart = $userCart->fresh();
            $cart->load(['items.drug']);

            return response()->json([
                'status' => 'success',
                'message' => 'Item removed from cart successfully',
                'data' => [
                    'cart' => new CartResource($cart)
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Clear entire cart
     */
    public function clear(Request $request): JsonResponse
    {
        try {
            $cart = $this->cartService->getOrCreateCart($request->user());
            $this->cartService->clearCart($cart);

            return response()->json([
                'status' => 'success',
                'message' => 'Cart cleared successfully',
                'data' => [
                    'cart' => new CartResource($cart->fresh())
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Get cart totals
     */
    public function totals(Request $request): JsonResponse
    {
        $cart = $this->cartService->getOrCreateCart($request->user());

        return response()->json([
            'status' => 'success',
            'data' => $this->cartService->getCartTotals($cart)
        ], 200);
    }

    /**
     * Validate cart items (check stock, prices, availability)
     */
    public function validate(Request $request): JsonResponse
    {
        $cart = $this->cartService->getOrCreateCart($request->user());
        $issues = $this->cartService->validateCartItems($cart);

        return response()->json([
            'status' => 'success',
            'data' => [
                'is_valid' => empty($issues),
                'issues' => $issues,
                'issues_count' => count($issues)
            ]
        ], 200);
    }
}
