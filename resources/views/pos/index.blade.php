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
        <div class="flex justify-center gap-4 mb-6 text-xs">
            <span class="flex items-center gap-1.5 text-gray-400"><span class="w-3 h-3 rounded-full bg-green-500"></span> Libre</span>
            <span class="flex items-center gap-1.5 text-gray-400"><span class="w-3 h-3 rounded-full bg-yellow-500"></span> En cuisine</span>
            <span class="flex items-center gap-1.5 text-gray-400"><span class="w-3 h-3 rounded-full bg-blue-500"></span> À encaisser</span>
            <span class="flex items-center gap-1.5 text-gray-400"><span class="w-3 h-3 rounded-full bg-red-500"></span> Occupée</span>
        </div>

        <div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-5 gap-3 mb-6">
            @foreach($tables ?? [] as $tbl)
                @php
                    $tc = $tbl->getStatusColor();
                    $tLabel = $tbl->getStatusLabel();
                    // Une table est sélectionnable si elle est libre OU si elle a une commande active
                    // (pour ajouter des items ou imprimer l'addition)
                    $isClickable = $tbl->isAvailable() || in_array($tbl->status, ['kitchen_processing', 'served_unpaid', 'occupied']);
                @endphp
                <button @click="selectTable({{ $tbl->id }}, '{{ $tbl->name }}')"
                        class="p-4 rounded-xl border-2 transition-all active:scale-95 min-h-[80px]
                               {{ $tc === 'green' ? 'border-green-500/50 bg-green-500/10 hover:bg-green-500/20 text-green-400 hover:border-green-400' : '' }}
                               {{ $tc === 'yellow' ? 'border-yellow-500/50 bg-yellow-500/10 hover:bg-yellow-500/20 text-yellow-400 hover:border-yellow-400' : '' }}
                               {{ $tc === 'blue' ? 'border-blue-500/50 bg-blue-500/10 hover:bg-blue-500/20 text-blue-400 hover:border-blue-400' : '' }}
                               {{ $tc === 'red' ? 'border-red-500/50 bg-red-500/10 hover:bg-red-500/20 text-red-400 hover:border-red-400' : '' }}"
                        {{ !$isClickable ? 'disabled' : '' }}>
                    <div class="text-lg font-bold">{{ $tbl->name }}</div>
                    <div class="text-xs mt-1">{{ $tbl->zone ? $tbl->zone : '' }}</div>
                    <div class="text-xs mt-0.5">
                        <span class="inline-flex items-center gap-1">
                            <span class="w-1.5 h-1.5 rounded-full {{ $tc === 'green' ? 'bg-green-400' : ($tc === 'yellow' ? 'bg-yellow-400 animate-pulse' : ($tc === 'blue' ? 'bg-blue-400 animate-pulse' : 'bg-red-400')) }}"></span>
                            {{ $tLabel }}
                        </span>
                    </div>
                    @if($tbl->currentOrder)
                        <div class="text-[10px] mt-1 text-gray-500">#{{ $tbl->currentOrder->order_number }}</div>
                    @endif
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
        <!-- Barre info table + bouton retour plan de salle -->
        <div class="bg-slate-800 dark:bg-slate-950 text-white px-4 py-2 flex items-center justify-between flex-shrink-0">
            <div class="flex items-center gap-3">
                <button @click="returnToFloorPlan()" class="text-xs bg-slate-700 hover:bg-slate-600 px-3 py-1 rounded-lg transition flex items-center gap-1">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    Plan de salle
                </button>
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
                            :disabled="product.track_inventory && product.stock_quantity <= 0"
                            :class="product.track_inventory && product.stock_quantity <= 0 ? 'opacity-50 cursor-not-allowed' : ''"
                            class="bg-white/90 dark:bg-slate-800/90 backdrop-blur-sm rounded-xl border border-gray-200/50 dark:border-slate-700/50 hover:border-slate-400 dark:hover:border-slate-500 hover:shadow-lg transition-all p-3 flex flex-col items-center text-center gap-2 min-h-[150px] active:scale-95 select-none relative">
                        <!-- Badge stock critique -->
                        <span x-show="product.track_inventory && product.stock_quantity <= product.stock_alert_threshold && product.stock_quantity > 0" class="absolute top-2 right-2 w-3 h-3 bg-orange-500 rounded-full" title="Stock critique"></span>
                        <span x-show="product.track_inventory && product.stock_quantity <= 0" class="absolute top-2 right-2 px-1.5 py-0.5 bg-red-500 text-white text-[9px] font-bold rounded">RUPTURE</span>
                        <!-- Badge route -->
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
        <!-- Totals + Send to Kitchen button -->
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
            <button @click="submitOrder()" :disabled="cart.length === 0 || isSubmitting"
                    :class="cart.length === 0 ? 'bg-gray-300 dark:bg-slate-700 cursor-not-allowed' : 'bg-green-600 hover:bg-green-700 active:scale-[0.98]'"
                    class="w-full py-3.5 rounded-xl text-white font-bold text-base shadow-lg transition-all mt-2">
                <span x-show="!isSubmitting && cart.length > 0">📤 Envoyer en Cuisine — <span x-text="formatMoney(grandTotal)"></span></span>
                <span x-show="!isSubmitting && cart.length === 0">Panier vide</span>
                <span x-show="isSubmitting" class="flex items-center justify-center gap-2"><svg class="animate-spin h-5 w-5" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg> Envoi...</span>
            </button>
        </div>
    </div>

    <!-- MODALE AJOUT PRODUIT (qty + notes) -->
    <div x-show="showAddModal" x-transition class="fixed inset-0 z-40 bg-black/50 backdrop-blur-sm flex items-center justify-center p-4" @click.self="showAddModal = false" style="display: none;">
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-2xl w-full max-w-sm overflow-hidden">
            <div class="bg-slate-900 dark:bg-slate-950 text-white px-5 py-4">
                <h3 class="text-lg font-bold" x-text="addModalProduct?.name ?? ''"></h3>
                <p class="text-sm text-gray-400 mt-0.5"><span x-text="formatMoney(addModalProduct?.price ?? 0)"></span><span class="text-gray-500 ml-2" x-text="formatUSD(addModalProduct?.price ?? 0)"></span></p>
                <p class="text-xs text-gray-500 mt-1">
                    <span x-show="addModalProduct?.kitchen_route === 'bar'" class="text-purple-400">🍺 Envoyé au Bar (pas de préparation cuisine)</span>
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
        currentOrderId: null,
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

        selectTable(tableId, tableName) {
            this.selectedTable = tableId;
            this.selectedTableName = tableName;
            this.tableSelected = true;
            this.cart = [];
            this.currentOrderId = null;
        },

        changeTable() {
            if (this.cart.length > 0 && !confirm('Le panier en cours sera perdu. Changer de table ?')) return;
            this.cart = [];
            this.tableSelected = false;
            this.selectedTable = null;
            this.selectedTableName = '';
            this.currentOrderId = null;
        },

        returnToFloorPlan() {
            if (this.cart.length > 0 && !confirm('Retourner au plan de salle ? Le panier en cours sera perdu.')) return;
            this.cart = [];
            this.tableSelected = false;
            this.selectedTable = null;
            this.selectedTableName = '';
            this.currentOrderId = null;
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
            // Bloquer si rupture de stock
            if (product.track_inventory && product.stock_quantity <= 0) {
                alert('❌ Produit en rupture de stock — impossible d\'ajouter au panier.');
                return;
            }
            this.addModalProduct = product;
            this.addModalQty = 1;
            this.addModalNotes = '';
            this.showAddModal = true;
        },

        confirmAddToCart() {
            if (!this.addModalProduct || this.addModalQty < 1) return;

            // Vérifier le stock disponible
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

        /**
         * ── Soumettre la commande (création + routage) ──
         * FIX BUG: Gestion propre des erreurs, validation côte serveur,
         * réponse JSON structurée, pas de 500 silencieux.
         */
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
                    // Vider le panier et retourner au plan de salle
                    this.cart = [];
                    this.currentOrderId = data.order_id;
                    alert('✅ ' + data.message);

                    // Retour automatique au plan de salle après succès
                    this.tableSelected = false;
                    this.selectedTable = null;
                    this.selectedTableName = '';
                } else {
                    // Afficher l'erreur retournée par le serveur
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
