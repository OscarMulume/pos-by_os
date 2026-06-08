<?php

namespace App\Models;

use App\Models\Scopes\RestaurantScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'restaurant_id', 'category_id', 'name', 'description',
        'price', 'cost_price', 'cost_price_calculated', 'food_cost_percentage', 'margin_percentage',
        'image_path', 'sort_order', 'prep_time_minutes', 'kitchen_route', 'is_available',
        'stock_quantity', 'low_stock_threshold', 'stock_alert_threshold', 'stock_status', 'track_inventory',
    ];

    protected $casts = [
        'price'                 => 'decimal:2',
        'cost_price'            => 'decimal:2',
        'cost_price_calculated' => 'decimal:2',
        'food_cost_percentage'  => 'decimal:2',
        'margin_percentage'     => 'decimal:2',
        'is_available'          => 'boolean',
        'track_inventory'       => 'boolean',
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

    /**
     * FICHE TECHNIQUE (BOM) — Les ingrédients nécessaires pour ce produit
     */
    public function ingredients(): BelongsToMany
    {
        return $this->belongsToMany(Ingredient::class, 'product_ingredients')
            ->withPivot('quantity_required', 'unit_of_measure')
            ->withTimestamps();
    }

    // ── Scopes ──

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

    public function scopeWithLowMargin($query, float $threshold = 30.0)
    {
        return $query->where('price', '>', 0)
            ->whereRaw('((price - COALESCE(cost_price_calculated, cost_price)) / price) * 100 < ?', [$threshold]);
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
            'bar'     => 'Bar',
            'counter' => 'Comptoir',
            default   => 'Cuisine',
        };
    }

    // ═══════════════════════════════════════════════════
    // FICHE TECHNIQUE — Calcul de coût et marge
    // ═══════════════════════════════════════════════════

    /**
     * Calcule le prix de revient (food cost) à partir des ingrédients.
     * Parcourt la fiche technique et somme: quantité requise × coût unitaire
     */
    public function calculateCostPrice(): float
    {
        $totalCost = 0;

        $this->loadMissing('ingredients');

        foreach ($this->ingredients as $ingredient) {
            $qty = $ingredient->pivot->quantity_required ?? 0;
            $cost = $ingredient->cost_per_unit ?? 0;
            $totalCost += $qty * $cost;
        }

        // Si pas d'ingrédients liés, utiliser le cost_price manuel
        if ($totalCost == 0) {
            $totalCost = (float) $this->cost_price;
        }

        return round($totalCost, 2);
    }

    /**
     * Calcule et met à jour le coût, le food cost % et la marge %
     */
    public function updateCostAndMargin(): void
    {
        $costPrice = $this->calculateCostPrice();
        $price = (float) $this->price;

        $foodCostPct = $price > 0 ? round(($costPrice / $price) * 100, 2) : 0;
        $marginPct = $price > 0 ? round((($price - $costPrice) / $price) * 100, 2) : 0;

        $this->update([
            'cost_price_calculated' => $costPrice,
            'food_cost_percentage'  => $foodCostPct,
            'margin_percentage'     => $marginPct,
        ]);
    }

    /**
     * Marge bénéficiaire actuelle (en %)
     */
    public function getMarginAttribute(): float
    {
        $cost = (float) ($this->cost_price_calculated ?: $this->cost_price);
        $price = (float) $this->price;
        if ($price == 0) return 0;
        return round((($price - $cost) / $price) * 100, 2);
    }

    /**
     * La marge est-elle sous le seuil critique (30%) ?
     */
    public function isLowMargin(float $threshold = 30.0): bool
    {
        return $this->margin < $threshold;
    }

    /**
     * Badge couleur pour la marge
     */
    public function getMarginBadgeColor(): string
    {
        $margin = $this->margin;
        if ($margin >= 50) return 'green';
        if ($margin >= 30) return 'yellow';
        return 'red';
    }

    /**
     * Vérifie si tous les ingrédients sont en stock pour produire ce produit
     */
    public function canBeProduced(int $quantity = 1): bool
    {
        $this->loadMissing('ingredients');

        foreach ($this->ingredients as $ingredient) {
            $required = ($ingredient->pivot->quantity_required ?? 0) * $quantity;
            if ($ingredient->stock_quantity < $required) {
                return false;
            }
        }

        return true;
    }

    // ═══════════════════════════════════════════════════
    // Gestion de stock
    // ═══════════════════════════════════════════════════

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

    public function isStockCritique(): bool
    {
        if (!$this->track_inventory) return false;
        return $this->stock_quantity <= $this->stock_alert_threshold;
    }

    public function updateStockStatus(): void
    {
        if (!$this->track_inventory) {
            $this->update(['stock_status' => 'normal']);
            return;
        }

        $status = match (true) {
            $this->stock_quantity <= 0                   => 'rupture',
            $this->stock_quantity <= $this->stock_alert_threshold => 'critique',
            $this->stock_quantity <= $this->low_stock_threshold   => 'low',
            default                                      => 'normal',
        };

        $this->update(['stock_status' => $status]);
    }

    /**
     * Déduire du stock lors d'une vente — avec mise à jour du statut d'alerte
     */
    public function deductStock(int $quantity, ?int $orderId = null, ?int $userId = null): bool
    {
        if (!$this->track_inventory) return true;

        $stockBefore = $this->stock_quantity;
        $stockAfter = max(0, $stockBefore - $quantity);

        $this->update(['stock_quantity' => $stockAfter]);

        if ($stockAfter <= 0) {
            $this->update(['is_available' => false, 'stock_status' => 'rupture']);
        } else {
            $this->updateStockStatus();
        }

        StockMovement::create([
            'product_id'    => $this->id,
            'restaurant_id' => $this->restaurant_id,
            'order_id'      => $orderId,
            'user_id'       => $userId,
            'type'          => 'sale',
            'quantity'      => -$quantity,
            'stock_before'  => $stockBefore,
            'stock_after'   => $stockAfter,
            'reason'        => 'Vente',
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
            'is_available'   => true,
        ]);

        StockMovement::create([
            'product_id'    => $this->id,
            'restaurant_id' => $this->restaurant_id,
            'user_id'       => $userId,
            'type'          => 'adjustment',
            'quantity'      => $quantity,
            'stock_before'  => $stockBefore,
            'stock_after'   => $stockAfter,
            'reason'        => $reason,
        ]);
    }
}
