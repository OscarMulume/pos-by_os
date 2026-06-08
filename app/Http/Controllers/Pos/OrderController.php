<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrderRequest;
use App\Models\Order;
use App\Models\AuditLog;
use App\Services\OrderService;
use App\Services\ReceiptService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class OrderController extends Controller
{
    /**
     * Créer une nouvelle commande (statut: sent_to_kitchen ou delivered)
     * FIX BUG: Utilise OrderService avec DB::transaction + gestion des erreurs propre
     */
    public function store(StoreOrderRequest $request, OrderService $orderService): JsonResponse
    {
        try {
            $order = $orderService->createOrder(
                $request->validated(),
                $request->user()
            );

            $order->load('items');

            return response()->json([
                'success'       => true,
                'order_id'      => $order->id,
                'order_number'  => $order->order_number,
                'total'         => $order->total_amount,
                'status'        => $order->status,
                'status_label'  => $order->status_label,
                'items'         => $order->items,
                'message'       => "Commande {$order->order_number} créée avec succès.",
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Produit introuvable. Veuillez rafraîchir la liste.',
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Order creation failed: ' . $e->getMessage(), [
                'user_id' => $request->user()->id,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création de la commande: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Marquer une commande comme "prête" (KDS → notification caisse)
     */
    public function markReady(Order $order, OrderService $orderService): JsonResponse
    {
        try {
            // Vérifier que la commande appartient au restaurant de l'utilisateur
            $this->authorizeOrderAccess($order);

            $order = $orderService->markAsReady($order);
            return response()->json([
                'success'      => true,
                'status'       => $order->status,
                'status_label' => $order->status_label,
                'message'      => "Commande {$order->order_number} marquée comme prête.",
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /**
     * Marquer une commande comme "servie" (livrée au client)
     */
    public function markDelivered(Order $order, OrderService $orderService): JsonResponse
    {
        try {
            $this->authorizeOrderAccess($order);

            $order = $orderService->markAsDelivered($order);
            return response()->json([
                'success'      => true,
                'status'       => $order->status,
                'status_label' => $order->status_label,
                'message'      => "Commande {$order->order_number} marquée comme servie.",
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /**
     * Payer une commande (ready/delivered → paid)
     */
    public function pay(Request $request, Order $order, OrderService $orderService): JsonResponse
    {
        $validated = $request->validate([
            'payment_method'    => 'required|in:cash,mobile_money,credit',
            'payment_reference' => 'nullable|string|max:100',
            'cash_received'     => 'nullable|numeric|min:0',
            'customer_name'     => 'nullable|string|max:100',
            'customer_phone'    => 'nullable|string|max:20',
        ]);

        // Validation cash_received si paiement en espèces
        if ($validated['payment_method'] === 'cash' && !isset($validated['cash_received'])) {
            return response()->json([
                'success' => false,
                'message' => 'Le montant reçu est obligatoire pour un paiement en espèces.',
            ], 422);
        }

        if (isset($validated['cash_received']) && $validated['cash_received'] < $order->total_amount) {
            return response()->json([
                'success' => false,
                'message' => 'Le montant reçu est inférieur au total.',
            ], 422);
        }

        if ($validated['payment_method'] === 'mobile_money' && empty($validated['payment_reference'])) {
            return response()->json([
                'success' => false,
                'message' => 'La référence de transaction est obligatoire pour Mobile Money.',
            ], 422);
        }

        try {
            $this->authorizeOrderAccess($order);

            $order = $orderService->payOrder($order, $validated);

            $receiptService = app(ReceiptService::class);
            $order->load(['items', 'restaurant', 'user']);
            $receiptHtml = $receiptService->generateHtmlReceipt($order);

            return response()->json([
                'success'       => true,
                'order_number'  => $order->order_number,
                'total'         => $order->total_amount,
                'change_given'  => $order->change_given,
                'status'        => $order->status,
                'status_label'  => $order->status_label,
                'receipt_html'  => $receiptHtml,
                'message'       => "Commande {$order->order_number} payée avec succès.",
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /**
     * Annuler une commande avec motif
     */
    public function cancel(Request $request, Order $order, OrderService $orderService): JsonResponse
    {
        $validated = $request->validate([
            'reason'         => 'required|string|min:3|max:500',
            'rupture_stock'  => 'boolean',
        ]);

        try {
            $this->authorizeOrderAccess($order);

            $order = $orderService->cancelOrder(
                $order,
                $request->user(),
                $validated['reason'],
                $request->boolean('rupture_stock', false)
            );

            return response()->json([
                'success'      => true,
                'status'       => $order->status,
                'status_label' => $order->status_label,
                'message'      => "Commande {$order->order_number} annulée.",
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /**
     * Afficher le reçu HTML d'une commande
     */
    public function receipt(Order $order, ReceiptService $receiptService): Response
    {
        $order->load(['items', 'restaurant', 'user']);
        $html = $receiptService->generateHtmlReceipt($order);
        return response($html, 200, ['Content-Type' => 'text/html']);
    }

    /**
     * Vérifier les commandes non soldées (clôture de caisse)
     */
    public function checkUnsettled(Request $request, OrderService $orderService): JsonResponse
    {
        $restaurantId = $request->user()->restaurant_id;
        $orders = $orderService->getUnsettledOrders($restaurantId);

        return response()->json([
            'has_unsettled' => $orders->isNotEmpty(),
            'count'         => $orders->count(),
            'orders'        => $orders,
        ]);
    }

    /**
     * Vérifier que la commande appartient au restaurant de l'utilisateur
     */
    private function authorizeOrderAccess(Order $order): void
    {
        if ($order->restaurant_id !== auth()->user()->restaurant_id) {
            abort(403, 'Cette commande n\'appartient pas à votre restaurant.');
        }
    }
}
