<!DOCTYPE html>
<html lang="fr" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>KDS — {{ $restaurant->name ?? 'Cuisine' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        html, body { height: 100%; overflow: hidden; }
        @keyframes urgentPulse {
            0%, 100% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.5); }
            50% { box-shadow: 0 0 0 15px rgba(239, 68, 68, 0); }
        }
        .urgent-pulse { animation: urgentPulse 1s ease-in-out infinite; }
        @keyframes gentlePulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }
        .gentle-pulse { animation: gentlePulse 2s ease-in-out infinite; }
    </style>
</head>
<body class="h-full bg-gray-900 text-white" x-data="kds()" x-init="init()">
    <!-- Header -->
    <header class="bg-gray-800 border-b border-gray-700 px-4 py-3 flex items-center justify-between h-14 flex-shrink-0">
        <div class="flex items-center gap-3">
            <svg class="w-6 h-6 text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            <span class="text-lg font-bold">{{ $restaurant->name ?? 'Cuisine' }}</span>
            <span class="text-xs bg-orange-500/20 text-orange-400 px-2 py-0.5 rounded-full font-medium">KDS</span>
        </div>
        <div class="flex items-center gap-4 text-sm">
            <div class="flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full bg-yellow-500 animate-pulse"></span>
                <span class="text-gray-400" x-text="enAttenteCount + ' en attente'"></span>
            </div>
            <div class="flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full bg-blue-500 animate-pulse"></span>
                <span class="text-gray-400" x-text="enPrepCount + ' en préparation'"></span>
            </div>
            <span class="text-gray-500">|</span>
            <span class="text-gray-300 font-mono text-lg" x-text="clock"></span>
        </div>
    </header>

    <!-- Kanban Board -->
    <div class="flex-1 flex gap-4 p-4 overflow-hidden" style="height: calc(100vh - 3.5rem);">

        <!-- Column: EN ATTENTE -->
        <div class="flex-1 flex flex-col min-w-0 bg-yellow-500/5 rounded-xl border border-yellow-500/20">
            <div class="px-4 py-3 border-b border-yellow-500/20 flex items-center justify-between flex-shrink-0">
                <div class="flex items-center gap-2">
                    <span class="w-3 h-3 rounded-full bg-yellow-500 animate-pulse"></span>
                    <span class="text-sm font-bold text-yellow-400 uppercase tracking-wider">En Attente</span>
                </div>
                <span class="text-xs font-bold text-yellow-400 bg-yellow-500/20 px-2.5 py-1 rounded-full" x-text="enAttenteOrders.length"></span>
            </div>
            <div class="flex-1 overflow-y-auto p-3 space-y-3">
                <template x-for="order in enAttenteOrders" :key="order.id">
                    <div class="bg-gray-800 rounded-xl border border-yellow-500/30 p-4 cursor-pointer hover:border-yellow-400/50 transition-all"
                         :class="isUrgent(order) ? 'urgent-pulse border-red-500' : (isWarning(order) ? 'gentle-pulse' : '')"
                         @click="startPrep(order)">
                        <div class="flex items-center justify-between mb-3">
                            <div class="flex items-center gap-2">
                                <span class="text-lg font-bold text-white" x-text="order.table_name || 'À emporter'"></span>
                                <span class="text-xs text-gray-500" x-text="order.order_number"></span>
                            </div>
                            <!-- Minuteur -->
                            <div class="flex items-center gap-2">
                                <span class="text-xs px-2 py-0.5 rounded-full font-bold"
                                      :class="getTimerClass(order)"
                                      x-text="getTimerDisplay(order)"></span>
                            </div>
                        </div>
                        <!-- Items avec prep_time individuel -->
                        <div class="space-y-1.5 mb-3">
                            <template x-for="item in order.items" :key="item.id">
                                <div class="flex items-center justify-between text-sm">
                                    <div class="flex items-center gap-2">
                                        <span class="font-bold text-white" x-text="item.quantity + '×'"></span>
                                        <span class="text-gray-300" x-text="item.product_name"></span>
                                    </div>
                                    <span class="text-xs px-1.5 py-0.5 rounded"
                                          :class="getItemTimerClass(order, item)"
                                          x-text="item.prep_time + ' min'"></span>
                                </div>
                                <p class="text-xs text-orange-300/70 ml-4" x-show="item.notes" x-text="'📝 ' + item.notes"></p>
                            </template>
                        </div>
                        <!-- Bar/Counter indicators -->
                        <div class="flex gap-2 mb-2" x-show="order.has_bar_items || order.has_counter_items">
                            <span class="text-xs px-2 py-0.5 rounded bg-purple-500/20 text-purple-300" x-show="order.has_bar_items">🍸 Bar</span>
                            <span class="text-xs px-2 py-0.5 rounded bg-gray-500/20 text-gray-300" x-show="order.has_counter_items">📦 Comptoir</span>
                        </div>
                        <div class="flex items-center justify-between pt-2 border-t border-gray-700">
                            <span class="text-xs text-gray-500" x-text="order.cashier_name"></span>
                            <button class="px-4 py-1.5 bg-yellow-500 hover:bg-yellow-600 text-gray-900 font-bold text-xs rounded-lg transition-all active:scale-95">
                                ▶ Commencer
                            </button>
                        </div>
                    </div>
                </template>
                <div x-show="enAttenteOrders.length === 0" class="flex flex-col items-center justify-center h-40 text-gray-600">
                    <svg class="w-12 h-12 mb-2 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 13l4 4L19 7"/></svg>
                    <p class="text-sm">Aucune commande en attente</p>
                </div>
            </div>
        </div>

        <!-- Column: EN PRÉPARATION -->
        <div class="flex-1 flex flex-col min-w-0 bg-blue-500/5 rounded-xl border border-blue-500/20">
            <div class="px-4 py-3 border-b border-blue-500/20 flex items-center justify-between flex-shrink-0">
                <div class="flex items-center gap-2">
                    <span class="w-3 h-3 rounded-full bg-blue-500 animate-pulse"></span>
                    <span class="text-sm font-bold text-blue-400 uppercase tracking-wider">En Préparation</span>
                </div>
                <span class="text-xs font-bold text-blue-400 bg-blue-500/20 px-2.5 py-1 rounded-full" x-text="enPrepOrders.length"></span>
            </div>
            <div class="flex-1 overflow-y-auto p-3 space-y-3">
                <template x-for="order in enPrepOrders" :key="order.id">
                    <div class="bg-gray-800 rounded-xl border border-blue-500/30 p-4 cursor-pointer hover:border-blue-400/50 transition-all"
                         :class="isUrgent(order) ? 'urgent-pulse border-red-500' : (isWarning(order) ? 'gentle-pulse' : '')"
                         @click="markReady(order)">
                        <div class="flex items-center justify-between mb-3">
                            <div class="flex items-center gap-2">
                                <span class="text-lg font-bold text-white" x-text="order.table_name || 'À emporter'"></span>
                                <span class="text-xs text-gray-500" x-text="order.order_number"></span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-xs px-2 py-0.5 rounded-full font-bold"
                                      :class="getTimerClass(order)"
                                      x-text="getTimerDisplay(order)"></span>
                            </div>
                        </div>
                        <div class="space-y-1.5 mb-3">
                            <template x-for="item in order.items" :key="item.id">
                                <div class="flex items-center justify-between text-sm">
                                    <div class="flex items-center gap-2">
                                        <span class="font-bold text-white" x-text="item.quantity + '×'"></span>
                                        <span class="text-gray-300" x-text="item.product_name"></span>
                                    </div>
                                    <span class="text-xs px-1.5 py-0.5 rounded"
                                          :class="getItemTimerClass(order, item)"
                                          x-text="item.prep_time + ' min'"></span>
                                </div>
                                <p class="text-xs text-orange-300/70 ml-4" x-show="item.notes" x-text="'📝 ' + item.notes"></p>
                            </template>
                        </div>
                        <div class="flex items-center justify-between pt-2 border-t border-gray-700">
                            <span class="text-xs text-gray-500" x-text="order.cashier_name"></span>
                            <button class="px-4 py-1.5 bg-green-500 hover:bg-green-600 text-gray-900 font-bold text-xs rounded-lg transition-all active:scale-95">
                                ✓ Prêt !
                            </button>
                        </div>
                    </div>
                </template>
                <div x-show="enPrepOrders.length === 0" class="flex flex-col items-center justify-center h-40 text-gray-600">
                    <svg class="w-12 h-12 mb-2 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <p class="text-sm">Aucune commande en préparation</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Notification sonore -->
    <audio id="kds-notify" preload="auto">
        <source src="data:audio/wav;base64,UklGRnoGAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQoGAACBhYqFbF1fdJivrJBhNjVgodDbq2EcBj+a2teleQo6l9/Ss2QdBz2Y3dKyaB8F" type="audio/wav">
    </audio>

    <script>
    function kds() {
        return {
            orders: @json($kitchenOrders ?? []),
            clock: '',
            now: Date.now(),

            get enAttenteOrders() { return this.orders.filter(o => o.kitchen_status === 'en_attente'); },
            get enPrepOrders() { return this.orders.filter(o => o.kitchen_status === 'en_preparation'); },
            get enAttenteCount() { return this.enAttenteOrders.length; },
            get enPrepCount() { return this.enPrepOrders.length; },

            init() {
                // Demander permission notifications
                if ('Notification' in window && Notification.permission === 'default') {
                    Notification.requestPermission();
                }
                // Horloge
                setInterval(() => {
                    this.clock = new Date().toLocaleTimeString('fr-FR', {hour:'2-digit',minute:'2-digit',second:'2-digit'});
                    this.now = Date.now();
                }, 1000);
                this.clock = new Date().toLocaleTimeString('fr-FR', {hour:'2-digit',minute:'2-digit',second:'2-digit'});

                // Polling toutes les 5s
                setInterval(() => this.pollOrders(), 5000);
            },

            async pollOrders() {
                try {
                    const resp = await fetch('{{ route("kds.orders") }}', {
                        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
                    });
                    if (resp.ok) {
                        const data = await resp.json();
                        if (data.orders) {
                            const hadEnAttente = this.enAttenteCount;
                            this.orders = data.orders;
                            if (this.enAttenteCount > hadEnAttente) {
                                const audio = document.getElementById('kds-notify');
                                if (audio) audio.play().catch(() => {});
                            }
                        }
                    }
                } catch(e) { console.error('KDS poll error:', e); }
            },

            // ── Minuteur dynamique ──
            getElapsedMinutes(order) {
                if (!order.sent_to_kitchen_at) return 0;
                const sent = new Date(order.sent_to_kitchen_at);
                return Math.floor((this.now - sent.getTime()) / 60000);
            },

            getTimerDisplay(order) {
                const elapsed = this.getElapsedMinutes(order);
                const maxPrep = order.max_prep_time || 15;
                const remaining = maxPrep - elapsed;
                if (remaining > 0) return remaining + ' min restantes';
                return '+' + Math.abs(remaining) min dépassé';
            },

            getTimerClass(order) {
                const elapsed = this.getElapsedMinutes(order);
                const maxPrep = order.max_prep_time || 15;
                const ratio = elapsed / maxPrep;
                if (ratio >= 1.0) return 'bg-red-500/20 text-red-400';
                if (ratio >= 0.6) return 'bg-yellow-500/20 text-yellow-400';
                return 'bg-green-500/20 text-green-400';
            },

            getItemTimerClass(order, item) {
                const elapsed = this.getElapsedMinutes(order);
                const prepTime = item.prep_time || 15;
                const ratio = elapsed / prepTime;
                if (ratio >= 1.0) return 'bg-red-500/20 text-red-400';
                if (ratio >= 0.6) return 'bg-yellow-500/20 text-yellow-400';
                return 'bg-green-500/20 text-green-400';
            },

            isUrgent(order) {
                const elapsed = this.getElapsedMinutes(order);
                const maxPrep = order.max_prep_time || 15;
                return elapsed >= maxPrep;
            },

            isWarning(order) {
                const elapsed = this.getElapsedMinutes(order);
                const maxPrep = order.max_prep_time || 15;
                return elapsed >= maxPrep * 0.6 && elapsed < maxPrep;
            },

            // ── Actions ──
            async startPrep(order) {
                try {
                    const resp = await fetch(`/kds/order/${order.id}/start-prep`, {
                        method: 'POST',
                        headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
                    });
                    const data = await resp.json();
                    if (data.success) {
                        const o = this.orders.find(o => o.id === order.id);
                        if (o) o.kitchen_status = 'en_preparation';
                    }
                } catch(e) { console.error(e); }
            },

            async markReady(order) {
                try {
                    const resp = await fetch(`/kds/order/${order.id}/mark-ready`, {
                        method: 'POST',
                        headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
                    });
                    const data = await resp.json();
                    if (data.success) {
                        // Notification visuelle + sonore
                        if ('Notification' in window && Notification.permission === 'granted') {
                            new Notification('KDS — Plat Prêt! 🔔', {
                                body: `${order.order_number} - ${order.table_name} est prêt!`,
                                icon: '/favicon.ico',
                            });
                        }
                        // Retirer de la liste KDS
                        this.orders = this.orders.filter(o => o.id !== order.id);
                        // Flash visuel
                        const flash = document.createElement('div');
                        flash.className = 'fixed inset-0 z-50 bg-green-500/20 pointer-events-none transition-opacity duration-500';
                        document.body.appendChild(flash);
                        setTimeout(() => { flash.classList.add('opacity-0'); setTimeout(() => flash.remove(), 500); }, 100);
                    }
                } catch(e) { console.error(e); }
            }
        };
    }
    </script>
</body>
</html>
