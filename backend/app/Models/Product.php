<?php

namespace App\Models;

use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Str;

#[Fillable(['user_id', 'name', 'description', 'price', 'category', 'image', 'available'])]
class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'available' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<OrderItem, $this>
     */
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function imageUrl(): ?string
    {
        if (! $this->image) {
            return null;
        }

        return Storage::disk('public')->url($this->image);
    }

    /**
     * @param  Builder<Product>  $query
     * @return Builder<Product>
     */
    public function scopeOwnedBy(Builder $query, User $user): Builder
    {
        return $query->where('user_id', $user->id);
    }

    public function resolveRouteBinding($value, $field = null): Product
    {
        $field ??= $this->getRouteKeyName();

        return static::query()
            ->ownedBy(request()->user())
            ->where($field, $value)
            ->firstOrFail();
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public static function booted(): void
    {
        static::creating(function (Product $product) {
            $product->slug = Str::slug($product->name);
        });

        static::updating(function (Product $product) {
            if ($product->isDirty('name')) {
                $product->slug = Str::slug($product->name);
            }
        });

    }
}
