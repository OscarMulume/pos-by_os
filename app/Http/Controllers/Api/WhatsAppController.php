<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\WhatsAppService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WhatsAppController extends Controller
{
    public function __construct(private WhatsAppService $whatsapp)
    {
    }

    /**
     * Envoyer un reçu par WhatsApp
     */
    public function sendReceipt(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'order_id' => 'required|integer|exists:orders,id',
            'phone' => 'required|string|max:20',
        ]);

        $order = Order::with(['items', 'user', 'table', 'restaurant'])
            ->findOrFail($validated['order_id']);

        // Vérifier que l'utilisateur a accès à cette commande
        if ($order->restaurant_id !== $request->user()->restaurant_id && !$request->user()->isSuperAdmin()) {
            return response()->json(['success' => false, 'message' => 'Accès non autorisé.'], 403);
        }

        $result = $this->whatsapp->sendReceipt($order, $validated['phone']);

        return response()->json($result, $result['success'] ? 200 : 422);
    }
}
