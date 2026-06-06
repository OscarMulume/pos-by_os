<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class PinController extends Controller
{
    /**
     * Vérifier le PIN code d'un utilisateur
     */
    public function verify(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'pin' => 'required|string|min:4|max:8',
            'user_id' => 'nullable|integer|exists:users,id',
        ]);

        $userId = $validated['user_id'] ?? $request->user()->id;
        $user = User::find($userId);

        if (!$user || !$user->pin_code) {
            return response()->json([
                'success' => false,
                'message' => 'Aucun PIN configuré pour cet utilisateur.',
            ], 422);
        }

        if (!Hash::check($validated['pin'], $user->pin_code)) {
            return response()->json([
                'success' => false,
                'message' => 'PIN incorrect.',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'message' => 'PIN vérifié.',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'role' => $user->role,
            ],
        ]);
    }
}
