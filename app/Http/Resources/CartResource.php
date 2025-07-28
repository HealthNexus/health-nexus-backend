<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'items' => CartItemResource::collection($this->whenLoaded('items')),
            'totals' => [
                'subtotal' => $this->subtotal,
                'tax_amount' => $this->tax_amount,
                'total_amount' => $this->total_amount,
                'total_items' => $this->total_items,
                'formatted_subtotal' => '₦' . number_format($this->subtotal, 2),
                'formatted_tax_amount' => '₦' . number_format($this->tax_amount, 2),
                'formatted_total_amount' => '₦' . number_format($this->total_amount, 2),
            ],
            'is_empty' => $this->isEmpty(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
