<?php

namespace App\Models;

use App\Models\Scopes\RestaurantScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WasteLog extends Model
{
    use HasFactory;

    // Raisons de perte
    const REASON_CASSE = 'casse';
    const REASON_AVARIATION = 'avariation';
    const REASON_EXPIRATION = 'expiration';
    const REASON_ERREUR_PREPARATION = 'erreur_preparation';
    const REASON_AUTRE = 'autre';

    public static function getReasons(): array
    {
        return [
            self::REASON_CASSE              => '🍾 Bouteille cassée / Matériel',
            self::REASON_AVARIATION         => '🥩 Produit avarié',
            self::REASON_EXPIRATION         => '📅 Expiration dépassée',
            self::REASON_ERREUR_PREPARATION => '👨‍🍳 Erreur de préparation',
            self::REASON_AUTRE              => '📝 Autre',
        ];
    }

    protected $fillable = [
        'restaurant_id', 'item_type', 'item_id', 'item_name',
        'quantity', 'unit_of_measure', 'cost_at_loss',
        'reason', 'notes', 'reported_by',
    ];

    protected $casts = [
        'quantity'     => 'decimal:3',
        'cost_at_loss' => 'decimal:2',
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

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    /**
     * Relation polymorphique vers l'item perdu (Product ou Ingredient)
     */
    public function item()
    {
        return $this->morphTo('item', 'item_type', 'item_id');
    }

    // ── Scopes ──

    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    public function scopeByReason($query, string $reason)
    {
        return $query->where('reason', $reason);
    }

    public function scopeIngredients($query)
    {
        return $query->where('item_type', 'ingredient');
    }

    public function scopeProducts($query)
    {
        return $query->where('item_type', 'product');
    }

    // ── Helpers ──

    public function getReasonLabel(): string
    {
        return self::getReasons()[$this->reason] ?? $this->reason;
    }

    public function getItemTypeLabel(): string
    {
        return $this->item_type === 'ingredient' ? 'Matière première' : 'Produit fini';
    }
}
