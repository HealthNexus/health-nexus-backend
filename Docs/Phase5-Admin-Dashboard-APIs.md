# Phase 5: Admin Dashboard APIs

## Overview

This phase implements comprehensive admin management capabilities for the e-pharmacy system. It includes inventory management, order processing workflows, analytics, and reporting features specifically designed for administrative users.

## Objectives

-   Create inventory management system with stock tracking
-   Implement admin order processing workflow
-   Build comprehensive analytics and reporting
-   Create drug management CRUD operations
-   Implement low stock alerts and notifications
-   Build revenue and sales analytics

## Tasks

### Task 5.1: Admin Middleware Enhancement

#### Create Admin Authorization Middleware

**File**: `app/Http/Middleware/AdminOnly.php`

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminOnly
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->user()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Authentication required'
            ], 401);
        }

        if (!in_array($request->user()->role->slug, ['admin'])) {
            return response()->json([
                'status' => 'error',
                'message' => 'Admin access required'
            ], 403);
        }

        return $next($request);
    }
}
```

#### Register Middleware

**File**: `app/Http/Kernel.php` (add to routeMiddleware array)

```php
'admin' => \App\Http\Middleware\AdminOnly::class,
```

### Task 5.2: Inventory Management

#### Create Inventory Controller

**File**: `app/Http/Controllers/Admin/InventoryController.php`

```php
<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\DrugResource;
use App\Models\Drug;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class InventoryController extends Controller
{
    public function __construct()
    {
        $this->middleware('admin');
    }

    /**
     * Get inventory overview
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Drug::with(['categories']);

        // Apply filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('low_stock')) {
            $lowStockThreshold = $request->get('threshold', 10);
            $query->where('stock', '<=', $lowStockThreshold);
        }

        if ($request->filled('out_of_stock')) {
            $query->where('stock', 0);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('manufacturer', 'like', "%{$search}%");
            });
        }

        if ($request->filled('category')) {
            $query->whereHas('categories', function ($q) use ($request) {
                $q->where('slug', $request->category);
            });
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'stock');
        $sortDirection = $request->get('sort_direction', 'asc');

        $allowedSorts = ['name', 'stock', 'price', 'created_at'];
        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortDirection);
        }

        $drugs = $query->paginate($request->get('per_page', 20));

        return DrugResource::collection($drugs);
    }

    /**
     * Get inventory statistics
     */
    public function statistics(): JsonResponse
    {
        $totalDrugs = Drug::count();
        $activeDrugs = Drug::where('status', 'active')->count();
        $inStockDrugs = Drug::where('stock', '>', 0)->count();
        $outOfStockDrugs = Drug::where('stock', 0)->count();
        $lowStockDrugs = Drug::where('stock', '>', 0)
                            ->where('stock', '<=', 10)
                            ->count();

        $totalStockValue = Drug::where('status', 'active')
                              ->selectRaw('SUM(stock * price) as total')
                              ->value('total') ?? 0;

        $topSellingDrugs = OrderItem::select('drug_id', 'drug_name')
                                  ->selectRaw('SUM(quantity) as total_sold')
                                  ->whereHas('order', function ($query) {
                                      $query->where('payment_status', 'paid');
                                  })
                                  ->groupBy('drug_id', 'drug_name')
                                  ->orderBy('total_sold', 'desc')
                                  ->limit(10)
                                  ->get();

        return response()->json([
            'status' => 'success',
            'data' => [
                'total_drugs' => $totalDrugs,
                'active_drugs' => $activeDrugs,
                'in_stock_drugs' => $inStockDrugs,
                'out_of_stock_drugs' => $outOfStockDrugs,
                'low_stock_drugs' => $lowStockDrugs,
                'total_stock_value' => $totalStockValue,
                'formatted_stock_value' => '₦' . number_format($totalStockValue, 2),
                'top_selling_drugs' => $topSellingDrugs,
            ]
        ], 200);
    }

    /**
     * Update drug stock
     */
    public function updateStock(Request $request, Drug $drug): JsonResponse
    {
        $request->validate([
            'quantity' => 'required|integer|min:0',
            'operation' => 'required|in:set,add,subtract',
            'reason' => 'nullable|string|max:255',
        ]);

        $oldQuantity = $drug->stock;
        $newQuantity = 0;

        switch ($request->operation) {
            case 'set':
                $newQuantity = $request->quantity;
                break;
            case 'add':
                $newQuantity = $oldQuantity + $request->quantity;
                break;
            case 'subtract':
                $newQuantity = max(0, $oldQuantity - $request->quantity);
                break;
        }

        $drug->update(['stock' => $newQuantity]);

        // Update status based on stock
        if ($newQuantity === 0) {
            $drug->update(['status' => 'out_of_stock']);
        } elseif ($drug->status === 'out_of_stock' && $newQuantity > 0) {
            $drug->update(['status' => 'active']);
        }

        // Log the stock change (you might want to create a StockLog model)
        \Log::info('Stock updated', [
            'drug_id' => $drug->id,
            'drug_name' => $drug->name,
            'old_quantity' => $oldQuantity,
            'new_quantity' => $newQuantity,
            'operation' => $request->operation,
            'quantity' => $request->quantity,
            'reason' => $request->reason,
            'admin_id' => auth()->id(),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Stock updated successfully',
            'data' => [
                'drug' => new DrugResource($drug->fresh()),
                'old_quantity' => $oldQuantity,
                'new_quantity' => $newQuantity,
            ]
        ], 200);
    }

    /**
     * Get low stock alerts
     */
    public function lowStockAlerts(Request $request): JsonResponse
    {
        $threshold = $request->get('threshold', 10);

        $lowStockDrugs = Drug::where('status', 'active')
                            ->where('stock', '>', 0)
                            ->where('stock', '<=', $threshold)
                            ->with(['categories'])
                            ->orderBy('stock', 'asc')
                            ->get();

        return response()->json([
            'status' => 'success',
            'data' => [
                'threshold' => $threshold,
                'count' => $lowStockDrugs->count(),
                'drugs' => DrugResource::collection($lowStockDrugs),
            ]
        ], 200);
    }

    /**
     * Generate inventory report
     */
    public function report(Request $request): JsonResponse
    {
        $request->validate([
            'format' => 'in:summary,detailed',
            'include_inactive' => 'boolean',
        ]);

        $query = Drug::with(['categories']);

        if (!$request->boolean('include_inactive')) {
            $query->where('status', 'active');
        }

        $drugs = $query->get();

        $report = [
            'generated_at' => now(),
            'total_items' => $drugs->count(),
            'summary' => [
                'total_stock_value' => $drugs->sum(function ($drug) {
                    return $drug->stock * $drug->price;
                }),
                'total_quantity' => $drugs->sum('stock'),
                'categories' => $drugs->groupBy('categories.0.name')->map(function ($items, $category) {
                    return [
                        'count' => $items->count(),
                        'total_quantity' => $items->sum('stock'),
                        'total_value' => $items->sum(function ($drug) {
                            return $drug->stock * $drug->price;
                        }),
                    ];
                }),
            ],
        ];

        if ($request->format === 'detailed') {
            $report['items'] = DrugResource::collection($drugs);
        }

        return response()->json([
            'status' => 'success',
            'data' => $report
        ], 200);
    }

    /**
     * Bulk stock update
     */
    public function bulkUpdateStock(Request $request): JsonResponse
    {
        $request->validate([
            'updates' => 'required|array|min:1',
            'updates.*.drug_id' => 'required|exists:drugs,id',
            'updates.*.quantity' => 'required|integer|min:0',
            'updates.*.operation' => 'required|in:set,add,subtract',
        ]);

        $results = [];
        $errors = [];

        DB::transaction(function () use ($request, &$results, &$errors) {
            foreach ($request->updates as $update) {
                try {
                    $drug = Drug::findOrFail($update['drug_id']);
                    $oldQuantity = $drug->stock;

                    $newQuantity = match($update['operation']) {
                        'set' => $update['quantity'],
                        'add' => $oldQuantity + $update['quantity'],
                        'subtract' => max(0, $oldQuantity - $update['quantity']),
                    };

                    $drug->update(['stock' => $newQuantity]);

                    // Update status based on stock
                    if ($newQuantity === 0) {
                        $drug->update(['status' => 'out_of_stock']);
                    } elseif ($drug->status === 'out_of_stock' && $newQuantity > 0) {
                        $drug->update(['status' => 'active']);
                    }

                    $results[] = [
                        'drug_id' => $drug->id,
                        'drug_name' => $drug->name,
                        'old_quantity' => $oldQuantity,
                        'new_quantity' => $newQuantity,
                        'status' => 'success',
                    ];

                } catch (\Exception $e) {
                    $errors[] = [
                        'drug_id' => $update['drug_id'],
                        'error' => $e->getMessage(),
                    ];
                }
            }
        });

        return response()->json([
            'status' => empty($errors) ? 'success' : 'partial_success',
            'message' => 'Bulk stock update completed',
            'data' => [
                'successful_updates' => count($results),
                'failed_updates' => count($errors),
                'results' => $results,
                'errors' => $errors,
            ]
        ], 200);
    }
}
```

### Task 5.3: Drug Management CRUD

#### Create Admin Drug Controller

**File**: `app/Http/Controllers/Admin/DrugController.php`

```php
<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\DrugResource;
use App\Models\Drug;
use App\Models\DrugCategory;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class DrugController extends Controller
{
    public function __construct()
    {
        $this->middleware('admin');
    }

    /**
     * Store a new drug
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'expiry_date' => 'nullable|date|after:today',
            'image' => 'nullable|url',
            'categories' => 'array',
            'categories.*' => 'exists:drug_categories,id',
        ]);

        $slug = Str::slug($request->name);
        $originalSlug = $slug;
        $counter = 1;

        // Ensure unique slug
        while (Drug::where('slug', $slug)->exists()) {
            $slug = $originalSlug . '-' . $counter++;
        }

        $drug = Drug::create([
            'name' => $request->name,
            'slug' => $slug,
            'price' => $request->price,
            'stock' => $request->stock,
            'expiry_date' => $request->expiry_date,
            'image' => $request->image,
            'status' => $request->stock > 0 ? 'active' : 'out_of_stock',
        ]);

        // Attach categories
        if ($request->has('categories')) {
            $drug->categories()->attach($request->categories);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Drug created successfully',
            'data' => [
                'drug' => new DrugResource($drug->load('categories'))
            ]
        ], 201);
    }

    /**
     * Update an existing drug
     */
    public function update(Request $request, Drug $drug): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'expiry_date' => 'nullable|date|after:today',
            'image' => 'nullable|url',
            'status' => ['required', Rule::in(['active', 'inactive', 'out_of_stock'])],
            'categories' => 'array',
            'categories.*' => 'exists:drug_categories,id',
        ]);

        // Update slug if name changed
        $slug = $drug->slug;
        if ($request->name !== $drug->name) {
            $slug = Str::slug($request->name);
            $originalSlug = $slug;
            $counter = 1;

            while (Drug::where('slug', $slug)->where('id', '!=', $drug->id)->exists()) {
                $slug = $originalSlug . '-' . $counter++;
            }
        }

        $drug->update([
            'name' => $request->name,
            'slug' => $slug,
            'price' => $request->price,
            'stock' => $request->stock,
            'expiry_date' => $request->expiry_date,
            'dosage' => $request->dosage,
            'image' => $request->image,
            'status' => $request->status,
        ]);

        // Sync categories
        if ($request->has('categories')) {
            $drug->categories()->sync($request->categories);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Drug updated successfully',
            'data' => [
                'drug' => new DrugResource($drug->load('categories'))
            ]
        ], 200);
    }

    /**
     * Delete a drug
     */
    public function destroy(Drug $drug): JsonResponse
    {
        // Check if drug has any orders
        $hasOrders = $drug->orderItems()->exists();

        if ($hasOrders) {
            // Don't delete, just deactivate
            $drug->update(['status' => 'inactive']);

            return response()->json([
                'status' => 'success',
                'message' => 'Drug deactivated successfully (has existing orders)',
                'data' => [
                    'drug' => new DrugResource($drug)
                ]
            ], 200);
        }

        // Safe to delete
        $drug->categories()->detach();
        $drug->diseases()->detach();
        $drug->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Drug deleted successfully'
        ], 200);
    }

    /**
     * Get drug categories for selection
     */
    public function categories(): JsonResponse
    {
        $categories = DrugCategory::orderBy('name')->get();

        return response()->json([
            'status' => 'success',
            'data' => $categories
        ], 200);
    }

    /**
     * Bulk operations
     */
    public function bulkAction(Request $request): JsonResponse
    {
        $request->validate([
            'action' => 'required|in:activate,deactivate,delete',
            'drug_ids' => 'required|array|min:1',
            'drug_ids.*' => 'exists:drugs,id',
        ]);

        $drugs = Drug::whereIn('id', $request->drug_ids)->get();
        $results = [];
        $errors = [];

        foreach ($drugs as $drug) {
            try {
                switch ($request->action) {
                    case 'activate':
                        $drug->update(['status' => 'active']);
                        $results[] = ['id' => $drug->id, 'name' => $drug->name, 'action' => 'activated'];
                        break;

                    case 'deactivate':
                        $drug->update(['status' => 'inactive']);
                        $results[] = ['id' => $drug->id, 'name' => $drug->name, 'action' => 'deactivated'];
                        break;

                    case 'delete':
                        if ($drug->orderItems()->exists()) {
                            $drug->update(['status' => 'inactive']);
                            $results[] = ['id' => $drug->id, 'name' => $drug->name, 'action' => 'deactivated (has orders)'];
                        } else {
                            $drug->delete();
                            $results[] = ['id' => $drug->id, 'name' => $drug->name, 'action' => 'deleted'];
                        }
                        break;
                }
            } catch (\Exception $e) {
                $errors[] = ['id' => $drug->id, 'name' => $drug->name, 'error' => $e->getMessage()];
            }
        }

        return response()->json([
            'status' => empty($errors) ? 'success' : 'partial_success',
            'message' => 'Bulk action completed',
            'data' => [
                'successful' => count($results),
                'failed' => count($errors),
                'results' => $results,
                'errors' => $errors,
            ]
        ], 200);
    }
}
```

### Task 5.4: Analytics Service

#### Create Analytics Service

**File**: `app/Http/Services/AnalyticsService.php`

```php
<?php

