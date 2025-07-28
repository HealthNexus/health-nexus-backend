<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DrugResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'price' => $this->price,
            'formatted_price' => $this->formatted_price,
            'stock' => $this->stock,
            'expiry_date' => $this->expiry_date?->format('Y-m-d'),
            'image' => $this->image,
            'status' => $this->status,
            'is_available' => $this->isAvailable(),
            'is_expired' => $this->is_expired,
            'categories' => DrugCategoryResource::collection($this->whenLoaded('categories')),
            'diseases' => DiseaseResource::collection($this->whenLoaded('diseases')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
