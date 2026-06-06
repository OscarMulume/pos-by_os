<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PosController extends Controller
{

    /**
     * Display the POS interface with categories and available products.
     */
    public function index(Request $request): View
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

        // Commandes du jour pour le workflow visuel
        $todayOrders = \App\Models\Order::with(['user', 'items'])
            ->where('restaurant_id', $restaurantId)
            ->whereDate('created_at', today())
            ->whereIn('status', ['en_cours', 'en_attente', 'payee', 'paid'])
            ->orderByDesc('created_at')
            ->get()
            ->map(fn($o) => [
                'id' => $o->id,
                'order_number' => $o->order_number,
                'total_amount' => $o->total_amount,
                'status' => $o->status === 'paid' ? 'payee' : $o->status,
                'items_count' => $o->items->count(),
                'cashier_name' => $o->user?->name ?? '—',
                'created_at_human' => $o->created_at->diffForHumans(),
                'items' => $o->items->map(fn($i) => [
                    'id' => $i->id,
                    'product_name' => $i->product_name,
                    'quantity' => $i->quantity,
                    'price_at_sale' => $i->price_at_sale,
                    'subtotal' => $i->subtotal,
                ]),
            ]);

        // Tables du restaurant pour la sélection
        $tables = \App\Models\RestaurantTable::where('restaurant_id', $restaurantId)
            ->where('is_active', true)
            ->orderBy('zone')
            ->orderBy('name')
            ->get();

        return view('pos.index', [
            'categories' => $categories,
            'products' => $products,
            'restaurant' => $restaurant,
            'todayOrders' => $todayOrders,
            'tables' => $tables,
        ]);
    }
}
