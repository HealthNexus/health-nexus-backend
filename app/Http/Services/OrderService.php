<?php

namespace App\Http\Services;

use App\Enums\OrderStatus;
use App\Models\Drug;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class OrderService
{
    public function createOrderFromItems(array $items, array $orderData, User $user): Order
    {
        return DB::transaction(function () use ($items, $orderData, $user) {
            // Validate items before creating order
            $validatedItems = $this->validateItemsForOrder($items);

            // Calculate totals
            $totals = $this->calculateOrderTotals($validatedItems, $orderData['delivery_fee'] ?? 0);

            // Create the order
            $order = Order::create([
                'user_id' => $user->id,
                'subtotal' => $totals['subtotal'],
                'tax_amount' => $totals['tax_amount'],
                'total_amount' => $totals['total_amount'],
                'total_items' => $totals['total_items'],
                'phone_number' => $orderData['phone_number'],
                'delivery_notes' => $orderData['delivery_notes'] ?? null,
                'delivery_area' => $orderData['delivery_area'] ?? null,
                'delivery_address' => $orderData['delivery_address'],
                'landmark' => $orderData['landmark'] ?? null,
                'delivery_fee' => $orderData['delivery_fee'] ?? 0,
                'status' => OrderStatus::PLACED,
                'payment_status' => 'pending',
            ]);

            // Create order items and update inventory
            foreach ($validatedItems as $item) {
                $drug = $item['drug'];
                $quantity = $item['quantity'];

                // Create order item with drug details at time of order
                $order->items()->create([
                    'drug_id' => $drug->id,
                    'drug_name' => $drug->name,
                    'drug_slug' => $drug->slug,
                    'drug_description' => $drug->description,
                    'quantity' => $quantity,
                    'unit_price' => $drug->price,
                    'total_price' => $quantity * $drug->price,
                ]);

                // Reduce inventory
                $drug->decrementStock($quantity);
            }

            return $order->load('items');
        });
    }

    protected function validateItemsForOrder(array $items): array
    {
        $validatedItems = [];

        foreach ($items as $item) {
            $drug = Drug::findOrFail($item['drug_id']);
            $quantity = (int) $item['quantity'];

            // Check if drug is available
            if (!$drug->isAvailable()) {
                throw new \InvalidArgumentException("Drug '{$drug->name}' is no longer available");
            }

            // Check stock availability
            if (!$drug->isInStock($quantity)) {
                throw new \InvalidArgumentException("Insufficient stock for '{$drug->name}'. Only {$drug->stock} available");
            }

            $validatedItems[] = [
                'drug' => $drug,
                'quantity' => $quantity,
            ];
        }

        return $validatedItems;
    }

    protected function calculateOrderTotals(array $validatedItems, float $deliveryFee): array
    {
        $subtotal = 0;
        $totalItems = 0;

        foreach ($validatedItems as $item) {
            $subtotal += $item['drug']->price * $item['quantity'];
            $totalItems += $item['quantity'];
        }

        $taxAmount = 0; // No tax for now, but can be calculated here
        $totalAmount = $subtotal + $taxAmount + $deliveryFee;

        return [
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'total_amount' => $totalAmount,
            'total_items' => $totalItems,
        ];
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
            throw new \Exception('You can only confirm your own orders');
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

    public function getOrderStatistics(?User $user = null): array
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
            'formatted_total_revenue' => '₦' . number_format($totalRevenue, 2),
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
