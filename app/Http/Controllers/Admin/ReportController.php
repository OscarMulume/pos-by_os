<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Restaurant;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $restaurantId = $user->isSuperAdmin()
            ? $request->input('restaurant_id')
            : $user->restaurant_id;

        $startDate = $request->input('start_date', now()->startOfDay()->toDateTimeString());
        $endDate = $request->input('end_date', now()->endOfDay()->toDateTimeString());

        // ═══════════════════════════════════════════════════
        // AGRÉGATIONS SQL — pas de ->get() sur les orders
        // ═══════════════════════════════════════════════════

        // Ventes brutes (tous les orders sauf annulés)
        $baseQuery = Order::whereBetween('created_at', [$startDate, $endDate])
            ->where('status', '!=', Order::STATUS_ANNULEE);

        if ($restaurantId) {
            $baseQuery->where('restaurant_id', $restaurantId);
        }

        $salesSummary = (clone $baseQuery)
            ->selectRaw('
                COUNT(*) as total_orders,
                SUM(total_amount) as gross_revenue,
                SUM(discount_amount) as total_discounts,
                SUM(tax_amount) as total_taxes,
                AVG(total_amount) as average_order_value
            ')
            ->first();

        // Ventes nettes (payées uniquement)
        $paidSummary = (clone $baseQuery)
            ->where('status', Order::STATUS_PAID)
            ->selectRaw('
                COUNT(*) as paid_orders,
                SUM(total_amount) as net_revenue
            ')
            ->first();

        // Annulations
        $cancelledSummary = Order::whereBetween('created_at', [$startDate, $endDate])
            ->where('status', Order::STATUS_ANNULEE)
            ->when($restaurantId, fn($q) => $q->where('restaurant_id', $restaurantId))
            ->selectRaw('COUNT(*) as cancelled_count, SUM(total_amount) as cancelled_amount')
            ->first();

        // Répartition par catégorie
        $salesByCategory = OrderItem::whereHas('order', function ($q) use ($startDate, $endDate, $restaurantId) {
                $q->whereBetween('created_at', [$startDate, $endDate])
                  ->where('status', Order::STATUS_PAID);
                if ($restaurantId) {
                    $q->where('restaurant_id', $restaurantId);
                }
            })
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
            ->selectRaw('
                COALESCE(categories.name, "Non catégorisé") as category_name,
                COALESCE(categories.icon, "📦") as category_icon,
                SUM(order_items.quantity) as total_quantity,
                SUM(order_items.subtotal) as total_revenue
            ')
            ->groupBy('category_name', 'category_icon')
            ->orderByDesc('total_revenue')
            ->get();

        // Répartition par mode de paiement
        $salesByPaymentMethod = (clone $baseQuery)
            ->where('status', Order::STATUS_PAID)
            ->selectRaw('
                payment_method,
                COUNT(*) as transaction_count,
                SUM(total_amount) as total_amount
            ')
            ->groupBy('payment_method')
            ->orderByDesc('total_amount')
            ->get()
            ->map(function ($item) use ($paidSummary) {
                $item->percentage = $paidSummary->net_revenue > 0
                    ? round(($item->total_amount / $paidSummary->net_revenue) * 100, 1)
                    : 0;
                $item->label = match ($item->payment_method) {
                    'cash' => 'Espèces',
                    'mobile_money' => 'Mobile Money',
                    'credit' => 'Crédit',
                    default => $item->payment_method,
                };
                return $item;
            });

        // Top produits
        $topProducts = OrderItem::whereHas('order', function ($q) use ($startDate, $endDate, $restaurantId) {
                $q->whereBetween('created_at', [$startDate, $endDate])
                  ->where('status', Order::STATUS_PAID);
                if ($restaurantId) {
                    $q->where('restaurant_id', $restaurantId);
                }
            })
            ->selectRaw('
                product_name,
                SUM(quantity) as total_quantity,
                SUM(subtotal) as total_revenue
            ')
            ->groupBy('product_name')
            ->orderByDesc('total_quantity')
            ->limit(10)
            ->get();

        // Ventes par restaurant (pour super admin)
        $salesByRestaurant = collect();
        if ($user->isSuperAdmin()) {
            $salesByRestaurant = Restaurant::leftJoin('orders', function ($join) use ($startDate, $endDate) {
                    $join->on('restaurants.id', '=', 'orders.restaurant_id')
                         ->whereBetween('orders.created_at', [$startDate, $endDate])
                         ->where('orders.status', Order::STATUS_PAID);
                })
                ->selectRaw('
                    restaurants.id,
                    restaurants.name,
                    COUNT(orders.id) as order_count,
                    COALESCE(SUM(orders.total_amount), 0) as total_revenue
                ')
                ->groupBy('restaurants.id', 'restaurants.name')
                ->orderByDesc('total_revenue')
                ->get()
                ->map(function ($item) {
                    $item->average_order = $item->order_count > 0
                        ? round($item->total_revenue / $item->order_count, 2)
                        : 0;
                    return $item;
                });
        }

        // Évolution journalière (pour graphique)
        $dailyTrend = (clone $baseQuery)
            ->where('status', Order::STATUS_PAID)
            ->selectRaw('
                DATE(created_at) as date,
                COUNT(*) as order_count,
                SUM(total_amount) as revenue
            ')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $restaurants = $user->isSuperAdmin()
            ? Restaurant::orderBy('name')->get()
            : collect();

        return view('admin.reports.index', compact(
            'salesSummary',
            'paidSummary',
            'cancelledSummary',
            'salesByCategory',
            'salesByPaymentMethod',
            'topProducts',
            'salesByRestaurant',
            'dailyTrend',
            'restaurants',
            'restaurantId',
            'startDate',
            'endDate',
        ));
    }

    /**
     * Export PDF du rapport
     */
    public function exportPdf(Request $request)
    {
        $user = $request->user();
        $restaurantId = $user->isSuperAdmin()
            ? $request->input('restaurant_id')
            : $user->restaurant_id;

        $startDate = $request->input('start_date', now()->startOfDay()->toDateTimeString());
        $endDate = $request->input('end_date', now()->endOfDay()->toDateTimeString());

        // Réutiliser les mêmes agrégations
        $baseQuery = Order::whereBetween('created_at', [$startDate, $endDate])
            ->where('status', '!=', Order::STATUS_ANNULEE);

        if ($restaurantId) {
            $baseQuery->where('restaurant_id', $restaurantId);
        }

        $salesSummary = (clone $baseQuery)
            ->selectRaw('COUNT(*) as total_orders, SUM(total_amount) as gross_revenue, SUM(discount_amount) as total_discounts, AVG(total_amount) as average_order_value')
            ->first();

        $paidSummary = (clone $baseQuery)
            ->where('status', Order::STATUS_PAID)
            ->selectRaw('COUNT(*) as paid_orders, SUM(total_amount) as net_revenue')
            ->first();

        $cancelledSummary = Order::whereBetween('created_at', [$startDate, $endDate])
            ->where('status', Order::STATUS_ANNULEE)
            ->when($restaurantId, fn($q) => $q->where('restaurant_id', $restaurantId))
            ->selectRaw('COUNT(*) as cancelled_count, SUM(total_amount) as cancelled_amount')
            ->first();

        $salesByCategory = OrderItem::whereHas('order', function ($q) use ($startDate, $endDate, $restaurantId) {
                $q->whereBetween('created_at', [$startDate, $endDate])->where('status', Order::STATUS_PAID);
                if ($restaurantId) $q->where('restaurant_id', $restaurantId);
            })
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
            ->selectRaw('COALESCE(categories.name, "Non catégorisé") as category_name, SUM(order_items.quantity) as total_quantity, SUM(order_items.subtotal) as total_revenue')
            ->groupBy('category_name')
            ->orderByDesc('total_revenue')
            ->get();

        $salesByPaymentMethod = (clone $baseQuery)
            ->where('status', Order::STATUS_PAID)
            ->selectRaw('payment_method, COUNT(*) as transaction_count, SUM(total_amount) as total_amount')
            ->groupBy('payment_method')
            ->get()
            ->map(function ($item) use ($paidSummary) {
                $item->percentage = $paidSummary->net_revenue > 0 ? round(($item->total_amount / $paidSummary->net_revenue) * 100, 1) : 0;
                $item->label = match ($item->payment_method) {
                    'cash' => 'Espèces',
                    'mobile_money' => 'Mobile Money',
                    'credit' => 'Crédit',
                    default => $item->payment_method,
                };
                return $item;
            });

        $restaurant = $restaurantId ? Restaurant::find($restaurantId) : null;

        $pdf = Pdf::loadView('admin.reports.pdf', compact(
            'salesSummary', 'paidSummary', 'cancelledSummary',
            'salesByCategory', 'salesByPaymentMethod',
            'restaurant', 'startDate', 'endDate'
        ));

        $pdf->setPaper('A4', 'portrait');

        return $pdf->download('rapport_' . now()->format('Y-m-d_His') . '.pdf');
    }
}
