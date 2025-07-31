# Phase 3: Order Management System

## Overview

This phase implements a comprehensive order management system with simplified order tracking (3 states: placed, delivering, delivered). The system handles order creation from cart, inventory management, and order status updates.

## Objectives

-   Create order and order items database tables
-   Implement order creation from cart
-   Create simple 3-state order tracking system
-   Build order management API for customers and admins
-   Implement inventory deduction on order placement
-   Create order status update system with customer confirmation

## Order Status Flow

1. **Order Placed** - Initial state when order is created
2. **Delivering** - Admin updates when order is shipped
3. **Delivered** - Customer confirms receipt of order

## Tasks

### Task 3.1: Create Order Status Enum

#### Create Order Status Enum

**File**: `app/Enums/OrderStatus.php`

```php
<?php

namespace App\Enums;

enum OrderStatus: string
{
    case PLACED = 'placed';
    case DELIVERING = 'delivering';
    case DELIVERED = 'delivered';

    public function label(): string
    {
        return match($this) {
            self::PLACED => 'Order Placed',
            self::DELIVERING => 'Delivering',
            self::DELIVERED => 'Delivered',
        };
    }

    public function description(): string
    {
        return match($this) {
            self::PLACED => 'Your order has been placed and is being processed',
            self::DELIVERING => 'Your order is on the way',
            self::DELIVERED => 'Your order has been delivered',
        };
    }

    public function canTransitionTo(OrderStatus $status): bool
    {
        return match($this) {
            self::PLACED => $status === self::DELIVERING,
            self::DELIVERING => $status === self::DELIVERED,
            self::DELIVERED => false, // Final state
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
```

### Task 3.2: Database Schema

#### Create Orders Migration

**File**: `database/migrations/2025_07_24_000004_create_orders_table.php`

```php
<?php

use App\Enums\OrderStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // Order totals
            $table->decimal('subtotal', 10, 2);
            $table->decimal('tax_amount', 10, 2);
            $table->decimal('total_amount', 10, 2);
            $table->integer('total_items');

            // Order status
            $table->enum('status', OrderStatus::values())->default(OrderStatus::PLACED->value);
            $table->timestamp('status_updated_at')->nullable();
            $table->foreignId('status_updated_by')->nullable()->constrained('users');

            // Shipping information
            $table->json('shipping_address');
            $table->string('phone_number');
            $table->text('delivery_notes')->nullable();

            // Payment information
            $table->enum('payment_status', ['pending', 'paid', 'failed', 'refunded'])->default('pending');
            $table->string('payment_method')->nullable();
            $table->string('payment_reference')->nullable();

            // Timestamps
            $table->timestamp('placed_at');
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();

            // Indexes
            $table->index(['user_id']);
            $table->index(['status']);
            $table->index(['payment_status']);
            $table->index(['order_number']);
            $table->index(['placed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
```

#### Create Order Items Migration

**File**: `database/migrations/2025_07_24_000005_create_order_items_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->foreignId('drug_id')->constrained();

            // Product details at time of order
            $table->string('drug_name'); // Store name in case drug is deleted
            $table->string('drug_slug');
            $table->text('drug_description')->nullable();
            $table->string('manufacturer')->nullable();
            $table->string('dosage')->nullable();
            $table->string('package_size')->nullable();

            // Pricing and quantity
            $table->integer('quantity');
            $table->decimal('unit_price', 10, 2);
            $table->decimal('total_price', 10, 2);

            $table->timestamps();

            // Indexes
            $table->index(['order_id']);
            $table->index(['drug_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
```

### Task 3.3: Models

#### Create Order Model

**File**: `app/Models/Order.php`

```php
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
        'shipping_address',
        'phone_number',
        'delivery_notes',
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
        'total_items' => 'integer',
        'status' => OrderStatus::class,
        'shipping_address' => 'array',
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
        return '₵' . number_format($this->total_amount, 2);
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            OrderStatus::PLACED => 'blue',
            OrderStatus::DELIVERING => 'orange',
            OrderStatus::DELIVERED => 'green',
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

    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('placed_at', '>=', now()->subDays($days));
    }
}
```

