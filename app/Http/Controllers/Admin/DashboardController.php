<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\PosTerminal;
use App\Models\CashShift;
use App\Models\RestaurantTable;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $restaurantId = $user->restaurant_id;

        if (!$restaurantId) {
            return view('admin.dashboard', ['data' => $this->getEmptyData()]);
        }

        $data = $this->getDashboardData($restaurantId);
        return view('admin.dashboard', compact('data'));
    }

    private function getDashboardData(int $restaurantId): array
    {
        $today = today();
        $yesterday = today()->subDay();

        // ═══════════════════════════════════════════
        // CA DU JOUR (agrégation SQL, pas de chargementbulk)
        // ═══════════════════════════════════════════
        $ca = Order::where('restaurant_id', $restaurantId)
            ->whereDate('created_at', $today)
            ->selectRaw("
                COUNT(CASE WHEN status IN ('paid','delivered','ready','sent_to_kitchen','pending') THEN 1 END) as total_orders,
                COALESCE(SUM(CASE WHEN status = 'paid' THEN total_amount ELSE 0 END), 0) as total_revenue,
                COUNT(CASE WHEN status IN ('sent_to_kitchen','pending') THEN 1 END) as orders_en_cours,
                COUNT(CASE WHEN status IN ('ready','delivered') THEN 1 END) as orders_en_attente,
                COUNT(CASE WHEN status = 'annulee' THEN 1 END) as orders_annulees
            ")
            ->first();

        // CA HIER pour comparatif
        $caYesterday = Order::where('restaurant_id', $restaurantId)
            ->whereDate('created_at', $yesterday)
            ->selectRaw("
                COUNT(CASE WHEN status IN ('paid','delivered','ready','sent_to_kitchen','pending') THEN 1 END) as total_orders,
                COALESCE(SUM(CASE WHEN status = 'paid' THEN total_amount ELSE 0 END), 0) as total_revenue
            ")
            ->first();

        // ═══════════════════════════════════════════
        // COMPARATIF % vs HIER
        // ═══════════════════════════════════════════
        $evoCa = $caYesterday->total_revenue > 0
            ? round((($ca->total_revenue - $caYesterday->total_revenue) / $caYesterday->total_revenue) * 100, 1)
            : ($ca->total_revenue > 0 ? 100 : 0);

        $evoOrders = $caYesterday->total_orders > 0
            ? round((($ca->total_orders - $caYesterday->total_orders) / $caYesterday->total_orders) * 100, 1)
            : ($ca->total_orders > 0 ? 100 : 0);

        // ═══════════════════════════════════════════
        // TOP PRODUITS (GROUP BY avec LIMIT)
        // ═══════════════════════════════════════════
        $topProducts = \DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->where('orders.restaurant_id', $restaurantId)
            ->whereDate('orders.created_at', $today)
            ->where('orders.status', 'paid')
            ->selectRaw('
                order_items.product_id,
                order_items.product_name,
                SUM(order_items.quantity) as total_qty,
                SUM(order_items.subtotal) as total_revenue
            ')
            ->groupBy('order_items.product_id', 'order_items.product_name')
            ->orderByDesc('total_qty')
            ->limit(5)
            ->get();

        // ═══════════════════════════════════════════
        // MODES DE PAIEMENT (GROUP BY)
        // ═══════════════════════════════════════════
        $paymentMethods = Order::where('restaurant_id', $restaurantId)
            ->whereDate('created_at', $today)
            ->where('status', 'paid')
            ->selectRaw("
                payment_method,
                COUNT(*) as count,
                SUM(total_amount) as total
            ")
            ->groupBy('payment_method')
            ->orderByDesc('total')
            ->get();

        $topPaymentMethod = $paymentMethods->first();

        // ═══════════════════════════════════════════
        // GRAPHIQUE 7 DERNIERS JOURS
        // ═══════════════════════════════════════════
        $chart7days = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $dayData = Order::where('restaurant_id', $restaurantId)
                ->whereDate('created_at', $date)
                ->where('status', 'paid')
                ->selectRaw('COUNT(*) as orders, COALESCE(SUM(total_amount), 0) as revenue')
                ->first();

            $chart7days[] = [
                'date' => $date->format('d/m'),
                'day_name' => $date->translatedFormat('D'),
                'orders' => (int) $dayData->orders,
                'revenue' => (float) $dayData->revenue,
                'is_today' => $date->isToday(),
            ];
        }

        // ═══════════════════════════════════════════
        // TERMINEAUX ACTIFS
        // ═══════════════════════════════════════════
        $terminals = PosTerminal::where('restaurant_id', $restaurantId)
            ->where('is_active', true)
            ->select('id', 'name')
            ->get();

        // ═══════════════════════════════════════════
        // TABLES STATUS (nouveaux statuts)
        // ═══════════════════════════════════════════
        $tablesFree = RestaurantTable::where('restaurant_id', $restaurantId)
            ->where('status', 'available')
            ->where('is_active', true)
            ->count();

        $tablesOccupied = RestaurantTable::where('restaurant_id', $restaurantId)
            ->whereIn('status', ['occupied', 'kitchen_processing', 'served_unpaid'])
            ->where('is_active', true)
            ->count();

        // ═══════════════════════════════════════════
        // SHIFTS OUVERTS
        // ═══════════════════════════════════════════
        $openShifts = CashShift::where('restaurant_id', $restaurantId)
            ->where('status', 'open')
            ->with('user:id,name')
            ->get();

        // ═══════════════════════════════════════════
        // ALERTES STOCK BAS
        // ═══════════════════════════════════════════
        $lowStockProducts = Product::where('restaurant_id', $restaurantId)
            ->where('track_inventory', true)
            ->whereColumn('stock_quantity', '<=', 'low_stock_threshold')
            ->where('is_available', true)
            ->select('id', 'name', 'stock_quantity', 'low_stock_threshold')
            ->orderBy('stock_quantity')
            ->limit(5)
            ->get();

        // ═══════════════════════════════════════════
        // DERNIÈRES COMMANDES
        // ═══════════════════════════════════════════
        $recentOrders = Order::with(['user:id,name', 'table:id,name'])
            ->where('restaurant_id', $restaurantId)
            ->whereDate('created_at', $today)
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        return [
            'ca' => [
                'today' => (float) $ca->total_revenue,
                'yesterday' => (float) $caYesterday->total_revenue,
                'evolution' => $evoCa,
                'orders_today' => (int) $ca->total_orders,
                'orders_yesterday' => (int) $caYesterday->total_orders,
                'orders_evolution' => $evoOrders,
                'en_cours' => (int) $ca->orders_en_cours,
                'en_attente' => (int) $ca->orders_en_attente,
                'annulees' => (int) $ca->orders_annulees,
            ],
            'top_products' => $topProducts,
            'payment_methods' => $paymentMethods,
            'top_payment_method' => $topPaymentMethod,
            'chart_7days' => $chart7days,
            'terminals' => $terminals,
            'tables_free' => $tablesFree,
            'tables_occupied' => $tablesOccupied,
            'open_shifts' => $openShifts,
            'low_stock' => $lowStockProducts,
            'recent_orders' => $recentOrders,
        ];
    }

    private function getEmptyData(): array
    {
        return [
            'ca' => ['today' => 0, 'yesterday' => 0, 'evolution' => 0, 'orders_today' => 0, 'orders_yesterday' => 0, 'orders_evolution' => 0, 'en_cours' => 0, 'en_attente' => 0, 'annulees' => 0],
            'top_products' => collect(),
            'payment_methods' => collect(),
            'top_payment_method' => null,
            'chart_7days' => [],
            'terminals' => collect(),
            'tables_free' => 0,
            'tables_occupied' => 0,
            'open_shifts' => collect(),
            'low_stock' => collect(),
            'recent_orders' => collect(),
        ];
    }
}
