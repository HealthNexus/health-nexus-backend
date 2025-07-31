<?php

namespace App\Http\Controllers;

use App\Http\Services\DeliveryService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeliveryController extends Controller
{
    use AuthorizesRequests;

    private DeliveryService $deliveryService;

    public function __construct(DeliveryService $deliveryService)
    {
        $this->deliveryService = $deliveryService;
    }

    /**
     * Get all delivery areas with fees
     */
    public function getDeliveryAreas(): JsonResponse
    {
        $areas = $this->deliveryService->getDeliveryAreas();

        return response()->json([
            'status' => 'success',
            'data' => $areas->map(function ($area) {
                return [
                    'code' => $area->code,
                    'name' => $area->name,
                    'description' => $area->description,
                    'base_fee' => $area->base_fee,
                    'formatted_fee' => $area->formatted_fee,
                    'landmarks' => $area->landmarks,
                    'is_active' => $area->is_active,
                    'pending_orders_count' => $area->pending_orders_count ?? 0,
                ];
            })
        ]);
    }

    /**
     * Calculate delivery fee
     */
    public function calculateDeliveryFee(Request $request): JsonResponse
    {
        $request->validate([
            'delivery_area' => 'required|string',
            'order_value' => 'nullable|numeric|min:0'
        ]);

        $area = $request->delivery_area;
        $orderValue = $request->order_value ?? 0;

        if (!$this->deliveryService->isValidDeliveryArea($area)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid delivery area'
            ], 400);
        }

        $deliveryFee = $this->deliveryService->calculateDeliveryFee($area, $orderValue);
        $estimatedTime = $this->deliveryService->getDeliveryTimeEstimate();

        return response()->json([
            'status' => 'success',
            'data' => [
                'delivery_fee' => $deliveryFee,
                'estimated_delivery_time' => $estimatedTime,
                'formatted_fee' => '₵' . number_format($deliveryFee, 2),
                'free_delivery_threshold' => 10000,
                'discount_threshold' => 5000,
                'is_free_delivery' => $deliveryFee == 0,
            ]
        ]);
    }

    /**
     * Get delivery statistics (Admin only)
     */
    public function getDeliveryStatistics(): JsonResponse
    {
        $this->authorize('viewAny', \App\Models\Order::class);

        $statistics = $this->deliveryService->getDeliveryStatistics();

        return response()->json([
            'status' => 'success',
            'data' => $statistics
        ]);
    }

    /**
     * Get optimized delivery routes (Admin only)
     */
    public function getDeliveryRoutes(): JsonResponse
    {
        $this->authorize('viewAny', \App\Models\Order::class);

        $routes = $this->deliveryService->getOptimizedDeliveryRoutes();

        return response()->json([
            'status' => 'success',
            'data' => [
                'routes' => $routes,
                'total_routes' => count($routes),
                'total_orders' => array_sum(array_column($routes, 'order_count')),
            ]
        ]);
    }

    /**
     * Get orders by delivery area (Admin only)
     */
    public function getOrdersByArea(Request $request, string $area): JsonResponse
    {
        $this->authorize('viewAny', \App\Models\Order::class);

        if (!$this->deliveryService->isValidDeliveryArea($area)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid delivery area'
            ], 400);
        }

        $orders = $this->deliveryService->getOrdersByArea($area);

        return response()->json([
            'status' => 'success',
            'data' => [
                'area' => $area,
                'orders' => $orders,
                'total_orders' => $orders->count(),
                'total_value' => $orders->sum('total_amount'),
            ]
        ]);
    }

    /**
     * Create new delivery area (Admin only)
     */
    public function createDeliveryArea(Request $request): JsonResponse
    {
        $this->authorize('viewAny', \App\Models\Order::class);

        $request->validate([
            'code' => 'required|string|max:50|unique:delivery_areas,code',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'base_fee' => 'required|numeric|min:0',
            'landmarks' => 'nullable|array',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $area = $this->deliveryService->createDeliveryArea($request->all());

        return response()->json([
            'status' => 'success',
            'message' => 'Delivery area created successfully',
            'data' => $area
        ], 201);
    }

    /**
     * Update delivery area (Admin only)
     */
    public function updateDeliveryArea(Request $request, string $areaCode): JsonResponse
    {
        $this->authorize('viewAny', \App\Models\Order::class);

        $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'base_fee' => 'sometimes|numeric|min:0',
            'landmarks' => 'nullable|array',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $updated = $this->deliveryService->updateDeliveryArea($areaCode, $request->all());

        if (!$updated) {
            return response()->json([
                'status' => 'error',
                'message' => 'Delivery area not found'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Delivery area updated successfully'
        ]);
    }

    /**
     * Toggle delivery area status (Admin only)
     */
    public function toggleDeliveryAreaStatus(string $areaCode): JsonResponse
    {
        $this->authorize('viewAny', \App\Models\Order::class);

        $toggled = $this->deliveryService->toggleDeliveryAreaStatus($areaCode);

        if (!$toggled) {
            return response()->json([
                'status' => 'error',
                'message' => 'Delivery area not found'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Delivery area status updated successfully'
        ]);
    }
}
