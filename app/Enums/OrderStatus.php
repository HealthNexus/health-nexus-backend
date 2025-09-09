<?php

namespace App\Enums;

enum OrderStatus: string
{
    case PLACED = 'placed';
    case DELIVERING = 'delivering';
    case DELIVERED = 'delivered';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::PLACED => 'Order Placed',
            self::DELIVERING => 'Delivering',
            self::DELIVERED => 'Delivered',
            self::CANCELLED => 'Cancelled',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::PLACED => 'Your order has been placed and is being processed',
            self::DELIVERING => 'Your order is on the way',
            self::DELIVERED => 'Your order has been delivered',
            self::CANCELLED => 'Your order has been cancelled',
        };
    }

    public function canTransitionTo(OrderStatus $status): bool
    {
        return match ($this) {
            self::PLACED => in_array($status, [self::DELIVERING, self::CANCELLED]),
            self::DELIVERING => in_array($status, [self::DELIVERED, self::CANCELLED]),
            self::DELIVERED => false,
            self::CANCELLED => false,
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
