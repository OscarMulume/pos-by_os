<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Services\OrderService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PosController extends Controller
{
    /**
     * Display the POS interface with categories, products, tables and active orders.
     */
    public function index(Request $request, OrderService $orderService): View
    {
        $user = $request->user();

        // Si le caissier n'est affecté à aucun restaurant, bloquer l'accès
        if ($user->isCashier() && !$user->restaurant_id) {
            return view('pos.unassigned', [
                'message' => 'Vous n\'êtes affecté à aucun restaurant. Veuillez contacter votre manager ou l\'administrateur pour être affecté.',
            ]);
        }

        $restaurantId = $user->restaurant_id;

        $categories = Category::where('restaurant_id', $restaurantId)
            ->where('is_active', true)
            ->orderBy('display_order', 'asc')
            ->orderBy('name', 'asc')
            ->get();

        $products = Product::with('category')
            ->where('restaurant_id', $restaurantId)
            ->where('is_available', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $restaurant = \App\Models\Restaurant::find($restaurantId);

        // Commandes actives du jour (non payées, non annulées)
        $activeOrders = \App\Models\Order::with(['user', 'items', 'table'])
            ->where('restaurant_id', $restaurantId)
            ->whereDate('created_at', today())
            ->whereIn('status', ['pending', 'sent_to_kitchen', 'ready', 'delivered'])
            ->orderByDesc('created_at')
            ->get()
            ->map(fn($o) => [
                'id' => $o->id,
                'order_number' => $o->order_number,
                'table_id' => $o->table_id,
                'table_name' => $o->table?->name ?? 'À emporter',
                'total_amount' => $o->total_amount,
                'status' => $o->status,
                'status_label' => $o->status_label,
                'status_color' => $o->status_color,
                'kitchen_status' => $o->kitchen_status,
                'items_count' => $o->items->count(),
                'cashier_name' => $o->user?->name ?? '—',
                'created_at_human' => $o->created_at->diffForHumans(),
                'sent_to_kitchen_at' => $o->sent_to_kitchen_at?->toIso8601String(),
                'kitchen_wait_mins' => $o->getKitchenWaitMinutes(),
                'items' => $o->items->map(fn($i) => [
                    'id' => $i->id,
                    'product_name' => $i->product_name,
                    'quantity' => $i->quantity,
                    'price_at_sale' => $i->price_at_sale,
                    'subtotal' => $i->subtotal,
                    'kitchen_route' => $i->kitchen_route ?? 'kitchen',
                    'kitchen_status' => $i->kitchen_status ?? 'en_attente',
                ]),
            ]);

        // Tables avec état en temps réel
        $tables = $orderService->getTablesWithStatus($restaurantId);

        return view('pos.index', [
            'categories' => $categories,
            'products' => $products,
            'restaurant' => $restaurant,
            'activeOrders' => $activeOrders,
            'tables' => $tables,
        ]);
    }
}
