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
        <p class="text-gray-500 text-sm mb-8">Choisissez la table à servir ou "À emporter" pour commencer</p>
        <div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-5 gap-3 mb-6">
            @foreach($tables ?? [] as $table)
                <button @click="selectTable({{ $table->id }}, '{{ $table->name }}')"
                        class="p-4 rounded-xl border-2 transition-all active:scale-95 min-h-[80px]
                               {{ $table->isLibre() ? 'border-green-500/50 bg-green-500/10 hover:bg-green-500/20 text-green-400 hover:border-green-400' : 'border-red-500/30 bg-red-500/5 text-red-400/60 cursor-not-allowed' }}"
                        {{ !$table->isLibre() ? 'disabled' : '' }}>
                    <div class="text-lg font-bold">{{ $table->name }}</div>
                    <div class="text-xs mt-1">{{ $table->zone ? $table->zone : '' }}</div>
                    <div class="text-xs mt-0.5">
                        @if($table->isLibre())<span class="inline-flex items-center gap-1"><span class="w-1.5 h-1.5 rounded-full bg-green-400"></span> Libre</span>
                        @else<span class="inline-flex items-center gap-1"><span class="w-1.5 h-1.5 rounded-full bg-red-400"></span> Occupée</span>@endif
                    </div>
                </button>
            @endforeach
        </div>
        <button @click="selectTable(null, 'À emporter')"
                class="px-8 py-3 rounded-xl border-2 border-gray-600 text-gray-300 hover:bg-gray-800 hover:border-gray-500 transition-all active:scale-95">
            🛍️ À emporter
        </button>
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
     style="display: none;">

    <!-- LEFT: PRODUITS -->
    <div class="flex-1 flex flex-col min-w-0 bg-white/60 dark:bg-slate-900/60 backdrop-blur-sm border-r border-gray-200/50 dark:border-slate-700/50">
        <!-- Barre info table -->
        <div class="bg-slate-800 dark:bg-slate-950 text-white px-4 py-2 flex items-center justify-between flex-shrink-0">
            <div class="flex items-center gap-3">
                <span class="text-sm font-bold" x-text="selectedTableName"></span>
                <span class="text-xs text-gray-400" x-text="'(' + cartItemCount + ' articles)'"></span>
            </div>
            <button @click="changeTable()" class="text-xs text-gray-400 hover:text-white underline transition">Changer de table</button>
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
                            class="bg-white/90 dark:bg-slate-800/90 backdrop-blur-sm rounded-xl border border-gray-200/50 dark:border-slate-700/50 hover:border-slate-400 dark:hover:border-slate-500 hover:shadow-lg transition-all p-3 flex flex-col items-center text-center gap-2 min-h-[150px] active:scale-95 select-none">
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
                        <p class="text-sm font-medium text-gray-800 dark:text-gray-200 truncate" x-text="item.name"></p>
                        <p class="text-xs text-gray-500 dark:text-gray-400"><span x-text="formatMoney(item.price)"></span> × <span x-text="item.quantity"></span></p>
                        <p class="text-xs text-gray-400 dark:text-gray-500" x-show="item.notes" x-text="'📝 ' + item.notes"></p>
                    </div>
                    <div class="text-right flex-shrink-0">
                        <p class="text-sm font-bold text-gray-800 dark:text-white" x-text="formatMoney(item.price * item.quantity)"></p>
                        <p class="text-xs text-gray-400" x-text="formatUSD(item.price * item.quantity)"></p>
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
        <!-- Totaux + Bouton -->
        <div class="border-t border-gray-200/50 dark:border-slate-700/50 bg-white/90 dark:bg-slate-800/90 px-4 py-3 flex-shrink-0 space-y-2">
            <div class="flex justify-between text-sm text-gray-600 dark:text-gray-400">
                <span>Sous-total</span>
                <div class="text-right"><span x-text="formatMoney(subtotal)"></span><span class="block text-xs text-gray-400" x-text="formatUSD(subtotal)"></span></div>
            </div>
            <div class="flex justify-between text-sm text-gray-600 dark:text-gray-400" x-show="tax > 0">
                <span>Taxes</span>
                <div class="text-right"><span x-text="formatMoney(tax)"></span><span class="block text-xs text-gray-400" x-text="formatUSD(tax)"></span></div>
            </div>
            <div class="flex justify-between items-center pt-2 border-t border-gray-200 dark:border-slate-700">
                <span class="text-base font-bold text-gray-800 dark:text-white">Total</span>
                <div class="text-right">
                    <span class="text-xl font-bold text-slate-900 dark:text-white" x-text="formatMoney(grandTotal)"></span>
                    <span class="block text-sm text-gray-500 dark:text-gray-400 font-medium" x-text="formatUSD(grandTotal)"></span>
                </div>
            </div>
            <button @click="openCheckout()" :disabled="cart.length === 0"
                    :class="cart.length === 0 ? 'bg-gray-300 dark:bg-slate-700 cursor-not-allowed' : 'bg-green-600 hover:bg-green-700 active:scale-[0.98]'"
                    class="w-full py-3.5 rounded-xl text-white font-bold text-base shadow-lg transition-all mt-2">
                <span x-show="cart.length > 0">Envoyer en Cuisine — <span x-text="formatMoney(grandTotal)"></span></span>
                <span x-show="cart.length === 0">Panier vide</span>
            </button>
        </div>
    </div>

    <!-- MODALE AJOUT PRODUIT -->
    <div x-show="showAddModal" x-transition class="fixed inset-0 z-40 bg-black/50 backdrop-blur-sm flex items-center justify-center p-4" @click.self="showAddModal = false" style="display: none;">
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-2xl w-full max-w-sm overflow-hidden">
            <div class="bg-slate-900 dark:bg-slate-950 text-white px-5 py-4">
                <h3 class="text-lg font-bold" x-text="addModalProduct?.name ?? ''"></h3>
                <p class="text-sm text-gray-400 mt-0.5"><span x-text="formatMoney(addModalProduct?.price ?? 0)"></span><span class="text-gray-500 ml-2" x-text="formatUSD(addModalProduct?.price ?? 0)"></span></p>
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
                <div><h3 class="text-lg font-bold">Paiement</h3><p class="text-sm text-gray-400" x-text="selectedTableName"></p></div>
                <div class="text-right"><span class="text-2xl font-bold" x-text="formatMoney(paymentTotal)"></span><span class="block text-sm text-gray-400" x-text="formatUSD(paymentTotal)"></span></div>
            </div>
            <div class="p-5 space-y-5">
                <div>
                    <label class="text-sm font-semibold text-gray-700 dark:text-gray-300 block mb-3">Mode de paiement</label>
                    <div class="grid grid-cols-3 gap-2">
                        <label :class="paymentMethod === 'cash' ? 'border-green-500 bg-green-50 dark:bg-green-900/30 ring-2 ring-green-500' : 'border-gray-200 dark:border-slate-600'" class="flex flex-col items-center gap-1 border-2 rounded-xl py-3 px-2 cursor-pointer transition-all">
                            <input type="radio" x-model="paymentMethod" value="cash" class="sr-only" />
                            <svg class="w-7 h-7" :class="paymentMethod === 'cash' ? 'text-green-600' : 'text-gray-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                            <span class="text-xs font-medium" :class="paymentMethod === 'cash' ? 'text-green-700 dark:text-green-300' : 'text-gray-600 dark:text-gray-400'">Cash</span>
                        </label>
                        <label :class="paymentMethod === 'mobile_money' ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/30 ring-2 ring-blue-500' : 'border-gray-200 dark:border-slate-600'" class="flex flex-col items-center gap-1 border-2 rounded-xl py-3 px-2 cursor-pointer transition-all">
                            <input type="radio" x-model="paymentMethod" value="mobile_money" class="sr-only" />
                            <svg class="w-7 h-7" :class="paymentMethod === 'mobile_money' ? 'text-blue-600' : 'text-gray-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" /></svg>
                            <span class="text-xs font-medium" :class="paymentMethod === 'mobile_money' ? 'text-blue-700 dark:text-blue-300' : 'text-gray-600 dark:text-gray-400'">Mobile Money</span>
                        </label>
                        <label :class="paymentMethod === 'credit' ? 'border-orange-500 bg-orange-50 dark:bg-orange-900/30 ring-2 ring-orange-500' : 'border-gray-200 dark:border-slate-600'" class="flex flex-col items-center gap-1 border-2 rounded-xl py-3 px-2 cursor-pointer transition-all">
                            <input type="radio" x-model="paymentMethod" value="credit" class="sr-only" />
                            <svg class="w-7 h-7" :class="paymentMethod === 'credit' ? 'text-orange-600' : 'text-gray-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
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
                            <p class="text-xs mt-1" :class="changeGiven >= 0 ? 'text-green-500' : 'text-red-500'" x-text="formatUSD(Math.abs(changeGiven))"></p>
                        </div>
                    </div>
                </template>
                <template x-if="paymentMethod === 'mobile_money'">
                    <div>
                        <label class="text-sm font-medium text-gray-700 dark:text-gray-300 block mb-1">Référence de transaction</label>
                        <input type="text" x-model="paymentReference" class="w-full border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-lg px-4 py-3 text-sm min-h-[48px] focus:ring-2 focus:ring-blue-500" placeholder="Ex: MOMO-XXXX-XXXX">
                    </div>
                </template>
                <template x-if="paymentMethod === 'credit'">
                    <div class="space-y-3">
                        <div>
                            <label class="text-sm font-medium text-gray-700 dark:text-gray-300 block mb-1">Nom du client</label>
                            <input type="text" x-model="customerName" class="w-full border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-lg px-4 py-3 text-sm min-h-[48px] focus:ring-2 focus:ring-orange-500" placeholder="Nom complet">
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-700 dark:text-gray-300 block mb-1">Téléphone</label>
                            <input type="tel" x-model="customerPhone" class="w-full border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-lg px-4 py-3 text-sm min-h-[48px] focus:ring-2 focus:ring-orange-500" placeholder="+243 XX XXX XXXX">
                        </div>
                    </div>
                </template>
            </div>
            <div class="px-5 py-4 border-t border-gray-200 dark:border-slate-700 flex gap-3">
                <button @click="showPaymentModal = false" class="flex-1 py-3 rounded-xl border-2 border-gray-300 dark:border-slate-600 text-gray-700 dark:text-gray-300 font-semibold text-sm hover:bg-gray-100 dark:hover:bg-slate-700 transition-colors">Annuler</button>
                <button @click="processPayment()" :disabled="isProcessing" :class="isProcessing ? 'bg-gray-400 cursor-not-allowed' : 'bg-green-600 hover:bg-green-700 active:scale-95'" class="flex-1 py-3 rounded-xl text-white font-semibold text-sm shadow-md transition-all">
                    <span x-show="!isProcessing">Valider le paiement</span>
                    <span x-show="isProcessing" class="flex items-center justify-center gap-2"><svg class="animate-spin h-5 w-5" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none" /><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" /></svg>Traitement...</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Cancel Modal -->
    <div x-show="showCancelModal" class="fixed inset-0 z-50 bg-black/50 backdrop-blur-sm flex items-center justify-center p-4" @click.self="showCancelModal = false" style="display: none;">
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-2xl w-full max-w-sm overflow-hidden">
            <div class="bg-red-600 text-white px-5 py-4"><h3 class="text-lg font-bold">Annuler la commande</h3></div>
            <div class="p-5 space-y-4">
                <div>
                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300 block mb-1">Motif d'annulation <span class="text-red-500">*</span></label>
                    <textarea x-model="cancelReason" rows="3" required class="w-full border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-lg px-4 py-3 text-sm focus:ring-2 focus:ring-red-500" placeholder="Ex: Client a changé d'avis..."></textarea>
                </div>
            </div>
            <div class="px-5 py-4 border-t border-gray-200 dark:border-slate-700 flex gap-3">
                <button @click="showCancelModal = false" class="flex-1 py-2.5 rounded-xl border-2 border-gray-300 dark:border-slate-600 text-gray-700 dark:text-gray-300 font-medium text-sm hover:bg-gray-100 dark:hover:bg-slate-700 transition-colors">Retour</button>
                <button @click="confirmCancel()" class="flex-1 py-2.5 rounded-xl bg-red-600 hover:bg-red-700 text-white font-medium text-sm transition-colors">Confirmer</button>
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
        orders: @json($todayOrders ?? []),
        selectedOrder: null,
        showPaymentModal: false,
        showCancelModal: false,
        showAddModal: false,
        cancelReason: '',
        cancelTargetOrder: null,
        paymentMethod: 'cash',
        cashReceived: 0,
        changeGiven: 0,
        paymentReference: '',
        customerName: '',
        customerPhone: '',
        paymentTotal: 0,
        isProcessing: false,
        taxRate: {{ $restaurant->tax_rate ?? 0 }},
        selectedTable: null,
        selectedTableName: '',
        tableSelected: false,
        addModalProduct: null,
        addModalQty: 1,
        addModalNotes: '',
        exchangeRate: {{ \App\Models\SiteSetting::getValue('exchange_rate', 2850) }},
        defaultCurrency: '{{ \App\Models\SiteSetting::getValue('default_currency', 'FC') }}',
        secondaryCurrency: '{{ \App\Models\SiteSetting::getValue('secondary_currency', 'USD') }}',

        get filteredProducts() {
            if (!this.activeCategory) return this.products;
            return this.products.filter(p => p.category_id === this.activeCategory);
        },
        get cartItemCount() { return this.cart.reduce((sum, item) => sum + item.quantity, 0); },
        get subtotal() { return this.cart.reduce((sum, item) => sum + (item.price * item.quantity), 0); },
        get discount() { return 0; },
        get tax() { return this.subtotal * (this.taxRate / 100); },
        get grandTotal() { return Math.max(0, this.subtotal + this.tax); },
        get enCoursOrders() { return this.orders.filter(o => o.status === 'en_cours'); },
        get enAttenteOrders() { return this.orders.filter(o => o.status === 'en_attente'); },
        get payeeOrders() { return this.orders.filter(o => o.status === 'payee' || o.status === 'paid'); },
        get enCoursCount() { return this.enCoursOrders.length; },
        get enAttenteCount() { return this.enAttenteOrders.length; },
        get payeeCount() { return this.payeeOrders.length; },

        init() { this.pollOrders(); },

        selectTable(tableId, tableName) {
            this.selectedTable = tableId;
            this.selectedTableName = tableName;
            this.tableSelected = true;
        },
        changeTable() {
            if (this.cart.length > 0 && !confirm('Le panier en cours sera perdu. Changer de table ?')) return;
            this.cart = [];
            this.tableSelected = false;
            this.selectedTable = null;
            this.selectedTableName = '';
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
            this.addModalProduct = product;
            this.addModalQty = 1;
            this.addModalNotes = '';
            this.showAddModal = true;
        },
        confirmAddToCart() {
            if (!this.addModalProduct || this.addModalQty < 1) return;
            const existing = this.cart.find(item => item.id === this.addModalProduct.id);
            if (existing) {
                existing.quantity += this.addModalQty;
                if (this.addModalNotes) existing.notes = this.addModalNotes;
            } else {
                this.cart.push({ id: this.addModalProduct.id, name: this.addModalProduct.name, price: parseFloat(this.addModalProduct.price), quantity: this.addModalQty, notes: this.addModalNotes });
            }
            this.showAddModal = false;
        },
        removeFromCart(index) { this.cart.splice(index, 1); },
        clearCart() { if (confirm('Vider le panier ?')) this.cart = []; },
        openCheckout() {
            if (this.cart.length === 0) return;
            this.paymentTotal = this.grandTotal;
            this.cashReceived = 0;
            this.changeGiven = 0;
            this.paymentReference = '';
            this.customerName = '';
            this.customerPhone = '';
            this.paymentMethod = 'cash';
            this.showPaymentModal = true;
        },
        calculateChange() {
            const received = parseFloat(this.cashReceived) || 0;
            this.changeGiven = received - this.paymentTotal;
        },
        async processPayment() {
            if (this.isProcessing) return;
            if (this.paymentMethod === 'cash') { const r = parseFloat(this.cashReceived) || 0; if (r < this.paymentTotal) { alert('Montant insuffisant.'); return; } }
            if (this.paymentMethod === 'mobile_money' && !this.paymentReference.trim()) { alert('Référence requise.'); return; }
            if (this.paymentMethod === 'credit' && (!this.customerName.trim() || !this.customerPhone.trim())) { alert('Nom et téléphone requis.'); return; }
            this.isProcessing = true;
            try {
                let orderId = this._payingOrderId;
                if (!orderId) {
                    const cr = await fetch('{{ route("pos.order.store") }}', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                        body: JSON.stringify({
                            items: this.cart.map(i => ({ product_id: i.id, quantity: i.quantity, unit_price: i.price, notes: i.notes })),
                            payment_method: this.paymentMethod, discount_amount: 0, tax_amount: this.tax, total: this.grandTotal,
                            cash_received: parseFloat(this.cashReceived) || 0, change_given: this.changeGiven,
                            payment_reference: this.paymentReference, customer_name: this.customerName, customer_phone: this.customerPhone, table_id: this.selectedTable
                        })
                    });
                    const cd = await cr.json();
                    if (!cd.success) { alert(cd.message || 'Erreur.'); this.isProcessing = false; return; }
                    orderId = cd.order_id || cd.id;
                }
                const pr = await fetch(`/pos/order/${orderId}/pay`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                    body: JSON.stringify({ payment_method: this.paymentMethod, payment_reference: this.paymentReference, cash_received: parseFloat(this.cashReceived) || 0, customer_name: this.customerName, customer_phone: this.customerPhone })
                });
                const pd = await pr.json();
                if (pd.success) {
                    if (pd.receipt_html) { const w = window.open('', '_blank', 'width=400,height=700'); w.document.write(pd.receipt_html); w.document.close(); }
                    this.cart = []; this.showPaymentModal = false; this.selectedOrder = null; this._payingOrderId = null;
                    this.orders = this.orders.filter(o => o.id !== orderId);
                    this.orders.push({ id: orderId, status: 'payee', order_number: pd.order_number, total_amount: pd.total, items_count: 0, cashier_name: '{{ auth()->user()->name }}', created_at_human: "À l'instant", items: [] });
                } else { alert(pd.message || 'Erreur.'); }
            } catch(e) { console.error(e); alert('Erreur.'); }
            this.isProcessing = false;
        },
        selectOrder(order) { this.selectedOrder = order; },
        async markReady(order) {
            try {
                const r = await fetch(`/pos/order/${order.id}/ready`, { method: 'POST', headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content } });
                const d = await r.json();
                if (d.success) { order.status = 'en_attente'; this.selectedOrder = null; } else { alert(d.message); }
            } catch(e) { alert('Erreur.'); }
        },
        payOrder(order) {
            this.paymentTotal = order.total_amount; this.cashReceived = 0; this.changeGiven = 0;
            this.paymentReference = ''; this.customerName = ''; this.customerPhone = '';
            this.paymentMethod = 'cash'; this.showPaymentModal = true; this._payingOrderId = order.id;
        },
        cancelOrder(order) { this.cancelTargetOrder = order; this.cancelReason = ''; this.showCancelModal = true; },
        async confirmCancel() {
            if (!this.cancelReason.trim()) { alert('Motif obligatoire.'); return; }
            try {
                const r = await fetch(`/pos/order/${this.cancelTargetOrder.id}/cancel`, {
                    method: 'POST', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                    body: JSON.stringify({ reason: this.cancelReason })
                });
                const d = await r.json();
                if (d.success) { this.cancelTargetOrder.status = 'annulee'; this.showCancelModal = false; this.selectedOrder = null; } else { alert(d.message); }
            } catch(e) { alert('Erreur.'); }
        },
        printReceipt(order) { window.open(`/pos/order/${order.id}/receipt`, '_blank', 'width=400,height=700'); },
        pollOrders() {
            setInterval(async () => {
                try {
                    const r = await fetch('{{ route("pos.unsettled") }}', { headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content } });
                    if (r.ok) { const d = await r.json(); if (d.orders) { d.orders.forEach(no => { const e = this.orders.find(o => o.id === no.id); if (e) Object.assign(e, no); else this.orders.push(no); }); } }
                } catch(e) { console.error('Poll error:', e); }
            }, 10000);
        }
    };
}
</script>
</div>
@endsection
