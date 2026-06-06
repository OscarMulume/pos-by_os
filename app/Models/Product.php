<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'restaurant_id', 'category_id', 'name', 'description',
        'price', 'cost_price', 'image_path', 'sort_order', 'prep_time_minutes', 'kitchen_route', 'is_available',
        'stock_quantity', 'low_stock_threshold', 'track_inventory',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'is_available' => 'boolean',
        'track_inventory' => 'boolean',
    ];

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function scopeAvailable($query)
    {
        return $query->where('is_available', true);
    }

    public function scopeByRestaurant($query, int $restaurantId)
    {
        return $query->where('restaurant_id', $restaurantId);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    public function scopeLowStock($query)
    {
        return $query->where('track_inventory', true)
            ->whereColumn('stock_quantity', '<=', 'low_stock_threshold');
    }

    // ── Routage cuisine ──

    public function goesToKitchen(): bool
    {
        return $this->kitchen_route === 'kitchen';
    }

    public function goesToBar(): bool
    {
        return $this->kitchen_route === 'bar';
    }

    public function staysAtCounter(): bool
    {
        return $this->kitchen_route === 'counter';
    }

    public function getKitchenRouteLabel(): string
    {
        return match ($this->kitchen_route) {
            'kitchen' => 'Cuisine (KDS)',
            'bar' => 'Bar',
            'counter' => 'Comptoir',
            default => 'Cuisine',
        };
    }

    public function getMarginAttribute(): float
    {
        if ($this->price == 0) return 0;
        return (float) ((($this->price - $this->cost_price) / $this->price) * 100);
    }

    public function isInStock(): bool
    {
        if (!$this->track_inventory) return true;
        return $this->stock_quantity > 0;
    }

    public function isLowStock(): bool
    {
        if (!$this->track_inventory) return false;
        return $this->stock_quantity <= $this->low_stock_threshold;
    }

    /**
     * Déduire du stock lors d'une vente
     */
    public function deductStock(int $quantity, ?int $orderId = null, ?int $userId = null): bool
    {
        if (!$this->track_inventory) return true;

        $stockBefore = $this->stock_quantity;
        $stockAfter = max(0, $stockBefore - $quantity);

        $this->update(['stock_quantity' => $stockAfter]);

        // Désactiver automatiquement si rupture
        if ($stockAfter <= 0) {
            $this->update(['is_available' => false]);
        }

        // Enregistrer le mouvement
        StockMovement::create([
            'product_id' => $this->id,
            'restaurant_id' => $this->restaurant_id,
            'order_id' => $orderId,
            'user_id' => $userId,
            'type' => 'sale',
            'quantity' => -$quantity,
            'stock_before' => $stockBefore,
            'stock_after' => $stockAfter,
            'reason' => 'Vente',
        ]);

        return true;
    }

    /**
     * Réapprovisionner le stock
     */
    public function addStock(int $quantity, string $reason = 'Réapprovisionnement', ?int $userId = null): void
    {
        $stockBefore = $this->stock_quantity;
        $stockAfter = $stockBefore + $quantity;

        $this->update([
            'stock_quantity' => $stockAfter,
            'is_available' => true,
        ]);

        StockMovement::create([
            'product_id' => $this->id,
            'restaurant_id' => $this->restaurant_id,
            'user_id' => $userId,
            'type' => 'adjustment',
            'quantity' => $quantity,
            'stock_before' => $stockBefore,
            'stock_after' => $stockAfter,
            'reason' => $reason,
        ]);
    }
}