#### Create Order Item Model

**File**: `app/Models/OrderItem.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'drug_id',
        'drug_name',
        'drug_slug',
        'drug_description',
        'manufacturer',
        'dosage',
        'package_size',
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
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function drug(): BelongsTo
    {
        return $this->belongsTo(Drug::class);
    }

    // Methods
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

### Task 3.4: Order Service

#### Create Order Service

**File**: `app/Http/Services/OrderService.php`

```php
<?php

namespace App\Http\Services;

use App\Enums\OrderStatus;
use App\Models\Cart;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class OrderService
{
    public function createOrderFromCart(Cart $cart, array $shippingData, User $user): Order
    {
        return DB::transaction(function () use ($cart, $shippingData, $user) {
            // Validate cart items before creating order
            $this->validateCartForOrder($cart);

            // Create the order
            $order = Order::create([
                'user_id' => $user->id,
                'subtotal' => $cart->subtotal,
                'tax_amount' => $cart->tax_amount,
                'total_amount' => $cart->total_amount,
                'total_items' => $cart->total_items,
                'shipping_address' => $shippingData['address'],
                'phone_number' => $shippingData['phone_number'],
                'delivery_notes' => $shippingData['delivery_notes'] ?? null,
                'status' => OrderStatus::PLACED,
                'payment_status' => 'pending',
            ]);

            // Create order items and update inventory
            foreach ($cart->items as $cartItem) {
                $drug = $cartItem->drug;

                // Create order item with drug details at time of order
                $order->items()->create([
                    'drug_id' => $drug->id,
                    'drug_name' => $drug->name,
                    'drug_slug' => $drug->slug,
                    'drug_description' => $drug->description,
                    'manufacturer' => $drug->manufacturer,
                    'dosage' => $drug->dosage,
                    'package_size' => $drug->package_size,
                    'quantity' => $cartItem->quantity,
                    'unit_price' => $cartItem->unit_price,
                    'total_price' => $cartItem->total_price,
                ]);

                // Reduce inventory
                $drug->decrementStock($cartItem->quantity);
            }

            // Clear the cart after successful order creation
            $cart->clear();

            return $order->load('items');
        });
    }

    public function updateOrderStatus(Order $order, OrderStatus $newStatus, ?User $updatedBy = null): Order
    {
        $order->updateStatus($newStatus, $updatedBy);

        // You can add additional logic here like sending notifications
        // $this->sendStatusUpdateNotification($order);

        return $order->fresh();
    }

    public function confirmDelivery(Order $order, User $user): Order
    {
        // Ensure only the order owner can confirm delivery
        if ($order->user_id !== $user->id) {
            throw new \UnauthorizedAccessException('You can only confirm your own orders');
        }

        if (!$order->canBeDelivered()) {
            throw new \InvalidArgumentException('Order cannot be confirmed as delivered');
        }

        return $this->updateOrderStatus($order, OrderStatus::DELIVERED, $user);
    }

    public function markAsDelivering(Order $order, User $admin): Order
    {
        if (!$order->canBeShipped()) {
            throw new \InvalidArgumentException('Order cannot be marked as delivering');
        }

        return $this->updateOrderStatus($order, OrderStatus::DELIVERING, $admin);
    }

    protected function validateCartForOrder(Cart $cart): void
    {
        if ($cart->isEmpty()) {
            throw new \InvalidArgumentException('Cannot create order from empty cart');
        }

        foreach ($cart->items as $item) {
            $drug = $item->drug;

            if (!$drug->isAvailable()) {
                throw new \InvalidArgumentException("Drug {$drug->name} is no longer available");
            }

            if (!$drug->isInStock($item->quantity)) {
                throw new \InvalidArgumentException("Insufficient stock for {$drug->name}");
            }
        }
    }

    public function getOrderStatistics(User $user = null): array
    {
        $query = Order::query();

        if ($user) {
            $query->where('user_id', $user->id);
        }

        $totalOrders = $query->count();
        $placedOrders = $query->clone()->byStatus(OrderStatus::PLACED)->count();
        $deliveringOrders = $query->clone()->byStatus(OrderStatus::DELIVERING)->count();
        $deliveredOrders = $query->clone()->byStatus(OrderStatus::DELIVERED)->count();
        $totalRevenue = $query->clone()->where('payment_status', 'paid')->sum('total_amount');

        return [
            'total_orders' => $totalOrders,
            'placed_orders' => $placedOrders,
            'delivering_orders' => $deliveringOrders,
            'delivered_orders' => $deliveredOrders,
            'total_revenue' => $totalRevenue,
            'formatted_total_revenue' => '₵' . number_format($totalRevenue, 2),
        ];
    }

    public function getUserOrderHistory(User $user, array $filters = []): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $query = Order::with(['items.drug'])
            ->where('user_id', $user->id)
            ->orderBy('placed_at', 'desc');

        // Apply filters
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['from_date'])) {
            $query->where('placed_at', '>=', $filters['from_date']);
        }

        if (isset($filters['to_date'])) {
            $query->where('placed_at', '<=', $filters['to_date']);
        }

        return $query->paginate($filters['per_page'] ?? 15);
    }
}
```

### Task 3.5: API Resources

#### Create Order Resource

**File**: `app/Http/Resources/OrderResource.php`

```php
<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_number' => $this->order_number,
            'status' => [
                'value' => $this->status->value,
                'label' => $this->status->label(),
                'description' => $this->status->description(),
                'color' => $this->status_color,
            ],
            'items' => OrderItemResource::collection($this->whenLoaded('items')),
            'totals' => [
                'subtotal' => $this->subtotal,
                'tax_amount' => $this->tax_amount,
                'total_amount' => $this->total_amount,
                'total_items' => $this->total_items,
                'formatted_subtotal' => '₵' . number_format($this->subtotal, 2),
                'formatted_tax_amount' => '₵' . number_format($this->tax_amount, 2),
                'formatted_total_amount' => $this->formatted_total,
            ],
            'shipping_address' => $this->shipping_address,
            'phone_number' => $this->phone_number,
            'delivery_notes' => $this->delivery_notes,
            'payment' => [
                'status' => $this->payment_status,
                'method' => $this->payment_method,
                'reference' => $this->payment_reference,
            ],
            'dates' => [
                'placed_at' => $this->placed_at,
                'delivered_at' => $this->delivered_at,
                'status_updated_at' => $this->status_updated_at,
                'days_old' => $this->days_old,
            ],
            'status_updated_by' => new UserResource($this->whenLoaded('statusUpdatedBy')),
            'can_be_delivered' => $this->canBeDelivered(),
            'can_be_shipped' => $this->canBeShipped(),
            'is_delivered' => $this->isDelivered(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
```

#### Create Order Item Resource

**File**: `app/Http/Resources/OrderItemResource.php`

```php
<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'drug' => [
                'id' => $this->drug_id,
                'name' => $this->drug_name,
                'slug' => $this->drug_slug,
                'description' => $this->drug_description,
                'manufacturer' => $this->manufacturer,
                'dosage' => $this->dosage,
                'package_size' => $this->package_size,
                'current_drug' => new DrugResource($this->whenLoaded('drug')), // Current drug data if still exists
            ],
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

#### Create User Resource (if not exists)

**File**: `app/Http/Resources/UserResource.php`

```php
<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role->name ?? null,
            'created_at' => $this->created_at,
        ];
    }
}
```

### Task 3.6: Order Controller

#### Create Order Controller

**File**: `app/Http/Controllers/OrderController.php`

```php
<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Http\Resources\OrderResource;
use App\Http\Services\CartService;
use App\Http\Services\OrderService;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class OrderController extends Controller
{
    protected OrderService $orderService;
    protected CartService $cartService;

    public function __construct(OrderService $orderService, CartService $cartService)
    {
        $this->orderService = $orderService;
        $this->cartService = $cartService;
    }

    /**
     * Get user's orders
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = [
            'status' => $request->get('status'),
            'from_date' => $request->get('from_date'),
            'to_date' => $request->get('to_date'),
            'per_page' => $request->get('per_page', 15),
        ];

        $orders = $this->orderService->getUserOrderHistory($request->user(), $filters);

        return OrderResource::collection($orders);
    }

    /**
     * Get specific order
     */
    public function show(Request $request, Order $order): OrderResource|JsonResponse
    {
        // Ensure user can only view their own orders
        if ($order->user_id !== $request->user()->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Order not found'
            ], 404);
        }

        $order->load(['items.drug', 'statusUpdatedBy']);

        return new OrderResource($order);
    }

    /**
     * Create order from cart
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'shipping_address' => 'required|array',
            'shipping_address.line1' => 'required|string|max:255',
            'shipping_address.line2' => 'nullable|string|max:255',
            'shipping_address.city' => 'required|string|max:100',
            'shipping_address.state' => 'required|string|max:100',
            'shipping_address.postal_code' => 'nullable|string|max:20',
            'phone_number' => 'required|string|max:20',
            'delivery_notes' => 'nullable|string|max:500',
        ]);

        try {
            $cart = $this->cartService->getOrCreateCart($request, $request->user());

            if ($cart->isEmpty()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cannot create order from empty cart'
                ], 400);
            }

            $shippingData = [
                'address' => $request->shipping_address,
                'phone_number' => $request->phone_number,
                'delivery_notes' => $request->delivery_notes,
            ];

            $order = $this->orderService->createOrderFromCart($cart, $shippingData, $request->user());

            return response()->json([
                'status' => 'success',
                'message' => 'Order created successfully',
                'data' => [
                    'order' => new OrderResource($order)
                ]
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Confirm order delivery (customer only)
     */
    public function confirmDelivery(Request $request, Order $order): JsonResponse
    {
        // Ensure user can only confirm their own orders
        if ($order->user_id !== $request->user()->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Order not found'
            ], 404);
        }

        try {
            $updatedOrder = $this->orderService->confirmDelivery($order, $request->user());

            return response()->json([
                'status' => 'success',
                'message' => 'Order confirmed as delivered',
                'data' => [
                    'order' => new OrderResource($updatedOrder->load(['items.drug', 'statusUpdatedBy']))
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
     * Get order statistics for user
     */
    public function statistics(Request $request): JsonResponse
    {
        $stats = $this->orderService->getOrderStatistics($request->user());

        return response()->json([
            'status' => 'success',
            'data' => $stats
        ], 200);
    }
}
```

### Task 3.7: Admin Order Controller

#### Create Admin Order Controller

**File**: `app/Http/Controllers/Admin/OrderManagementController.php`

```php
<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Http\Services\OrderService;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class OrderManagementController extends Controller
{
    protected OrderService $orderService;

    public function __construct(OrderService $orderService)
    {
        $this->orderService = $orderService;

        // Ensure only admin users can access these endpoints
        $this->middleware(function ($request, $next) {
            if (!$request->user() || !in_array($request->user()->role->slug, ['admin'])) {
                abort(403, 'Unauthorized - Admin access required');
            }
            return $next($request);
        });
    }

    /**
     * Get all orders (admin view)
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Order::with(['user', 'items.drug', 'statusUpdatedBy'])
            ->orderBy('placed_at', 'desc');

        // Apply filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }

        if ($request->filled('from_date')) {
            $query->where('placed_at', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $query->where('placed_at', '<=', $request->to_date);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($userQuery) use ($search) {
                      $userQuery->where('name', 'like', "%{$search}%")
                               ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        $orders = $query->paginate($request->get('per_page', 15));

        return OrderResource::collection($orders);
    }

    /**
     * Get specific order (admin view)
     */
    public function show(Order $order): OrderResource
    {
        $order->load(['user', 'items.drug', 'statusUpdatedBy']);

        return new OrderResource($order);
    }

    /**
     * Update order status
     */
    public function updateStatus(Request $request, Order $order): JsonResponse
    {
        $request->validate([
            'status' => 'required|in:' . implode(',', OrderStatus::values()),
        ]);

        try {
            $newStatus = OrderStatus::from($request->status);
            $updatedOrder = $this->orderService->updateOrderStatus($order, $newStatus, $request->user());

            return response()->json([
                'status' => 'success',
                'message' => "Order status updated to {$newStatus->label()}",
                'data' => [
                    'order' => new OrderResource($updatedOrder->load(['user', 'items.drug', 'statusUpdatedBy']))
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
     * Mark order as delivering
     */
    public function markAsDelivering(Request $request, Order $order): JsonResponse
    {
        try {
            $updatedOrder = $this->orderService->markAsDelivering($order, $request->user());

            return response()->json([
                'status' => 'success',
                'message' => 'Order marked as delivering',
                'data' => [
                    'order' => new OrderResource($updatedOrder->load(['user', 'items.drug', 'statusUpdatedBy']))
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
     * Get order analytics
     */
    public function analytics(Request $request): JsonResponse
    {
        $stats = $this->orderService->getOrderStatistics();

        // Additional admin-specific analytics
        $recentOrders = Order::recent(7)->count();
        $pendingOrders = Order::pending()->count();
        $deliveringOrders = Order::delivering()->count();

        $analytics = array_merge($stats, [
            'recent_orders_7_days' => $recentOrders,
            'pending_orders' => $pendingOrders,
            'delivering_orders' => $deliveringOrders,
        ]);

        return response()->json([
            'status' => 'success',
            'data' => $analytics
        ], 200);
    }

    /**
     * Get orders requiring attention
     */
    public function requiresAttention(Request $request): AnonymousResourceCollection
    {
        $orders = Order::with(['user', 'items.drug'])
            ->where(function ($query) {
                $query->where('status', OrderStatus::PLACED)
                      ->where('payment_status', 'paid')
                      ->where('placed_at', '<=', now()->subHours(24))
                      ->orWhere('status', OrderStatus::DELIVERING)
                      ->where('status_updated_at', '<=', now()->subDays(3));
            })
            ->orderBy('placed_at', 'asc')
            ->paginate(10);

        return OrderResource::collection($orders);
    }
}
```

### Task 3.8: Routes

#### Add Order Routes

**File**: `routes/api.php` (add these routes)

```php
// Customer order routes
Route::middleware(['auth:sanctum'])->prefix('orders')->group(function () {
    Route::get('/', [OrderController::class, 'index']);
    Route::post('/', [OrderController::class, 'store']);
    Route::get('/statistics', [OrderController::class, 'statistics']);
    Route::get('/{order}', [OrderController::class, 'show']);
    Route::post('/{order}/confirm-delivery', [OrderController::class, 'confirmDelivery']);
});

// Admin order management routes
Route::middleware(['auth:sanctum'])->prefix('admin/orders')->group(function () {
    Route::get('/', [Admin\OrderManagementController::class, 'index']);
    Route::get('/analytics', [Admin\OrderManagementController::class, 'analytics']);
    Route::get('/requires-attention', [Admin\OrderManagementController::class, 'requiresAttention']);
    Route::get('/{order}', [Admin\OrderManagementController::class, 'show']);
    Route::put('/{order}/status', [Admin\OrderManagementController::class, 'updateStatus']);
    Route::post('/{order}/mark-delivering', [Admin\OrderManagementController::class, 'markAsDelivering']);
});
```

### Task 3.9: Factories

#### Create Order Factory

**File**: `database/factories/OrderFactory.php`

```php
<?php

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderFactory extends Factory
{
    public function definition(): array
    {
        $subtotal = $this->faker->randomFloat(2, 100, 5000);
        $taxAmount = $subtotal * 0.075;
        $totalAmount = $subtotal + $taxAmount;

        return [
            'user_id' => User::factory(),
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'total_amount' => $totalAmount,
            'total_items' => $this->faker->numberBetween(1, 10),
            'status' => $this->faker->randomElement(OrderStatus::cases()),
            'shipping_address' => [
                'line1' => $this->faker->streetAddress(),
                'line2' => $this->faker->optional()->secondaryAddress(),
                'city' => $this->faker->city(),
                'state' => $this->faker->state(),
                'postal_code' => $this->faker->postcode(),
            ],
            'phone_number' => $this->faker->phoneNumber(),
            'delivery_notes' => $this->faker->optional()->sentence(),
            'payment_status' => $this->faker->randomElement(['pending', 'paid', 'failed']),
            'payment_method' => $this->faker->randomElement(['paystack', 'bank_transfer']),
            'placed_at' => $this->faker->dateTimeBetween('-30 days', 'now'),
        ];
    }

    public function placed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => OrderStatus::PLACED,
            'payment_status' => 'paid',
        ]);
    }

    public function delivering(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => OrderStatus::DELIVERING,
            'payment_status' => 'paid',
            'status_updated_at' => now(),
        ]);
    }

    public function delivered(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => OrderStatus::DELIVERED,
            'payment_status' => 'paid',
            'delivered_at' => now(),
            'status_updated_at' => now(),
        ]);
    }
}
```

#### Create Order Item Factory

**File**: `database/factories/OrderItemFactory.php`

```php
<?php

namespace Database\Factories;

use App\Models\Drug;
use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderItemFactory extends Factory
{
    public function definition(): array
    {
        $drug = Drug::factory()->create();
        $quantity = $this->faker->numberBetween(1, 5);
        $unitPrice = $this->faker->randomFloat(2, 50, 1000);
        $totalPrice = $unitPrice * $quantity;

        return [
            'order_id' => Order::factory(),
            'drug_id' => $drug->id,
            'drug_name' => $drug->name,
            'drug_slug' => $drug->slug,
            'drug_description' => $drug->description,
            'manufacturer' => $drug->manufacturer,
            'dosage' => $drug->dosage,
            'package_size' => $drug->package_size,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'total_price' => $totalPrice,
        ];
    }
}
```

### Task 3.10: Update User Model

#### Add Order Relationship to User Model

**File**: `app/Models/User.php` (add this relationship)

```php
// Add this to the User model
public function orders(): HasMany
{
    return $this->hasMany(Order::class);
}
```

## Testing

### API Endpoints to Test

1. **POST /api/orders** - Create order from cart
2. **GET /api/orders** - List user orders
3. **GET /api/orders/{id}** - Get order details
4. **POST /api/orders/{id}/confirm-delivery** - Confirm delivery
5. **GET /api/orders/statistics** - User order statistics
6. **GET /api/admin/orders** - Admin order listing
7. **PUT /api/admin/orders/{id}/status** - Update order status
8. **GET /api/admin/orders/analytics** - Order analytics

### Test Scenarios

-   Order creation from cart with inventory deduction
-   Order status transitions (placed → delivering → delivered)
-   Customer delivery confirmation
-   Admin order management
-   Order history filtering
-   Inventory validation during order creation

## Success Criteria

-   [ ] Orders can be created from cart successfully
-   [ ] Inventory is properly deducted on order placement
-   [ ] Order status follows 3-state flow correctly
-   [ ] Customers can confirm delivery
-   [ ] Admins can update order status
-   [ ] Order history and analytics work correctly
-   [ ] All order data is properly stored and retrievable
-   [ ] API responses are properly formatted
-   [ ] Error handling covers all edge cases

## Next Phase

Once Phase 3 is complete, proceed to **Phase 4: Payment Integration**.
