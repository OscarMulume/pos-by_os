<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CashShift extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'restaurant_id', 'pos_terminal_id',
        'start_amount', 'end_amount_expected', 'end_amount_counted',
        'difference', 'status', 'opened_at', 'closed_at',
    ];

    protected $casts = [
        'start_amount'        => 'decimal:2',
        'end_amount_expected' => 'decimal:2',
        'end_amount_counted'  => 'decimal:2',
        'difference'          => 'decimal:2',
        'opened_at'           => 'datetime',
        'closed_at'           => 'datetime',
    ];

    // ── Relations ──

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function restaurant()
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function posTerminal()
    {
        return $this->belongsTo(PosTerminal::class);
    }

    public function orders()
    {
        return $this->hasMany(\App\Models\Order::class, 'user_id', 'user_id')
            ->where('restaurant_id', $this->restaurant_id)
            ->whereBetween('created_at', [$this->opened_at, $this->closed_at ?? now()]);
    }

    // ── Scopes ──

    public function scopeOpen($query)
    {
        return $query->where('status', 'open');
    }

    public function scopeClosed($query)
    {
        return $query->where('status', 'closed');
    }

    public function scopeToday($query)
    {
        return $query->whereDate('opened_at', today());
    }

    // ── Helpers ──

    public function isOpen(): bool
    {
        return $this->status === 'open';
    }

    public function isClosed(): bool
    {
        return $this->status === 'closed';
    }

    public function calculateExpected(): float
    {
        $salesTotal = $this->orders()
            ->where('status', \App\Models\Order::STATUS_PAID)
            ->sum('total_amount');
        return $this->start_amount + $salesTotal;
    }

    public function getDifferenceLabel(): string
    {
        if ($this->difference > 0) {
            return 'Excédent: +' . number_format($this->difference, 0, ',', '.') . ' ' . ($this->restaurant->currency ?? 'FC');
        }
        if ($this->difference < 0) {
            return 'Manquant: ' . number_format($this->difference, 0, ',', '.') . ' ' . ($this->restaurant->currency ?? 'FC');
        }
        return 'Équilibré';
    }
}
