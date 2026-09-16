<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_manager_cannot_view_another_managers_product(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $product = Product::factory()->create(['user_id' => $owner->id]);

        Sanctum::actingAs($intruder);

        $this->getJson("/api/products/{$product->id}")->assertNotFound();
    }

    public function test_product_index_only_returns_owned_products(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        Product::factory()->create(['user_id' => $owner->id, 'name' => 'Owner Latte']);
        Product::factory()->create(['user_id' => $other->id, 'name' => 'Other Mocha']);

        Sanctum::actingAs($owner);

        $this->getJson('/api/products')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Owner Latte');
    }

    public function test_a_manager_cannot_create_an_order_with_another_shops_product(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $product = Product::factory()->create(['user_id' => $owner->id]);

        Sanctum::actingAs($intruder);

        $this->postJson('/api/orders', [
            'customer_name' => 'Walk-in',
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1],
            ],
        ])->assertUnprocessable();
    }

    public function test_dashboard_stats_are_scoped_to_the_authenticated_user(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();

        $ownerProduct = Product::factory()->create(['user_id' => $owner->id, 'price' => 10]);
        $otherProduct = Product::factory()->create(['user_id' => $other->id, 'price' => 50]);

        $ownerOrder = Order::factory()->completed()->create(['user_id' => $owner->id, 'total' => 10]);
        OrderItem::factory()->create([
            'order_id' => $ownerOrder->id,
            'product_id' => $ownerProduct->id,
            'quantity' => 1,
            'unit_price' => 10,
            'subtotal' => 10,
        ]);

        $otherOrder = Order::factory()->completed()->create(['user_id' => $other->id, 'total' => 50]);
        OrderItem::factory()->create([
            'order_id' => $otherOrder->id,
            'product_id' => $otherProduct->id,
            'quantity' => 1,
            'unit_price' => 50,
            'subtotal' => 50,
        ]);

        Sanctum::actingAs($owner);

        $this->getJson('/api/dashboard')
            ->assertOk()
            ->assertJsonPath('data.total_revenue', 10)
            ->assertJsonPath('data.total_orders', 1);
    }

    public function test_register_login_and_logout(): void
    {
        $this->postJson('/api/auth/register', [
            'name' => 'Amina',
            'email' => 'amina@coffee.test',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertCreated()->assertJsonStructure(['token', 'user' => ['id', 'email']]);

        $login = $this->postJson('/api/auth/login', [
            'email' => 'amina@coffee.test',
            'password' => 'password',
        ])->assertOk();

        $token = $login->json('token');

        $this->withToken($token)->postJson('/api/auth/logout')->assertOk();
    }

    public function test_order_snapshots_unit_price(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['user_id' => $user->id, 'price' => 4.50]);

        Sanctum::actingAs($user);

        $this->postJson('/api/orders', [
            'customer_name' => 'Sara',
            'status' => OrderStatus::Pending->value,
            'items' => [['product_id' => $product->id, 'quantity' => 2]],
        ])->assertCreated()
            ->assertJsonPath('data.total', 9)
            ->assertJsonPath('data.items.0.unit_price', 4.5);

        $product->update(['price' => 9]);

        $orderId = Order::query()->first()->id;

        $this->getJson("/api/orders/{$orderId}")
            ->assertOk()
            ->assertJsonPath('data.items.0.unit_price', 4.5)
            ->assertJsonPath('data.total', 9);
    }
}
