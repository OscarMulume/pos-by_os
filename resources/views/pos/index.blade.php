@extends('layouts.pos')

@section('content')
<!-- Écran de sélection de table -->
<div x-show="!selectedTable && !showTableSelector" x-data="{ showTableSelector: false }" class="fixed inset-0 z-50 bg-gray-900/95 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="text-center">
        <h2 class="text-2xl font-bold text-white mb-2">Sélectionner une table</h2>
        <p class="text-gray-400 mb-6">Choisissez la table à servir ou "À emporter"</p>
        <div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-5 gap-3 max-w-2xl mx-auto mb-6">
            @foreach($tables ?? [] as $table)
                <button @click="selectTable({{ $table->id }}, '{{ $table->name }}')"
                        class="p-4 rounded-xl border-2 transition-all active:scale-95 {{ $table->isLibre() ? 'border-green-500/50 bg-green-500/10 hover:bg-green-500/20 text-green-400' : 'border-red-500/50 bg-red-500/10 text-red-400' }}">
                    <div class="text-lg font-bold">{{ $table->name }}</div>
                    <div class="text-xs mt-1">{{ $table->isLibre() ? 'Libre' : 'Occupée' }}</div>
                </button>
            @endforeach
        </div>
        <button @click="selectTable(null, 'À emporter')"
                class="px-6 py-3 rounded-xl border-2 border-gray-600 text-gray-300 hover:bg-gray-800 transition-all">
            🛍️ À emporter
        </button>
    </div>
</div>

