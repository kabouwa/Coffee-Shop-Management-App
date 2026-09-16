<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->randomElement(['Espresso', 'Cappuccino', 'Latte', 'Americano', 'Mocha', 'Croissant', 'Muffin']),
            'description' => fake()->sentence(),
            'price' => fake()->randomFloat(2, 2.5, 8.5),
            'category' => fake()->randomElement(['Coffee', 'Tea', 'Pastry', 'Sandwich']),
            'image' => null,
            'available' => true,
        ];
    }

    public function unavailable(): static
    {
        return $this->state(fn () => ['available' => false]);
    }
}
