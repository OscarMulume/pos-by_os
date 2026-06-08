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

        if ($user->isCashier() && !$user->restaurant_id) {
            return view('pos.unassigned', [
                'message' => 'Vous n\'êtes affecté à aucun restaurant.',
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

        // Tables avec leurs commandes actives (pour le panneau d'actions)
        $tables = $orderService->getTablesWithStatus($restaurantId);

        return view('pos.index', [
            'categories' => $categories,
            'products' => $products,
            'restaurant' => $restaurant,
            'tables' => $tables,
        ]);
    }
}
