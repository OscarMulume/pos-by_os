<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Models\CashShift;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CashShiftController extends Controller
{
    /**
     * Vérifier si le caissier a un shift ouvert
     */
    public function status(Request $request): JsonResponse
    {
        $user = $request->user();
        $shift = CashShift::where('user_id', $user->id)
            ->where('restaurant_id', $user->restaurant_id)
            ->where('status', 'open')
            ->first();

        return response()->json([
            'has_open_shift' => (bool) $shift,
            'shift' => $shift ? [
                'id' => $shift->id,
                'start_amount' => $shift->start_amount,
                'opened_at' => $shift->opened_at->toISOString(),
                'duration_mins' => $shift->opened_at->diffInMinutes(now()),
                'sales_count' => $shift->orders()->whereIn('status', ['payee', 'paid'])->count(),
                'sales_total' => $shift->orders()->whereIn('status', ['payee', 'paid'])->sum('total_amount'),
                'expected_amount' => $shift->calculateExpected(),
            ] : null,
        ]);
    }

    /**
     * Ouvrir un nouveau shift de caisse
     */
    public function open(Request $request): JsonResponse
    {
        $user = $request->user();

        // Vérifier qu'il n'a pas déjà un shift ouvert
        $existing = CashShift::where('user_id', $user->id)
            ->where('restaurant_id', $user->restaurant_id)
            ->where('status', 'open')
            ->first();

        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'Vous avez déjà un shift ouvert.',
            ], 422);
        }

        $validated = $request->validate([
            'start_amount' => 'required|numeric|min:0',
            'pos_terminal_id' => 'nullable|exists:pos_terminals,id',
        ]);

        $shift = CashShift::create([
            'user_id' => $user->id,
            'restaurant_id' => $user->restaurant_id,
            'pos_terminal_id' => $validated['pos_terminal_id'] ?? $user->pos_terminal_id,
            'start_amount' => $validated['start_amount'],
            'status' => 'open',
            'opened_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Caisse ouverte avec succès.',
            'shift' => [
                'id' => $shift->id,
                'start_amount' => $shift->start_amount,
                'opened_at' => $shift->opened_at->toISOString(),
            ],
        ]);
    }

    /**
     * Fermer le shift de caisse
     */
    public function close(Request $request): JsonResponse
    {
        $user = $request->user();

        $shift = CashShift::where('user_id', $user->id)
            ->where('restaurant_id', $user->restaurant_id)
            ->where('status', 'open')
            ->first();

        if (!$shift) {
            return response()->json([
                'success' => false,
                'message' => 'Aucun shift ouvert trouvé.',
            ], 422);
        }

        $validated = $request->validate([
            'end_amount_counted' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:500',
        ]);

        $expected = $shift->calculateExpected();
        $counted = $validated['end_amount_counted'];
        $difference = $counted - $expected;

        $shift->update([
            'end_amount_expected' => $expected,
            'end_amount_counted' => $counted,
            'difference' => $difference,
            'status' => 'closed',
            'closed_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Caisse fermée avec succès.',
            'summary' => [
                'start_amount' => $shift->start_amount,
                'expected_amount' => $expected,
                'counted_amount' => $counted,
                'difference' => $difference,
                'difference_label' => $shift->getDifferenceLabel(),
                'sales_count' => $shift->orders()->whereIn('status', ['payee', 'paid'])->count(),
                'duration' => $shift->opened_at->diffForHumans($shift->closed_at, true),
            ],
        ]);
    }

    /**
     * Historique des shifts
     */
    public function history(Request $request): View
    {
        $user = $request->user();
        $restaurantId = $user->restaurant_id;

        $shifts = CashShift::with(['user', 'posTerminal'])
            ->where('restaurant_id', $restaurantId)
            ->orderByDesc('opened_at')
            ->paginate(20);

        return view('pos.cash-shifts', compact('shifts'));
    }
}
