<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
use App\Models\ProductVariant;
use App\Models\ProductVariantOption;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $restaurantId = $request->user()->restaurant_id;
        $query = Product::with('category')->where('restaurant_id', $restaurantId);

        if ($request->filled('category')) {
            $query->where('category_id', $request->category);
        }
        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }
        if ($request->get('filter') === 'unavailable') {
            $query->where('is_available', false);
        }
        if ($request->get('filter') === 'low_stock') {
            $query->lowStock();
        }

        $products = $query->orderBy('name')->paginate(30);
        $categories = Category::where('restaurant_id', $restaurantId)->where('is_active', true)->orderBy('display_order', 'asc')->orderBy('name', 'asc')->get();

        return view('admin.products.index', compact('products', 'categories'));
    }

    public function create(Request $request): View
    {
        $restaurantId = $request->user()->restaurant_id;
        $categories = Category::where('restaurant_id', $restaurantId)->where('is_active', true)->orderBy('display_order', 'asc')->orderBy('name', 'asc')->get();
        return view('admin.products.create', compact('categories'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
            'price' => 'required|numeric|min:0',
            'cost_price' => 'nullable|numeric|min:0',
            'category_id' => 'nullable|exists:categories,id',
            'image' => 'nullable|image|mimes:png,jpg,jpeg,webp|max:2048',
            'track_inventory' => 'boolean',
            'stock_quantity' => 'nullable|integer|min:0',
            'low_stock_threshold' => 'nullable|integer|min:0',
            'sort_order' => 'nullable|integer|min:0',
            'prep_time_minutes' => 'nullable|integer|min:1|max:120',
            'kitchen_route' => 'nullable|in:kitchen,bar,counter',
            'variants' => 'nullable|array',
            'variants.*.name' => 'required|string|max:100',
            'variants.*.options' => 'nullable|array',
            'variants.*.options.*.name' => 'required|string|max:100',
            'variants.*.options.*.price_adjustment' => 'nullable|numeric',
        ]);

        $restaurantId = $request->user()->restaurant_id;

        $product = Product::create([
            'restaurant_id' => $restaurantId,
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'price' => $validated['price'],
            'cost_price' => $validated['cost_price'] ?? 0,
            'category_id' => $validated['category_id'] ?? null,
            'track_inventory' => $validated['track_inventory'] ?? false,
            'stock_quantity' => $validated['stock_quantity'] ?? 0,
            'low_stock_threshold' => $validated['low_stock_threshold'] ?? 5,
            'is_available' => true,
            'sort_order' => $validated['sort_order'] ?? 0,
            'prep_time_minutes' => $validated['prep_time_minutes'] ?? 15,
            'kitchen_route' => $validated['kitchen_route'] ?? 'kitchen',
        ]);

        // Créer les variantes
        if (!empty($validated['variants'])) {
            foreach ($validated['variants'] as $vIndex => $variantData) {
                $variant = ProductVariant::create([
                    'product_id' => $product->id,
                    'name' => $variantData['name'],
                    'is_required' => $variantData['is_required'] ?? false,
                    'allow_multiple' => $variantData['allow_multiple'] ?? false,
                    'sort_order' => $vIndex,
                ]);

                if (!empty($variantData['options'])) {
                    foreach ($variantData['options'] as $oIndex => $optData) {
                        ProductVariantOption::create([
                            'product_variant_id' => $variant->id,
                            'name' => $optData['name'],
                            'price_adjustment' => $optData['price_adjustment'] ?? 0,
                            'is_default' => $optData['is_default'] ?? false,
                            'sort_order' => $oIndex,
                        ]);
                    }
                }
            }
        }

        return redirect()->route('admin.products.index')->with('success', "Produit « {$product->name} » créé.");
    }

    public function edit(Request $request, Product $product): View
    {
        $restaurantId = $request->user()->restaurant_id;
        $categories = Category::where('restaurant_id', $restaurantId)->where('is_active', true)->orderBy('display_order', 'asc')->orderBy('name', 'asc')->get();
        $product->load(['variants.options']);
        return view('admin.products.edit', compact('product', 'categories'));
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
            'price' => 'required|numeric|min:0',
            'cost_price' => 'nullable|numeric|min:0',
            'category_id' => 'nullable|exists:categories,id',
            'track_inventory' => 'boolean',
            'stock_quantity' => 'nullable|integer|min:0',
            'low_stock_threshold' => 'nullable|integer|min:0',
        ]);

        $product->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'price' => $validated['price'],
            'cost_price' => $validated['cost_price'] ?? 0,
            'category_id' => $validated['category_id'] ?? null,
            'track_inventory' => $validated['track_inventory'] ?? false,
            'stock_quantity' => $validated['stock_quantity'] ?? $product->stock_quantity,
            'low_stock_threshold' => $validated['low_stock_threshold'] ?? $product->low_stock_threshold,
        ]);

        return redirect()->route('admin.products.index')->with('success', "Produit « {$product->name} » mis à jour.");
    }

    public function toggleAvailability(Product $product): RedirectResponse
    {
        $product->update(['is_available' => !$product->is_available]);
        $status = $product->is_available ? 'activé' : 'désactivé';
        return back()->with('success', "Produit « {$product->name} » {$status}.");
    }

    public function destroy(Product $product): RedirectResponse
    {
        $name = $product->name;
        $product->delete();
        return redirect()->route('admin.products.index')->with('success', "Produit « {$name} » supprimé.");
    }
}
