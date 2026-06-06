<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    // Rôles
    const ROLE_SUPER_ADMIN = 'super_admin';
    const ROLE_ADMIN       = 'admin';
    const ROLE_MANAGER     = 'manager';
    const ROLE_CASHIER     = 'cashier';
    const ROLE_COOK        = 'cook';

    protected $fillable = [
        'restaurant_id', 'pos_terminal_id',
        'name', 'username', 'email', 'password',
        'pin_code', 'webauthn_id', 'webauthn_public_key', 'webauthn_name',
        'role', 'is_active', 'last_login_at', 'avatar_path',
    ];

    protected $hidden = [
        'password', 'remember_token', 'pin_code',
    ];

    protected $casts = [
        'password'      => 'hashed',
        'pin_code'      => 'hashed',
        'is_active'     => 'boolean',
        'last_login_at' => 'datetime',
    ];

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

    public function cashShifts(): HasMany
    {
        return $this->hasMany(CashShift::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    // ── Role checks ──

    public function isSuperAdmin(): bool
    {
        return $this->role === self::ROLE_SUPER_ADMIN;
    }

    public function isAdmin(): bool
    {
        return in_array($this->role, [self::ROLE_SUPER_ADMIN, self::ROLE_ADMIN]);
    }

    public function isManager(): bool
    {
        return $this->role === self::ROLE_MANAGER;
    }

    public function isCashier(): bool
    {
        return $this->role === self::ROLE_CASHIER;
    }

    public function isCook(): bool
    {
        return $this->role === self::ROLE_COOK;
    }

    public function isStaff(): bool
    {
        return in_array($this->role, [self::ROLE_ADMIN, self::ROLE_MANAGER, self::ROLE_CASHIER, self::ROLE_COOK]);
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

    // ── Helpers ──

    public function getRoleLabel(): string
    {
        return match ($this->role) {
            self::ROLE_SUPER_ADMIN => 'Super-Admin',
            self::ROLE_ADMIN       => 'Administrateur',
            self::ROLE_MANAGER     => 'Manager',
            self::ROLE_CASHIER     => 'Caissier',
            self::ROLE_COOK        => 'Cuisinier',
            default                => ucfirst($this->role),
        };
    }

    public function hasActiveShift(): bool
    {
        return $this->cashShifts()->where('status', 'open')->exists();
    }

    public function getActiveShift(): ?CashShift
    {
        return $this->cashShifts()->where('status', 'open')->latest('opened_at')->first();
    }
}
