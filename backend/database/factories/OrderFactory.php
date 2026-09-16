<?php

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'customer_name' => fake()->name(),
            'status' => OrderStatus::Pending,
            'total' => 0,
        ];
    }

    public function completed(): static
    {
        return $this->state(fn () => ['status' => OrderStatus::Completed]);
    }
}
