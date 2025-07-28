<?php

namespace App\Http\Services;

use App\Models\DeliveryArea;
use App\Models\Order;
use Illuminate\Support\Collection;

class DeliveryService
{
    /**
     * Get all available delivery areas
     */
    public function getDeliveryAreas(): Collection
    {
        return DeliveryArea::active()->ordered()->get();
    }

    /**
     * Get delivery areas as array (for backward compatibility)
     */
    public function getDeliveryAreasArray(): array
    {
        return DeliveryArea::active()
            ->ordered()
            ->get()
            ->mapWithKeys(function ($area) {
                return [$area->code => [
                    'name' => $area->name,
                    'base_fee' => $area->base_fee,
                    'description' => $area->description,
                    'landmarks' => $area->landmarks
                ]];
            })
            ->toArray();
    }

    /**
     * Calculate delivery fee based on area
     */
    public function calculateDeliveryFee(string $areaCode, float $orderValue = 0): float
    {
        $area = DeliveryArea::where('code', $areaCode)->where('is_active', true)->first();

        if (!$area) {
            // Default fee if area not found
            $baseFee = 300;
        } else {
            $baseFee = $area->base_fee;
        }

        // Free delivery for orders above ₵100 (₦10,000 equivalent in Ghana Cedis)
        if ($orderValue >= 100) {
            return 0;
        }

        // 50% discount for orders above ₵50 (₦5,000 equivalent in Ghana Cedis)
        if ($orderValue >= 50) {
            $baseFee *= 0.5;
        }

        return round($baseFee, 2);
    }

    /**
     * Get delivery time estimate
     */
    public function getDeliveryTimeEstimate(): string
    {
        return '1-2 business days';
    }

    /**
     * Validate delivery area
     */
    public function isValidDeliveryArea(string $areaCode): bool
    {
        return DeliveryArea::where('code', $areaCode)
            ->where('is_active', true)
            ->exists();
    }

    /**
     * Get delivery area by code
     */
    public function getDeliveryAreaByCode(string $areaCode): ?DeliveryArea
    {
        return DeliveryArea::where('code', $areaCode)
            ->where('is_active', true)
            ->first();
    }

    /**
     * Get orders by delivery area for admin
     */
    public function getOrdersByArea(string $areaCode): Collection
    {
        return Order::where('delivery_area', $areaCode)
            ->whereIn('status', ['placed', 'delivering'])
            ->orderBy('placed_at', 'asc')
            ->get();
    }

    /**
     * Get delivery statistics
     */
    public function getDeliveryStatistics(): array
    {
        $totalOrders = Order::count();
        $pendingDeliveries = Order::where('status', 'placed')->count();
        $deliveringOrders = Order::where('status', 'delivering')->count();
        $deliveredOrders = Order::where('status', 'delivered')->count();

        $ordersByArea = Order::selectRaw('delivery_area, COUNT(*) as count')
            ->groupBy('delivery_area')
            ->pluck('count', 'delivery_area')
            ->toArray();

        // Get delivery areas with their statistics
        $deliveryAreas = DeliveryArea::active()
            ->ordered()
            ->withCount(['orders as pending_orders_count' => function ($query) {
                $query->whereIn('status', ['placed', 'delivering']);
            }])
            ->get();

        return [
            'totals' => [
                'total_orders' => $totalOrders,
                'pending_deliveries' => $pendingDeliveries,
                'delivering_orders' => $deliveringOrders,
                'delivered_orders' => $deliveredOrders,
            ],
            'by_area' => $ordersByArea,
            'delivery_areas' => $deliveryAreas,
        ];
    }

    /**
     * Optimize delivery routes by area
     */
    public function getOptimizedDeliveryRoutes(): array
    {
        $routes = [];
        $deliveryAreas = DeliveryArea::active()->get();

        foreach ($deliveryAreas as $area) {
            $orders = $this->getOrdersByArea($area->code);

            if ($orders->isNotEmpty()) {
                $routes[$area->code] = [
                    'area_info' => [
                        'code' => $area->code,
                        'name' => $area->name,
                        'description' => $area->description,
                        'base_fee' => $area->base_fee,
                        'landmarks' => $area->landmarks
                    ],
                    'order_count' => $orders->count(),
                    'total_value' => $orders->sum('total_amount'),
                    'orders' => $orders->map(function ($order) {
                        return [
                            'id' => $order->id,
                            'order_number' => $order->order_number,
                            'delivery_address' => $order->delivery_address,
                            'landmark' => $order->landmark,
                            'phone_number' => $order->phone_number,
                            'total_amount' => $order->total_amount,
                        ];
                    })
                ];
            }
        }

        // Sort routes by order count (areas with more orders first)
        uasort($routes, function ($a, $b) {
            return $b['order_count'] <=> $a['order_count'];
        });

        return $routes;
    }

    /**
     * Update delivery area
     */
    public function updateDeliveryArea(string $areaCode, array $data): bool
    {
        $area = DeliveryArea::where('code', $areaCode)->first();

        if (!$area) {
            return false;
        }

        return $area->update($data);
    }

    /**
     * Create new delivery area
     */
    public function createDeliveryArea(array $data): DeliveryArea
    {
        return DeliveryArea::create($data);
    }

    /**
     * Toggle delivery area status
     */
    public function toggleDeliveryAreaStatus(string $areaCode): bool
    {
        $area = DeliveryArea::where('code', $areaCode)->first();

        if (!$area) {
            return false;
        }

        $area->is_active = !$area->is_active;
        return $area->save();
    }
}
