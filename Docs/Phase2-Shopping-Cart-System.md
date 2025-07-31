# Phase 2: Shopping Cart System

## Overview

This phase implements a comprehensive shopping cart system supporting both guest users (session-based) and authenticated users (database-backed). The system includes cart persistence, item management, and seamless user experience.

## Objectives

-   Create cart and cart items database tables
-   Implement session-based cart for guest users
-   Implement database-backed cart for authenticated users
-   Create cart synchronization when guest users register/login
-   Build comprehensive cart management API
-   Implement cart total calculations with taxes

## Tasks

### Task 2.1: Database Schema

#### Create Carts Migration

**File**: `database/migrations/2025_07_24_000002_create_carts_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('carts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('session_id')->nullable();
            $table->decimal('subtotal', 10, 2)->default(0);
            $table->decimal('tax_amount', 10, 2)->default(0);
            $table->decimal('total_amount', 10, 2)->default(0);
            $table->integer('total_items')->default(0);
            $table->json('metadata')->nullable(); // For additional cart data
            $table->timestamps();

            // Indexes
            $table->index(['user_id']);
            $table->index(['session_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('carts');
    }
};
```

#### Create Cart Items Migration

**File**: `database/migrations/2025_07_24_000003_create_cart_items_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cart_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cart_id')->constrained()->onDelete('cascade');
            $table->foreignId('drug_id')->constrained()->onDelete('cascade');
            $table->integer('quantity');
            $table->decimal('unit_price', 10, 2); // Price at time of adding to cart
            $table->decimal('total_price', 10, 2); // quantity * unit_price
            $table->timestamps();

            // Indexes
            $table->index(['cart_id']);
            $table->index(['drug_id']);
            $table->unique(['cart_id', 'drug_id']); // Prevent duplicate items
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cart_items');
    }
};
```

### Task 2.2: Models

#### Create Cart Model

**File**: `app/Models/Cart.php`

```php
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
        'session_id',
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

    public function updateItemQuantity(CartItem $item, int $quantity): CartItem
    {
        if ($quantity <= 0) {
            return $this->removeItem($item);
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
```

#### Create Cart Item Model

**File**: `app/Models/CartItem.php`

```php
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
        return '₵' . number_format($this->unit_price, 2);
    }

    public function getFormattedTotalPriceAttribute(): string
    {
        return '₵' . number_format($this->total_price, 2);
    }
}
```

### Task 2.3: Cart Service

#### Create Cart Service

**File**: `app/Http/Services/CartService.php`

```php
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
    public function getOrCreateCart(Request $request, ?User $user = null): Cart
    {
        if ($user) {
            // Get or create cart for authenticated user
            $cart = Cart::where('user_id', $user->id)->first();

            if (!$cart) {
                $cart = Cart::create(['user_id' => $user->id]);

                // Merge session cart if exists
                $sessionCart = $this->getSessionCart($request);
                if ($sessionCart && !$sessionCart->isEmpty()) {
                    $cart->mergeWithCart($sessionCart);
                }
            }
        } else {
            // Get or create session-based cart for guest
            $cart = $this->getSessionCart($request);

            if (!$cart) {
                $cart = Cart::create(['session_id' => $request->session()->getId()]);
            }
        }

        return $cart;
    }

    public function getSessionCart(Request $request): ?Cart
    {
        return Cart::where('session_id', $request->session()->getId())->first();
    }

    public function addToCart(Request $request, Drug $drug, int $quantity = 1, ?User $user = null): CartItem
    {
        // Validate stock availability
        if (!$drug->isInStock($quantity)) {
            throw new \Exception('Insufficient stock available');
        }

        // Validate drug availability
        if (!$drug->isAvailable()) {
            throw new \Exception('Drug is not available for purchase');
        }

        $cart = $this->getOrCreateCart($request, $user);

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

    public function syncCartOnLogin(Request $request, User $user): Cart
    {
        $sessionCart = $this->getSessionCart($request);
        $userCart = Cart::where('user_id', $user->id)->first();

        if ($sessionCart && !$sessionCart->isEmpty()) {
            if ($userCart) {
                // Merge session cart into user cart
                $userCart->mergeWithCart($sessionCart);
                return $userCart;
            } else {
                // Convert session cart to user cart
                $sessionCart->update([
                    'user_id' => $user->id,
                    'session_id' => null,
                ]);
                return $sessionCart;
            }
        }

        return $userCart ?: Cart::create(['user_id' => $user->id]);
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
                $availableStock = $drug->stock_quantity;
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
```

