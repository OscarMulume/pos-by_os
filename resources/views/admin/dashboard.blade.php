@extends('layouts.app')
@section('title', 'Tableau de Bord')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Tableau de Bord</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ now()->translatedFormat('l d F Y') }}</p>
        </div>
        <div class="flex items-center gap-3">
            <span class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-medium bg-green-50 text-green-700 dark:bg-green-900/30 dark:text-green-400">
                <span class="w-2 h-2 rounded-full bg-green-500 mr-2"></span>
                {{ $data['terminals']->count() }} Terminal(aux)
            </span>
            <span class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-medium bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400">
                {{ $data['tables_free'] }} tables libres / {{ $data['tables_occupied'] }} occupées
            </span>
        </div>
    </div>

    <!-- Alertes stock bas -->
    @if($data['low_stock']->isNotEmpty())
    <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-xl p-4">
        <div class="flex items-center gap-2 mb-2">
            <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
            <span class="text-sm font-semibold text-amber-800 dark:text-amber-300">Alertes Stock Bas</span>
        </div>
        <div class="flex flex-wrap gap-2">
            @foreach($data['low_stock'] as $p)
                <span class="px-2 py-1 bg-amber-100 dark:bg-amber-900/40 text-amber-800 dark:text-amber-300 rounded-lg text-xs font-medium">
                    {{ $p->name }} ({{ $p->stock_quantity }} restants)
                </span>
            @endforeach
        </div>
    </div>
    @endif

    <!-- KPIs Principaux -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- CA du jour -->
        <div class="bg-white/80 dark:bg-slate-800/80 backdrop-blur-xl rounded-xl shadow-sm border border-gray-100 dark:border-slate-700/50 p-5">
            <div class="flex items-center justify-between">
                <p class="text-sm text-gray-500 dark:text-gray-400">CA du Jour</p>
                <span class="text-xs font-medium px-2 py-0.5 rounded-full {{ $data['ca']['evolution'] >= 0 ? 'bg-green-50 text-green-700 dark:bg-green-900/30 dark:text-green-400' : 'bg-red-50 text-red-700 dark:bg-red-900/30 dark:text-red-400' }}">
                    {{ $data['ca']['evolution'] >= 0 ? '+' : '' }}{{ $data['ca']['evolution'] }}% vs hier
                </span>
            </div>
            <p class="text-2xl font-bold text-gray-900 dark:text-white mt-2">{{ number_format($data['ca']['today'], 0, ',', ' ') }} <span class="text-sm font-normal text-gray-400">FC</span></p>
            <p class="text-xs text-gray-400 mt-1">Hier: {{ number_format($data['ca']['yesterday'], 0, ',', ' ') }} FC</p>
        </div>

        <!-- Commandes du jour -->
        <div class="bg-white/80 dark:bg-slate-800/80 backdrop-blur-xl rounded-xl shadow-sm border border-gray-100 dark:border-slate-700/50 p-5">
            <div class="flex items-center justify-between">
                <p class="text-sm text-gray-500 dark:text-gray-400">Commandes (jour)</p>
                <span class="text-xs font-medium px-2 py-0.5 rounded-full {{ $data['ca']['orders_evolution'] >= 0 ? 'bg-green-50 text-green-700 dark:bg-green-900/30 dark:text-green-400' : 'bg-red-50 text-red-700 dark:bg-red-900/30 dark:text-red-400' }}">
                    {{ $data['ca']['orders_evolution'] >= 0 ? '+' : '' }}{{ $data['ca']['orders_evolution'] }}% vs hier
                </span>
            </div>
            <p class="text-2xl font-bold text-gray-900 dark:text-white mt-2">{{ $data['ca']['orders_today'] }}</p>
            <div class="flex items-center gap-3 mt-1 text-xs text-gray-400">
                <span>{{ $data['ca']['en_cours'] }} en cours</span>
                <span>{{ $data['ca']['en_attente'] }} en attente</span>
                @if($data['ca']['annulees'] > 0)
                    <span class="text-red-500">{{ $data['ca']['annulees'] }} annulée(s)</span>
                @endif
            </div>
        </div>

        <!-- Top mode de paiement -->
        <div class="bg-white/80 dark:bg-slate-800/80 backdrop-blur-xl rounded-xl shadow-sm border border-gray-100 dark:border-slate-700/50 p-5">
            <p class="text-sm text-gray-500 dark:text-gray-400">Mode de Paiement #1</p>
            @if($data['top_payment_method'])
                <p class="text-2xl font-bold text-gray-900 dark:text-white mt-2 capitalize">
                    {{ $data['top_payment_method']->payment_method === 'cash' ? 'Espèces' : ($data['top_payment_method']->payment_method === 'mobile_money' ? 'Mobile Money' : 'Crédit') }}
                </p>
                <p class="text-xs text-gray-400 mt-1">{{ $data['top_payment_method']->count }} transactions · {{ number_format($data['top_payment_method']->total, 0, ',', ' ') }} FC</p>
            @else
                <p class="text-2xl font-bold text-gray-400 mt-2">—</p>
            @endif
        </div>

        <!-- Shifts ouverts -->
        <div class="bg-white/80 dark:bg-slate-800/80 backdrop-blur-xl rounded-xl shadow-sm border border-gray-100 dark:border-slate-700/50 p-5">
            <p class="text-sm text-gray-500 dark:text-gray-400">Shifts Ouverts</p>
            <p class="text-2xl font-bold text-gray-900 dark:text-white mt-2">{{ $data['open_shifts']->count() }}</p>
            <div class="mt-1 space-y-0.5">
                @foreach($data['open_shifts'] as $shift)
                    <p class="text-xs text-gray-400">{{ $shift->user->name }} — {{ $shift->opened_at->diffForHumans() }}</p>
                @endforeach
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Graphique 7 jours -->
        <div class="lg:col-span-2 bg-white/80 dark:bg-slate-800/80 backdrop-blur-xl rounded-xl shadow-sm border border-gray-100 dark:border-slate-700/50 p-5">
            <h2 class="text-base font-semibold text-gray-800 dark:text-gray-200 mb-4">Chiffre d'Affaires — 7 derniers jours</h2>
            <div class="h-64">
                <canvas id="revenueChart"></canvas>
            </div>
        </div>

        <!-- Top Produits -->
        <div class="bg-white/80 dark:bg-slate-800/80 backdrop-blur-xl rounded-xl shadow-sm border border-gray-100 dark:border-slate-700/50 p-5">
            <h2 class="text-base font-semibold text-gray-800 dark:text-gray-200 mb-4">Top Produits</h2>
            @if($data['top_products']->isNotEmpty())
                <div class="space-y-3">
                    @foreach($data['top_products'] as $i => $product)
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <span class="w-6 h-6 rounded-full bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400 text-xs font-bold flex items-center justify-center">{{ $i + 1 }}</span>
                                <div>
                                    <p class="text-sm font-medium text-gray-800 dark:text-gray-200">{{ $product->product_name }}</p>
                                    <p class="text-xs text-gray-400">{{ $product->total_qty }} vendus</p>
                                </div>
                            </div>
                            <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ number_format($product->total_revenue, 0, ',', ' ') }}</span>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-sm text-gray-400 text-center py-8">Aucune vente aujourd'hui</p>
            @endif
        </div>
    </div>

    <!-- Modes de paiement détaillés -->
    @if($data['payment_methods']->isNotEmpty())
    <div class="bg-white/80 dark:bg-slate-800/80 backdrop-blur-xl rounded-xl shadow-sm border border-gray-100 dark:border-slate-700/50 p-5">
        <h2 class="text-base font-semibold text-gray-800 dark:text-gray-200 mb-4">Répartition des Paiements</h2>
        <div class="grid grid-cols-3 gap-4">
            @foreach($data['payment_methods'] as $pm)
                <div class="text-center p-4 bg-gray-50 dark:bg-slate-700/30 rounded-xl">
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($pm->total, 0, ',', ' ') }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 capitalize">
                        {{ $pm->payment_method === 'cash' ? 'Espèces' : ($pm->payment_method === 'mobile_money' ? 'Mobile Money' : 'Crédit') }}
                    </p>
                    <p class="text-xs text-gray-400">{{ $pm->count }} transactions</p>
                </div>
            @endforeach
        </div>
    </div>
    @endif

    <!-- Dernières commandes -->
    <div class="bg-white/80 dark:bg-slate-800/80 backdrop-blur-xl rounded-xl shadow-sm border border-gray-100 dark:border-slate-700/50 p-5">
        <h2 class="text-base font-semibold text-gray-800 dark:text-gray-200 mb-4">Dernières Commandes</h2>
        @if($data['recent_orders']->isNotEmpty())
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider border-b border-gray-100 dark:border-slate-700/50">
                            <th class="pb-3">N°</th>
                            <th class="pb-3">Table</th>
                            <th class="pb-3">Caissier</th>
                            <th class="pb-3">Total</th>
                            <th class="pb-3">Statut</th>
                            <th class="pb-3">Heure</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50 dark:divide-slate-700/30">
                        @foreach($data['recent_orders'] as $order)
                            <tr class="hover:bg-gray-50 dark:hover:bg-slate-700/20 transition">
                                <td class="py-3 text-sm font-medium text-gray-800 dark:text-gray-200">{{ $order->order_number }}</td>
                                <td class="py-3 text-sm text-gray-600 dark:text-gray-400">{{ $order->table?->name ?? 'À emporter' }}</td>
                                <td class="py-3 text-sm text-gray-600 dark:text-gray-400">{{ $order->user?->name ?? '—' }}</td>
                                <td class="py-3 text-sm font-semibold text-gray-900 dark:text-white">{{ number_format($order->total_amount, 0, ',', ' ') }}</td>
                                <td class="py-3">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                                        {{ $order->status === 'payee' ? 'bg-green-50 text-green-700 dark:bg-green-900/30 dark:text-green-400' : '' }}
                                        {{ $order->status === 'en_cours' ? 'bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400' : '' }}
                                        {{ $order->status === 'en_attente' ? 'bg-yellow-50 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400' : '' }}
                                        {{ $order->status === 'annulee' ? 'bg-red-50 text-red-700 dark:bg-red-900/30 dark:text-red-400' : '' }}">
                                        {{ $order->status_label }}
                                    </span>
                                </td>
                                <td class="py-3 text-xs text-gray-400">{{ $order->created_at->format('H:i') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="text-sm text-gray-400 text-center py-8">Aucune commande aujourd'hui</p>
        @endif
    </div>
</div>

@push('scripts')
<script>
    const ctx = document.getElementById('revenueChart');
    if (ctx) {
        const chartData = @json($data['chart_7days']);
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: chartData.map(d => d.date),
                datasets: [{
                    label: 'CA (FC)',
                    data: chartData.map(d => d.revenue),
                    backgroundColor: chartData.map(d => d.is_today ? '#f59e0b' : '#6366f1'),
                    borderRadius: 6,
                    borderSkipped: false,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.05)' } },
                    x: { grid: { display: false } }
                }
            }
        });
    }
</script>
@endpush
@endsection
