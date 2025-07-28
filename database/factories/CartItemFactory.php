<?php

namespace Database\Factories;

use App\Models\Cart;
use App\Models\Drug;
use Illuminate\Database\Eloquent\Factories\Factory;

class CartItemFactory extends Factory
{
    public function definition(): array
    {
        $quantity = $this->faker->numberBetween(1, 5);
        $unitPrice = $this->faker->randomFloat(2, 50, 1000);

        return [
            'cart_id' => Cart::factory(),
            'drug_id' => Drug::factory(),
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'total_price' => $unitPrice * $quantity,
        ];
    }
}
