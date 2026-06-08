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
     * Affiche UNIQUEMENT les items routés vers 'kitchen'
     * Les items 'bar' et 'counter' sont invisibles ici (déjà marqués livrés)
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
        return response()->json(['orders' => $orders, 'timestamp' => now()->toIso8601String()]);
    }

    /**
     * Marquer la préparation en cours
     */
    public function startPrep(Request $request, Order $order): JsonResponse
    {
        // Vérification multi-tenant
        if ($order->restaurant_id !== $request->user()->restaurant_id) {
            return response()->json(['success' => false, 'message' => 'Accès non autorisé.'], 403);
        }

        if ($order->kitchen_status !== Order::KITCHEN_EN_ATTENTE) {
            return response()->json(['success' => false, 'message' => 'Commande pas en attente.'], 422);
        }

        $order->update(['kitchen_status' => Order::KITCHEN_EN_PREPARATION]);

        return response()->json([
            'success' => true,
            'kitchen_status' => $order->kitchen_status,
            'message' => "Préparation commencée pour {$order->order_number}.",
        ]);
    }

    /**
     * Marquer une commande comme "prête" → notifie la caisse
     */
    public function markReady(Request $request, Order $order): JsonResponse
    {
        if ($order->restaurant_id !== $request->user()->restaurant_id) {
            return response()->json(['success' => false, 'message' => 'Accès non autorisé.'], 403);
        }

        if ($order->kitchen_status !== Order::KITCHEN_EN_PREPARATION) {
            return response()->json(['success' => false, 'message' => 'Commande pas en préparation.'], 422);
        }

        $order->update([
            'kitchen_status' => Order::KITCHEN_PRET,
            'ready_at'       => now(),
        ]);

        // Vérifier s'il reste des items cuisine non prêts
        $hasMoreKitchenItems = $order->items()
            ->where('kitchen_route', 'kitchen')
            ->where('kitchen_status', '!=', 'delivered')
            ->exists();

        if (!$hasMoreKitchenItems) {
            // Tous les items cuisine sont prêts → marquer la commande comme ready
            $order->update(['status' => Order::STATUS_READY]);

            // Mettre à jour la table
            if ($order->table_id) {
                \App\Models\RestaurantTable::where('id', $order->table_id)
                    ->update(['status' => \App\Models\RestaurantTable::STATUS_SERVED_UNPAID]);
            }
        }

        return response()->json([
            'success' => true,
            'kitchen_status' => $order->kitchen_status,
            'order_status' => $order->status,
            'message' => "✅ {$order->order_number} PRÊT — Notification envoyée à la caisse!",
        ]);
    }

    /**
     * Récupère les données KDS formatées
     * Filtre uniquement les items routés vers 'kitchen'
     * Les items bar/counter NE SONT PAS affichés ici
     */
    private function getKitchenOrdersData(int $restaurantId): array
    {
        $orders = Order::with(['items', 'table', 'user'])
            ->where('restaurant_id', $restaurantId)
            ->whereDate('created_at', today())
            ->whereNotIn('status', [Order::STATUS_ANNULEE, Order::STATUS_PAID])
            ->where('kitchen_status', '!=', Order::KITCHEN_PRET)
            ->orderByDesc('created_at')
            ->get();

        return $orders->map(function ($o) {
            // Ne garder que les items qui vont en cuisine (pas bar, pas counter)
            $kitchenItems = $o->items->filter(
                fn($i) => ($i->kitchen_route ?? 'kitchen') === 'kitchen'
                    && !in_array($i->kitchen_status, ['delivered'])
            );

            // Si aucun item cuisine en attente, ne pas afficher
            if ($kitchenItems->isEmpty()) return null;

            // Calculer le max prep_time parmi les items
            $maxPrepTime = $kitchenItems->max(fn($i) => $i->product?->prep_time_minutes ?? 15);

            return [
                'id'                => $o->id,
                'order_number'      => $o->order_number,
                'table_name'        => $o->table?->name ?? 'À emporter',
                'kitchen_status'    => $o->kitchen_status ?? 'en_attente',
                'status'            => $o->status,
                'sent_to_kitchen_at'=> $o->sent_to_kitchen_at?->toIso8601String(),
                'max_prep_time'     => $maxPrepTime,
                'prep_time_remaining' => $this->calcPrepTimeRemaining($o, $maxPrepTime),
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
                'timer_color'       => $o->getKitchenTimerColor(),
                'has_bar_items'     => $o->items->contains(fn($i) => ($i->kitchen_route ?? '') === 'bar'),
                'has_counter_items' => $o->items->contains(fn($i) => ($i->kitchen_route ?? '') === 'counter'),
                'all_items_count'   => $o->items->sum('quantity'),
                'kitchen_items_count' => $kitchenItems->sum('quantity'),
            ];
        })->filter()->values()->all();
    }

    /**
     * Calculer le temps de préparation restant
     */
    private function calcPrepTimeRemaining(Order $order, int $maxPrepTime): int
    {
        if (!$order->sent_to_kitchen_at) return $maxPrepTime;
        $elapsed = (int) $order->sent_to_kitchen_at->diffInMinutes(now());
        return max(0, $maxPrepTime - $elapsed);
    }
}
