<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\AuditLog;
use App\Models\User;
use App\Models\RestaurantTable;
use Illuminate\Support\Facades\DB;

class OrderService
{
    /**
     * Créer une nouvelle commande (statut initial : en_cours)
     */
    public function createOrder(array $data, User $cashier): Order
    {
        return DB::transaction(function () use ($data, $cashier) {
            $orderNumber = $this->generateOrderNumber($cashier->restaurant_id);

            $total = collect($data['items'])->sum(
                fn($item) => $item['quantity'] * $item['price']
            );

            $discountAmount = $data['discount_amount'] ?? 0;
            $taxAmount = $data['tax_amount'] ?? 0;
            $netTotal = $total + $taxAmount - $discountAmount;

            $order = Order::create([
                'restaurant_id'      => $cashier->restaurant_id,
                'pos_terminal_id'    => $data['pos_terminal_id'] ?? $cashier->pos_terminal_id,
                'user_id'            => $cashier->id,
                'table_id'           => $data['table_id'] ?? null,
                'order_number'       => $orderNumber,
                'total_amount'       => $netTotal,
                'tax_amount'         => $taxAmount,
                'discount_amount'    => $discountAmount,
                'payment_method'     => $data['payment_method'] ?? 'cash',
                'payment_reference'  => $data['payment_reference'] ?? null,
                'cash_received'      => $data['cash_received'] ?? null,
                'change_given'       => isset($data['cash_received'])
                    ? $data['cash_received'] - $netTotal : null,
                'customer_name'      => $data['customer_name'] ?? null,
                'customer_phone'     => $data['customer_phone'] ?? null,
                'notes'              => $data['notes'] ?? null,
                'status'             => Order::STATUS_EN_COURS,
                'kitchen_status'     => Order::KITCHEN_EN_ATTENTE,
                'sent_to_kitchen_at' => now(),
            ]);

            foreach ($data['items'] as $item) {
                $product = Product::findOrFail($item['product_id']);
                $kitchenRoute = $product->kitchen_route ?? 'kitchen';
                $itemKitchenStatus = match ($kitchenRoute) {
                    'kitchen' => 'en_attente',
                    'bar' => 'bar',
                    'counter' => 'comptoir',
                    default => 'en_attente',
                };
                OrderItem::create([
                    'order_id'      => $order->id,
                    'product_id'    => $item['product_id'],
                    'product_name'  => $product->name,
                    'quantity'      => $item['quantity'],
                    'price_at_sale' => $product->price,
                    'subtotal'      => $item['quantity'] * $product->price,
                    'notes'         => $item['notes'] ?? null,
                    'kitchen_status'=> $itemKitchenStatus,
                    'kitchen_route' => $kitchenRoute,
                ]);
            }

            // Marquer la table comme occupée
            if ($order->table_id) {
                RestaurantTable::where('id', $order->table_id)
                    ->update(['status' => RestaurantTable::STATUS_OCCUPEE]);
            }

            AuditLog::create([
                'user_id'     => $cashier->id,
                'action'      => 'create_order',
                'entity_type' => 'order',
                'entity_id'   => $order->id,
                'new_values'  => json_decode($order->load('items')->toJson()),
                'ip_address'  => request()->ip(),
            ]);

            return $order->load('items');
        });
    }

    /**
     * Marquer une commande comme "en attente" (prête mais pas encore payée)
     */
    public function markAsReady(Order $order): Order
    {
        if (!$order->isEnCours()) {
            throw new \Exception('Seules les commandes en cours peuvent être marquées comme prêtes.');
        }

        $order->update(['status' => Order::STATUS_EN_ATTENTE]);

        AuditLog::create([
            'user_id'     => auth()->id(),
            'action'      => 'order_ready',
            'entity_type' => 'order',
            'entity_id'   => $order->id,
            'old_values'  => ['status' => Order::STATUS_EN_COURS],
            'new_values'  => ['status' => Order::STATUS_EN_ATTENTE],
            'ip_address'  => request()->ip(),
        ]);

        return $order->fresh();
    }

