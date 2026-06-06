<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory;

    // Statuts de commande
    const STATUS_EN_COURS = 'en_cours';
    const STATUS_EN_ATTENTE = 'en_attente';
    const STATUS_PAYEE = 'payee';
    const STATUS_ANNULEE = 'annulee';

    // Statuts cuisine (KDS)
    const KITCHEN_EN_ATTENTE = 'en_attente';
    const KITCHEN_EN_PREPARATION = 'en_preparation';
    const KITCHEN_PRET = 'pret';

    protected $fillable = [
        'restaurant_id', 'pos_terminal_id', 'user_id', 'table_id', 'order_number', 'total_amount',
        'tax_amount', 'discount_amount', 'payment_method', 'payment_reference',
        'cash_received', 'change_given', 'customer_name', 'customer_phone',
        'status', 'kitchen_status', 'sent_to_kitchen_at', 'ready_at',
        'cancelled_by', 'cancellation_reason', 'notes',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'cash_received' => 'decimal:2',
        'change_given' => 'decimal:2',
    ];

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function cancelledByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function table(): BelongsTo
    {
        return $this->belongsTo(RestaurantTable::class, 'table_id');
    }

    public function posTerminal(): BelongsTo
    {
        return $this->belongsTo(PosTerminal::class);
    }

    // Scopes
    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    public function scopePaid($query)
    {
        return $query->where('status', self::STATUS_PAYEE);
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', self::STATUS_ANNULEE);
    }

    public function scopeEnCours($query)
    {
        return $query->where('status', self::STATUS_EN_COURS);
    }

    public function scopeEnAttente($query)
    {
        return $query->where('status', self::STATUS_EN_ATTENTE);
    }

    public function scopeByRestaurant($query, int $restaurantId)
    {
        return $query->where('restaurant_id', $restaurantId);
    }

    public function scopeDateRange($query, $start, $end)
    {
        return $query->whereBetween('created_at', [$start, $end]);
    }

    public function scopePaymentMethod($query, string $method)
    {
        return $query->where('payment_method', $method);
    }

    // Helpers
    public function isEnCours(): bool
    {
        return $this->status === self::STATUS_EN_COURS;
    }

    public function isEnAttente(): bool
    {
        return $this->status === self::STATUS_EN_ATTENTE;
    }

    public function isPayee(): bool
    {
        return $this->status === self::STATUS_PAYEE;
    }

    public function isAnnulee(): bool
    {
        return $this->status === self::STATUS_ANNULEE;
    }

    public function canBeCancelled(): bool
    {
        return in_array($this->status, [self::STATUS_EN_COURS, self::STATUS_EN_ATTENTE]);
    }

    public function canBePaid(): bool
    {
        return in_array($this->status, [self::STATUS_EN_COURS, self::STATUS_EN_ATTENTE]);
    }

    // Labels
    public function getPaymentMethodLabelAttribute(): string
    {
        return match ($this->payment_method) {
            'cash' => 'Espèces',
            'mobile_money' => 'Mobile Money',
            'credit' => 'Crédit',
            default => $this->payment_method,
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_EN_COURS => 'En cours',
            self::STATUS_EN_ATTENTE => 'En attente',
            self::STATUS_PAYEE => 'Payée',
            self::STATUS_ANNULEE => 'Annulée',
            default => $this->status,
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_EN_COURS => 'blue',
            self::STATUS_EN_ATTENTE => 'yellow',
            self::STATUS_PAYEE => 'green',
            self::STATUS_ANNULEE => 'red',
            default => 'gray',
        };
    }

    // Labels
    public function getKitchenStatusLabelAttribute(): string
    {
        return match ($this->kitchen_status) {
            self::KITCHEN_EN_ATTENTE => 'En attente',
            self::KITCHEN_EN_PREPARATION => 'En préparation',
            self::KITCHEN_PRET => 'Prêt',
            default => $this->kitchen_status ?? '—',
        };
    }

    public function getKitchenStatusColorAttribute(): string
    {
        return match ($this->kitchen_status) {
            self::KITCHEN_EN_ATTENTE => 'yellow',
            self::KITCHEN_EN_PREPARATION => 'blue',
            self::KITCHEN_PRET => 'green',
            default => 'gray',
        };
    }

    // Temps d'attente en cuisine (minutes)
    public function getKitchenWaitMinutes(): ?int
    {
        if (!$this->sent_to_kitchen_at) return null;
        $end = $this->ready_at ?? now();
        return (int) $this->sent_to_kitchen_at->diffInMinutes($end);
    }

    // Couleur du minuteur cuisine
    public function getKitchenTimerColor(): string
    {
        $mins = $this->getKitchenWaitMinutes();
        if ($mins === null) return 'gray';
        if ($mins < 15) return 'green';
        if ($mins < 25) return 'yellow';
        return 'red';
    }

    // Motifs d'annulation disponibles
    public static function getCancellationReasons(): array
    {
        return [
            'rupture_stock' => 'Rupture de stock',
            'client_insatisfait' => 'Client insatisfait',
            'erreur_commande' => 'Erreur de commande',
            'probleme_paiement' => 'Problème de paiement',
            'autre' => 'Autre',
        ];
    }
}
