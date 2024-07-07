<?php

namespace Database\Factories;

use App\Models\Post;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Post>
 */
class PostFactory extends Factory
{
    protected $model = Post::class;
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->unique()->sentence(),
            'excerpt' => fake()->paragraph(1),
            'body' => fake()->paragraph(10),
            'thumbnail' => fake()->unique()->word(),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