### Task 2.4: API Resources

#### Create Cart Resource

**File**: `app/Http/Resources/CartResource.php`

```php
<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'items' => CartItemResource::collection($this->whenLoaded('items')),
            'totals' => [
                'subtotal' => $this->subtotal,
                'tax_amount' => $this->tax_amount,
                'total_amount' => $this->total_amount,
                'total_items' => $this->total_items,
                'formatted_subtotal' => '₵' . number_format($this->subtotal, 2),
                'formatted_tax_amount' => '₵' . number_format($this->tax_amount, 2),
                'formatted_total_amount' => '₵' . number_format($this->total_amount, 2),
            ],
            'is_empty' => $this->isEmpty(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
```

#### Create Cart Item Resource

**File**: `app/Http/Resources/CartItemResource.php`

```php
<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'drug' => new DrugResource($this->whenLoaded('drug')),
            'quantity' => $this->quantity,
            'unit_price' => $this->unit_price,
            'total_price' => $this->total_price,
            'formatted_unit_price' => $this->formatted_unit_price,
            'formatted_total_price' => $this->formatted_total_price,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
```

### Task 2.5: Cart Controller

#### Create Cart Controller

**File**: `app/Http/Controllers/CartController.php`

```php
<?php

namespace App\Http\Controllers;

use App\Http\Resources\CartResource;
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
        $cart = $this->cartService->getOrCreateCart($request, $request->user());
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
                $request,
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
                    'added_item' => new \App\Http\Resources\CartItemResource($cartItem)
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
        $userCart = $this->cartService->getOrCreateCart($request, $request->user());

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
                    'updated_item' => new \App\Http\Resources\CartItemResource($updatedItem)
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
        $userCart = $this->cartService->getOrCreateCart($request, $request->user());

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
            $cart = $this->cartService->getOrCreateCart($request, $request->user());
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
        $cart = $this->cartService->getOrCreateCart($request, $request->user());

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
        $cart = $this->cartService->getOrCreateCart($request, $request->user());
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
```

### Task 2.6: Configuration

#### Create Cart Configuration

**File**: `config/cart.php`

```php
<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Tax Rate
    |--------------------------------------------------------------------------
    |
    | The tax rate to apply to cart totals. This should be a decimal value.
    | For example, 0.075 for 7.5% VAT.
    |
    */
    'tax_rate' => env('CART_TAX_RATE', 0.075),

    /*
    |--------------------------------------------------------------------------
    | Session Cart Expiry
    |--------------------------------------------------------------------------
    |
    | How long to keep session-based carts (in minutes).
    | After this time, session carts will be eligible for cleanup.
    |
    */
    'session_cart_expiry' => env('CART_SESSION_EXPIRY', 10080), // 7 days

    /*
    |--------------------------------------------------------------------------
    | Maximum Items per Cart
    |--------------------------------------------------------------------------
    |
    | Maximum number of different items that can be added to a cart.
    |
    */
    'max_items' => env('CART_MAX_ITEMS', 50),

    /*
    |--------------------------------------------------------------------------
    | Maximum Quantity per Item
    |--------------------------------------------------------------------------
    |
    | Maximum quantity that can be ordered for a single item.
    |
    */
    'max_quantity_per_item' => env('CART_MAX_QUANTITY_PER_ITEM', 100),
];
```

### Task 2.7: Routes

#### Add Cart Routes

**File**: `routes/api.php` (add these routes)

