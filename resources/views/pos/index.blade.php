@extends('layouts.pos')

@section('content')
<div x-data="posWorkflow()" x-init="init()">

<!-- ═══════════════════════════════════════════════════════
     MODALE BLOQUANTE DE SÉLECTION DE TABLE
     ═══════════════════════════════════════════════════════ -->
<div x-show="!tableSelected"
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     class="fixed inset-0 z-50 bg-slate-900/95 backdrop-blur-md flex items-center justify-center p-4"
     style="display: none;">
    <div class="text-center max-w-2xl w-full">
        @if($restaurant->logo_path)
            <img src="{{ asset('storage/' . $restaurant->logo_path) }}" alt="Logo" class="w-16 h-16 mx-auto mb-4 rounded-xl object-cover">
        @endif
        <h2 class="text-2xl font-bold text-white mb-1">Sélectionner une table</h2>
        <p class="text-gray-400 mb-2">{{ $restaurant->name ?? 'Restaurant' }}</p>
        <p class="text-gray-500 text-sm mb-8">Choisissez la table à servir ou "À emporter"</p>

        <!-- Legende couleurs -->
        <div class="flex justify-center gap-4 mb-6 text-xs flex-wrap">
            <span class="flex items-center gap-1.5 text-gray-400"><span class="w-3 h-3 rounded-full bg-green-500"></span> Libre</span>
            <span class="flex items-center gap-1.5 text-gray-400"><span class="w-3 h-3 rounded-full bg-yellow-500"></span> En cuisine</span>
            <span class="flex items-center gap-1.5 text-gray-400"><span class="w-3 h-3 rounded-full bg-blue-500"></span> À encaisser</span>
            <span class="flex items-center gap-1.5 text-gray-400"><span class="w-3 h-3 rounded-full bg-red-500"></span> Occupée</span>
            <span class="flex items-center gap-1.5 text-gray-400"><span class="w-3 h-3 rounded-full bg-red-600 animate-ping"></span> SLA dépassé</span>
        </div>

        <div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-5 gap-3 mb-6">
            @foreach($tables ?? [] as $tbl)
                @php
                    $tc = $tbl->getStatusColor();
                    $tLabel = $tbl->getStatusLabel();
                    $isClickable = $tbl->isAvailable() || in_array($tbl->status, ['kitchen_processing', 'served_unpaid', 'occupied']);
                @endphp
                <button @click="selectTable({{ $tbl->id }}, '{{ $tbl->name }}', {{ $tbl->currentOrder ? $tbl->currentOrder->id : 'null' }}, '{{ $tbl->currentOrder ? $tbl->currentOrder->order_number : '' }}', {{ $tbl->currentOrder ? $tbl->currentOrder->total_amount : 0 }}, '{{ $tbl->currentOrder ? $tbl->total_amount : '' }}')"
                        class="p-4 rounded-xl border-2 transition-all active:scale-95 min-h-[80px] relative
                               {{ $tc === 'green' ? 'border-green-500/50 bg-green-500/10 hover:bg-green-500/20 text-green-400 hover:border-green-400' : '' }}
                               {{ $tc === 'yellow' ? 'border-yellow-500/50 bg-yellow-500/10 hover:bg-yellow-500/20 text-yellow-400 hover:border-yellow-400' : '' }}
                               {{ $tc === 'blue' ? 'border-blue-500/50 bg-blue-500/10 hover:bg-blue-500/20 text-blue-400 hover:border-blue-400' : '' }}
                               {{ $tc === 'red' ? 'border-red-500/50 bg-red-500/10 hover:bg-red-500/20 text-red-400 hover:border-red-400' : '' }}
                               {{ $tbl->isSlaBreached(30) ? 'animate-pulse ring-2 ring-red-500 ring-offset-2 ring-offset-slate-900' : '' }}"
                        {{ !$isClickable ? 'disabled' : '' }}>
                    @if($tbl->isSlaBreached(30))
                        <div class="absolute -top-2 -right-2 w-6 h-6 bg-red-600 rounded-full flex items-center justify-center z-10">
                            <span class="text-white text-xs font-bold">!</span>
                        </div>
                    @endif
                    <div class="text-lg font-bold">{{ $tbl->name }}</div>
                    <div class="text-xs mt-1">{{ $tbl->zone ? $tbl->zone : '' }}</div>
                    <div class="text-xs mt-0.5">
                        <span class="inline-flex items-center gap-1">
                            <span class="w-1.5 h-1.5 rounded-full {{ $tc === 'green' ? 'bg-green-400' : ($tc === 'yellow' ? 'bg-yellow-400 animate-pulse' : ($tc === 'blue' ? 'bg-blue-400 animate-pulse' : 'bg-red-400')) }}"></span>
                            {{ $tLabel }}
                        </span>
                    </div>
                    @if($tbl->getWaitMinutes() !== null && in_array($tbl->status, ['kitchen_processing', 'served_unpaid']))
                        <div class="mt-1">
                            <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-mono font-bold
                                {{ $tbl->getWaitMinutes() < 15 ? 'bg-green-500/20 text-green-400' : '' }}
                                {{ $tbl->getWaitMinutes() >= 15 && $tbl->getWaitMinutes() < 30 ? 'bg-yellow-500/20 text-yellow-400' : '' }}
                                {{ $tbl->getWaitMinutes() >= 30 ? 'bg-red-500/20 text-red-400 animate-pulse' : '' }}">
                                ⏳ {{ $tbl->getWaitMinutes() }} min
                            </span>
                        </div>
                    @endif
                    @if($tbl->currentOrder)
                        <div class="text-[10px] mt-1 text-gray-500">
                            #{{ $tbl->currentOrder->order_number }} — {{ number_format($tbl->currentOrder->total_amount, 0, ',', '.') }} {{ $restaurant->currency ?? 'FC' }}
                        </div>
                    @endif
                </button>
            @endforeach
        </div>
        <button @click="selectTable(null, 'À emporter', null, null, 0, '')"
                class="px-8 py-3 rounded-xl border-2 border-gray-600 text-gray-300 hover:bg-gray-800 hover:border-gray-500 transition-all active:scale-95">
            🛍️ À emporter
        </button>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════
     PANNEAU D'ACTIONS TABLE (si commande active)
     ═══════════════════════════════════════════════════════ -->
