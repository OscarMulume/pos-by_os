<?php

namespace App\Models;

use App\Models\Scopes\RestaurantScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RestaurantTable extends Model
{
    use HasFactory;

    // ── Statuts de table (State Machine) ──
    const STATUS_AVAILABLE        = 'available';
    const STATUS_OCCUPIED         = 'occupied';
    const STATUS_KITCHEN_PROCESSING = 'kitchen_processing';
    const STATUS_SERVED_UNPAID    = 'served_unpaid';

    protected $fillable = [
        'restaurant_id', 'pos_terminal_id', 'name',
        'status', 'capacity', 'zone', 'is_active',
        'current_order_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
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

    public function posTerminal(): BelongsTo
    {
        return $this->belongsTo(PosTerminal::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function currentOrder(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'current_order_id');
    }

    // ── Scopes ──

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByRestaurant($query, int $restaurantId)
    {
        return $query->where('restaurant_id', $restaurantId);
    }

    public function scopeAvailable($query)
    {
        return $query->where('status', self::STATUS_AVAILABLE);
    }

    public function scopeOccupied($query)
    {
        return $query->whereIn('status', [
            self::STATUS_OCCUPIED,
            self::STATUS_KITCHEN_PROCESSING,
            self::STATUS_SERVED_UNPAID,
        ]);
    }

    // ── Helpers ──

    public function isActive(): bool
    {
        return $this->is_active;
    }

    public function isAvailable(): bool
    {
        return $this->status === self::STATUS_AVAILABLE;
    }

    public function isOccupied(): bool
    {
        return $this->status === self::STATUS_OCCUPIED;
    }

    public function isKitchenProcessing(): bool
    {
        return $this->status === self::STATUS_KITCHEN_PROCESSING;
    }

    public function isServedUnpaid(): bool
    {
        return $this->status === self::STATUS_SERVED_UNPAID;
    }

    /**
     * Code couleur universel pour le plan de salle
     * 🟢 Vert = Libre
     * 🟡 Jaune = En cuisine (kitchen_processing)
     * 🔵 Bleu = Servi/À encaisser (served_unpaid)
     * 🔴 Rouge = Occupée / temps dépassé
     */
    public function getStatusColor(): string
    {
        return match ($this->status) {
            self::STATUS_AVAILABLE        => 'green',
            self::STATUS_OCCUPIED         => 'red',
            self::STATUS_KITCHEN_PROCESSING => 'yellow',
            self::STATUS_SERVED_UNPAID    => 'blue',
            default => 'gray',
        };
    }

    public function getStatusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_AVAILABLE        => 'Libre',
            self::STATUS_OCCUPIED         => 'Occupée',
            self::STATUS_KITCHEN_PROCESSING => 'En cuisine',
            self::STATUS_SERVED_UNPAID    => 'Servie / À encaisser',
            default => ucfirst($this->status),
        };
    }

    /**
     * Temps d'attente en minutes depuis la commande active
     */
    public function getWaitMinutes(): ?int
    {
        if (!$this->currentOrder || !$this->currentOrder->sent_to_kitchen_at) {
            return null;
        }
        return (int) $this->currentOrder->sent_to_kitchen_at->diffInMinutes(now());
    }

    /**
     * La table doit-elle clignoter en rouge (SLA depasse) ?
     */
    public function isSlaBreached(int $slaMinutes = 30): bool
    {
        $mins = $this->getWaitMinutes();
        if ($mins === null) return false;
        return $mins > $slaMinutes;
    }
}
