<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Restaurant;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    /**
     * Envoyer un reçu via WhatsApp Business API
     *
     * Nécessite :
     * - WHATSAPP_BUSINESS_ID dans .env
     * - WHATSAPP_ACCESS_TOKEN dans .env
     * - WHATSAPP_PHONE_NUMBER_ID dans .env
     */
    public function sendReceipt(Order $order, string $phoneNumber): array
    {
        $restaurant = $order->restaurant;
        $currency = $restaurant->currency ?? 'FC';

        // Formater le numéro (supprimer les espaces, ajouter le code pays)
        $phone = $this->formatPhoneNumber($phoneNumber);

        // Construire le message
        $message = $this->buildReceiptMessage($order, $restaurant, $currency);

        // Envoyer via l'API WhatsApp Business
        try {
            $response = Http::withToken(config('services.whatsapp.access_token'))
                ->post("https://graph.facebook.com/v18.0/" . config('services.whatsapp.phone_number_id') . '/messages', [
                    'messaging_product' => 'whatsapp',
                    'recipient_type' => 'individual',
                    'to' => $phone,
                    'type' => 'text',
                    'text' => [
                        'preview_url' => false,
                        'body' => $message,
                    ],
                ]);

            if ($response->successful()) {
                Log::info('WhatsApp receipt sent', [
                    'order_id' => $order->id,
                    'phone' => $phone,
                ]);
                return ['success' => true, 'message' => 'Reçu envoyé sur WhatsApp.'];
            }

            Log::error('WhatsApp API error', [
                'order_id' => $order->id,
                'response' => $response->json(),
            ]);
            return ['success' => false, 'message' => 'Erreur API WhatsApp: ' . ($response->json()['error']['message'] ?? 'Inconnue')];

        } catch (\Exception $e) {
            Log::error('WhatsApp send exception', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
            return ['success' => false, 'message' => 'Erreur de connexion WhatsApp.'];
        }
    }

    /**
     * Construire le message texte du reçu
     */
    private function buildReceiptMessage(Order $order, Restaurant $restaurant, string $currency): string
    {
        $line = str_repeat('─', 25);
        $msg = "";

        $msg .= "*" . $restaurant->name . "*\n";
        $msg .= $line . "\n";
        $msg .= "📋 N°: *" . $order->order_number . "*\n";
        $msg .= "📅 " . $order->created_at->format('d/m/Y H:i') . "\n";
        $msg .= "👤 Caissier: " . ($order->user?->name ?? '—') . "\n";
        if ($order->table) {
            $msg .= "🪑 Table: " . $order->table->name . "\n";
        } else {
            $msg .= "🛍️ À emporter\n";
        }
        $msg .= $line . "\n";

        foreach ($order->items as $item) {
            $msg .= $item->product_name . " x" . $item->quantity . " → " . number_format($item->subtotal, 0, ',', ' ') . " " . $currency . "\n";
        }

        $msg .= $line . "\n";
        if ($order->discount_amount > 0) {
            $msg .= "Remise: -" . number_format($order->discount_amount, 0, ',', ' ') . " " . $currency . "\n";
        }
        $msg .= "💰 *TOTAL: " . number_format($order->total_amount, 0, ',', ' ') . " " . $currency . "*\n";
        $msg .= "💳 Paiement: " . ($order->payment_method_label ?? $order->payment_method) . "\n";
        $msg .= $line . "\n";

        if ($restaurant->receipt_footer) {
            $msg .= "_" . $restaurant->receipt_footer . "_\n";
        } else {
            $msg .= "_Merci de votre visite !_\n";
        }

        return $msg;
    }

    /**
     * Formater le numéro de téléphone
     */
    private function formatPhoneNumber(string $phone): string
    {
        // Supprimer tous les caractères non numériques sauf le +
        $phone = preg_replace('/[^\d+]/', '', $phone);

        // Si le numéro commence par 0, remplacer par le code pays (RDC: +243)
        if (str_starts_with($phone, '0')) {
            $phone = '+243' . substr($phone, 1);
        }

        // Si pas de code pays, ajouter +243
        if (!str_starts_with($phone, '+')) {
            $phone = '+243' . $phone;
        }

        return $phone;
    }
}