<div x-show="tableSelected && activeOrderId"
     x-transition
     class="fixed top-0 left-0 right-0 z-40 bg-slate-800/95 backdrop-blur-md border-b border-slate-700 px-4 py-3">
    <div class="max-w-4xl mx-auto flex items-center justify-between">
        <!-- Info commande -->
        <div class="flex items-center gap-4">
            <div class="bg-blue-600 text-white px-3 py-1 rounded-lg text-sm font-bold">
                <span x-text="activeTableName"></span>
            </div>
            <div class="text-white text-sm">
                <span class="text-gray-400">Ticket:</span>
                <span class="font-bold" x-text="activeOrderNumber"></span>
            </div>
            <div class="text-white text-sm">
                <span class="text-gray-400">Total:</span>
                <span class="font-bold text-green-400" x-text="formatMoney(activeOrderTotal)"></span>
            </div>
            <div class="text-white text-sm">
                <span class="text-gray-400">Statut:</span>
                <span class="px-2 py-0.5 rounded text-xs font-bold"
                      :class="activeOrderStatus === 'sent_to_kitchen' ? 'bg-yellow-500/20 text-yellow-400' : (activeOrderStatus === 'ready' ? 'bg-blue-500/20 text-blue-400' : 'bg-green-500/20 text-green-400')"
                      x-text="activeOrderStatusLabel"></span>
            </div>
        </div>

        <!-- Actions -->
        <div class="flex items-center gap-2">
            <!-- Imprimer l'addition (Proforma) -->
            <button @click="printProforma(activeOrderId)"
                    class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium transition flex items-center gap-2">
                🖨️ Imprimer
            </button>
            <!-- Payer la note -->
            <button @click="openPaymentModal()"
                    class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg text-sm font-medium transition flex items-center gap-2">
                💳 Payer
            </button>
            <!-- WhatsApp -->
            <button @click="sendViaWhatsApp(activeOrderId, activeOrderNumber, '{{ $restaurant->name ?? '' }}')"
                    class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-sm font-medium transition flex items-center gap-2">
                📱 WhatsApp
            </button>
            <!-- Retour plan de salle -->
            <button @click="returnToFloorPlan()"
                    class="px-4 py-2 bg-slate-600 hover:bg-slate-500 text-white rounded-lg text-sm font-medium transition flex items-center gap-2">
                ← Tables
            </button>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════
     INTERFACE POS PRINCIPALE
     ═══════════════════════════════════════════════════════ -->
