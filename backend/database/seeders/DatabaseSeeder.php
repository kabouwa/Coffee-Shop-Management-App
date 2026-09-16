<?php

namespace Database\Seeders;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $managerA = User::factory()->create([
            'name' => 'Manager A',
            'email' => 'manager.a@coffee.test',
            'password' => 'password',
        ]);

        $managerA->shop()->create([
            'shop_name' => 'Coffee Corner A',
            'address' => '12 Rue des Fleurs',
            'zipcode' => '10000',
            'city' => 'Rabat',
            'country' => 'Morocco',
        ]);

        $managerB = User::factory()->create([
            'name' => 'Manager B',
            'email' => 'manager.b@coffee.test',
            'password' => 'password',
        ]);

        $managerB->shop()->create([
            'shop_name' => 'Tea House B',
            'address' => '45 Avenue Hassan II',
            'zipcode' => '20000',
            'city' => 'Casablanca',
            'country' => 'Morocco',
        ]);

        $this->seedShop($managerA, [
            ['Espresso', 'Coffee', 2.80],
            ['Cappuccino', 'Coffee', 3.90],
            ['Latte', 'Coffee', 4.20],
            ['Croissant', 'Pastry', 2.50],
        ]);

        $this->seedShop($managerB, [
            ['Matcha Latte', 'Tea', 4.80],
            ['Earl Grey', 'Tea', 3.10],
            ['Blueberry Muffin', 'Pastry', 3.00],
        ]);
    }

    /**
     * @param  list<array{0: string, 1: string, 2: float}>  $catalog
     */
    private function seedShop(User $user, array $catalog): void
    {
        $products = collect($catalog)->map(fn (array $item) => Product::factory()->create([
            'user_id' => $user->id,
            'name' => $item[0],
            'category' => $item[1],
            'price' => $item[2],
        ]));

        foreach (range(1, 8) as $index) {
            $selected = $products->random(fake()->numberBetween(1, min(3, $products->count())));
            $total = 0;
            $status = fake()->randomElement(OrderStatus::cases());

            $order = Order::factory()->create([
                'user_id' => $user->id,
                'status' => $status,
                'created_at' => now()->subDays(fake()->numberBetween(0, 12)),
            ]);

            foreach ($selected as $product) {
                $quantity = fake()->numberBetween(1, 3);
                $subtotal = round((float) $product->price * $quantity, 2);
                $total += $subtotal;

                OrderItem::factory()->create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'unit_price' => $product->price,
                    'subtotal' => $subtotal,
                ]);
            }

            $order->update(['total' => $total]);
        }
    }
}
