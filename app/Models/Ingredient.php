<?php

namespace App\Models;

use App\Models\Scopes\RestaurantScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Ingredient extends Model
{
    use HasFactory;

    protected $fillable = [
        'restaurant_id', 'name', 'unit_of_measure',
        'cost_per_unit', 'stock_quantity', 'alert_threshold', 'is_active',
    ];

    protected $casts = [
        'cost_per_unit'    => 'decimal:4',
        'stock_quantity'   => 'decimal:3',
        'alert_threshold'  => 'decimal:3',
        'is_active'        => 'boolean',
    ];

    /**
     * Global Scope multi-tenant
     */
    protected static function booted(): void
    {
        static::addGlobalScope(new RestaurantScope());
    }

    // ── Relations ──

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    /**
     * Les produits qui utilisent cet ingrédient (Fiche Technique inversée)
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_ingredients')
            ->withPivot('quantity_required', 'unit_of_measure')
            ->withTimestamps();
    }

    // ── Scopes ──

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeLowStock($query)
    {
        return $query->whereColumn('stock_quantity', '<=', 'alert_threshold');
    }

    // ── Helpers ──

    public function isLowStock(): bool
    {
        return $this->stock_quantity <= $this->alert_threshold;
    }

    public function isOutOfStock(): bool
    {
        return $this->stock_quantity <= 0;
    }

    /**
     * Déduire du stock d'ingrédient (utilisé lors de la vente d'un produit composé)
     */
    public function deduct(float $quantity, ?int $orderId = null, ?int $wasteLogId = null, ?int $userId = null): bool
    {
        $stockBefore = $this->stock_quantity;
        $stockAfter = max(0, $stockBefore - $quantity);

        $this->update(['stock_quantity' => $stockAfter]);

        // Enregistrer le mouvement
        StockMovement::create([
            'product_id'   => null,
            'restaurant_id'=> $this->restaurant_id,
            'order_id'     => $orderId,
            'user_id'      => $userId,
            'type'         => $wasteLogId ? 'waste' : 'sale',
            'quantity'     => -$quantity,
            'stock_before' => $stockBefore,
            'stock_after'  => $stockAfter,
            'reason'       => $wasteLogId ? "Démarque #{$wasteLogId}" : "Vente ingrédient #{$orderId}",
        ]);

        return true;
    }

    /**
     * Réapprovisionner un ingrédient
     */
    public function restock(float $quantity, string $reason = 'Réapprovisionnement', ?int $userId = null): void
    {
        $stockBefore = $this->stock_quantity;
        $stockAfter = $stockBefore + $quantity;

        $this->update(['stock_quantity' => $stockAfter]);

        StockMovement::create([
            'product_id'   => null,
            'restaurant_id'=> $this->restaurant_id,
            'user_id'      => $userId,
            'type'         => 'adjustment',
            'quantity'     => $quantity,
            'stock_before' => $stockBefore,
            'stock_after'  => $stockAfter,
            'reason'       => $reason,
        ]);
    }

    public function getUnitLabel(): string
    {
        return match ($this->unit_of_measure) {
            'kg'     => 'kg',
            'litre'  => 'L',
            'piece'  => 'pc',
            'gramme' => 'g',
            'cl'     => 'cl',
            'ml'     => 'ml',
            default  => $this->unit_of_measure,
        };
    }
}