<div x-show="tableSelected"
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     class="flex h-full gap-0"
     :class="activeOrderId ? 'pt-16' : ''"
     style="display: none;">

    <!-- LEFT: PRODUITS -->
    <div class="flex-1 flex flex-col min-w-0 bg-white/60 dark:bg-slate-900/60 backdrop-blur-sm border-r border-gray-200/50 dark:border-slate-700/50">
        <!-- Barre info table -->
        <div class="bg-slate-800 dark:bg-slate-950 text-white px-4 py-2 flex items-center justify-between flex-shrink-0">
            <div class="flex items-center gap-3">
                <button @click="returnToFloorPlan()" class="text-xs bg-slate-700 hover:bg-slate-600 px-3 py-1 rounded-lg transition flex items-center gap-1">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    Tables
                </button>
                <span class="text-sm font-bold" x-text="selectedTableName"></span>
                <span class="text-xs text-gray-400" x-text="'(' + cartItemCount + ' articles)'"></span>
            </div>
            <button @click="changeTable()" class="text-xs text-gray-400 hover:text-white underline transition">Changer</button>
        </div>
        <!-- Category Tabs -->
        <div class="bg-white/80 dark:bg-slate-800/80 backdrop-blur-sm border-b border-gray-200/50 dark:border-slate-700/50 px-3 py-2 flex-shrink-0">
            <div class="flex gap-2 overflow-x-auto scrollbar-hide" style="-ms-overflow-style: none; scrollbar-width: none;">
                <button @click="activeCategory = null"
                        :class="activeCategory === null ? 'bg-slate-900 dark:bg-slate-600 text-white' : 'bg-gray-200 dark:bg-slate-700 text-gray-700 dark:text-gray-300 hover:bg-gray-300 dark:hover:bg-slate-600'"
                        class="flex-shrink-0 px-5 py-2.5 rounded-lg font-semibold text-sm transition-colors whitespace-nowrap min-h-[44px]">Tous</button>
                <template x-for="cat in categories" :key="cat.id">
                    <button @click="activeCategory = cat.id"
                            :class="activeCategory === cat.id ? 'bg-slate-900 dark:bg-slate-600 text-white' : 'bg-gray-200 dark:bg-slate-700 text-gray-700 dark:text-gray-300 hover:bg-gray-300 dark:hover:bg-slate-600'"
                            class="flex-shrink-0 px-5 py-2.5 rounded-lg font-semibold text-sm transition-colors whitespace-nowrap min-h-[44px]" x-text="cat.name"></button>
                </template>
            </div>
        </div>
        <!-- Product Grid -->
        <div class="flex-1 overflow-y-auto p-3">
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3">
                <template x-for="product in filteredProducts" :key="product.id">
                    <button @click="openAddToCartModal(product)"
                            :disabled="product.track_inventory && product.stock_quantity <= 0"
                            :class="product.track_inventory && product.stock_quantity <= 0 ? 'opacity-50 cursor-not-allowed' : ''"
                            class="bg-white/90 dark:bg-slate-800/90 backdrop-blur-sm rounded-xl border border-gray-200/50 dark:border-slate-700/50 hover:border-slate-400 dark:hover:border-slate-500 hover:shadow-lg transition-all p-3 flex flex-col items-center text-center gap-2 min-h-[150px] active:scale-95 select-none relative">
                        <span x-show="product.track_inventory && product.stock_quantity <= product.stock_alert_threshold && product.stock_quantity > 0" class="absolute top-2 right-2 w-3 h-3 bg-orange-500 rounded-full" title="Stock critique"></span>
                        <span x-show="product.track_inventory && product.stock_quantity <= 0" class="absolute top-2 right-2 px-1.5 py-0.5 bg-red-500 text-white text-[9px] font-bold rounded">RUPTURE</span>
                        <span x-show="product.kitchen_route === 'bar'" class="absolute top-2 left-2 px-1.5 py-0.5 bg-purple-500 text-white text-[9px] font-bold rounded">BAR</span>
                        <span x-show="product.kitchen_route === 'counter'" class="absolute top-2 left-2 px-1.5 py-0.5 bg-cyan-500 text-white text-[9px] font-bold rounded">COMPTOIR</span>
                        <div class="w-full h-24 bg-gray-100 dark:bg-slate-700 rounded-lg flex items-center justify-center overflow-hidden flex-shrink-0">
                            <template x-if="product.image"><img :src="'/storage/' + product.image" :alt="product.name" class="w-full h-full object-cover" /></template>
                            <template x-if="!product.image"><svg class="w-10 h-10 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg></template>
                        </div>
                        <span class="text-sm font-medium text-gray-800 dark:text-gray-200 leading-tight line-clamp-2" x-text="product.name"></span>
                        <div class="mt-auto text-center">
                            <span class="text-base font-bold text-slate-900 dark:text-white" x-text="formatMoney(product.price)"></span>
                            <span class="block text-xs text-gray-500 dark:text-gray-400 mt-0.5" x-text="formatUSD(product.price)"></span>
                        </div>
                    </button>
                </template>
            </div>
        </div>
    </div>

    <!-- RIGHT: PANIER -->
    <div class="w-[50%] min-w-[380px] flex flex-col bg-gray-50/60 dark:bg-slate-900/40 backdrop-blur-sm">
        <div class="bg-white/80 dark:bg-slate-800/80 backdrop-blur-sm border-b border-gray-200/50 dark:border-slate-700/50 px-4 py-3 flex-shrink-0">
            <div class="flex items-center justify-between">
                <h2 class="text-base font-bold text-gray-800 dark:text-gray-100">Ticket en cours</h2>
                <button @click="clearCart()" x-show="cart.length > 0" class="text-xs text-red-500 hover:text-red-600 transition">Vider</button>
            </div>
        </div>
        <div class="flex-1 overflow-y-auto px-3 py-2 space-y-2">
            <template x-for="(item, index) in cart" :key="index">
                <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-200 dark:border-slate-700 p-3 flex items-center gap-3">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2">
                            <p class="text-sm font-medium text-gray-800 dark:text-gray-200 truncate" x-text="item.name"></p>
                            <span x-show="item.kitchen_route === 'bar'" class="px-1.5 py-0.5 bg-purple-100 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400 text-[9px] font-bold rounded flex-shrink-0">BAR</span>
                            <span x-show="item.kitchen_route === 'counter'" class="px-1.5 py-0.5 bg-cyan-100 dark:bg-cyan-900/30 text-cyan-600 dark:text-cyan-400 text-[9px] font-bold rounded flex-shrink-0">COMPTOIR</span>
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400"><span x-text="formatMoney(item.price)"></span> × <span x-text="item.quantity"></span></p>
                        <p class="text-xs text-gray-400 dark:text-gray-500" x-show="item.notes" x-text="'📝 ' + item.notes"></p>
                    </div>
                    <div class="text-right flex-shrink-0">
                        <p class="text-sm font-bold text-gray-800 dark:text-white" x-text="formatMoney(item.price * item.quantity)"></p>
                    </div>
                    <button @click="removeFromCart(index)" class="flex-shrink-0 w-8 h-8 rounded-lg bg-red-50 dark:bg-red-900/20 text-red-500 hover:bg-red-100 dark:hover:bg-red-900/40 flex items-center justify-center transition active:scale-90">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
            </template>
            <div x-show="cart.length === 0" class="flex flex-col items-center justify-center h-full text-gray-400 dark:text-gray-600 py-12">
                <svg class="w-16 h-16 mb-3 opacity-30" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                <p class="text-sm">Panier vide</p>
            </div>
        </div>
        <!-- Totals + Actions -->
        <div class="border-t border-gray-200/50 dark:border-slate-700/50 bg-white/90 dark:bg-slate-800/90 px-4 py-3 flex-shrink-0 space-y-2">
            <div class="flex justify-between text-sm text-gray-600 dark:text-gray-400">
                <span>Sous-total</span>
                <span x-text="formatMoney(subtotal)"></span>
            </div>
            <div class="flex justify-between text-sm text-gray-600 dark:text-gray-400" x-show="tax > 0">
                <span>Taxes</span>
                <span x-text="formatMoney(tax)"></span>
            </div>
            <div class="flex justify-between items-center pt-2 border-t border-gray-200 dark:border-slate-700">
                <span class="text-base font-bold text-gray-800 dark:text-white">Total</span>
                <span class="text-xl font-bold text-slate-900 dark:text-white" x-text="formatMoney(grandTotal)"></span>
            </div>
            <button @click="submitOrder()" :disabled="cart.length === 0 || isSubmitting"
                    :class="cart.length === 0 ? 'bg-gray-300 dark:bg-slate-700 cursor-not-allowed' : 'bg-green-600 hover:bg-green-700 active:scale-[0.98]'"
                    class="w-full py-3.5 rounded-xl text-white font-bold text-base shadow-lg transition-all mt-2">
                <span x-show="!isSubmitting && cart.length > 0">📤 Envoyer en Cuisine — <span x-text="formatMoney(grandTotal)"></span></span>
                <span x-show="!isSubmitting && cart.length === 0">Panier vide</span>
                <span x-show="isSubmitting" class="flex items-center justify-center gap-2"><svg class="animate-spin h-5 w-5" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg> Envoi...</span>
            </button>
        </div>
    </div>

    <!-- MODALE AJOUT PRODUIT -->
    <div x-show="showAddModal" x-transition class="fixed inset-0 z-40 bg-black/50 backdrop-blur-sm flex items-center justify-center p-4" @click.self="showAddModal = false" style="display: none;">
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-2xl w-full max-w-sm overflow-hidden">
            <div class="bg-slate-900 dark:bg-slate-950 text-white px-5 py-4">
                <h3 class="text-lg font-bold" x-text="addModalProduct?.name ?? ''"></h3>
                <p class="text-sm text-gray-400 mt-0.5"><span x-text="formatMoney(addModalProduct?.price ?? 0)"></span></p>
                <p class="text-xs text-gray-500 mt-1">
                    <span x-show="addModalProduct?.kitchen_route === 'bar'" class="text-purple-400">🍺 Envoyé au Bar</span>
                    <span x-show="addModalProduct?.kitchen_route === 'counter'" class="text-cyan-400">🥤 Servi au comptoir</span>
                    <span x-show="!addModalProduct?.kitchen_route || addModalProduct?.kitchen_route === 'kitchen'" class="text-yellow-400">👨‍🍳 Envoyé en cuisine (KDS)</span>
                </p>
            </div>
            <div class="p-5 space-y-5">
                <div>
                    <label class="text-sm font-semibold text-gray-700 dark:text-gray-300 block mb-2">Quantité</label>
                    <div class="flex items-center justify-center gap-4">
                        <button @click="addModalQty = Math.max(1, addModalQty - 1)" class="w-14 h-14 rounded-xl border-2 border-gray-300 dark:border-slate-600 text-2xl font-bold text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-slate-700 transition-all active:scale-90 flex items-center justify-center">−</button>
                        <input type="number" x-model.number="addModalQty" min="1" max="99" class="w-20 text-center text-3xl font-bold border-2 border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-xl py-2 focus:ring-2 focus:ring-blue-500">
                        <button @click="addModalQty++" class="w-14 h-14 rounded-xl border-2 border-gray-300 dark:border-slate-600 text-2xl font-bold text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-slate-700 transition-all active:scale-90 flex items-center justify-center">+</button>
                    </div>
                </div>
                <div>
                    <label class="text-sm font-semibold text-gray-700 dark:text-gray-300 block mb-1">Note cuisine (optionnel)</label>
                    <input type="text" x-model="addModalNotes" class="w-full border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-lg px-4 py-3 text-sm min-h-[48px] focus:ring-2 focus:ring-blue-500" placeholder="Ex: Sans oignons, bien cuit...">
                </div>
            </div>
            <div class="px-5 py-4 border-t border-gray-200 dark:border-slate-700 flex gap-3">
                <button @click="showAddModal = false" class="flex-1 py-3 rounded-xl border-2 border-gray-300 dark:border-slate-600 text-gray-700 dark:text-gray-300 font-semibold text-sm hover:bg-gray-100 dark:hover:bg-slate-700 transition-colors">Annuler</button>
                <button @click="confirmAddToCart()" class="flex-1 py-3 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-semibold text-sm transition-all active:scale-95">Ajouter — <span x-text="formatMoney((addModalProduct?.price ?? 0) * addModalQty)"></span></button>
            </div>
        </div>
    </div>

    <!-- MODALE PAIEMENT -->
    <div x-show="showPaymentModal" x-transition class="fixed inset-0 z-50 bg-black/50 backdrop-blur-sm flex items-center justify-center p-4" @click.self="showPaymentModal = false" style="display: none;">
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-2xl w-full max-w-md overflow-hidden">
            <div class="bg-slate-900 dark:bg-slate-950 text-white px-5 py-4 flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-bold">Paiement</h3>
                    <p class="text-sm text-gray-400" x-text="activeTableName"></p>
                </div>
                <div class="text-right">
                    <span class="text-2xl font-bold" x-text="formatMoney(activeOrderTotal)"></span>
                </div>
            </div>
            <div class="p-5 space-y-5">
                <div>
                    <label class="text-sm font-semibold text-gray-700 dark:text-gray-300 block mb-3">Mode de paiement</label>
                    <div class="grid grid-cols-3 gap-2">
                        <label :class="paymentMethod === 'cash' ? 'border-green-500 bg-green-50 dark:bg-green-900/30 ring-2 ring-green-500' : 'border-gray-200 dark:border-slate-600'" class="flex flex-col items-center gap-1 border-2 rounded-xl py-3 px-2 cursor-pointer transition-all">
                            <input type="radio" x-model="paymentMethod" value="cash" class="sr-only" />
                            <span class="text-2xl">💵</span>
                            <span class="text-xs font-medium" :class="paymentMethod === 'cash' ? 'text-green-700 dark:text-green-300' : 'text-gray-600 dark:text-gray-400'">Cash</span>
                        </label>
                        <label :class="paymentMethod === 'mobile_money' ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/30 ring-2 ring-blue-500' : 'border-gray-200 dark:border-slate-600'" class="flex flex-col items-center gap-1 border-2 rounded-xl py-3 px-2 cursor-pointer transition-all">
                            <input type="radio" x-model="paymentMethod" value="mobile_money" class="sr-only" />
                            <span class="text-2xl">📱</span>
                            <span class="text-xs font-medium" :class="paymentMethod === 'mobile_money' ? 'text-blue-700 dark:text-blue-300' : 'text-gray-600 dark:text-gray-400'">Mobile Money</span>
                        </label>
                        <label :class="paymentMethod === 'credit' ? 'border-orange-500 bg-orange-50 dark:bg-orange-900/30 ring-2 ring-orange-500' : 'border-gray-200 dark:border-slate-600'" class="flex flex-col items-center gap-1 border-2 rounded-xl py-3 px-2 cursor-pointer transition-all">
                            <input type="radio" x-model="paymentMethod" value="credit" class="sr-only" />
                            <span class="text-2xl">💳</span>
                            <span class="text-xs font-medium" :class="paymentMethod === 'credit' ? 'text-orange-700 dark:text-orange-300' : 'text-gray-600 dark:text-gray-400'">Crédit</span>
                        </label>
                    </div>
                </div>
                <template x-if="paymentMethod === 'cash'">
                    <div class="space-y-3">
                        <div>
                            <label class="text-sm font-medium text-gray-700 dark:text-gray-300 block mb-1">Montant reçu (FC)</label>
                            <input type="number" x-model.number="cashReceived" min="0" step="100" @input="calculateChange()" class="w-full border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-lg px-4 py-3 text-lg text-right font-semibold min-h-[48px] focus:ring-2 focus:ring-green-500" placeholder="0">
                        </div>
                        <div class="rounded-xl p-4 text-center" :class="changeGiven >= 0 ? 'bg-green-100 dark:bg-green-900/30' : 'bg-red-100 dark:bg-red-900/30'">
                            <p class="text-xs uppercase tracking-wide mb-1" :class="changeGiven >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'">Monnaie à rendre</p>
                            <p class="text-2xl font-bold" :class="changeGiven >= 0 ? 'text-green-700 dark:text-green-300' : 'text-red-700 dark:text-red-300'"><span x-text="formatMoney(Math.abs(changeGiven))"></span></p>
                        </div>
                    </div>
                </template>
                <template x-if="paymentMethod === 'mobile_money'">
                    <div>
                        <label class="text-sm font-medium text-gray-700 dark:text-gray-300 block mb-1">Référence de transaction</label>
                        <input type="text" x-model="paymentReference" class="w-full border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-lg px-4 py-3 text-sm min-h-[48px] focus:ring-2 focus:ring-blue-500" placeholder="Ex: MOMO-XXXX-XXXX">
                    </div>
                </template>
            </div>
            <div class="px-5 py-4 border-t border-gray-200 dark:border-slate-700 flex gap-3">
                <button @click="showPaymentModal = false" class="flex-1 py-3 rounded-xl border-2 border-gray-300 dark:border-slate-600 text-gray-700 dark:text-gray-300 font-semibold text-sm hover:bg-gray-100 dark:hover:bg-slate-700 transition-colors">Annuler</button>
                <button @click="processPayment()" :disabled="isProcessing" :class="isProcessing ? 'bg-gray-400 cursor-not-allowed' : 'bg-green-600 hover:bg-green-700 active:scale-95'" class="flex-1 py-3 rounded-xl text-white font-semibold text-sm shadow-md transition-all">
                    <span x-show="!isProcessing">✅ Valider le paiement</span>
                    <span x-show="isProcessing" class="flex items-center justify-center gap-2"><svg class="animate-spin h-5 w-5" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>...</span>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function posWorkflow() {
    return {
        products: @json($products ?? []),
        categories: @json($categories ?? []),
        activeCategory: null,
        cart: [],
        selectedTable: null,
        selectedTableName: '',
        tableSelected: false,
        showAddModal: false,
        addModalProduct: null,
        addModalQty: 1,
        addModalNotes: '',
        isSubmitting: false,
        // Commande active (si table avec commande)
        activeOrderId: null,
        activeOrderNumber: '',
        activeOrderTotal: 0,
        activeOrderStatus: '',
        activeOrderStatusLabel: '',
        activeTableName: '',
        // Paiement
        showPaymentModal: false,
        paymentMethod: 'cash',
        cashReceived: 0,
        changeGiven: 0,
        paymentReference: '',
        isProcessing: false,
        // Taxes et devises
        taxRate: {{ $restaurant->tax_rate ?? 0 }},
        exchangeRate: {{ \App\Models\SiteSetting::getValue('exchange_rate', 2850) }},
        defaultCurrency: '{{ \App\Models\SiteSetting::getValue('default_currency', 'FC') }}',
        secondaryCurrency: '{{ \App\Models\SiteSetting::getValue('secondary_currency', 'USD') }}',

        get filteredProducts() {
            if (!this.activeCategory) return this.products;
            return this.products.filter(p => p.category_id === this.activeCategory);
        },
        get cartItemCount() { return this.cart.reduce((sum, item) => sum + item.quantity, 0); },
        get subtotal() { return this.cart.reduce((sum, item) => sum + (item.price * item.quantity), 0); },
        get tax() { return this.subtotal * (this.taxRate / 100); },
        get grandTotal() { return Math.max(0, this.subtotal + this.tax); },

        init() {},

        selectTable(tableId, tableName, orderId, orderNumber, orderTotal, status) {
            this.selectedTable = tableId;
            this.selectedTableName = tableName;
            this.tableSelected = true;
            this.cart = [];
            // Si la table a une commande active
            if (orderId) {
                this.activeOrderId = orderId;
                this.activeOrderNumber = orderNumber;
                this.activeOrderTotal = orderTotal;
                this.activeOrderStatus = status;
                this.activeOrderStatusLabel = this.getStatusLabel(status);
                this.activeTableName = tableName;
            } else {
                this.activeOrderId = null;
                this.activeOrderNumber = '';
                this.activeOrderTotal = 0;
                this.activeOrderStatus = '';
                this.activeOrderStatusLabel = '';
            }
        },

        getStatusLabel(status) {
            return {
                'pending': 'Brouillon',
                'sent_to_kitchen': 'En cuisine',
                'ready': 'Prêt à servir',
                'delivered': 'Servi',
                'paid': 'Payée',
                'annulee': 'Annulée'
            }[status] || status;
        },

        changeTable() {
            if (this.cart.length > 0 && !confirm('Le panier en cours sera perdu. Changer de table ?')) return;
            this.cart = [];
            this.tableSelected = false;
            this.selectedTable = null;
            this.selectedTableName = '';
            this.activeOrderId = null;
        },

        returnToFloorPlan() {
            if (this.cart.length > 0 && !confirm('Retourner au plan de salle ? Le panier en cours sera perdu.')) return;
            this.cart = [];
            this.tableSelected = false;
            this.selectedTable = null;
            this.selectedTableName = '';
            this.activeOrderId = null;
        },

        formatMoney(amount) {
            const val = parseFloat(amount) || 0;
            return new Intl.NumberFormat('fr-FR', { minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(val) + ' ' + this.defaultCurrency;
        },
        formatUSD(amount) {
            if (!this.exchangeRate || this.exchangeRate <= 0) return '';
            const val = parseFloat(amount) || 0;
            const usd = val / this.exchangeRate;
            return '≈ ' + new Intl.NumberFormat('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(usd) + ' ' + this.secondaryCurrency;
        },

        openAddToCartModal(product) {
            if (product.track_inventory && product.stock_quantity <= 0) {
                alert('❌ Produit en rupture de stock');
                return;
            }
            this.addModalProduct = product;
            this.addModalQty = 1;
            this.addModalNotes = '';
            this.showAddModal = true;
        },

        confirmAddToCart() {
            if (!this.addModalProduct || this.addModalQty < 1) return;
            const product = this.products.find(p => p.id === this.addModalProduct.id);
            if (product && product.track_inventory) {
                const inCart = this.cart.filter(i => i.id === this.addModalProduct.id).reduce((s, i) => s + i.quantity, 0);
                if (inCart + this.addModalQty > product.stock_quantity) {
                    alert('Stock insuffisant. Disponible: ' + (product.stock_quantity - inCart));
                    return;
                }
            }
            const existing = this.cart.find(item => item.id === this.addModalProduct.id);
            if (existing) {
                existing.quantity += this.addModalQty;
                if (this.addModalNotes) existing.notes = this.addModalNotes;
            } else {
                this.cart.push({
                    id: this.addModalProduct.id,
                    name: this.addModalProduct.name,
                    price: parseFloat(this.addModalProduct.price),
                    quantity: this.addModalQty,
                    notes: this.addModalNotes,
                    kitchen_route: this.addModalProduct.kitchen_route || 'kitchen',
                    track_inventory: this.addModalProduct.track_inventory,
                });
            }
            this.showAddModal = false;
        },

        removeFromCart(index) { this.cart.splice(index, 1); },
        clearCart() { if (confirm('Vider le panier ?')) this.cart = []; },

        // ── Imprimer l'addition (PROFORMA) ──
        printProforma(orderId) {
            if (!orderId) { alert('Aucune commande active.'); return; }
            window.open(`/pos/order/${orderId}/receipt/proforma`, '_blank', 'width=400,height=700');
        },

        // ── Envoyer via WhatsApp ──
        sendViaWhatsApp(orderId, orderNumber, restaurantName) {
            if (!orderId) { alert('Aucune commande active.'); return; }
            const receiptUrl = `${window.location.origin}/pos/order/${orderId}/receipt`;
            const message = encodeURIComponent(
                `Merci pour votre visite chez ${restaurantName} !\n\n` +
                `📋 Ticket: ${orderNumber}\n` +
                `🧾 Votre reçu: ${receiptUrl}\n\n` +
                `À bientôt ! — M-SEC Technology Consulting`
            );
            window.open(`https://wa.me/?text=${message}`, '_blank');
        },

        // ── Ouvrir modale paiement ──
        openPaymentModal() {
            if (!this.activeOrderId) { alert('Aucune commande à payer.'); return; }
            this.cashReceived = this.activeOrderTotal;
            this.changeGiven = 0;
            this.paymentReference = '';
            this.paymentMethod = 'cash';
            this.showPaymentModal = true;
        },

        calculateChange() {
            const received = parseFloat(this.cashReceived) || 0;
            this.changeGiven = received - this.activeOrderTotal;
        },

        // ── Traiter le paiement ──
        async processPayment() {
            if (this.isProcessing) return;
            if (this.paymentMethod === 'cash') {
                const r = parseFloat(this.cashReceived) || 0;
                if (r < this.activeOrderTotal) { alert('Montant insuffisant.'); return; }
            }
            if (this.paymentMethod === 'mobile_money' && !this.paymentReference.trim()) {
                alert('Référence requise.'); return;
            }
            this.isProcessing = true;
            try {
                const response = await fetch(`/pos/order/${this.activeOrderId}/pay`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({
                        payment_method: this.paymentMethod,
                        payment_reference: this.paymentReference,
                        cash_received: parseFloat(this.cashReceived) || 0,
                    }),
                });
                const data = await response.json();
                if (data.success) {
                    alert('✅ Paiement réussi! Monnaie: ' + this.formatMoney(data.change_given));
                    this.showPaymentModal = false;
                    this.activeOrderId = null;
                    this.activeOrderStatus = 'paid';
                    this.activeOrderStatusLabel = 'Payée';
                    // Recharger la page pour mettre à jour
                    location.reload();
                } else {
                    alert('❌ ' + (data.message || 'Erreur de paiement.'));
                }
            } catch (e) {
                alert('❌ Erreur de connexion.');
            }
            this.isProcessing = false;
        },

        // ── Soumettre la commande ──
        async submitOrder() {
            if (this.cart.length === 0 || this.isSubmitting) return;
            this.isSubmitting = true;
            try {
                const payload = {
                    items: this.cart.map(i => ({
                        product_id: i.id,
                        quantity: i.quantity,
                        unit_price: i.price,
                        notes: i.notes || null,
                    })),
                    payment_method: 'cash',
                    discount_amount: 0,
                    tax_amount: this.tax,
                    total: this.grandTotal,
                    table_id: this.selectedTable,
                    notes: null,
                };
                const response = await fetch('{{ route("pos.order.store") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify(payload),
                });
                const data = await response.json();
                if (response.ok && data.success) {
                    this.cart = [];
                    this.activeOrderId = data.order_id;
                    this.activeOrderNumber = data.order_number || '';
                    this.activeOrderTotal = data.total;
                    this.activeOrderStatus = data.status;
                    this.activeOrderStatusLabel = this.getStatusLabel(data.status);
                    alert('✅ ' + data.message);
                } else {
                    alert('❌ ' + (data.message || 'Erreur lors de la soumission.'));
                }
            } catch (error) {
                console.error('Submit order error:', error);
                alert('❌ Erreur de connexion. Vérifiez votre réseau et réessayez.');
            } finally {
                this.isSubmitting = false;
            }
        },
    };
}
</script>
</div>
@endsection
