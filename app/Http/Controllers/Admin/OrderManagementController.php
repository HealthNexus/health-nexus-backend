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
