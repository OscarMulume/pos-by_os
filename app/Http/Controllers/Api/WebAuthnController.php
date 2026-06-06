<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class WebAuthnController extends Controller
{
    /**
     * Enregistrer les données WebAuthn d'un utilisateur
     */
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'webauthn_id' => 'required|string|max:255',
            'public_key' => 'required|string',
            'name' => 'nullable|string|max:100',
        ]);

        $user = $request->user();

        $user->update([
            'webauthn_id' => $validated['webauthn_id'],
            'webauthn_public_key' => $validated['public_key'],
            'webauthn_name' => $validated['name'] ?? 'Biométrie ' . $user->name,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Biométrie enregistrée avec succès.',
        ]);
    }

    /**
     * Vérifier l'authentification WebAuthn
     */
    public function verify(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'webauthn_id' => 'required|string',
            'user_id' => 'nullable|integer|exists:users,id',
        ]);

        $userId = $validated['user_id'] ?? $request->user()->id;
        $user = User::find($userId);

        if (!$user || !$user->webauthn_id) {
            return response()->json([
                'success' => false,
                'message' => 'Aucune biométrie enregistrée.',
            ], 422);
        }

        if (!hash_equals($user->webauthn_id, $validated['webauthn_id'])) {
            return response()->json([
                'success' => false,
                'message' => 'Biométrie non reconnue.',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'message' => 'Biométrie vérifiée.',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'role' => $user->role,
            ],
        ]);
    }

    /**
     * Supprimer la biométrie d'un utilisateur
     */
    public function destroy(Request $request): JsonResponse
    {
        $request->user()->update([
            'webauthn_id' => null,
            'webauthn_public_key' => null,
            'webauthn_name' => null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Biométrie supprimée.',
        ]);
    }
}
