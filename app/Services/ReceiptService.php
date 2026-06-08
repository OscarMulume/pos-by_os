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
        return $this->buildReceiptHtml($order, false);
    }

    /**
     * Générer un reçu PROFORMA (non payé) — Note provisoire
     * Avec filigrane "PROFORMA - NON PAYÉ"
     */
    public function generateProformaReceipt(Order $order): string
    {
        return $this->buildReceiptHtml($order, true);
    }

    /**
     * Générer un reçu thermique (format 80mm) avec logo en base64
     */
    public function generateThermalReceipt(Order $order): string
    {
        $r = $order->restaurant;
        $currency = $r->currency ?? 'FC';
        $restaurantName = $r->receipt_header ?? $r->name;
        $footerText = $r->receipt_footer ?? 'Merci de votre visite!';

        // Logo en base64 pour compatibilité imprimante thermique
        $logoBase64 = $this->getLogoBase64($r);

        $itemsHtml = '';
        foreach ($order->items as $item) {
            $subtotal = number_format($item->subtotal, 0, ',', '.');
            $itemsHtml .= "<tr>
                <td>{$item->product_name}</td>
                <td style='text-align:center'>{$item->quantity}</td>
                <td style='text-align:right'>{$subtotal}</td>
            </tr>";
        }

        $total = number_format($order->total_amount, 0, ',', '.');
        $date = $order->created_at->format('d/m/Y H:i');
        $table = $order->table?->name ?? 'À emporter';

        return "<!DOCTYPE html>
<html><head><meta charset='utf-8'><title>Ticket {$order->order_number}</title>
<style>
    @page { size: 80mm auto; margin: 0; }
    body { font-family: 'Courier New', monospace; width: 72mm; margin: 0 auto; padding: 3mm; font-size: 11px; }
    .header { text-align: center; border-bottom: 1px dashed #000; padding-bottom: 5px; margin-bottom: 5px; }
    .header img { max-height: 40px; filter: grayscale(100%); }
    .header h2 { margin: 3px 0; font-size: 14px; }
    .header p { margin: 1px 0; font-size: 10px; }
    .info { font-size: 10px; margin: 5px 0; }
    table { width: 100%; border-collapse: collapse; font-size: 10px; }
    td { padding: 2px 0; }
    .total-row td { border-top: 1px solid #000; font-weight: bold; padding-top: 3px; font-size: 12px; }
    .footer { text-align: center; border-top: 1px dashed #000; padding-top: 5px; margin-top: 5px; font-size: 10px; }
    @media print { body { width: 80mm; } .no-print { display: none; } }
</style></head><body>
    <div class='header'>
        {$logoBase64}
        <h2>{$restaurantName}</h2>
        <p>{$r->address}</p>
        <p>Tel: {$r->phone}</p>
    </div>
    <div class='info'>
        <p><strong>Ticket:</strong> {$order->order_number}</p>
        <p><strong>Table:</strong> {$table}</p>
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
    <div class='footer'>
        <p><strong>{$footerText}</strong></p>
    </div>
    <button class='no-print' onclick='window.print()' style='margin-top:10px;padding:8px 16px;background:#333;color:#fff;border:none;cursor:pointer;border-radius:4px;font-size:12px;'>
        🖨️ Imprimer
    </button>
</body></html>";
    }

    /**
     * Convertir le logo en base64 pour impression thermique
     */
    private function getLogoBase64($restaurant): string
    {
        if (!$restaurant->logo_path) return '';

        $logoPath = storage_path('app/public/' . $restaurant->logo_path);
        if (!file_exists($logoPath)) return '';

        $mime = mime_content_type($logoPath) ?: 'image/png';
        $data = base64_encode(file_get_contents($logoPath));

        return "<img src='data:{$mime};base64,{$data}' alt='Logo' style='max-height:40px;filter:grayscale(100%);'>";
    }

    /**
     * Construction HTML commune des reçus (normal + proforma)
     */
    private function buildReceiptHtml(Order $order, bool $isProforma): string
    {
        $r = $order->restaurant;
        $currency = $r->currency ?? 'FC';
        $items = $order->items;
        $date = $order->created_at->format('d/m/Y H:i:s');
        $total = number_format($order->total_amount, 0, ',', '.');
        $restaurantName = $r->receipt_header ?? $r->name;
        $footerText = $r->receipt_footer ?? 'Merci de votre visite!';
        $table = $order->table?->name ?? 'À emporter';

        // Logo en base64
        $logoBase64 = $this->getLogoBase64($r);
        $logoHtml = $logoBase64 ? "<div style='text-align:center;margin-bottom:8px;'>{$logoBase64}</div>" : '';

        // Filigrane PROFORMA
        $proformaWatermark = '';
        $proformaBanner = '';
        if ($isProforma) {
            $proformaWatermark = "
                <div style='position:fixed;top:50%;left:50%;transform:translate(-50%,-50%) rotate(-45deg);font-size:48px;color:rgba(200,0,0,0.15);pointer-events:none;z-index:9999;white-space:nowrap;'>
                    PROFORMA — NON PAYÉ
                </div>";
            $proformaBanner = "
                <div style='background:#f59e0b;color:#000;text-align:center;padding:8px;font-weight:bold;font-size:14px;margin-bottom:10px;border-radius:4px;'>
                    ⚠️ NOTE PROVISOIRE — NON PAYÉ
                </div>";
        }

        $itemsHtml = '';
        foreach ($items as $item) {
            $subtotal = number_format($item->subtotal, 0, ',', '.');
            $route = $item->kitchen_route ?? 'kitchen';
            $routeBadge = match ($route) {
                'bar' => "<span style='font-size:9px;background:#7c3aed;color:#fff;padding:1px 4px;border-radius:3px;'>BAR</span>",
                'counter' => "<span style='font-size:9px;background:#06b6d4;color:#fff;padding:1px 4px;border-radius:3px;'>COMPTOIR</span>",
                default => '',
            };
            $itemsHtml .= "<tr>
                <td>{$item->product_name} {$routeBadge}</td>
                <td style='text-align:center'>{$item->quantity}</td>
                <td style='text-align:right'>{$subtotal} {$currency}</td>
            </tr>";
        }

        $paymentLabel = match ($order->payment_method) {
            'cash' => 'Espèces',
            'mobile_money' => 'Mobile Money',
            'credit' => 'Crédit',
            default => $order->payment_method,
        };

        $cashHtml = '';
        if ($order->payment_method === 'cash' && $order->cash_received) {
            $received = number_format($order->cash_received, 0, ',', '.');
            $change = number_format($order->change_given, 0, ',', '.');
            $cashHtml = "<p><strong>Reçu:</strong> {$received} {$currency}</p>
                         <p><strong>Monnaie:</strong> {$change} {$currency}</p>";
        }

        $refHtml = '';
        if ($order->payment_reference) {
            $refHtml = "<p><strong>Ref:</strong> {$order->payment_reference}</p>";
        }

        return "<!DOCTYPE html>
<html><head><meta charset='utf-8'><title>Ticket {$order->order_number}</title>
<style>
    body { font-family: 'Courier New', monospace; max-width: 300px; margin: 0 auto; padding: 10px; position: relative; }
    .header { text-align: center; border-bottom: 2px dashed #333; padding-bottom: 10px; margin-bottom: 10px; }
    .header h2 { margin: 0; font-size: 16px; }
    .header p { margin: 2px 0; font-size: 12px; }
    .info { font-size: 12px; margin-bottom: 10px; }
    table { width: 100%; border-collapse: collapse; font-size: 12px; margin: 10px 0; }
    td { padding: 3px 0; }
    .total-row td { border-top: 2px solid #333; font-weight: bold; padding-top: 5px; }
    .footer { text-align: center; border-top: 2px dashed #333; padding-top: 10px; margin-top: 10px; font-size: 12px; }
    @media print { body { max-width: 80mm; } .no-print { display: none; } }
</style></head><body>
    {$proformaWatermark}
    <div class='header'>
        {$logoHtml}
        <h2>{$restaurantName}</h2>
        <p>{$r->address}</p>
        <p>Tel: {$r->phone}</p>
    </div>
    {$proformaBanner}
    <div class='info'>
        <p><strong>Ticket:</strong> {$order->order_number}</p>
        <p><strong>Table:</strong> {$table}</p>
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
        {$refHtml}
    </div>
    <div class='footer'>
        <p><strong>{$footerText}</strong></p>
        <p>{$r->name} — M-SEC Technology Consulting</p>
    </div>
    <div class='no-print' style='text-align:center;margin-top:15px;'>
        <button onclick='window.print()' style='padding:10px 20px;background:#333;color:#fff;border:none;cursor:pointer;border-radius:5px;margin-right:5px;'>
            🖨️ Imprimer
        </button>
    </div>
</body></html>";
    }
}
