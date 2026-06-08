<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ingredient;
use App\Models\Product;
use App\Models\WasteLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WasteController extends Controller
{
    /**
     * Enregistrer une perte/démarque
     * Déduit le stock SANS impacter le CA
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'item_type' => 'required|in:product,ingredient',
            'item_id'   => 'required|integer',
            'quantity'  => 'required|numeric|min:0.001',
            'reason'    => 'required|in:casse,avariation,expiration,erreur_preparation,autre',
            'notes'     => 'nullable|string|max:500',
        ]);

        $user = $request->user();
        $restaurantId = $user->restaurant_id;

        // Récupérer l'item et déduire le stock
        if ($validated['item_type'] === 'ingredient') {
            $item = Ingredient::where('restaurant_id', $restaurantId)
                ->findOrFail($validated['item_id']);
            $itemName = $item->name;
            $unitMeasure = $item->unit_of_measure;
            $costAtLoss = $item->cost_per_unit * $validated['quantity'];

            // Déduire le stock d'ingrédient
            $item->deduct($validated['quantity'], null, null, $user->id);
        } else {
            $item = Product::where('restaurant_id', $restaurantId)
                ->findOrFail($validated['item_id']);
            $itemName = $item->name;
            $unitMeasure = 'piece';
            $costAtLoss = ($item->cost_price_calculated ?: $item->cost_price) * $validated['quantity'];

            // Déduire le stock de produit
            if ($item->track_inventory) {
                $item->deductStock((int) ceil($validated['quantity']), null, $user->id);
            }
        }

        // Créer le log de perte
        $wasteLog = WasteLog::create([
            'restaurant_id' => $restaurantId,
            'item_type'     => $validated['item_type'],
            'item_id'       => $validated['item_id'],
            'item_name'     => $itemName,
            'quantity'      => $validated['quantity'],
            'unit_of_measure'=> $unitMeasure,
            'cost_at_loss'  => round($costAtLoss, 2),
            'reason'        => $validated['reason'],
            'notes'         => $validated['notes'] ?? null,
            'reported_by'   => $user->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => "Démarque enregistrée: {$itemName} ({$validated['quantity']} {$unitMeasure})",
            'cost'    => round($costAtLoss, 2),
            'waste_id'=> $wasteLog->id,
        ]);
    }

    /**
     * Liste des pertes du jour
     */
    public function index(Request $request)
    {
        $restaurantId = $request->user()->restaurant_id;

        $wasteLogs = WasteLog::with('reporter:id,name')
            ->where('restaurant_id', $restaurantId)
            ->today()
            ->orderByDesc('created_at')
            ->paginate(20);

        $totalCost = WasteLog::where('restaurant_id', $restaurantId)
            ->today()
            ->sum('cost_at_loss');

        return view('admin.waste.index', compact('wasteLogs', 'totalCost'));
    }
}