    /**
     * Payer une commande (en_cours ou en_attente → payee)
     * Déduit automatiquement les stocks
     */
    public function payOrder(Order $order, array $paymentData): Order
    {
        if (!$order->canBePaid()) {
            throw new \Exception('Cette commande ne peut pas être payée.');
        }

        $netTotal = $order->total_amount;
        $cashReceived = $paymentData['cash_received'] ?? null;
        $changeGiven = $cashReceived ? $cashReceived - $netTotal : null;

        DB::transaction(function () use ($order, $paymentData, $cashReceived, $changeGiven) {
            $oldStatus = $order->status;

            $order->update([
                'status'            => Order::STATUS_PAYEE,
                'payment_method'    => $paymentData['payment_method'],
                'payment_reference' => $paymentData['payment_reference'] ?? null,
                'cash_received'     => $cashReceived,
                'change_given'      => $changeGiven,
                'customer_name'     => $paymentData['customer_name'] ?? null,
                'customer_phone'    => $paymentData['customer_phone'] ?? null,
            ]);

            // Déduire les stocks des produits
            foreach ($order->items as $item) {
                $product = Product::find($item->product_id);
                if ($product && $product->track_inventory) {
                    $product->deductStock($item->quantity, $order->id, auth()->id());
                }
            }

            // Libérer la table
            if ($order->table_id) {
                RestaurantTable::where('id', $order->table_id)
                    ->update(['status' => RestaurantTable::STATUS_LIBRE]);
            }

            AuditLog::create([
                'user_id'     => auth()->id(),
                'action'      => 'pay_order',
                'entity_type' => 'order',
                'entity_id'   => $order->id,
                'old_values'  => ['status' => $oldStatus],
                'new_values'  => ['status' => Order::STATUS_PAYEE],
                'ip_address'  => request()->ip(),
            ]);
        });

        return $order->fresh();
    }

    /**
     * Annuler une commande avec motif obligatoire
     * Si rupture de stock → passe le produit en is_available=false
     */
    public function cancelOrder(Order $order, User $user, string $reason, bool $ruptureStock = false): Order
    {
        if (!$order->canBeCancelled()) {
            throw new \Exception('Cette commande ne peut pas être annulée.');
        }

        DB::transaction(function () use ($order, $user, $reason, $ruptureStock) {
            $oldStatus = $order->status;

            $order->update([
                'status'             => Order::STATUS_ANNULEE,
                'cancelled_by'       => $user->id,
                'cancellation_reason' => $reason,
            ]);

            // Si rupture de stock, désactiver les produits concernés
            if ($ruptureStock) {
                foreach ($order->items as $item) {
                    $product = Product::find($item->product_id);
                    if ($product) {
                        $product->update(['is_available' => false]);
                    }
                }
            }

            // Libérer la table si la commande est annulée
            if ($order->table_id) {
                RestaurantTable::where('id', $order->table_id)
                    ->update(['status' => RestaurantTable::STATUS_LIBRE]);
            }

            AuditLog::create([
                'user_id'     => $user->id,
                'action'      => 'cancel_order',
                'entity_type' => 'order',
                'entity_id'   => $order->id,
                'old_values'  => ['status' => $oldStatus],
                'new_values'  => [
                    'status' => Order::STATUS_ANNULEE,
                    'reason' => $reason,
                    'rupture_stock' => $ruptureStock,
                ],
                'ip_address'  => request()->ip(),
            ]);
        });

        return $order->fresh();
    }

    /**
     * Vérifier s'il y a des commandes non soldées
     */
    public function hasUnsettledOrders(int $restaurantId): bool
    {
        return Order::where('restaurant_id', $restaurantId)
            ->whereDate('created_at', today())
            ->whereIn('status', [Order::STATUS_EN_COURS, Order::STATUS_EN_ATTENTE])
            ->exists();
    }

    /**
     * Récupérer les commandes non soldées
     */
    public function getUnsettledOrders(int $restaurantId)
    {
        return Order::with(['user', 'items'])
            ->where('restaurant_id', $restaurantId)
            ->whereDate('created_at', today())
            ->whereIn('status', [Order::STATUS_EN_COURS, Order::STATUS_EN_ATTENTE])
            ->orderBy('created_at')
            ->get();
    }

    private function generateOrderNumber(int $restaurantId): string
    {
        $date = now()->format('Ymd');
        $prefix = "R{$restaurantId}-{$date}-";

        $lastOrder = Order::where('restaurant_id', $restaurantId)
            ->whereDate('created_at', today())
            ->latest('id')
            ->first();

        $seq = $lastOrder
            ? (int) substr($lastOrder->order_number, -5) + 1
            : 1;

        return $prefix . str_pad($seq, 5, '0', STR_PAD_LEFT);
    }
}
