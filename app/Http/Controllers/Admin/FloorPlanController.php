<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Restaurant;
use App\Models\RestaurantTable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FloorPlanController extends Controller
{
    /**
     * Afficher le plan de salle en temps réel
     */
    public function index(Request $request): View
    {
        $restaurantId = $request->user()->restaurant_id;
        $restaurant = Restaurant::find($restaurantId);

        $tables = RestaurantTable::with(['currentOrder.items'])
            ->where('restaurant_id', $restaurantId)
            ->where('is_active', true)
            ->orderBy('zone')
            ->orderBy('name')
            ->get();

        return view('admin.floor-plan.index', compact('restaurant', 'tables'));
    }

    /**
     * API: Données temps réel du plan de salle (polling AJAX)
     */
    public function data(Request $request): JsonResponse
    {
        $restaurantId = $request->user()->restaurant_id;
        $slaMinutes = $request->user()->restaurant->sla_warning_minutes ?? 30;

        $tables = RestaurantTable::with(['currentOrder.items'])
            ->where('restaurant_id', $restaurantId)
            ->where('is_active', true)
            ->orderBy('zone')
            ->orderBy('name')
            ->get();

        $stats = [
            'available'         => $tables->where('status', RestaurantTable::STATUS_AVAILABLE)->count(),
            'kitchen_processing'=> $tables->where('status', RestaurantTable::STATUS_KITCHEN_PROCESSING)->count(),
            'served_unpaid'     => $tables->where('status', RestaurantTable::STATUS_SERVED_UNPAID)->count(),
            'occupied'          => $tables->where('status', RestaurantTable::STATUS_OCCUPIED)->count(),
            'sla_breached'      => $tables->filter(fn($t) => $t->isSlaBreached($slaMinutes))->count(),
        ];

        $tablesData = $tables->map(fn($t) => [
            'id'             => $t->id,
            'name'           => $t->name,
            'zone'           => $t->zone,
            'status'         => $t->status,
            'status_color'   => $t->getStatusColor(),
            'status_label'   => $t->getStatusLabel(),
            'wait_minutes'   => $t->getWaitMinutes(),
            'sla_breached'   => $t->isSlaBreached($slaMinutes),
            'current_order'  => $t->currentOrder ? [
                'id'           => $t->currentOrder->id,
                'order_number' => $t->currentOrder->order_number,
                'total'        => $t->currentOrder->total_amount,
                'status'       => $t->currentOrder->status,
                'items_count'  => $t->currentOrder->items->count(),
                'sent_at'      => $t->currentOrder->sent_to_kitchen_at?->toIso8601String(),
            ] : null,
        ]);

        return response()->json([
            'stats'  => $stats,
            'tables' => $tablesData,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Détails d'une table (modale)
     */
    public function tableDetails(Request $request, RestaurantTable $table): View
    {
        $table->load(['currentOrder.items.product', 'currentOrder.user']);

        return view('admin.floor-plan.table-details', compact('table'));
    }
}
