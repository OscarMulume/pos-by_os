<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RestaurantTable extends Model
{
    use HasFactory;

    const STATUS_LIBRE = 'libre';
    const STATUS_OCCUPEE = 'occupee';

    protected $fillable = [
        'restaurant_id', 'pos_terminal_id', 'name',
        'status', 'capacity', 'zone', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

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

    public function isActive(): bool
    {
        return $this->is_active;
    }

    public function isLibre(): bool
    {
        return $this->status === self::STATUS_LIBRE;
    }

    public function isOccupee(): bool
    {
        return $this->status === self::STATUS_OCCUPEE;
    }

    public function getStatusColor(): string
    {
        return $this->status === self::STATUS_LIBRE ? 'green' : 'red';
    }
}
