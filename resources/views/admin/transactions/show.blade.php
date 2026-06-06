@extends('layouts.app')

@section('title', 'Commande #' . $order->order_number)

@section('content')
<div class="space-y-6" x-data="{ showCancelForm: false }">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="flex items-center gap-4">
            <a href="{{ route('admin.transactions.index', request()->query()) }}"
               class="inline-flex items-center p-2 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-lg transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
            </a>
            <div>
                <div class="flex items-center gap-3">
                    <h2 class="text-2xl font-bold text-gray-800">Commande #{{ $order->order_number }}</h2>
                    @php
                        $headerStatusColors = [
                            'paid' => 'bg-green-100 text-green-800 border-green-200',
                            'cancelled' => 'bg-red-100 text-red-800 border-red-200',
                            'pending' => 'bg-yellow-100 text-yellow-800 border-yellow-200',
                        ];
                        $headerClass = $headerStatusColors[$order->status] ?? 'bg-gray-100 text-gray-800 border-gray-200';
                    @endphp
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium border {{ $headerClass }}">
                        {{ $order->status_label }}
                    </span>
                </div>
                <p class="text-sm text-gray-500 mt-1">
 créée le {{ $order->created_at->format('d/m/Y à H:i') }}
                </p>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <button type="button" onclick="window.print()"
                    class="inline-flex items-center px-4 py-2 bg-gray-100 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-200 transition shadow-sm">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                </svg>
                Imprimer
            </button>
        </div>
    </div>

    <!-- Info cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <!-- Restaurant -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
            <div class="flex items-center gap-3 mb-3">
                <div class="p-2 bg-blue-100 rounded-lg">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                </div>
                <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider">Restaurant</h3>
            </div>
            <p class="text-lg font-semibold text-gray-900">{{ $order->restaurant->name ?? '-' }}</p>
            @if($order->restaurant && $order->restaurant->address)
                <p class="text-sm text-gray-500 mt-1">{{ $order->restaurant->address }}</p>
            @endif
        </div>

        <!-- Cashier -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
            <div class="flex items-center gap-3 mb-3">
                <div class="p-2 bg-purple-100 rounded-lg">
                    <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                </div>
                <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider">Caissier</h3>
            </div>
            <p class="text-lg font-semibold text-gray-900">{{ $order->user->name ?? '-' }}</p>
            @if($order->user && $order->user->email)
                <p class="text-sm text-gray-500 mt-1">{{ $order->user->email }}</p>
            @endif
        </div>

        <!-- Date & Time -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
            <div class="flex items-center gap-3 mb-3">
                <div class="p-2 bg-amber-100 rounded-lg">
                    <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                </div>
                <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider">Date & Heure</h3>
            </div>
            <p class="text-lg font-semibold text-gray-900">{{ $order->created_at->format('d/m/Y') }}</p>
            <p class="text-sm text-gray-500 mt-1">{{ $order->created_at->format('H:i') }}</p>
        </div>
    </div>

    <!-- Customer info (if available) -->
    @if($order->customer_name || $order->customer_phone)
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
        <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-3">Client</h3>
        <div class="flex flex-wrap gap-6 text-sm">
            @if($order->customer_name)
                <div>
                    <span class="text-gray-500">Nom:</span>
                    <span class="font-medium text-gray-900 ml-1">{{ $order->customer_name }}</span>
                </div>
            @endif
            @if($order->customer_phone)
                <div>
                    <span class="text-gray-500">Téléphone:</span>
                    <span class="font-medium text-gray-900 ml-1">{{ $order->customer_phone }}</span>
                </div>
            @endif
        </div>
    </div>
    @endif>

    <!-- Items table -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider">Articles commandés</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="bg-gray-50 text-xs uppercase text-gray-500 font-semibold">
                    <tr>
                        <th class="px-6 py-3">Produit</th>
                        <th class="px-6 py-3 text-center">Quantité</th>
                        <th class="px-6 py-3 text-right">Prix unitaire</th>
                        <th class="px-6 py-3 text-right">Sous-total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($order->items as $item)
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-6 py-3">
                            <div class="font-medium text-gray-900">{{ $item->product_name }}</div>
                            @if($item->notes)
                                <div class="text-xs text-gray-400 mt-0.5">{{ $item->notes }}</div>
                            @endif
                        </td>
                        <td class="px-6 py-3 text-center font-medium text-gray-700">
                            {{ $item->quantity }}
                        </td>
                        <td class="px-6 py-3 text-right text-gray-700">
                            {{ number_format($item->price_at_sale, 2) }} DH
                        </td>
                        <td class="px-6 py-3 text-right font-semibold text-gray-900">
                            {{ number_format($item->subtotal, 2) }} DH
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Payment info & Totals -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <!-- Payment info -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
            <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-4">Informations de paiement</h3>
            <div class="space-y-3">
                <div class="flex items-center justify-between text-sm">
                    <span class="text-gray-500">Méthode</span>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                        {{ $order->payment_method === 'cash' ? 'bg-green-100 text-green-800' : '' }}
                        {{ $order->payment_method === 'mobile_money' ? 'bg-purple-100 text-purple-800' : '' }}
                        {{ $order->payment_method === 'credit' ? 'bg-orange-100 text-orange-800' : '' }}
                        {{ !in_array($order->payment_method, ['cash', 'mobile_money', 'credit']) ? 'bg-gray-100 text-gray-800' : '' }}">
                        {{ $order->payment_method_label }}
                    </span>
                </div>
                @if($order->payment_reference)
                <div class="flex items-center justify-between text-sm">
                    <span class="text-gray-500">Référence</span>
                    <span class="font-mono text-gray-900">{{ $order->payment_reference }}</span>
                </div>
                @endif
                @if($order->cash_received !== null)
                <div class="flex items-center justify-between text-sm">
                    <span class="text-gray-500">Montant reçu</span>
                    <span class="font-medium text-gray-900">{{ number_format($order->cash_received, 2) }} DH</span>
                </div>
                @endif
                @if($order->change_given !== null)
                <div class="flex items-center justify-between text-sm">
                    <span class="text-gray-500">Monnaie rendue</span>
                    <span class="font-medium text-gray-900">{{ number_format($order->change_given, 2) }} DH</span>
                </div>
                @endif
            </div>
        </div>

        <!-- Totals -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
            <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-4">Détail du total</h3>
            <div class="space-y-2">
                @if($order->discount_amount > 0)
                <div class="flex items-center justify-between text-sm">
                    <span class="text-gray-500">Remise</span>
                    <span class="text-red-600 font-medium">-{{ number_format($order->discount_amount, 2) }} DH</span>
                </div>
                @endif
                @if($order->tax_amount > 0)
                <div class="flex items-center justify-between text-sm">
                    <span class="text-gray-500">Taxe</span>
                    <span class="text-gray-700">{{ number_format($order->tax_amount, 2) }} DH</span>
                </div>
                @endif
                <div class="border-t border-gray-200 pt-2 mt-2">
                    <div class="flex items-center justify-between">
                        <span class="text-base font-semibold text-gray-900">Total</span>
                        <span class="text-xl font-bold text-gray-900">{{ number_format($order->total_amount, 2) }} DH</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Notes -->
    @if($order->notes)
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
        <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-2">Notes</h3>
        <p class="text-sm text-gray-700">{{ $order->notes }}</p>
    </div>
    @endif

    <!-- Cancellation info -->
    @if($order->status === 'cancelled')
    <div class="bg-red-50 rounded-xl shadow-sm border border-red-200 p-4">
        <h3 class="text-sm font-semibold text-red-700 uppercase tracking-wider mb-2">Informations d'annulation</h3>
        <div class="space-y-2 text-sm">
            <div class="flex justify-between">
                <span class="text-red-600">Annulée par</span>
                <span class="font-medium text-red-800">{{ $order->cancelledByUser->name ?? 'Inconnu' }}</span>
            </div>
            @if($order->cancellation_reason)
            <div>
                <span class="text-red-600">Raison</span>
                <p class="mt-1 text-red-800">{{ $order->cancellation_reason }}</p>
            </div>
            @endif
        </div>
    </div>
    @endif

    <!-- Cancel action (only for paid orders) -->
    @if($order->status === 'paid')
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <button type="button" @click="showCancelForm = !showCancelForm"
                class="w-full flex items-center justify-between px-4 py-3 text-left hover:bg-gray-50 transition">
            <div class="flex items-center gap-2 text-red-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                </svg>
                <span class="text-sm font-semibold">Annuler cette commande</span>
            </div>
            <svg class="w-5 h-5 text-gray-400 transition-transform" :class="showCancelForm ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
            </svg>
        </button>

        <div x-show="showCancelForm" x-transition class="border-t border-gray-200 p-4">
            <form method="POST" action="{{ route('admin.transactions.cancel', $order) }}">
                @csrf
                <div class="mb-4">
                    <label for="reason" class="block text-sm font-medium text-gray-700 mb-1">Raison de l'annulation <span class="text-red-500">*</span></label>
                    <textarea name="reason" id="reason" rows="3" required minlength="5" maxlength="500"
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-red-500 focus:border-red-500 @error('reason') border-red-500 @enderror"
                              placeholder="Veuillez indiquer la raison de l'annulation (min. 5 caractères)...">{{ old('reason') }}</textarea>
                    @error('reason')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div class="flex items-center gap-3">
                    <button type="submit"
                            class="inline-flex items-center px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700 transition shadow-sm"
                            onclick="return confirm('Êtes-vous sûr de vouloir annuler cette commande ? Cette action est irréversible.')">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                        Confirmer l'annulation
                    </button>
                    <button type="button" @click="showCancelForm = false"
                            class="px-4 py-2 text-gray-700 text-sm font-medium hover:text-gray-900 transition">
                        Annuler
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif
</div>
@endsection