namespace App\Http\Services;

use App\Models\Drug;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AnalyticsService
{
    public function getDashboardStats(): array
    {
        $today = Carbon::today();
        $yesterday = Carbon::yesterday();
        $thisMonth = Carbon::now()->startOfMonth();
        $lastMonth = Carbon::now()->subMonth()->startOfMonth();

        return [
            'overview' => $this->getOverviewStats(),
            'revenue' => $this->getRevenueStats($today, $yesterday, $thisMonth, $lastMonth),
            'orders' => $this->getOrderStats($today, $yesterday, $thisMonth, $lastMonth),
            'inventory' => $this->getInventoryStats(),
            'customers' => $this->getCustomerStats($today, $yesterday, $thisMonth, $lastMonth),
            'top_products' => $this->getTopSellingProducts(),
            'recent_orders' => $this->getRecentOrders(),
        ];
    }

    protected function getOverviewStats(): array
    {
        $totalRevenue = Payment::successful()->sum('amount');
        $totalOrders = Order::count();
        $totalCustomers = User::whereHas('role', function ($query) {
            $query->where('slug', '!=', 'admin');
        })->count();
        $totalProducts = Drug::count();

        return [
            'total_revenue' => $totalRevenue,
            'formatted_total_revenue' => '₦' . number_format($totalRevenue, 2),
            'total_orders' => $totalOrders,
            'total_customers' => $totalCustomers,
            'total_products' => $totalProducts,
        ];
    }

    protected function getRevenueStats($today, $yesterday, $thisMonth, $lastMonth): array
    {
        $todayRevenue = Payment::successful()
            ->whereDate('paid_at', $today)
            ->sum('amount');

        $yesterdayRevenue = Payment::successful()
            ->whereDate('paid_at', $yesterday)
            ->sum('amount');

        $thisMonthRevenue = Payment::successful()
            ->where('paid_at', '>=', $thisMonth)
            ->sum('amount');

        $lastMonthRevenue = Payment::successful()
            ->whereBetween('paid_at', [$lastMonth, $thisMonth])
            ->sum('amount');

        return [
            'today' => [
                'amount' => $todayRevenue,
                'formatted' => '₦' . number_format($todayRevenue, 2),
                'change_from_yesterday' => $this->calculatePercentageChange($todayRevenue, $yesterdayRevenue),
            ],
            'this_month' => [
                'amount' => $thisMonthRevenue,
                'formatted' => '₦' . number_format($thisMonthRevenue, 2),
                'change_from_last_month' => $this->calculatePercentageChange($thisMonthRevenue, $lastMonthRevenue),
            ],
            'last_30_days' => $this->getRevenueLast30Days(),
        ];
    }

    protected function getOrderStats($today, $yesterday, $thisMonth, $lastMonth): array
    {
        $todayOrders = Order::whereDate('placed_at', $today)->count();
        $yesterdayOrders = Order::whereDate('placed_at', $yesterday)->count();
        $thisMonthOrders = Order::where('placed_at', '>=', $thisMonth)->count();
        $lastMonthOrders = Order::whereBetween('placed_at', [$lastMonth, $thisMonth])->count();

        $ordersByStatus = Order::select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        return [
            'today' => [
                'count' => $todayOrders,
                'change_from_yesterday' => $this->calculatePercentageChange($todayOrders, $yesterdayOrders),
            ],
            'this_month' => [
                'count' => $thisMonthOrders,
                'change_from_last_month' => $this->calculatePercentageChange($thisMonthOrders, $lastMonthOrders),
            ],
            'by_status' => $ordersByStatus,
            'last_30_days' => $this->getOrdersLast30Days(),
        ];
    }

    protected function getInventoryStats(): array
    {
        $totalProducts = Drug::count();
        $activeProducts = Drug::where('status', 'active')->count();
        $outOfStock = Drug::where('stock', 0)->count();
        $lowStock = Drug::where('stock', '>', 0)
                        ->where('stock', '<=', 10)
                        ->count();

        $totalStockValue = Drug::where('status', 'active')
                              ->selectRaw('SUM(stock * price) as total')
                              ->value('total') ?? 0;

        return [
            'total_products' => $totalProducts,
            'active_products' => $activeProducts,
            'out_of_stock' => $outOfStock,
            'low_stock' => $lowStock,
            'total_stock_value' => $totalStockValue,
            'formatted_stock_value' => '₦' . number_format($totalStockValue, 2),
        ];
    }

    protected function getCustomerStats($today, $yesterday, $thisMonth, $lastMonth): array
    {
        $newToday = User::whereHas('role', function ($query) {
                $query->where('slug', '!=', 'admin');
            })
            ->whereDate('created_at', $today)
            ->count();

        $newYesterday = User::whereHas('role', function ($query) {
                $query->where('slug', '!=', 'admin');
            })
            ->whereDate('created_at', $yesterday)
            ->count();

        $newThisMonth = User::whereHas('role', function ($query) {
                $query->where('slug', '!=', 'admin');
            })
            ->where('created_at', '>=', $thisMonth)
            ->count();

        return [
            'new_today' => [
                'count' => $newToday,
                'change_from_yesterday' => $this->calculatePercentageChange($newToday, $newYesterday),
            ],
            'new_this_month' => $newThisMonth,
        ];
    }

    protected function getTopSellingProducts(int $limit = 10): array
    {
        return OrderItem::select('drug_id', 'drug_name')
            ->selectRaw('SUM(quantity) as total_sold')
            ->selectRaw('SUM(total_price) as total_revenue')
            ->whereHas('order', function ($query) {
                $query->where('payment_status', 'paid');
            })
            ->groupBy('drug_id', 'drug_name')
            ->orderBy('total_sold', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($item) {
                return [
                    'drug_id' => $item->drug_id,
                    'drug_name' => $item->drug_name,
                    'total_sold' => $item->total_sold,
                    'total_revenue' => $item->total_revenue,
                    'formatted_revenue' => '₦' . number_format($item->total_revenue, 2),
                ];
            })
            ->toArray();
    }

    protected function getRecentOrders(int $limit = 10): array
    {
        return Order::with(['user'])
            ->orderBy('placed_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($order) {
                return [
                    'id' => $order->id,
                    'order_number' => $order->order_number,
                    'customer_name' => $order->user->name,
                    'total_amount' => $order->total_amount,
                    'formatted_total' => $order->formatted_total,
                    'status' => $order->status->value,
                    'placed_at' => $order->placed_at,
                ];
            })
            ->toArray();
    }

    protected function getRevenueLast30Days(): array
    {
        $data = [];
        for ($i = 29; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i)->format('Y-m-d');
            $revenue = Payment::successful()
                ->whereDate('paid_at', $date)
                ->sum('amount');

            $data[] = [
                'date' => $date,
                'revenue' => $revenue,
                'formatted_revenue' => '₦' . number_format($revenue, 2),
            ];
        }

        return $data;
    }

    protected function getOrdersLast30Days(): array
    {
        $data = [];
        for ($i = 29; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i)->format('Y-m-d');
            $orders = Order::whereDate('placed_at', $date)->count();

            $data[] = [
                'date' => $date,
                'orders' => $orders,
            ];
        }

        return $data;
    }

    protected function calculatePercentageChange($current, $previous): float
    {
        if ($previous == 0) {
            return $current > 0 ? 100 : 0;
        }

        return round((($current - $previous) / $previous) * 100, 2);
    }

    public function getSalesReport(Carbon $startDate, Carbon $endDate): array
    {
        $orders = Order::with(['items'])
            ->whereBetween('placed_at', [$startDate, $endDate])
            ->where('payment_status', 'paid')
            ->get();

        $totalRevenue = $orders->sum('total_amount');
        $totalOrders = $orders->count();
        $averageOrderValue = $totalOrders > 0 ? $totalRevenue / $totalOrders : 0;

        $productSales = $orders->flatMap->items
            ->groupBy('drug_id')
            ->map(function ($items) {
                return [
                    'drug_name' => $items->first()->drug_name,
                    'total_quantity' => $items->sum('quantity'),
                    'total_revenue' => $items->sum('total_price'),
                ];
            })
            ->sortByDesc('total_revenue')
            ->values();

        return [
            'period' => [
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
            ],
            'summary' => [
                'total_revenue' => $totalRevenue,
                'formatted_total_revenue' => '₦' . number_format($totalRevenue, 2),
                'total_orders' => $totalOrders,
                'average_order_value' => $averageOrderValue,
                'formatted_average_order_value' => '₦' . number_format($averageOrderValue, 2),
            ],
            'product_sales' => $productSales,
            'daily_breakdown' => $this->getDailyBreakdown($startDate, $endDate),
        ];
    }

    protected function getDailyBreakdown(Carbon $startDate, Carbon $endDate): array
    {
        $data = [];
        $currentDate = $startDate->copy();

        while ($currentDate->lte($endDate)) {
            $dayRevenue = Payment::successful()
                ->whereDate('paid_at', $currentDate)
                ->sum('amount');

            $dayOrders = Order::whereDate('placed_at', $currentDate)
                ->where('payment_status', 'paid')
                ->count();

            $data[] = [
                'date' => $currentDate->format('Y-m-d'),
                'revenue' => $dayRevenue,
                'orders' => $dayOrders,
                'formatted_revenue' => '₦' . number_format($dayRevenue, 2),
            ];

            $currentDate->addDay();
        }

        return $data;
    }
}
```

### Task 5.5: Analytics Controller

#### Create Analytics Controller

**File**: `app/Http/Controllers/Admin/AnalyticsController.php`

```php
<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Services\AnalyticsService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AnalyticsController extends Controller
{
    protected AnalyticsService $analyticsService;

    public function __construct(AnalyticsService $analyticsService)
    {
        $this->middleware('admin');
        $this->analyticsService = $analyticsService;
    }

    /**
     * Get dashboard analytics
     */
    public function dashboard(): JsonResponse
    {
        $stats = $this->analyticsService->getDashboardStats();

        return response()->json([
            'status' => 'success',
            'data' => $stats
        ], 200);
    }

    /**
     * Get sales report
     */
    public function salesReport(Request $request): JsonResponse
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $startDate = Carbon::parse($request->start_date)->startOfDay();
        $endDate = Carbon::parse($request->end_date)->endOfDay();

        // Limit to maximum 1 year range
        if ($startDate->diffInDays($endDate) > 365) {
            return response()->json([
                'status' => 'error',
                'message' => 'Date range cannot exceed 365 days'
            ], 400);
        }

        $report = $this->analyticsService->getSalesReport($startDate, $endDate);

        return response()->json([
            'status' => 'success',
            'data' => $report
        ], 200);
    }

    /**
     * Get revenue analytics
     */
    public function revenue(Request $request): JsonResponse
    {
        $period = $request->get('period', '30d'); // 7d, 30d, 90d, 1y

        $endDate = Carbon::now();
        $startDate = match($period) {
            '7d' => Carbon::now()->subDays(7),
            '30d' => Carbon::now()->subDays(30),
            '90d' => Carbon::now()->subDays(90),
            '1y' => Carbon::now()->subYear(),
            default => Carbon::now()->subDays(30),
        };

        $report = $this->analyticsService->getSalesReport($startDate, $endDate);

        return response()->json([
            'status' => 'success',
            'data' => [
                'period' => $period,
                'report' => $report,
            ]
        ], 200);
    }

    /**
     * Get top performing products
     */
    public function topProducts(Request $request): JsonResponse
    {
        $limit = min($request->get('limit', 20), 100); // Max 100
        $period = $request->get('period', '30d');

        $endDate = Carbon::now();
        $startDate = match($period) {
            '7d' => Carbon::now()->subDays(7),
            '30d' => Carbon::now()->subDays(30),
            '90d' => Carbon::now()->subDays(90),
            '1y' => Carbon::now()->subYear(),
            default => Carbon::now()->subDays(30),
        };

        $topProducts = \App\Models\OrderItem::select('drug_id', 'drug_name')
            ->selectRaw('SUM(quantity) as total_sold')
            ->selectRaw('SUM(total_price) as total_revenue')
            ->selectRaw('COUNT(DISTINCT order_id) as total_orders')
            ->whereHas('order', function ($query) use ($startDate, $endDate) {
                $query->where('payment_status', 'paid')
                      ->whereBetween('placed_at', [$startDate, $endDate]);
            })
            ->groupBy('drug_id', 'drug_name')
            ->orderBy('total_sold', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($item) {
                return [
                    'drug_id' => $item->drug_id,
                    'drug_name' => $item->drug_name,
                    'total_sold' => $item->total_sold,
                    'total_revenue' => $item->total_revenue,
                    'total_orders' => $item->total_orders,
                    'formatted_revenue' => '₦' . number_format($item->total_revenue, 2),
                    'average_order_quantity' => round($item->total_sold / $item->total_orders, 2),
                ];
            });

        return response()->json([
            'status' => 'success',
            'data' => [
                'period' => $period,
                'products' => $topProducts,
            ]
        ], 200);
    }

    /**
     * Get customer analytics
     */
    public function customers(Request $request): JsonResponse
    {
        $period = $request->get('period', '30d');

        $endDate = Carbon::now();
        $startDate = match($period) {
            '7d' => Carbon::now()->subDays(7),
            '30d' => Carbon::now()->subDays(30),
            '90d' => Carbon::now()->subDays(90),
            '1y' => Carbon::now()->subYear(),
            default => Carbon::now()->subDays(30),
        };

        $totalCustomers = \App\Models\User::whereHas('role', function ($query) {
            $query->where('slug', '!=', 'admin');
        })->count();

        $newCustomers = \App\Models\User::whereHas('role', function ($query) {
            $query->where('slug', '!=', 'admin');
        })
        ->whereBetween('created_at', [$startDate, $endDate])
        ->count();

        $activeCustomers = \App\Models\User::whereHas('orders', function ($query) use ($startDate, $endDate) {
            $query->whereBetween('placed_at', [$startDate, $endDate])
                  ->where('payment_status', 'paid');
        })->count();

        $topCustomers = \App\Models\User::with(['role'])
            ->whereHas('orders', function ($query) use ($startDate, $endDate) {
                $query->whereBetween('placed_at', [$startDate, $endDate])
                      ->where('payment_status', 'paid');
            })
            ->withSum(['orders as total_spent' => function ($query) use ($startDate, $endDate) {
                $query->whereBetween('placed_at', [$startDate, $endDate])
                      ->where('payment_status', 'paid');
            }], 'total_amount')
            ->withCount(['orders as total_orders' => function ($query) use ($startDate, $endDate) {
                $query->whereBetween('placed_at', [$startDate, $endDate])
                      ->where('payment_status', 'paid');
            }])
            ->orderBy('total_spent', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($customer) {
                return [
                    'id' => $customer->id,
                    'name' => $customer->name,
                    'email' => $customer->email,
                    'total_spent' => $customer->total_spent ?? 0,
                    'formatted_total_spent' => '₦' . number_format($customer->total_spent ?? 0, 2),
                    'total_orders' => $customer->total_orders ?? 0,
                    'average_order_value' => $customer->total_orders > 0
                        ? round(($customer->total_spent ?? 0) / $customer->total_orders, 2)
                        : 0,
                ];
            });

        return response()->json([
            'status' => 'success',
            'data' => [
                'period' => $period,
                'summary' => [
                    'total_customers' => $totalCustomers,
                    'new_customers' => $newCustomers,
                    'active_customers' => $activeCustomers,
                    'retention_rate' => $totalCustomers > 0 ? round(($activeCustomers / $totalCustomers) * 100, 2) : 0,
                ],
                'top_customers' => $topCustomers,
            ]
        ], 200);
    }
}
```

### Task 5.6: Routes

#### Add Admin Routes

**File**: `routes/api.php` (add these routes)

```php
// Admin routes
Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->group(function () {

    // Drug management
    Route::prefix('drugs')->group(function () {
        Route::post('/', [Admin\DrugController::class, 'store']);
        Route::put('/{drug}', [Admin\DrugController::class, 'update']);
        Route::delete('/{drug}', [Admin\DrugController::class, 'destroy']);
        Route::get('/categories', [Admin\DrugController::class, 'categories']);
        Route::post('/bulk-action', [Admin\DrugController::class, 'bulkAction']);
    });

    // Inventory management
    Route::prefix('inventory')->group(function () {
        Route::get('/', [Admin\InventoryController::class, 'index']);
        Route::get('/statistics', [Admin\InventoryController::class, 'statistics']);
        Route::put('/{drug}/stock', [Admin\InventoryController::class, 'updateStock']);
        Route::get('/low-stock-alerts', [Admin\InventoryController::class, 'lowStockAlerts']);
        Route::get('/report', [Admin\InventoryController::class, 'report']);
        Route::post('/bulk-update-stock', [Admin\InventoryController::class, 'bulkUpdateStock']);
    });

    // Analytics
    Route::prefix('analytics')->group(function () {
        Route::get('/dashboard', [Admin\AnalyticsController::class, 'dashboard']);
        Route::get('/sales-report', [Admin\AnalyticsController::class, 'salesReport']);
        Route::get('/revenue', [Admin\AnalyticsController::class, 'revenue']);
        Route::get('/top-products', [Admin\AnalyticsController::class, 'topProducts']);
        Route::get('/customers', [Admin\AnalyticsController::class, 'customers']);
    });

    // Order management (from Phase 3)
    Route::prefix('orders')->group(function () {
        Route::get('/', [Admin\OrderManagementController::class, 'index']);
        Route::get('/analytics', [Admin\OrderManagementController::class, 'analytics']);
        Route::get('/requires-attention', [Admin\OrderManagementController::class, 'requiresAttention']);
        Route::get('/{order}', [Admin\OrderManagementController::class, 'show']);
        Route::put('/{order}/status', [Admin\OrderManagementController::class, 'updateStatus']);
        Route::post('/{order}/mark-delivering', [Admin\OrderManagementController::class, 'markAsDelivering']);
    });
});
```

### Task 5.7: Add Relationship to Drug Model

#### Update Drug Model

**File**: `app/Models/Drug.php` (add this relationship)

```php
// Add this to the Drug model
public function orderItems(): HasMany
{
    return $this->hasMany(OrderItem::class);
}
```

## Testing

### API Endpoints to Test

1. **GET /api/admin/analytics/dashboard** - Dashboard analytics
2. **GET /api/admin/inventory** - Inventory listing
3. **PUT /api/admin/inventory/{drug}/stock** - Update stock
4. **POST /api/admin/drugs** - Create drug
5. **PUT /api/admin/drugs/{drug}** - Update drug
6. **DELETE /api/admin/drugs/{drug}** - Delete drug
7. **GET /api/admin/analytics/sales-report** - Sales report
8. **GET /api/admin/inventory/low-stock-alerts** - Low stock alerts

### Test Scenarios

-   Admin authentication and authorization
-   CRUD operations for drugs
-   Inventory management and stock updates
-   Analytics data accuracy
-   Bulk operations functionality
-   Report generation
-   Low stock alert system

## Success Criteria

-   [ ] Admin-only access is properly enforced
-   [ ] Drug CRUD operations work correctly
-   [ ] Inventory management functions properly
-   [ ] Stock updates reflect in real-time
-   [ ] Analytics provide accurate insights
-   [ ] Bulk operations handle errors gracefully
-   [ ] Reports generate correct data
-   [ ] Low stock alerts work as expected
-   [ ] All admin endpoints are secure and performant

## Completion

Phase 5 provides comprehensive admin management capabilities for the e-pharmacy system. All core functionality for backend e-pharmacy operations is now complete across all 5 phases.
