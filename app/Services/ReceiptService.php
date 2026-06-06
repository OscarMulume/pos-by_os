<?php

namespace App\Services;

use App\Models\Order;

class ReceiptService
{
    public function generateReceiptText(Order $order): string
    {
        $r = $order->restaurant;
        $width = 32;
        $lines = [];

        // En-tête personnalisable
        $headerLine = $r->receipt_header ?? $r->name;
        $lines[] = str_pad($headerLine, $width, ' ', STR_PAD_BOTH);

        if ($r->address) {
            $lines[] = str_pad($r->address, $width, ' ', STR_PAD_BOTH);
        }
        if ($r->phone) {
            $lines[] = "Tel: {$r->phone}";
        }
        $lines[] = str_repeat('-', $width);
        $lines[] = "Ticket: {$order->order_number}";
        $lines[] = "Date: " . $order->created_at->format('d/m/Y H:i:s');
        $lines[] = "Caissier: " . $order->user->name;
        $lines[] = str_repeat('-', $width);

        foreach ($order->items as $item) {
            $name = substr($item->product_name, 0, 14);
            $qty = $item->quantity . 'x';
            $price = number_format($item->subtotal, 0, ',', '.');
            $lines[] = sprintf('%-16s %3s %8s', $name, $qty, $price);
        }

        $lines[] = str_repeat('-', $width);
        $total = number_format($order->total_amount, 0, ',', '.');
        $lines[] = sprintf('%20s %10s %s', 'TOTAL:', $total, $r->currency ?? 'FC');

        $paymentLabel = match ($order->payment_method) {
            'cash' => 'Especes',
            'mobile_money' => 'Mobile Money',
            'credit' => 'Credit',
        };
        $lines[] = sprintf('%20s %10s', 'Paiement:', $paymentLabel);

        if ($order->payment_method === 'cash' && $order->cash_received) {
            $received = number_format($order->cash_received, 0, ',', '.');
            $change = number_format($order->change_given, 0, ',', '.');
            $lines[] = sprintf('%20s %10s %s', 'Recu:', $received, $r->currency ?? 'FC');
            $lines[] = sprintf('%20s %10s %s', 'Monnaie:', $change, $r->currency ?? 'FC');
        }

        if ($order->payment_reference) {
            $lines[] = sprintf('%20s %10s', 'Ref:', $order->payment_reference);
        }

        $lines[] = str_repeat('-', $width);

        // Pied de reçu personnalisable
        $footerLine = $r->receipt_footer ?? 'Merci de votre visite!';
        $lines[] = str_pad($footerLine, $width, ' ', STR_PAD_BOTH);
        $lines[] = str_pad('A bientot!', $width, ' ', STR_PAD_BOTH);
        $lines[] = "\n\n\n";

        return implode("\n", $lines);
    }

    public function generateHtmlReceipt(Order $order): string
    {
        $r = $order->restaurant;
        $currency = $r->currency ?? 'FC';
        $items = $order->items;
        $date = $order->created_at->format('d/m/Y H:i:s');
        $total = number_format($order->total_amount, 0, ',', '.');
        $restaurantName = $r->receipt_header ?? $r->name;
        $footerText = $r->receipt_footer ?? 'Merci de votre visite!';

        // Logo du restaurant
        $logoHtml = '';
        if ($r->logo_path) {
            $logoUrl = asset('storage/' . $r->logo_path);
            $logoHtml = "<img src='{$logoUrl}' alt='Logo' style='max-height:60px;margin-bottom:8px;'>";
        }

        $itemsHtml = '';
        foreach ($items as $item) {
            $subtotal = number_format($item->subtotal, 0, ',', '.');
            $itemsHtml .= "<tr>
                <td>{$item->product_name}</td>
                <td style='text-align:center'>{$item->quantity}</td>
                <td style='text-align:right'>{$subtotal} {$currency}</td>
            </tr>";
        }

        $paymentLabel = match ($order->payment_method) {
            'cash' => 'Especes',
            'mobile_money' => 'Mobile Money',
            'credit' => 'Credit',
        };

        $cashHtml = '';
        if ($order->payment_method === 'cash' && $order->cash_received) {
            $received = number_format($order->cash_received, 0, ',', '.');
            $change = number_format($order->change_given, 0, ',', '.');
            $cashHtml = "<p><strong>Recu:</strong> {$received} {$currency}</p>
                         <p><strong>Monnaie:</strong> {$change} {$currency}</p>";
        }

        return "<!DOCTYPE html>
<html><head><meta charset='utf-8'><title>Ticket {$order->order_number}</title>
<style>
    body { font-family: 'Courier New', monospace; max-width: 300px; margin: 0 auto; padding: 10px; }
    .header { text-align: center; border-bottom: 2px dashed #333; padding-bottom: 10px; margin-bottom: 10px; }
    .header h2 { margin: 0; font-size: 16px; }
    .header p { margin: 2px 0; font-size: 12px; }
    .logo { max-height: 60px; margin-bottom: 8px; }
    .info { font-size: 12px; margin-bottom: 10px; }
    table { width: 100%; border-collapse: collapse; font-size: 12px; margin: 10px 0; }
    td { padding: 3px 0; }
    .total-row td { border-top: 2px solid #333; font-weight: bold; padding-top: 5px; }
    .footer { text-align: center; border-top: 2px dashed #333; padding-top: 10px; margin-top: 10px; font-size: 12px; }
    @media print { body { max-width: 80mm; } .no-print { display: none; } }
</style></head><body>
    <div class='header'>
        {$logoHtml}
        <h2>{$restaurantName}</h2>
        <p>{$r->address}</p>
        <p>Tel: {$r->phone}</p>
    </div>
    <div class='info'>
        <p><strong>Ticket:</strong> {$order->order_number}</p>
        <p><strong>Date:</strong> {$date}</p>
        <p><strong>Caissier:</strong> {$order->user->name}</p>
    </div>
    <table>
        {$itemsHtml}
        <tr class='total-row'>
            <td colspan='2'>TOTAL</td>
            <td style='text-align:right'>{$total} {$currency}</td>
        </tr>
    </table>
    <div class='info'>
        <p><strong>Paiement:</strong> {$paymentLabel}</p>
        {$cashHtml}
    </div>
    <div class='footer'>
        <p><strong>{$footerText}</strong></p>
        <p>A bientot!</p>
    </div>
    <button class='no-print' onclick='window.print()' style='margin-top:15px;padding:10px 20px;background:#333;color:#fff;border:none;cursor:pointer;border-radius:5px;'>
        Imprimer
    </button>
</body></html>";
    }
}
