<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\ReceiptService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ReceiptController extends Controller
{
    /**
     * Générer un reçu PROFORMA (non payé) — Note provisoire
     * Affiche le ticket avec filigrane "PROFORMA - NON PAYÉ"
     */
    public function proforma(Order $order, ReceiptService $receiptService): Response
    {
        // Vérifier que la commande appartient au restaurant de l'utilisateur
        if ($order->restaurant_id !== auth()->user()->restaurant_id) {
            abort(403);
        }

        $order->load(['items', 'restaurant', 'user', 'table']);
        $html = $receiptService->generateProformaReceipt($order);

        return response($html, 200, ['Content-Type' => 'text/html']);
    }

    /**
     * Reçu normal (après paiement)
     */
    public function show(Order $order, ReceiptService $receiptService): Response
    {
        if ($order->restaurant_id !== auth()->user()->restaurant_id) {
            abort(403);
        }

        $order->load(['items', 'restaurant', 'user', 'table']);
        $html = $receiptService->generateHtmlReceipt($order);

        return response($html, 200, ['Content-Type' => 'text/html']);
    }

    /**
     * Version thermique ESC/POS du reçu
     */
    public function thermal(Order $order, ReceiptService $receiptService): Response
    {
        if ($order->restaurant_id !== auth()->user()->restaurant_id) {
            abort(403);
        }

        $order->load(['items', 'restaurant', 'user', 'table']);
        $html = $receiptService->generateThermalReceipt($order);

        return response($html, 200, ['Content-Type' => 'text/html']);
    }
}
