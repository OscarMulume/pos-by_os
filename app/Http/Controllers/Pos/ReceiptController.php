<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Restaurant;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReceiptController extends Controller
{
    /**
     * Afficher le reçu HTML (pour impression navigateur)
     */
    public function show(Order $order): View
    {
        $order->load(['items', 'user', 'table', 'restaurant', 'posTerminal']);
        return view('pos.receipt', compact('order'));
    }

    /**
     * Générer le reçu en texte brut (pour impression thermique ESC/POS)
     */
    public function thermal(Order $order): \Illuminate\Http\Response
    {
        $order->load(['items', 'user', 'table', 'restaurant']);
        $restaurant = $order->restaurant;

        $text = $this->generateThermalText($order, $restaurant);

        return response($text, 200, [
            'Content-Type' => 'text/plain; charset=utf-8',
        ]);
    }

    /**
     * Générer le texte ESC/POS pour imprimante thermique
     */
    private function generateThermalText(Order $order, Restaurant $restaurant): string
    {
        $currency = $restaurant->currency ?? 'FC';
        $line = str_repeat('─', 32);
        $doubleLine = str_repeat('═', 32);

        $text = "";

        // Header restaurant
        $text .= "\n";
        $text .= $this->centerText($restaurant->name) . "\n";
        if ($restaurant->receipt_header) {
            $text .= $this->centerText($restaurant->receipt_header) . "\n";
        }
        if ($restaurant->address) {
            $text .= $this->centerText($restaurant->address) . "\n";
        }
        if ($restaurant->phone) {
            $text .= $this->centerText('Tel: ' . $restaurant->phone) . "\n";
        }
        $text .= $doubleLine . "\n";

        // Infos commande
        $text .= "N°: {$order->order_number}\n";
        $text .= "Date: " . $order->created_at->format('d/m/Y H:i') . "\n";
        $text .= "Caissier: " . ($order->user?->name ?? '—') . "\n";
        if ($order->table) {
            $text .= "Table: " . $order->table->name . "\n";
        } else {
            $text .= "Type: A emporter\n";
        }
        if ($order->customer_name) {
            $text .= "Client: " . $order->customer_name . "\n";
        }
        $text .= $line . "\n";

        // Items
        $text .= sprintf("%-18s %3s %8s\n", "Article", "Qt", "Total");
        $text .= $line . "\n";

        foreach ($order->items as $item) {
            $name = mb_strlen($item->product_name) > 16
                ? mb_substr($item->product_name, 0, 15) . '.'
                : $item->product_name;
            $text .= sprintf("%-18s %3d %8s\n",
                $name,
                $item->quantity,
                number_format($item->subtotal, 0, ',', ' ')
            );
        }

        $text .= $line . "\n";

        // Totaux
        if ($order->discount_amount > 0) {
            $text .= sprintf("%-22s -%7s\n", "Remise", number_format($order->discount_amount, 0, ',', ' '));
        }
        if ($order->tax_amount > 0) {
            $text .= sprintf("%-22s %8s\n", "Taxe", number_format($order->tax_amount, 0, ',', ' '));
        }
        $text .= $doubleLine . "\n";
        $text .= sprintf("%-22s %8s %s\n", "TOTAL", number_format($order->total_amount, 0, ',', ' '), $currency);
        $text .= $doubleLine . "\n";

        // Paiement
        $text .= "Paiement: " . ($order->payment_method_label ?? $order->payment_method) . "\n";
        if ($order->cash_received) {
            $text .= sprintf("%-22s %8s %s\n", "Recu", number_format($order->cash_received, 0, ',', ' '), $currency);
            $text .= sprintf("%-22s %8s %s\n", "Monnaie", number_format($order->change_given ?? 0, 0, ',', ' '), $currency);
        }
        if ($order->payment_reference) {
            $text .= "Ref: " . $order->payment_reference . "\n";
        }

        $text .= "\n";
        if ($restaurant->receipt_footer) {
            $text .= $this->centerText($restaurant->receipt_footer) . "\n";
        } else {
            $text .= $this->centerText("Merci de votre visite !") . "\n";
        }
        $text .= "\n\n\n"; // Espace pour découpe

        return $text;
    }

    private function centerText(string $text, int $width = 32): string
    {
        $len = mb_strlen($text);
        if ($len >= $width) return mb_substr($text, 0, $width);
        $pad = (int) (($width - $len) / 2);
        return str_repeat(' ', $pad) . $text;
    }
}
