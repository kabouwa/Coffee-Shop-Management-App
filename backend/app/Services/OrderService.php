<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderService
{
    /**
     * @param  array{customer_name: string, status?: string, items: list<array{product_id: int, quantity: int}>}  $payload
     */
    public function create(User $user, array $payload): Order
    {
        return DB::transaction(function () use ($user, $payload) {
            $order = $user->orders()->create([
                'customer_name' => $payload['customer_name'],
                'status' => $payload['status'] ?? OrderStatus::Pending->value,
                'total' => 0,
            ]);

            $this->syncItems($user, $order, $payload['items']);

            return $order->load('items.product');
        });
    }

    /**
     * @param  array{customer_name?: string, status?: string, items?: list<array{product_id: int, quantity: int}>}  $payload
     */
    public function update(User $user, Order $order, array $payload): Order
    {
        return DB::transaction(function () use ($user, $order, $payload) {
            $order->fill(array_filter([
                'customer_name' => $payload['customer_name'] ?? null,
                'status' => $payload['status'] ?? null,
            ], fn ($value) => $value !== null));

            if (isset($payload['items'])) {
                $order->items()->delete();
                $this->syncItems($user, $order, $payload['items']);
            } else {
                $order->save();
            }

            return $order->refresh()->load('items.product');
        });
    }

    /**
     * @param  list<array{product_id: int, quantity: int}>  $items
     */
    private function syncItems(User $user, Order $order, array $items): void
    {
        $total = 0;

        foreach ($items as $item) {
            $product = Product::query()
                ->ownedBy($user)
                ->whereKey($item['product_id'])
                ->first();

            if (! $product) {
                throw ValidationException::withMessages([
                    'items' => ['Each product must belong to your coffee shop.'],
                ]);
            }

            if (! $product->available) {
                throw ValidationException::withMessages([
                    'items' => ["{$product->name} is not available."],
                ]);
            }

            $quantity = (int) $item['quantity'];
            $unitPrice = $product->price;
            $subtotal = round((float) $unitPrice * $quantity, 2);
            $total += $subtotal;

            $order->items()->create([
                'product_id' => $product->id,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'subtotal' => $subtotal,
            ]);
        }

        $order->forceFill(['total' => round($total, 2)])->save();
    }
}
