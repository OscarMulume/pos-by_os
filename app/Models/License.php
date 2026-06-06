<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
use Carbon\Carbon;

class License extends Model
{
    protected $fillable = [
        'restaurant_id',
        'token',
        'plan',
        'expires_at',
        'is_active',
        'max_tables',
        'max_terminals',
        'features',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
        'features' => 'array',
    ];

    public function restaurant()
    {
        return $this->belongsTo(Restaurant::class);
    }

    /**
     * Générer un jeton de licence signé pour un restaurant
     */
    public static function generateToken(Restaurant $restaurant, string $plan = 'basic', int $days = 30): string
    {
        $payload = [
            'sub' => $restaurant->id,
            'name' => $restaurant->name,
            'plan' => $plan,
            'iat' => now()->timestamp,
            'exp' => now()->addDays($days)->timestamp,
            'jti' => Str::uuid()->toString(),
        ];

        // Signer avec la clé secrète de l'application
        $token = base64_encode(json_encode($payload));
        $signature = hash_hmac('sha256', $token, config('app.key'));

        return $token . '.' . $signature;
    }

    /**
     * Vérifier un jeton de licence (fonctionne hors-ligne)
     */
    public static function verifyToken(string $token): array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 2) {
            return ['valid' => false, 'reason' => 'Format invalide'];
        }

        [$payload, $signature] = $parts;

        // Vérifier la signature
        $expectedSig = hash_hmac('sha256', $payload, config('app.key'));
        if (!hash_equals($expectedSig, $signature)) {
            return ['valid' => false, 'reason' => 'Signature invalide'];
        }

        $data = json_decode(base64_decode($payload), true);
        if (!$data) {
            return ['valid' => false, 'reason' => 'Payload invalide'];
        }

        // Vérifier l'expiration
        if (isset($data['exp']) && now()->timestamp > $data['exp']) {
            return ['valid' => false, 'reason' => 'Licence expirée', 'data' => $data];
        }

        return ['valid' => true, 'data' => $data];
    }

    /**
     * Vérifier si la licence permet une fonctionnalité
     */
    public function hasFeature(string $feature): bool
    {
        $features = $this->features ?? [];
        return in_array($feature, $features);
    }

    /**
     * Nombre de jours restants
     */
    public function daysRemaining(): int
    {
        if (!$this->expires_at) return 0;
        return max(0, now()->diffInDays($this->expires_at, false));
    }

    /**
     * Vérifie si la licence est valide
     */
    public function isValid(): bool
    {
        return $this->is_active && $this->expires_at && $this->expires_at->isFuture();
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }
}
