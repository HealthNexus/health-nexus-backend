<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Http\Resources\OrderResource;
use App\Http\Services\OrderService;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class OrderController extends Controller
{
    protected OrderService $orderService;

    public function __construct(OrderService $orderService)
    {
        $this->orderService = $orderService;
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
     * Create order from frontend cart items
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.drug_id' => 'required|exists:drugs,id',
            'items.*.quantity' => 'required|integer|min:1|max:100',
            
            'phone_number' => 'required|string|max:20',
            'delivery_notes' => 'nullable|string|max:500',

            // Delivery fields for in-city delivery
            'delivery_area' => 'nullable|string|max:100',
            'delivery_address' => 'required|string|max:500',
            'landmark' => 'nullable|string|max:255',
            'delivery_fee' => 'nullable|numeric|min:0',
        ]);

        try {
            $orderData = [
                'phone_number' => $request->phone_number,
                'delivery_notes' => $request->delivery_notes,
                'delivery_area' => $request->delivery_area,
                'delivery_address' => $request->delivery_address,
                'landmark' => $request->landmark,
                'delivery_fee' => $request->delivery_fee ?? 0,
            ];

            $order = $this->orderService->createOrderFromItems(
                $request->items, 
                $orderData, 
                $request->user()
            );

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
