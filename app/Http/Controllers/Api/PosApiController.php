<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\Restaurant;
use App\Models\RestaurantTable;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PosApiController extends Controller
{
    /**
     * Retourne les produits du restaurant de l'utilisateur authentifié
     */
    public function products(Request $request): JsonResponse
    {
        $restaurantId = $request->user()->restaurant_id;

        $products = Product::with('category')
            ->where('restaurant_id', $restaurantId)
            ->where('is_available', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(fn($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'price' => $p->price,
                'image' => $p->image,
                'category_id' => $p->category_id,
                'track_inventory' => $p->track_inventory,
                'stock_quantity' => $p->stock_quantity,
                'stock_alert_threshold' => $p->stock_alert_threshold,
                'kitchen_route' => $p->kitchen_route,
                'prep_time' => $p->prep_time,
            ]);

        return response()->json(['success' => true, 'data' => $products]);
    }

    /**
     * Retourne les catégories du restaurant
     */
    public function categories(Request $request): JsonResponse
    {
        $restaurantId = $request->user()->restaurant_id;

        $categories = Category::where('restaurant_id', $restaurantId)
            ->where('is_active', true)
            ->orderBy('display_order', 'asc')
            ->orderBy('name', 'asc')
            ->get()
            ->map(fn($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'color' => $c->color,
                'icon' => $c->icon,
            ]);

        return response()->json(['success' => true, 'data' => $categories]);
    }

    /**
     * Retourne les tables avec leur statut et commande active
     */
    public function tables(Request $request, OrderService $orderService): JsonResponse
    {
        $restaurantId = $request->user()->restaurant_id;
        $tables = $orderService->getTablesWithStatus($restaurantId);

        return response()->json(['success' => true, 'data' => $tables]);
    }

    /**
     * Retourne les infos du restaurant
     */
    public function restaurant(Request $request): JsonResponse
    {
        $restaurantId = $request->user()->restaurant_id;
        $restaurant = Restaurant::find($restaurantId);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $restaurant->id,
                'name' => $restaurant->name,
                'currency' => $restaurant->currency ?? 'FC',
                'tax_rate' => $restaurant->tax_rate ?? 0,
                'logo_path' => $restaurant->logo_path,
                'address' => $restaurant->address,
                'phone' => $restaurant->phone,
            ]
        ]);
    }

    /**
     * Authentification par email/password — crée une session Laravel
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if (!auth()->attempt($request->only('email', 'password'))) {
            return response()->json([
                'success' => false,
                'message' => 'Identifiants incorrects.',
            ], 401);
        }

        $user = auth()->user();

        if (!$user->restaurant_id) {
            auth()->logout();
            return response()->json([
                'success' => false,
                'message' => 'Aucun restaurant assigné à ce compte.',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'token' => 'session-based',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'restaurant_id' => $user->restaurant_id,
            ],
        ]);
    }

    /**
     * Rafraîchir les données (polling KDS)
     */
    public function refresh(Request $request, OrderService $orderService): JsonResponse
    {
        $restaurantId = $request->user()->restaurant_id;
        $tables = $orderService->getTablesWithStatus($restaurantId);

        return response()->json([
            'success' => true,
            'data' => [
                'tables' => $tables,
                'timestamp' => now()->toIso8601String(),
            ]
        ]);
    }
}
