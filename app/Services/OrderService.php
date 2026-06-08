<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\AuditLog;
use App\Models\User;
use App\Models\RestaurantTable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderService
{
    /**
     * Créer une nouvelle commande avec routage intelligent Bar vs Cuisine.
     *
     * Logique:
     * 1. La commande est créée en statut 'pending'
     * 2. Les items sont analysés selon leur kitchen_route:
     *    - 'kitchen' → envoyés au KDS (kitchen_status: en_attente)
     *    - 'bar' → marqués comme livrés immédiatement (pas au KDS)
     *    - 'counter' → marqués comme livrés immédiatement (pas au KDS)
     * 3. La table passe en statut 'kitchen_processing' si items cuisine, 'occupied' sinon
     * 4. Transaction DB atomique — tout ou rien
     */
    public function createOrder(array $data, User $cashier): Order
    {
        return DB::transaction(function () use ($data, $cashier) {
            $restaurantId = $cashier->restaurant_id;

            // Calcul du total
            $total = collect($data['items'])->sum(
                fn($item) => $item['quantity'] * $item['price']
            );
            $discountAmount = $data['discount_amount'] ?? 0;
            $taxAmount = $data['tax_amount'] ?? 0;
            $netTotal = $total + $taxAmount - $discountAmount;

            // Déterminer s'il y a des items cuisine
            $hasKitchenItems = false;
            $hasBarItems = false;
            $hasCounterItems = false;

            foreach ($data['items'] as $item) {
                $product = Product::findOrFail($item['product_id']);
                $route = $product->kitchen_route ?? 'kitchen';
                if ($route === 'kitchen') $hasKitchenItems = true;
                if ($route === 'bar') $hasBarItems = true;
                if ($route === 'counter') $hasCounterItems = true;
            }

            // Statut initial: pending (brouillon) — sera mis à jour après
            $initialStatus = Order::STATUS_PENDING;

            // Créer la commande
            $order = Order::create([
                'restaurant_id'      => $restaurantId,
                'pos_terminal_id'    => $data['pos_terminal_id'] ?? $cashier->pos_terminal_id,
                'user_id'            => $cashier->id,
                'table_id'           => $data['table_id'] ?? null,
                'order_number'       => $this->generateOrderNumber($restaurantId),
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
                'status'             => $initialStatus,
                'kitchen_status'     => $hasKitchenItems ? Order::KITCHEN_EN_ATTENTE : null,
                'sent_to_kitchen_at' => $hasKitchenItems ? now() : null,
            ]);

            // Créer les items avec routage
            foreach ($data['items'] as $item) {
                $product = Product::findOrFail($item['product_id']);
                $kitchenRoute = $product->kitchen_route ?? 'kitchen';

                // Déterminer le statut initial de l'item
                $itemKitchenStatus = match ($kitchenRoute) {
                    'kitchen' => 'en_attente',
                    'bar'     => 'delivered',  // Livré immédiatement
                    'counter' => 'delivered',  // Livré immédiatement
                    default   => 'en_attente',
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

            // Mettre à jour le statut de la commande selon le routage
            if ($hasKitchenItems) {
                $order->update(['status' => Order::STATUS_SENT_TO_KITCHEN]);
            } else {
                // Pas d'items cuisine → commande prête immédiatement
                $order->update([
                    'status'       => Order::STATUS_DELIVERED,
                    'delivered_at' => now(),
                ]);
            }

            // Mettre à jour la table
            if ($order->table_id) {
                $tableStatus = $hasKitchenItems
                    ? RestaurantTable::STATUS_KITCHEN_PROCESSING
                    : RestaurantTable::STATUS_OCCUPIED;

                RestaurantTable::where('id', $order->table_id)
                    ->update([
                        'status'           => $tableStatus,
                        'current_order_id' => $order->id,
                    ]);
            }

            // Audit log
            AuditLog::create([
                'user_id'     => $cashier->id,
                'action'      => 'create_order',
                'entity_type' => 'order',
                'entity_id'   => $order->id,
                'new_values'  => ['order_number' => $order->order_number, 'total' => $netTotal, 'items_count' => count($data['items'])],
                'ip_address'  => request()->ip(),
            ]);

            return $order->load('items');
        });
    }

    /**
     * Marquer une commande comme "prête" (KDS → caisse notifiée)
     */
    public function markAsReady(Order $order): Order
    {
        if (!in_array($order->status, [Order::STATUS_SENT_TO_KITCHEN, Order::STATUS_PENDING])) {
            throw new \Exception('Seules les commandes en cuisine peuvent être marquées comme prêtes.');
        }

        DB::transaction(function () use ($order) {
            $oldStatus = $order->status;

            $order->update([
                'status'             => Order::STATUS_READY,
                'kitchen_status'     => Order::KITCHEN_PRET,
                'ready_at'           => now(),
            ]);

            // Mettre à jour la table en "servi non payé"
            if ($order->table_id) {
                RestaurantTable::where('id', $order->table_id)
                    ->update(['status' => RestaurantTable::STATUS_SERVED_UNPAID]);
            }

            AuditLog::create([
                'user_id'     => auth()->id(),
                'action'      => 'order_ready',
                'entity_type' => 'order',
                'entity_id'   => $order->id,
                'old_values'  => ['status' => $oldStatus],
                'new_values'  => ['status' => Order::STATUS_READY],
                'ip_address'  => request()->ip(),
            ]);
        });

        return $order->fresh();
    }

    /**
     * Marquer une commande comme "servie" (livrée au client)
     */
    public function markAsDelivered(Order $order): Order
    {
        if ($order->status !== Order::STATUS_READY) {
            throw new \Exception('Seules les commandes prêtes peuvent être marquées comme servies.');
        }

        DB::transaction(function () use ($order) {
            $oldStatus = $order->status;

            $order->update([
                'status'       => Order::STATUS_DELIVERED,
                'delivered_at' => now(),
            ]);

            // Mettre à jour la table
            if ($order->table_id) {
                RestaurantTable::where('id', $order->table_id)
                    ->update(['status' => RestaurantTable::STATUS_SERVED_UNPAID]);
            }

            AuditLog::create([
                'user_id'     => auth()->id(),
                'action'      => 'order_delivered',
                'entity_type' => 'order',
                'entity_id'   => $order->id,
                'old_values'  => ['status' => $oldStatus],
                'new_values'  => ['status' => Order::STATUS_DELIVERED],
                'ip_address'  => request()->ip(),
            ]);
        });

        return $order->fresh();
    }

    /**
     * Payer une commande (delivered → paid)
     * Déduit automatiquement les stocks
     */
    public function payOrder(Order $order, array $paymentData): Order
    {
        if (!$order->canBePaid()) {
            throw new \Exception('Cette commande ne peut pas être payée. Statut actuel: ' . $order->status);
        }

        $netTotal = $order->total_amount;
        $cashReceived = $paymentData['cash_received'] ?? null;
        $changeGiven = $cashReceived ? $cashReceived - $netTotal : null;

        DB::transaction(function () use ($order, $paymentData, $cashReceived, $changeGiven) {
            $oldStatus = $order->status;

            $order->update([
                'status'            => Order::STATUS_PAID,
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
                    ->update([
                        'status'           => RestaurantTable::STATUS_AVAILABLE,
                        'current_order_id' => null,
                    ]);
            }

            AuditLog::create([
                'user_id'     => auth()->id(),
                'action'      => 'pay_order',
                'entity_type' => 'order',
                'entity_id'   => $order->id,
                'old_values'  => ['status' => $oldStatus],
                'new_values'  => ['status' => Order::STATUS_PAID],
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
            throw new \Exception('Cette commande ne peut pas être annulée. Statut: ' . $order->status);
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

            // Libérer la table
            if ($order->table_id) {
                RestaurantTable::where('id', $order->table_id)
                    ->update([
                        'status'           => RestaurantTable::STATUS_AVAILABLE,
                        'current_order_id' => null,
                    ]);
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
            ->whereIn('status', [
                Order::STATUS_PENDING,
                Order::STATUS_SENT_TO_KITCHEN,
                Order::STATUS_READY,
                Order::STATUS_DELIVERED,
            ])
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
            ->whereIn('status', [
                Order::STATUS_PENDING,
                Order::STATUS_SENT_TO_KITCHEN,
                Order::STATUS_READY,
                Order::STATUS_DELIVERED,
            ])
            ->orderBy('created_at')
            ->get();
    }

    /**
     * Récupérer les tables avec leurs commandes actives (pour plan de salle)
     */
    public function getTablesWithStatus(int $restaurantId): \Illuminate\Database\Eloquent\Collection
    {
        return RestaurantTable::with(['currentOrder.items'])
            ->where('restaurant_id', $restaurantId)
            ->where('is_active', true)
            ->orderBy('zone')
            ->orderBy('name')
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
