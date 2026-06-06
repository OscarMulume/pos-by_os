<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\CancelOrderRequest;
use App\Models\Order;
use App\Services\OrderService;
use App\Services\ReportService;
use Illuminate\Http\Request;

class TransactionController extends Controller
{

    public function index(Request $request, ReportService $reportService)
    {
        $report = $reportService->getTransactionsReport(
            restaurantId: $request->integer('restaurant_id') ?: null,
            startDate: $request->input('start_date') ?: null,
            endDate: $request->input('end_date') ?: null,
            paymentMethod: $request->input('payment_method') ?: null,
            status: $request->input('status') ?: null,
        );

        return view('admin.transactions.index', [
            'transactions' => $report,
            'filters' => $request->only(['restaurant_id', 'start_date', 'end_date', 'payment_method', 'status']),
        ]);
    }

    public function show(Order $order)
    {
        $order->load(['restaurant', 'user', 'items', 'cancelledByUser']);

        return view('admin.transactions.show', compact('order'));
    }

    public function cancel(Order $order, CancelOrderRequest $request, OrderService $orderService)
    {
        $orderService->cancelOrder(
            order: $order,
            admin: auth()->user(),
            reason: $request->input('reason'),
        );

        return redirect()->back()->with('success', 'Commande annulée avec succès.');
    }
}
