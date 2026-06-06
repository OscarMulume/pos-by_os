<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Restaurant;
use App\Helpers\Helper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class KdsController extends Controller
{
    /**
     * Écran KDS (Kitchen Display System)
     */
    public function index(Request $request): View
    {
        $user = $request->user();
        $restaurantId = $user->restaurant_id;

        $restaurant = Restaurant::find($restaurantId);

        // Commandes du jour non annulées, non payées (qui passent en cuisine)
        $kitchenOrders = Order::with(['items', 'table', 'user'])
            ->where('restaurant_id', $restaurantId)
            ->whereDate('created_at', today())
            ->whereNotIn('status', ['annulee', 'payee', 'paid'])
            ->orderByDesc('created_at')
            ->get()
            ->map(fn($o) => [
                'id'               => $o->id,
                'order_number'     => $o->order_number,
                'table_name'       => $o->table?->name ?? 'À emporter',
                'kitchen_status'   => $o->kitchen_status ?? 'en_attente',
                'items'            => $o->items->map(fn($i) => [
                    'id'           => $i->id,
                    'product_name' => $i->product_name,
                    'quantity'     => $i->quantity,
                ]),
                'cashier_name'     => $o->user?->name ?? '—',
                'kitchen_wait_mins'=> $o->getKitchenWaitMinutes(),
                'notes'            => $o->notes,
            ]);

        return view('kds.index', compact('restaurant', 'kitchenOrders'));
    }

    /**
     * API : Récupérer les commandes cuisine (polling)
     */
    public function orders(Request $request): JsonResponse
    {
        $restaurantId = $request->user()->restaurant_id;

        $orders = Order::with(['items', 'table', 'user'])
            ->where('restaurant_id', $restaurantId)
            ->whereDate('created_at', today())
            ->whereNotIn('status', ['annulee', 'payee', 'paid'])
            ->orderByDesc('created_at')
            ->get()
            ->map(fn($o) => [
                'id'               => $o->id,
                'order_number'     => $o->order_number,
                'table_name'       => $o->table?->name ?? 'À emporter',
                'kitchen_status'   => $o->kitchen_status ?? 'en_attente',
                'items'            => $o->items->map(fn($i) => [
                    'id'           => $i->id,
                    'product_name' => $i->product_name,
                    'quantity'     => $i->quantity,
                ]),
                'cashier_name'     => $o->user?->name ?? '—',
                'kitchen_wait_mins'=> $o->getKitchenWaitMinutes(),
            ]);

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
}