```php
// Public cart routes (for guest users)
Route::prefix('cart')->group(function () {
    Route::get('/', [CartController::class, 'index']);
    Route::post('/add', [CartController::class, 'addItem']);
    Route::put('/item/{cartItem}', [CartController::class, 'updateItem']);
    Route::delete('/item/{cartItem}', [CartController::class, 'removeItem']);
    Route::delete('/clear', [CartController::class, 'clear']);
    Route::get('/totals', [CartController::class, 'totals']);
    Route::get('/validate', [CartController::class, 'validate']);
});

// Protected cart routes will use the same endpoints but with user context
Route::middleware(['auth:sanctum'])->prefix('cart')->group(function () {
    // These will automatically work with authenticated users
    // No need to duplicate routes as the controller handles both cases
});
```

### Task 2.8: Factories

#### Create Cart Factory

**File**: `database/factories/CartFactory.php`

```php
<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CartFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => null,
            'session_id' => $this->faker->uuid(),
            'subtotal' => 0,
            'tax_amount' => 0,
            'total_amount' => 0,
            'total_items' => 0,
            'metadata' => null,
        ];
    }

    public function withUser(): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => User::factory(),
            'session_id' => null,
        ]);
    }

    public function session(): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => null,
            'session_id' => $this->faker->uuid(),
        ]);
    }
}
```

#### Create Cart Item Factory

**File**: `database/factories/CartItemFactory.php`

```php
<?php

namespace Database\Factories;

use App\Models\Cart;
use App\Models\Drug;
use Illuminate\Database\Eloquent\Factories\Factory;

class CartItemFactory extends Factory
{
    public function definition(): array
    {
        $quantity = $this->faker->numberBetween(1, 5);
        $unitPrice = $this->faker->randomFloat(2, 50, 1000);

        return [
            'cart_id' => Cart::factory(),
            'drug_id' => Drug::factory(),
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'total_price' => $unitPrice * $quantity,
        ];
    }
}
```

### Task 2.9: Update User Model

#### Add Cart Relationship to User Model

**File**: `app/Models/User.php` (add this relationship)

```php
// Add this to the User model
public function cart(): HasOne
{
    return $this->hasOne(Cart::class);
}
```

### Task 2.10: Middleware for Cart Sync

#### Create Cart Sync Middleware

**File**: `app/Http/Middleware/SyncCart.php`

```php
<?php

namespace App\Http\Middleware;

use App\Http\Services\CartService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SyncCart
{
    protected CartService $cartService;

    public function __construct(CartService $cartService)
    {
        $this->cartService = $cartService;
    }

    public function handle(Request $request, Closure $next): Response
    {
        // If user just logged in and has session cart, sync it
        if ($request->user() && $request->session()->has('cart_sync_needed')) {
            $this->cartService->syncCartOnLogin($request, $request->user());
            $request->session()->forget('cart_sync_needed');
        }

        return $next($request);
    }
}
```

## Testing

### API Endpoints to Test

1. **GET /api/cart** - Get cart contents
2. **POST /api/cart/add** - Add item to cart
3. **PUT /api/cart/item/{id}** - Update cart item
4. **DELETE /api/cart/item/{id}** - Remove cart item
5. **DELETE /api/cart/clear** - Clear cart
6. **GET /api/cart/totals** - Get cart totals
7. **GET /api/cart/validate** - Validate cart

### Test Scenarios

-   Guest user cart operations
-   Authenticated user cart operations
-   Cart synchronization on login
-   Stock validation when adding items
-   Price updates in cart
-   Cart persistence across sessions
-   Cart cleanup for expired sessions

## Success Criteria

-   [ ] Cart system works for both guest and authenticated users
-   [ ] Cart items persist correctly in database
-   [ ] Session-based carts work for guest users
-   [ ] Cart synchronization works on user login
-   [ ] Stock validation prevents overselling
-   [ ] Tax calculations are correct
-   [ ] Cart totals update automatically
-   [ ] API responses are properly formatted
-   [ ] All cart operations are atomic
-   [ ] Performance is acceptable for cart operations

## Next Phase

Once Phase 2 is complete, proceed to **Phase 3: Order Management System**.
