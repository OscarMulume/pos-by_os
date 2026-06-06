<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Restaurant;
use Carbon\Carbon;

class ReportService
{
    public function getDashboardData(): array
    {
        $restaurants = Restaurant::where('is_active', true)->get();
        $today = today();
        $yesterday = today()->subDay();

        $todayRevenue = Order::whereDate('created_at', $today)->where('status', 'paid')->sum('total_amount');
        $yesterdayRevenue = Order::whereDate('created_at', $yesterday)->where('status', 'paid')->sum('total_amount');
        $todayOrders = Order::whereDate('created_at', $today)->where('status', 'paid')->count();
        $yesterdayOrders = Order::whereDate('created_at', $yesterday)->where('status', 'paid')->count();

        $revenueGrowth = $yesterdayRevenue > 0
            ? round((($todayRevenue - $yesterdayRevenue) / $yesterdayRevenue) * 100, 1)
            : 0;
        $orderGrowth = $yesterdayOrders > 0
            ? round((($todayOrders - $yesterdayOrders) / $yesterdayOrders) * 100, 1)
            : 0;

        $paymentMethods = Order::whereDate('created_at', $today)
            ->where('status', 'paid')
            ->selectRaw('payment_method, COUNT(*) as count, SUM(total_amount) as total')
            ->groupBy('payment_method')
            ->get();

        $topProducts = \App\Models\OrderItem::whereHas('order', function ($q) use ($today) {
                $q->whereDate('created_at', $today)->where('status', 'paid');
            })
            ->selectRaw('product_name, SUM(quantity) as total_qty, SUM(subtotal) as total_revenue')
            ->groupBy('product_name')
            ->orderByDesc('total_qty')
            ->limit(5)
            ->get();

        $restaurantStats = $restaurants->map(function ($resto) use ($today) {
            $revenue = Order::where('restaurant_id', $resto->id)
                ->whereDate('created_at', $today)
                ->where('status', 'paid')
                ->sum('total_amount');
            $orders = Order::where('restaurant_id', $resto->id)
                ->whereDate('created_at', $today)
                ->where('status', 'paid')
                ->count();
            return [
                'id' => $resto->id,
                'name' => $resto->name,
                'revenue' => (float) $revenue,
                'orders' => $orders,
            ];
        });

        $last7Days = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = today()->subDays($i);
            $last7Days[] = [
                'date' => $date->format('d/m'),
                'revenue' => (float) Order::whereDate('created_at', $date)->where('status', 'paid')->sum('total_amount'),
                'orders' => Order::whereDate('created_at', $date)->where('status', 'paid')->count(),
            ];
        }

        return [
            'today_revenue' => (float) $todayRevenue,
            'yesterday_revenue' => (float) $yesterdayRevenue,
            'revenue_growth' => $revenueGrowth,
            'today_orders' => $todayOrders,
            'yesterday_orders' => $yesterdayOrders,
            'order_growth' => $orderGrowth,
            'payment_methods' => $paymentMethods,
            'top_products' => $topProducts,
            'restaurant_stats' => $restaurantStats,
            'last_7_days' => $last7Days,
        ];
    }

    public function getTransactionsReport(?int $restaurantId = null, ?string $startDate = null, ?string $endDate = null, ?string $paymentMethod = null, ?string $status = null)
    {
        $query = Order::with(['restaurant', 'user', 'items'])
            ->orderByDesc('created_at');

        if ($restaurantId) {
            $query->where('restaurant_id', $restaurantId);
        }
        if ($startDate) {
            $query->whereDate('created_at', '>=', $startDate);
        }
        if ($endDate) {
            $query->whereDate('created_at', '<=', $endDate);
        }
        if ($paymentMethod) {
            $query->where('payment_method', $paymentMethod);
        }
        if ($status) {
            $query->where('status', $status);
        }

        return $query->paginate(25);
    }

    public function exportCsv(?int $restaurantId = null, ?string $startDate = null, ?string $endDate = null): string
    {
        $query = Order::with(['restaurant', 'user', 'items'])
            ->orderByDesc('created_at');

        if ($restaurantId) $query->where('restaurant_id', $restaurantId);
        if ($startDate) $query->whereDate('created_at', '>=', $startDate);
        if ($endDate) $query->whereDate('created_at', '<=', $endDate);

        $orders = $query->get();

        $headers = [
            'Numéro', 'Restaurant', 'Caissier', 'Date', 'Total',
            'Méthode', 'Référence', 'Client', 'Statut', 'Articles',
        ];

        $rows = [];
        foreach ($orders as $order) {
            $itemsList = $order->items->map(
                fn($i) => "{$i->quantity}x {$i->product_name}"
            )->implode('; ');

            $rows[] = [
                $order->order_number,
                $order->restaurant->name,
                $order->user->name,
                $order->created_at->format('d/m/Y H:i'),
                $order->total_amount,
                $order->payment_method_label,
                $order->payment_reference ?? '',
                $order->customer_name ?? '',
                $order->status_label,
                $itemsList,
            ];
        }

        $filename = 'rapport_ventes_' . now()->format('Y-m-d_His') . '.csv';
        $path = storage_path("app/exports/{$filename}");

        if (!is_dir(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        $fp = fopen($path, 'w');
        fprintf($fp, chr(0xEF) . chr(0xBB) . chr(0xBF));
        fputcsv($fp, $headers, ';');
        foreach ($rows as $row) {
            fputcsv($fp, $row, ';');
        }
        fclose($fp);

        return $path;
    }
}
