<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PosTerminal extends Model
{
    use HasFactory;

    protected $fillable = [
        'restaurant_id', 'name', 'is_active',
    ];

    // ── Relations ──

    public function restaurant()
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function cashShifts()
    {
        return $this->hasMany(CashShift::class);
    }

    // ── Scopes ──

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // ── Helpers ──

    public function getActiveShift(): ?CashShift
    {
        return $this->cashShifts()->where('status', 'open')->latest('opened_at')->first();
    }

    public function hasOpenShift(): bool
    {
        return $this->getActiveShift() !== null;
    }
}
