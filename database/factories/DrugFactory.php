<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Drug>
 */
class DrugFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = $this->faker->randomElement([
            'Paracetamol',
            'Ibuprofen',
            'Amoxicillin',
            'Omeprazole',
            'Aspirin',
            'Metformin',
            'Atorvastatin',
            'Amlodipine',
            'Cetirizine',
            'Loratadine',
            'Diclofenac',
            'Azithromycin',
            'Ciprofloxacin',
            'Prednisone',
            'Insulin'
        ]);

        $baseSlug = Str::slug($name);
        $uniqueSlug = $baseSlug . '-' . $this->faker->unique()->randomNumber(5);

        return [
            'name' => $name,
            'slug' => $uniqueSlug,
            'description' => $this->faker->paragraph(3),
            'price' => $this->faker->randomFloat(2, 50, 5000),
            'stock' => $this->faker->numberBetween(0, 1000),
            'expiry_date' => $this->faker->dateTimeBetween('+6 months', '+3 years'),
            'image' => $this->faker->imageUrl(400, 400, 'medicine'),
            'status' => $this->faker->randomElement(['active', 'active', 'active', 'inactive']), // 75% active
        ];
    }

    public function active(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'active',
            'stock' => $this->faker->numberBetween(10, 1000),
        ]);
    }

    public function outOfStock(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'out_of_stock',
            'stock' => 0,
        ]);
    }
}