<div x-data="posWorkflow()" x-init="init()" class="flex h-full gap-0">

    <!-- ═══════════════════════════════════════════════════════
         LEFT: PRODUCTS + CART (40% width)
         ═══════════════════════════════════════════════════════ -->
    <div class="flex-1 flex flex-col min-w-0 bg-white/60 dark:bg-slate-900/60 backdrop-blur-sm border-r border-gray-200/50 dark:border-slate-700/50">

        <!-- Category Tabs -->
        <div class="bg-white/80 dark:bg-slate-800/80 backdrop-blur-sm border-b border-gray-200/50 dark:border-slate-700/50 px-3 py-2 flex-shrink-0">
            <div class="flex gap-2 overflow-x-auto scrollbar-hide" style="-ms-overflow-style: none; scrollbar-width: none;">
                <button @click="activeCategory = null"
                        :class="activeCategory === null ? 'bg-slate-900 dark:bg-slate-600 text-white' : 'bg-gray-200 dark:bg-slate-700 text-gray-700 dark:text-gray-300 hover:bg-gray-300 dark:hover:bg-slate-600'"
                        class="flex-shrink-0 px-5 py-2.5 rounded-lg font-semibold text-sm transition-colors whitespace-nowrap min-h-[44px]">
                    Tous
                </button>
                <template x-for="cat in categories" :key="cat.id">
                    <button @click="activeCategory = cat.id"
                            :class="activeCategory === cat.id ? 'bg-slate-900 dark:bg-slate-600 text-white' : 'bg-gray-200 dark:bg-slate-700 text-gray-700 dark:text-gray-300 hover:bg-gray-300 dark:hover:bg-slate-600'"
                            class="flex-shrink-0 px-5 py-2.5 rounded-lg font-semibold text-sm transition-colors whitespace-nowrap min-h-[44px]"
                            x-text="cat.name">
                    </button>
                </template>
            </div>
        </div>

        <!-- Product Grid -->
        <div class="flex-1 overflow-y-auto p-3">
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3">
                <template x-for="product in filteredProducts" :key="product.id">
                    <button @click="addToCart(product)"
                            class="bg-white/90 dark:bg-slate-800/90 backdrop-blur-sm rounded-xl border border-gray-200/50 dark:border-slate-700/50 hover:border-slate-400 dark:hover:border-slate-500 hover:shadow-lg transition-all p-3 flex flex-col items-center text-center gap-2 min-h-[150px] active:scale-95 select-none">
                        <div class="w-full h-24 bg-gray-100 dark:bg-slate-700 rounded-lg flex items-center justify-center overflow-hidden flex-shrink-0">
                            <template x-if="product.image">
                                <img :src="'/storage/' + product.image" :alt="product.name" class="w-full h-full object-cover" />
                            </template>
                            <template x-if="!product.image">
                                <svg class="w-10 h-10 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                            </template>
                        </div>
                        <span class="text-sm font-medium text-gray-800 dark:text-gray-200 leading-tight line-clamp-2" x-text="product.name"></span>
                        <span class="text-base font-bold text-slate-900 dark:text-white mt-auto" x-text="formatMoney(product.price)"></span>
                    </button>
                </template>
            </div>
        </div>

        <!-- Cart Summary Bar (compact) -->
        <div class="border-t border-gray-200/50 dark:border-slate-700/50 bg-white/90 dark:bg-slate-800/90 backdrop-blur-sm px-4 py-3 flex-shrink-0">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300"><span x-text="cartItemCount"></span> article(s)</span>
                    </div>
                    <div class="text-lg font-bold text-slate-900 dark:text-white" x-text="formatMoney(grandTotal)"></div>
                </div>
                <button @click="openCheckout()"
                        :disabled="cart.length === 0"
                        :class="cart.length === 0 ? 'bg-gray-300 dark:bg-slate-700 cursor-not-allowed' : 'bg-green-600 hover:bg-green-700 active:scale-95'"
                        class="px-6 py-2.5 rounded-xl text-white font-bold text-sm shadow-lg transition-all min-h-[44px]">
                    ENCASSER
                </button>
            </div>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════
         RIGHT: ORDER WORKFLOW BOARD (60% width)
         ═══════════════════════════════════════════════════════ -->
    <div class="w-[55%] min-w-[500px] flex flex-col bg-gray-50/60 dark:bg-slate-900/40 backdrop-blur-sm">

        <!-- Workflow Header -->
        <div class="bg-white/80 dark:bg-slate-800/80 backdrop-blur-sm border-b border-gray-200/50 dark:border-slate-700/50 px-4 py-3 flex-shrink-0">
            <div class="flex items-center justify-between">
                <h2 class="text-base font-bold text-gray-800 dark:text-gray-100">Commandes du Jour</h2>
                <div class="flex items-center gap-3 text-xs">
                    <span class="flex items-center gap-1.5">
                        <span class="w-3 h-3 rounded-full bg-blue-500 animate-pulse"></span>
                        <span class="text-gray-600 dark:text-gray-400" x-text="enCoursCount + ' en cours'"></span>
                    </span>
                    <span class="flex items-center gap-1.5">
                        <span class="w-3 h-3 rounded-full bg-amber-500 animate-pulse"></span>
                        <span class="text-gray-600 dark:text-gray-400" x-text="enAttenteCount + ' prêtes'"></span>
                    </span>
                    <span class="flex items-center gap-1.5">
                        <span class="w-3 h-3 rounded-full bg-green-500"></span>
                        <span class="text-gray-600 dark:text-gray-400" x-text="payeeCount + ' payées'"></span>
                    </span>
                </div>
            </div>
        </div>

        <!-- 3-Column Kanban Board -->
        <div class="flex-1 overflow-hidden flex gap-3 p-3">

            <!-- Column: EN COURS -->
            <div class="flex-1 flex flex-col min-w-0 bg-blue-50/50 dark:bg-blue-950/20 rounded-xl border border-blue-200/50 dark:border-blue-800/30">
                <div class="px-3 py-2 border-b border-blue-200/50 dark:border-blue-800/30 flex items-center justify-between flex-shrink-0">
                    <div class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-blue-500 animate-pulse"></span>
                        <span class="text-xs font-bold text-blue-700 dark:text-blue-300 uppercase tracking-wider">En Cours</span>
                    </div>
                    <span class="text-xs font-bold text-blue-600 dark:text-blue-400 bg-blue-100 dark:bg-blue-900/40 px-2 py-0.5 rounded-full" x-text="enCoursOrders.length"></span>
                </div>
                <div class="flex-1 overflow-y-auto p-2 space-y-2">
                    <template x-for="order in enCoursOrders" :key="order.id">
                        <div class="order-card order-card-en_cours bg-white/90 dark:bg-slate-800/90 backdrop-blur-sm p-3 cursor-pointer"
                             @click="selectOrder(order)">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-sm font-bold text-gray-800 dark:text-gray-200" x-text="order.order_number"></span>
                                <span class="text-xs text-gray-500 dark:text-gray-400" x-text="order.created_at_human"></span>
                            </div>
                            <div class="text-xs text-gray-600 dark:text-gray-400 mb-2" x-text="order.items_count + ' article(s)'"></div>
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-bold text-slate-900 dark:text-white" x-text="formatMoney(order.total_amount)"></span>
                                <span class="text-xs text-gray-500 dark:text-gray-400" x-text="order.cashier_name"></span>
                            </div>
                        </div>
                    </template>
                    <div x-show="enCoursOrders.length === 0" class="flex flex-col items-center justify-center h-32 text-gray-400 dark:text-gray-600">
                        <svg class="w-10 h-10 mb-2 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                        <p class="text-xs">Aucune commande en cours</p>
                    </div>
                </div>
            </div>

            <!-- Column: EN ATTENTE (PRÊTE) -->
            <div class="flex-1 flex flex-col min-w-0 bg-amber-50/50 dark:bg-amber-950/20 rounded-xl border border-amber-200/50 dark:border-amber-800/30">
                <div class="px-3 py-2 border-b border-amber-200/50 dark:border-amber-800/30 flex items-center justify-between flex-shrink-0">
                    <div class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-amber-500 animate-pulse"></span>
                        <span class="text-xs font-bold text-amber-700 dark:text-amber-300 uppercase tracking-wider">Prête</span>
                    </div>
                    <span class="text-xs font-bold text-amber-600 dark:text-amber-400 bg-amber-100 dark:bg-amber-900/40 px-2 py-0.5 rounded-full" x-text="enAttenteOrders.length"></span>
                </div>
                <div class="flex-1 overflow-y-auto p-2 space-y-2">
                    <template x-for="order in enAttenteOrders" :key="order.id">
                        <div class="order-card order-card-en_attente bg-white/90 dark:bg-slate-800/90 backdrop-blur-sm p-3 cursor-pointer"
                             @click="selectOrder(order)">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-sm font-bold text-gray-800 dark:text-gray-200" x-text="order.order_number"></span>
                                <span class="text-xs text-gray-500 dark:text-gray-400" x-text="order.created_at_human"></span>
                            </div>
                            <div class="text-xs text-gray-600 dark:text-gray-400 mb-2" x-text="order.items_count + ' article(s)'"></div>
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-bold text-slate-900 dark:text-white" x-text="formatMoney(order.total_amount)"></span>
                                <span class="text-xs text-gray-500 dark:text-gray-400" x-text="order.cashier_name"></span>
                            </div>
                        </div>
                    </template>
                    <div x-show="enAttenteOrders.length === 0" class="flex flex-col items-center justify-center h-32 text-gray-400 dark:text-gray-600">
                        <svg class="w-10 h-10 mb-2 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 13l4 4L19 7"/></svg>
                        <p class="text-xs">Aucune commande prête</p>
                    </div>
                </div>
            </div>

            <!-- Column: PAYÉE -->
            <div class="flex-1 flex flex-col min-w-0 bg-green-50/50 dark:bg-green-950/20 rounded-xl border border-green-200/50 dark:border-green-800/30">
                <div class="px-3 py-2 border-b border-green-200/50 dark:border-green-800/30 flex items-center justify-between flex-shrink-0">
                    <div class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-green-500"></span>
                        <span class="text-xs font-bold text-green-700 dark:text-green-300 uppercase tracking-wider">Payée</span>
                    </div>
                    <span class="text-xs font-bold text-green-600 dark:text-green-400 bg-green-100 dark:bg-green-900/40 px-2 py-0.5 rounded-full" x-text="payeeOrders.length"></span>
                </div>
                <div class="flex-1 overflow-y-auto p-2 space-y-2">
                    <template x-for="order in payeeOrders" :key="order.id">
                        <div class="order-card order-card-payee bg-white/90 dark:bg-slate-800/90 backdrop-blur-sm p-3 cursor-pointer opacity-80"
                             @click="selectOrder(order)">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-sm font-bold text-gray-800 dark:text-gray-200" x-text="order.order_number"></span>
                                <span class="text-xs text-gray-500 dark:text-gray-400" x-text="order.created_at_human"></span>
                            </div>
                            <div class="text-xs text-gray-600 dark:text-gray-400 mb-2" x-text="order.items_count + ' article(s)'"></div>
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-bold text-green-700 dark:text-green-400" x-text="formatMoney(order.total_amount)"></span>
                                <span class="text-xs px-2 py-0.5 rounded-full bg-green-100 dark:bg-green-900/40 text-green-700 dark:text-green-300 font-medium">✓ Payée</span>
                            </div>
                        </div>
                    </template>
                    <div x-show="payeeOrders.length === 0" class="flex flex-col items-center justify-center h-32 text-gray-400 dark:text-gray-600">
                        <svg class="w-10 h-10 mb-2 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <p class="text-xs">Aucune commande payée</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════
         ORDER DETAIL SIDEBAR (slide-over)
         ═══════════════════════════════════════════════════════ -->
    <div x-show="selectedOrder"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="translate-x-full"
         x-transition:enter-end="translate-x-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="translate-x-0"
         x-transition:leave-end="translate-x-full"
         class="fixed right-0 top-14 bottom-0 w-[400px] bg-white/95 dark:bg-slate-800/95 backdrop-blur-xl shadow-2xl border-l border-gray-200/50 dark:border-slate-700/50 z-40 flex flex-col"
         style="display: none;">

        <template x-if="selectedOrder">
            <div class="flex flex-col h-full">
                <!-- Header -->
                <div class="px-4 py-3 border-b border-gray-200/50 dark:border-slate-700/50 flex items-center justify-between flex-shrink-0">
                    <div>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white" x-text="selectedOrder.order_number"></h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400" x-text="selectedOrder.created_at_human + ' · ' + selectedOrder.cashier_name"></p>
                    </div>
                    <button @click="selectedOrder = null" class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-slate-700 transition">
                        <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <!-- Workflow Progress -->
                <div class="px-4 py-3 border-b border-gray-200/50 dark:border-slate-700/50 flex-shrink-0">
                    <div class="flex items-center justify-between">
                        <div class="flex flex-col items-center">
                            <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold"
                                 :class="true ? 'bg-blue-500 text-white' : 'bg-gray-200 dark:bg-slate-700 text-gray-500'">1</div>
                            <span class="text-[10px] mt-1 text-gray-500 dark:text-gray-400">En cours</span>
                        </div>
                        <div class="flex-1 h-1 mx-2 rounded-full" :class="selectedOrder.status !== 'en_cours' ? 'bg-blue-500' : 'bg-gray-200 dark:bg-slate-700'"></div>
                        <div class="flex flex-col items-center">
                            <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold"
                                 :class="selectedOrder.status === 'en_attente' || selectedOrder.status === 'payee' ? 'bg-amber-500 text-white' : 'bg-gray-200 dark:bg-slate-700 text-gray-500'">2</div>
                            <span class="text-[10px] mt-1 text-gray-500 dark:text-gray-400">Prête</span>
                        </div>
                        <div class="flex-1 h-1 mx-2 rounded-full" :class="selectedOrder.status === 'payee' ? 'bg-green-500' : 'bg-gray-200 dark:bg-slate-700'"></div>
                        <div class="flex flex-col items-center">
                            <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold"
                                 :class="selectedOrder.status === 'payee' ? 'bg-green-500 text-white' : 'bg-gray-200 dark:bg-slate-700 text-gray-500'">3</div>
                            <span class="text-[10px] mt-1 text-gray-500 dark:text-gray-400">Payée</span>
                        </div>
                    </div>
                </div>

                <!-- Order Items -->
                <div class="flex-1 overflow-y-auto px-4 py-3">
                    <template x-for="item in selectedOrder.items" :key="item.id">
                        <div class="flex items-center justify-between py-2 border-b border-gray-100 dark:border-slate-700/50">
                            <div>
                                <p class="text-sm font-medium text-gray-800 dark:text-gray-200" x-text="item.product_name || item.name"></p>
                                <p class="text-xs text-gray-500 dark:text-gray-400" x-text="item.quantity + ' × ' + formatMoney(item.price_at_sale || item.unit_price)"></p>
                            </div>
                            <span class="text-sm font-bold text-gray-800 dark:text-gray-200" x-text="formatMoney(item.subtotal || (item.quantity * (item.price_at_sale || item.unit_price)))"></span>
                        </div>
                    </template>
                </div>

                <!-- Total + Actions -->
                <div class="border-t border-gray-200/50 dark:border-slate-700/50 px-4 py-3 flex-shrink-0 space-y-3">
                    <div class="flex justify-between text-lg font-bold text-gray-900 dark:text-white">
                        <span>Total</span>
                        <span x-text="formatMoney(selectedOrder.total_amount)"></span>
                    </div>

                    <!-- Action buttons based on status -->
                    <div class="grid grid-cols-2 gap-2">
                        <!-- Mark Ready (en_cours → en_attente) -->
                        <button x-show="selectedOrder.status === 'en_cours'"
                                @click="markReady(selectedOrder)"
                                class="col-span-2 py-3 rounded-xl bg-amber-500 hover:bg-amber-600 text-white font-bold text-sm transition-all active:scale-95 flex items-center justify-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            Marquer Prête
                        </button>

                        <!-- Pay (en_cours/en_attente → payee) -->
                        <button x-show="selectedOrder.status === 'en_cours' || selectedOrder.status === 'en_attente'"
                                @click="payOrder(selectedOrder)"
                                class="col-span-2 py-3 rounded-xl bg-green-600 hover:bg-green-700 text-white font-bold text-sm transition-all active:scale-95 flex items-center justify-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                            Encaisser
                        </button>

                        <!-- Print Receipt -->
                        <button @click="printReceipt(selectedOrder)"
                                class="py-2.5 rounded-xl border-2 border-gray-300 dark:border-slate-600 text-gray-700 dark:text-gray-300 font-medium text-sm hover:bg-gray-50 dark:hover:bg-slate-700 transition-all flex items-center justify-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                            Reçu
                        </button>

                        <!-- Cancel -->
                        <button x-show="selectedOrder.status !== 'annulee' && selectedOrder.status !== 'payee'"
                                @click="cancelOrder(selectedOrder)"
                                class="py-2.5 rounded-xl border-2 border-red-300 dark:border-red-800 text-red-600 dark:text-red-400 font-medium text-sm hover:bg-red-50 dark:hover:bg-red-900/20 transition-all flex items-center justify-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            Annuler
                        </button>
                    </div>
                </div>
            </div>
        </template>
    </div>

    <!-- ═══════════════════════════════════════════════════════
         PAYMENT MODAL
         ═══════════════════════════════════════════════════════ -->
    <div x-show="showPaymentModal"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         class="fixed inset-0 z-50 bg-black/50 backdrop-blur-sm flex items-center justify-center p-4"
         @click.self="showPaymentModal = false"
         style="display: none;">
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-2xl w-full max-w-md overflow-hidden">
            <div class="bg-slate-900 dark:bg-slate-950 text-white px-5 py-4 flex items-center justify-between">
                <h3 class="text-lg font-bold">Paiement</h3>
                <span class="text-2xl font-bold" x-text="formatMoney(paymentTotal)"></span>
            </div>
            <div class="p-5 space-y-5">
                <div>
                    <label class="text-sm font-semibold text-gray-700 dark:text-gray-300 block mb-3">Mode de paiement</label>
                    <div class="grid grid-cols-3 gap-2">
                        <label :class="paymentMethod === 'cash' ? 'border-green-500 bg-green-50 dark:bg-green-900/30 ring-2 ring-green-500' : 'border-gray-200 dark:border-slate-600 hover:border-gray-400'"
                               class="flex flex-col items-center gap-1 border-2 rounded-xl py-3 px-2 cursor-pointer transition-all">
                            <input type="radio" x-model="paymentMethod" value="cash" class="sr-only" />
                            <svg class="w-7 h-7" :class="paymentMethod === 'cash' ? 'text-green-600' : 'text-gray-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                            <span class="text-xs font-medium" :class="paymentMethod === 'cash' ? 'text-green-700 dark:text-green-300' : 'text-gray-600 dark:text-gray-400'">Cash</span>
                        </label>
                        <label :class="paymentMethod === 'mobile_money' ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/30 ring-2 ring-blue-500' : 'border-gray-200 dark:border-slate-600 hover:border-gray-400'"
                               class="flex flex-col items-center gap-1 border-2 rounded-xl py-3 px-2 cursor-pointer transition-all">
                            <input type="radio" x-model="paymentMethod" value="mobile_money" class="sr-only" />
                            <svg class="w-7 h-7" :class="paymentMethod === 'mobile_money' ? 'text-blue-600' : 'text-gray-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" /></svg>
                            <span class="text-xs font-medium" :class="paymentMethod === 'mobile_money' ? 'text-blue-700 dark:text-blue-300' : 'text-gray-600 dark:text-gray-400'">Mobile Money</span>
                        </label>
                        <label :class="paymentMethod === 'credit' ? 'border-orange-500 bg-orange-50 dark:bg-orange-900/30 ring-2 ring-orange-500' : 'border-gray-200 dark:border-slate-600 hover:border-gray-400'"
                               class="flex flex-col items-center gap-1 border-2 rounded-xl py-3 px-2 cursor-pointer transition-all">
                            <input type="radio" x-model="paymentMethod" value="credit" class="sr-only" />
                            <svg class="w-7 h-7" :class="paymentMethod === 'credit' ? 'text-orange-600' : 'text-gray-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                            <span class="text-xs font-medium" :class="paymentMethod === 'credit' ? 'text-orange-700 dark:text-orange-300' : 'text-gray-600 dark:text-gray-400'">Crédit</span>
                        </label>
                    </div>
                </div>

                <!-- Cash fields -->
                <template x-if="paymentMethod === 'cash'">
                    <div class="space-y-3">
                        <div>
                            <label class="text-sm font-medium text-gray-700 dark:text-gray-300 block mb-1">Montant reçu</label>
                            <input type="number" x-model.number="cashReceived" min="0" step="0.01" @input="calculateChange()"
                                   class="w-full border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-lg px-4 py-3 text-lg text-right font-semibold min-h-[48px] focus:ring-2 focus:ring-green-500 focus:border-green-500"
                                   placeholder="0.00" />
                        </div>
                        <div class="rounded-xl p-4 text-center" :class="changeGiven >= 0 ? 'bg-green-100 dark:bg-green-900/30' : 'bg-red-100 dark:bg-red-900/30'">
                            <p class="text-xs uppercase tracking-wide mb-1" :class="changeGiven >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'">Monnaie à rendre</p>
                            <p class="text-3xl font-bold" :class="changeGiven >= 0 ? 'text-green-700 dark:text-green-300' : 'text-red-700 dark:text-red-300'" x-text="formatMoney(Math.abs(changeGiven))"></p>
                        </div>
                    </div>
                </template>

                <!-- Mobile Money ref -->
                <template x-if="paymentMethod === 'mobile_money'">
                    <div>
                        <label class="text-sm font-medium text-gray-700 dark:text-gray-300 block mb-1">Référence de transaction</label>
                        <input type="text" x-model="paymentReference"
                               class="w-full border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-lg px-4 py-3 text-sm min-h-[48px] focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                               placeholder="Ex: MOMO-XXXX-XXXX" />
                    </div>
                </template>

                <!-- Credit fields -->
                <template x-if="paymentMethod === 'credit'">
                    <div class="space-y-3">
                        <div>
                            <label class="text-sm font-medium text-gray-700 dark:text-gray-300 block mb-1">Nom du client</label>
                            <input type="text" x-model="customerName"
                                   class="w-full border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-lg px-4 py-3 text-sm min-h-[48px] focus:ring-2 focus:ring-orange-500 focus:border-orange-500"
                                   placeholder="Nom complet" />
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-700 dark:text-gray-300 block mb-1">Téléphone</label>
                            <input type="tel" x-model="customerPhone"
                                   class="w-full border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-lg px-4 py-3 text-sm min-h-[48px] focus:ring-2 focus:ring-orange-500 focus:border-orange-500"
                                   placeholder="+221 XX XXX XX XX" />
                        </div>
                    </div>
                </template>
            </div>

            <div class="px-5 py-4 border-t border-gray-200 dark:border-slate-700 flex gap-3">
                <button @click="showPaymentModal = false" class="flex-1 py-3 rounded-xl border-2 border-gray-300 dark:border-slate-600 text-gray-700 dark:text-gray-300 font-semibold text-sm hover:bg-gray-100 dark:hover:bg-slate-700 transition-colors min-h-[48px]">Annuler</button>
                <button @click="processPayment()" :disabled="isProcessing"
                        :class="isProcessing ? 'bg-gray-400 cursor-not-allowed' : 'bg-green-600 hover:bg-green-700 active:scale-95'"
                        class="flex-1 py-3 rounded-xl text-white font-semibold text-sm shadow-md transition-all min-h-[48px]">
                    <span x-show="!isProcessing">Valider le paiement</span>
                    <span x-show="isProcessing" class="flex items-center justify-center gap-2">
                        <svg class="animate-spin h-5 w-5" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none" /><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" /></svg>
                        Traitement...
                    </span>
                </button>
            </div>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════
         CANCEL MODAL
         ═══════════════════════════════════════════════════════ -->
    <div x-show="showCancelModal"
         class="fixed inset-0 z-50 bg-black/50 backdrop-blur-sm flex items-center justify-center p-4"
         @click.self="showCancelModal = false"
         style="display: none;">
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-2xl w-full max-w-sm overflow-hidden">
            <div class="bg-red-600 text-white px-5 py-4">
                <h3 class="text-lg font-bold">Annuler la commande</h3>
            </div>
            <div class="p-5 space-y-4">
                <div>
                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300 block mb-1">Motif d'annulation <span class="text-red-500">*</span></label>
                    <textarea x-model="cancelReason" rows="3" required
                              class="w-full border border-gray-300 dark:border-slate-600 dark:bg-slate-700 dark:text-white rounded-lg px-4 py-3 text-sm focus:ring-2 focus:ring-red-500 focus:border-red-500"
                              placeholder="Ex: Client a changé d'avis, rupture de stock..."></textarea>
                </div>
                <label class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                    <input type="checkbox" x-model="cancelRuptureStock" class="rounded border-gray-300 text-red-600 focus:ring-red-500" />
                    Rupture de stock (désactiver les produits concernés)
                </label>
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
        // Data
        products: @json($products ?? []),
        categories: @json($categories ?? []),
        activeCategory: null,
        cart: [],
        orders: @json($todayOrders ?? []),
        selectedOrder: null,
        showPaymentModal: false,
        showCancelModal: false,
        cancelReason: '',
        cancelRuptureStock: false,
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

        // Computed
        get filteredProducts() {
            if (!this.activeCategory) return this.products;
            return this.products.filter(p => p.category_id === this.activeCategory);
        },
        get cartItemCount() {
            return this.cart.reduce((sum, item) => sum + item.quantity, 0);
        },
        get subtotal() {
            return this.cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
        },
        get discount() { return 0; },
        get tax() { return this.subtotal * (this.taxRate / 100); },
        get grandTotal() { return Math.max(0, this.subtotal + this.tax); },

        get enCoursOrders() { return this.orders.filter(o => o.status === 'en_cours'); },
        get enAttenteOrders() { return this.orders.filter(o => o.status === 'en_attente'); },
        get payeeOrders() { return this.orders.filter(o => o.status === 'payee' || o.status === 'paid'); },
        get enCoursCount() { return this.enCoursOrders.length; },
        get enAttenteCount() { return this.enAttenteOrders.length; },
        get payeeCount() { return this.payeeOrders.length; },

        // Init
        init() {
            this.pollOrders();
        },

        selectTable(tableId, tableName) {
            this.selectedTable = tableId;
            this.selectedTableName = tableName;
        },

        // Poll for new orders every 10s
        pollOrders() {
            setInterval(async () => {
                try {
                    const resp = await fetch('{{ route("pos.unsettled") }}', {
                        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
                    });
                    if (resp.ok) {
                        const data = await resp.json();
                        if (data.orders) {
                            // Merge new orders
                            data.orders.forEach(no => {
                                const existing = this.orders.find(o => o.id === no.id);
                                if (existing) { Object.assign(existing, no); }
                                else { this.orders.push(no); }
                            });
                        }
                    }
                } catch(e) { console.error('Poll error:', e); }
            }, 10000);
        },

        formatMoney(amount) {
            const val = parseFloat(amount) || 0;
            return new Intl.NumberFormat('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(val) + ' FCFA';
        },

        addToCart(product) {
            const existing = this.cart.find(item => item.id === product.id);
            if (existing) { existing.quantity++; }
            else { this.cart.push({ id: product.id, name: product.name, price: parseFloat(product.price), quantity: 1 }); }
        },

        // Checkout: create order then pay
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

        selectOrder(order) {
            this.selectedOrder = order;
        },

        async markReady(order) {
            try {
                const resp = await fetch(`/pos/order/${order.id}/ready`, {
                    method: 'POST',
                    headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
                });
                const data = await resp.json();
                if (data.success) {
                    order.status = 'en_attente';
                    this.selectedOrder = null;
                } else { alert(data.message); }
            } catch(e) { alert('Erreur de connexion.'); }
        },

        payOrder(order) {
            this.paymentTotal = order.total_amount;
            this.cashReceived = 0;
            this.changeGiven = 0;
            this.paymentReference = '';
            this.customerName = '';
            this.customerPhone = '';
            this.paymentMethod = 'cash';
            this.showPaymentModal = true;
            this._payingOrderId = order.id;
        },

        async processPayment() {
            if (this.isProcessing) return;
            // Validation
            if (this.paymentMethod === 'cash') {
                const received = parseFloat(this.cashReceived) || 0;
                if (received < this.paymentTotal) { alert('Le montant reçu est insuffisant.'); return; }
            }
            if (this.paymentMethod === 'mobile_money' && !this.paymentReference.trim()) {
                alert('Veuillez saisir la référence.'); return;
            }
            if (this.paymentMethod === 'credit' && (!this.customerName.trim() || !this.customerPhone.trim())) {
                alert('Nom et téléphone requis.'); return;
            }

            this.isProcessing = true;

            // If we have a selected order (paying existing), use pay endpoint
            // Otherwise, create new order first
            try {
                let orderId = this._payingOrderId;

                if (!orderId) {
                    // Create order first
                    const createResp = await fetch('{{ route("pos.order.store") }}', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                        body: JSON.stringify({
                            items: this.cart.map(item => ({ product_id: item.id, quantity: item.quantity, unit_price: item.price })),
                            payment_method: this.paymentMethod,
                            discount_amount: 0,
                            tax_amount: this.tax,
                            total: this.grandTotal,
                            cash_received: parseFloat(this.cashReceived) || 0,
                            change_given: this.changeGiven,
                            payment_reference: this.paymentReference,
                            customer_name: this.customerName,
                            customer_phone: this.customerPhone,
                            table_id: this.selectedTable
                        })
                    });
                    const createData = await createResp.json();
                    if (!createData.success) { alert(createData.message || 'Erreur.'); this.isProcessing = false; return; }
                    orderId = createData.order_id || createData.id;
                }

                // Pay the order
                const payResp = await fetch(`/pos/order/${orderId}/pay`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                    body: JSON.stringify({
                        payment_method: this.paymentMethod,
                        payment_reference: this.paymentReference,
                        cash_received: parseFloat(this.cashReceived) || 0,
                        customer_name: this.customerName,
                        customer_phone: this.customerPhone
                    })
                });
                const payData = await payResp.json();
                if (payData.success) {
                    // Print receipt
                    if (payData.receipt_html) {
                        const w = window.open('', '_blank', 'width=400,height=700');
                        w.document.write(payData.receipt_html);
                        w.document.close();
                    }
                    // Reset
                    this.cart = [];
                    this.showPaymentModal = false;
                    this.selectedOrder = null;
                    this._payingOrderId = null;
                    // Refresh orders
                    this.orders = this.orders.filter(o => o.id !== orderId);
                    this.orders.push({ id: orderId, status: 'payee', order_number: payData.order_number, total_amount: payData.total, items_count: 0, cashier_name: '{{ auth()->user()->name }}', created_at_human: 'À l\'instant', items: [] });
                } else { alert(payData.message || 'Erreur de paiement.'); }
            } catch(e) { console.error(e); alert('Erreur de connexion.'); }
            this.isProcessing = false;
        },

        cancelOrder(order) {
            this.cancelTargetOrder = order;
            this.cancelReason = '';
            this.cancelRuptureStock = false;
            this.showCancelModal = true;
        },

        async confirmCancel() {
            if (!this.cancelReason.trim()) { alert('Le motif est obligatoire.'); return; }
            try {
                const resp = await fetch(`/pos/order/${this.cancelTargetOrder.id}/cancel`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                    body: JSON.stringify({ reason: this.cancelReason, rupture_stock: this.cancelRuptureStock })
                });
                const data = await resp.json();
                if (data.success) {
                    this.cancelTargetOrder.status = 'annulee';
                    this.showCancelModal = false;
                    this.selectedOrder = null;
                } else { alert(data.message); }
            } catch(e) { alert('Erreur de connexion.'); }
        },

        printReceipt(order) {
            window.open(`/pos/order/${order.id}/receipt`, '_blank', 'width=400,height=700');
        }
    };
}
</script>
@endsection
