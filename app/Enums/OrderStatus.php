<?php

namespace App\Enums;

enum OrderStatus: string
{
    case PLACED = 'placed';
    case DELIVERING = 'delivering';
    case DELIVERED = 'delivered';

    public function label(): string
    {
        return match ($this) {
            self::PLACED => 'Order Placed',
            self::DELIVERING => 'Delivering',
            self::DELIVERED => 'Delivered',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::PLACED => 'Your order has been placed and is being processed',
            self::DELIVERING => 'Your order is on the way',
            self::DELIVERED => 'Your order has been delivered',
        };
    }

    public function canTransitionTo(OrderStatus $status): bool
    {
        return match ($this) {
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
