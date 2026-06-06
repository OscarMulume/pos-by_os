<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\License;
use App\Models\Restaurant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LicenseController extends Controller
{
    /**
     * Vérifier la licence d'un restaurant (en ligne)
     */
    public function verify(Request $request): JsonResponse
    {
        $user = $request->user();
        $restaurantId = $user->restaurant_id;

        $license = License::where('restaurant_id', $restaurantId)
            ->where('is_active', true)
            ->first();

        if (!$license) {
            return response()->json([
                'valid' => false,
                'reason' => 'Aucune licence active',
            ]);
        }

        if (!$license->isValid()) {
            return response()->json([
                'valid' => false,
                'reason' => 'Licence expirée',
                'expires_at' => $license->expires_at->toISOString(),
            ]);
        }

        // Mettre à jour la date de vérification
        Restaurant::where('id', $restaurantId)->update([
            'last_license_check' => now(),
        ]);

        return response()->json([
            'valid' => true,
            'license' => [
                'token' => $license->token,
                'plan' => $license->plan,
                'expires_at' => $license->expires_at->toISOString(),
                'days_remaining' => $license->daysRemaining(),
                'max_tables' => $license->max_tables,
                'max_terminals' => $license->max_terminals,
                'features' => $license->features,
            ],
        ]);
    }

    /**
     * Générer une nouvelle licence (Super-Admin uniquement)
     */
    public function generate(Request $request, Restaurant $restaurant): JsonResponse
    {
        $validated = $request->validate([
            'plan' => 'required|in:basic,pro,enterprise',
            'days' => 'required|integer|min:1|max:365',
            'max_tables' => 'nullable|integer|min:1',
            'max_terminals' => 'nullable|integer|min:1',
            'features' => 'nullable|array',
        ]);

        $token = License::generateToken(
            $restaurant,
            $validated['plan'],
            $validated['days']
        );

        $license = License::create([
            'restaurant_id' => $restaurant->id,
            'token' => $token,
            'plan' => $validated['plan'],
            'expires_at' => now()->addDays($validated['days']),
            'is_active' => true,
            'max_tables' => $validated['max_tables'] ?? 20,
            'max_terminals' => $validated['max_terminals'] ?? 5,
            'features' => $validated['features'] ?? ['pos', 'kds', 'reports'],
        ]);

        return response()->json([
            'success' => true,
            'license' => $license,
        ]);
    }
}
