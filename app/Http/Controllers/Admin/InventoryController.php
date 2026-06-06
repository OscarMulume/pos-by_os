<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class InventoryController extends Controller
{
    /**
     * Liste des produits avec stock
     */
    public function index(Request $request): View
    {
        $restaurantId = $request->user()->restaurant_id;

        $query = Product::where('restaurant_id', $restaurantId)
            ->with('category');

        // Filtre par stock bas
        if ($request->get('filter') === 'low') {
            $query->lowStock();
        }

        // Recherche
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
        }

        $products = $query->orderBy('name')->paginate(30);

        // Stats
        $stats = [
            'total_products' => Product::where('restaurant_id', $restaurantId)->count(),
            'low_stock' => Product::where('restaurant_id', $restaurantId)->lowStock()->count(),
            'out_of_stock' => Product::where('restaurant_id', $restaurantId)
                ->where('track_inventory', true)
                ->where('stock_quantity', '<=', 0)
                ->count(),
            'tracked' => Product::where('restaurant_id', $restaurantId)->where('track_inventory', true)->count(),
        ];

        return view('admin.inventory.index', compact('products', 'stats'));
    }

    /**
     * Ajuster le stock d'un produit
     */
    public function adjust(Request $request, Product $product): RedirectResponse
    {
        $validated = $request->validate([
            'quantity' => 'required|integer|min:1',
            'reason' => 'required|string|max:255',
        ]);

        $product->addStock($validated['quantity'], $validated['reason'], auth()->id());

        return back()->with('success', "Stock de « {$product->name} » augmenté de {$validated['quantity']}.");
    }

    /**
     * Mettre à jour les paramètres de suivi de stock
     */
    public function updateSettings(Request $request, Product $product): RedirectResponse
    {
        $validated = $request->validate([
            'track_inventory' => 'boolean',
            'low_stock_threshold' => 'nullable|integer|min:0',
            'stock_quantity' => 'nullable|integer|min:0',
        ]);

        $product->update([
            'track_inventory' => $validated['track_inventory'] ?? false,
            'low_stock_threshold' => $validated['low_stock_threshold'] ?? 5,
            'stock_quantity' => $validated['stock_quantity'] ?? $product->stock_quantity,
        ]);

        return back()->with('success', "Paramètres de stock mis à jour pour « {$product->name} ».");
    }

    /**
     * Historique des mouvements de stock
     */
    public function movements(Request $request): View
    {
        $restaurantId = $request->user()->restaurant_id;

        $movements = StockMovement::with(['product:id,name', 'user:id,name'])
            ->where('restaurant_id', $restaurantId)
            ->orderByDesc('created_at')
            ->paginate(50);

        return view('admin.inventory.movements', compact('movements'));
    }
}
