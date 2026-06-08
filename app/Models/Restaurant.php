<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Restaurant extends Model
{
    use HasFactory;

    // Statuts
    const STATUS_ACTIVE   = 'active';
    const STATUS_INACTIVE = 'inactive';
    const STATUS_SUSPENDED = 'suspended';
    const STATUS_FERME_TEMPORAIRE = 'ferme_temporairement';

    // Types
    const TYPE_PERMANENT = 'permanent';
    const TYPE_EPHEMERE  = 'ephemere';

    protected $fillable = [
        'name', 'address', 'phone', 'email',
        'logo_path', 'photo_path',
        'currency', 'tax_rate', 'receipt_header', 'receipt_footer',
        'is_active', 'status', 'type', 'subscription_ends_at',
    ];

    protected $casts = [
        'is_active'              => 'boolean',
        'subscription_ends_at'   => 'datetime',
    ];

    // ── Relations ──

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function posTerminals(): HasMany
    {
        return $this->hasMany(PosTerminal::class);
    }

    public function cashShifts(): HasMany
    {
        return $this->hasMany(CashShift::class);
    }

    public function tables(): HasMany
    {
        return $this->hasMany(RestaurantTable::class);
    }

    public function license()
    {
        return $this->hasOne(License::class);
    }

    public function cashiers()
    {
        return $this->hasMany(User::class)->where('role', 'cashier');
    }

    public function managers()
    {
        return $this->hasMany(User::class)->where('role', 'manager');
    }

    // ── Scopes ──

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE)->where('is_active', true);
    }

    public function scopeSuspended($query)
    {
        return $query->where('status', self::STATUS_SUSPENDED);
    }

    // ── Helpers ──

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE && $this->is_active;
    }

    public function isSuspended(): bool
    {
        return $this->status === self::STATUS_SUSPENDED;
    }

    public function isSubscriptionExpired(): bool
    {
        if (!$this->subscription_ends_at) return false;
        return now()->greaterThan($this->subscription_ends_at);
    }

    public function getStatusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_ACTIVE              => 'Actif',
            self::STATUS_INACTIVE            => 'Inactif',
            self::STATUS_SUSPENDED           => 'Suspendu',
            self::STATUS_FERME_TEMPORAIRE    => 'Fermé temporairement',
            default                          => ucfirst($this->status),
        };
    }

    public function getStatusColor(): string
    {
        return match ($this->status) {
            self::STATUS_ACTIVE              => 'green',
            self::STATUS_INACTIVE            => 'gray',
            self::STATUS_SUSPENDED           => 'red',
            self::STATUS_FERME_TEMPORAIRE    => 'yellow',
            default                          => 'gray',
        };
    }

    public function todaySales()
    {
        return $this->orders()
            ->whereDate('created_at', today())
            ->where('status', Order::STATUS_PAID);
    }

    public function todayOrdersEnCours()
    {
        return $this->orders()
            ->whereDate('created_at', today())
            ->whereIn('status', [Order::STATUS_EN_COURS, Order::STATUS_EN_ATTENTE]);
    }

    public function todayRevenue(): float
    {
        return (float) $this->todaySales()->sum('total_amount');
    }

    public function todayOrderCount(): int
    {
        return $this->todaySales()->count();
    }

    public function totalRevenue(): float
    {
        return (float) $this->orders()
            ->where('status', Order::STATUS_PAID)
            ->sum('total_amount');
    }

    public function totalOrderCount(): int
    {
        return $this->orders()
            ->where('status', Order::STATUS_PAID)
            ->count();
    }

    public function getLogoUrlAttribute(): ?string
    {
        return $this->logo_path ? asset('storage/' . $this->logo_path) : null;
    }

    public function getPhotoUrlAttribute(): ?string
    {
        return $this->photo_path ? asset('storage/' . $this->photo_path) : null;
    }
}
