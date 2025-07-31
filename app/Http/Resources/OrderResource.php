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
                'formatted_subtotal' => config('payment.currency_symbol') . number_format($this->subtotal, 2),
                'formatted_tax_amount' => config('payment.currency_symbol') . number_format($this->tax_amount, 2),
                'formatted_total_amount' => $this->formatted_total,
            ],
            'delivery' => [
                'area' => $this->delivery_area,
                'address' => $this->delivery_address,
                'landmark' => $this->landmark,
                'fee' => $this->delivery_fee,
                'formatted_fee' => config('payment.currency_symbol') . number_format($this->delivery_fee, 2),
                'notes' => $this->delivery_notes,
            ],
            'phone_number' => $this->phone_number,
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
            'created_at' => $this->created_at->diffForHumans(),
            'updated_at' => $this->updated_at,
        ];
    }
}
