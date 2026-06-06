<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Restaurant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class KdsController extends Controller
{
    /**
     * Écran KDS (Kitchen Display System)
     * Affiche les commandes avec items routés vers la cuisine
     */
    public function index(Request $request): View
    {
        $user = $request->user();
        $restaurantId = $user->restaurant_id;
        $restaurant = Restaurant::find($restaurantId);

        $kitchenOrders = $this->getKitchenOrdersData($restaurantId);

        return view('kds.index', compact('restaurant', 'kitchenOrders'));
    }

    /**
     * API : Récupérer les commandes cuisine (polling AJAX)
     */
    public function orders(Request $request): JsonResponse
    {
        $restaurantId = $request->user()->restaurant_id;
        $orders = $this->getKitchenOrdersData($restaurantId);
        return response()->json(['orders' => $orders]);
    }

    /**
     * Marquer une commande comme "en préparation"
     */
    public function startPrep(Request $request, Order $order): JsonResponse
    {
        if ($order->kitchen_status !== Order::KITCHEN_EN_ATTENTE) {
            return response()->json(['success' => false, 'message' => 'Commande pas en attente.'], 422);
        }

        $order->update(['kitchen_status' => Order::KITCHEN_EN_PREPARATION]);

        return response()->json(['success' => true, 'kitchen_status' => $order->kitchen_status]);
    }

    /**
     * Marquer une commande comme "prête"
     */
    public function markReady(Request $request, Order $order): JsonResponse
    {
        if ($order->kitchen_status !== Order::KITCHEN_EN_PREPARATION) {
            return response()->json(['success' => false, 'message' => 'Commande pas en préparation.'], 422);
        }

        $order->update([
            'kitchen_status' => Order::KITCHEN_PRET,
            'ready_at'       => now(),
        ]);

        return response()->json(['success' => true, 'kitchen_status' => $order->kitchen_status]);
    }

    /**
     * Récupère les données KDS formatées
     * Filtre uniquement les items routés vers 'kitchen'
     */
    private function getKitchenOrdersData(int $restaurantId): array
    {
        $orders = Order::with(['items', 'table', 'user'])
            ->where('restaurant_id', $restaurantId)
            ->whereDate('created_at', today())
            ->whereNotIn('status', ['annulee', 'payee', 'paid'])
            ->where('kitchen_status', '!=', Order::KITCHEN_PRET)
            ->orderByDesc('created_at')
            ->get();

        return $orders->map(function ($o) {
            // Ne garder que les items qui vont en cuisine
            $kitchenItems = $o->items->filter(fn($i) => ($i->kitchen_route ?? 'kitchen') === 'kitchen');

            // Si aucun item cuisine, ne pas afficher cette commande au KDS
            if ($kitchenItems->isEmpty()) return null;

            // Calculer le max prep_time parmi les items
            $maxPrepTime = $kitchenItems->max(fn($i) => $i->product?->prep_time_minutes ?? 15);

            return [
                'id'                => $o->id,
                'order_number'      => $o->order_number,
                'table_name'        => $o->table?->name ?? 'À emporter',
                'kitchen_status'    => $o->kitchen_status ?? 'en_attente',
                'sent_to_kitchen_at'=> $o->sent_to_kitchen_at?->toIso8601String(),
                'max_prep_time'     => $maxPrepTime,
                'items'             => $kitchenItems->map(fn($i) => [
                    'id'              => $i->id,
                    'product_name'    => $i->product_name,
                    'quantity'        => $i->quantity,
                    'notes'           => $i->notes,
                    'prep_time'       => $i->product?->prep_time_minutes ?? 15,
                    'kitchen_status'  => $i->kitchen_status ?? 'en_attente',
                ]),
                'cashier_name'      => $o->user?->name ?? '—',
                'kitchen_wait_mins' => $o->getKitchenWaitMinutes(),
                'has_bar_items'     => $o->items->contains(fn($i) => ($i->kitchen_route ?? '') === 'bar'),
                'has_counter_items' => $o->items->contains(fn($i) => ($i->kitchen_route ?? '') === 'counter'),
            ];
        })->filter()->values()->all();
    }
}
