@extends('layouts.app')

@section('title', 'Rapports Financiers')

@section('content')
<div class="container mx-auto px-4 py-6">

    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Rapports Financiers</h1>
            <p class="text-sm text-gray-500 mt-1">Synthèse X/Z — Données agrégées en temps réel</p>
        </div>
        <a href="{{ route('admin.reports.export-pdf', request()->query()) }}"
           class="inline-flex items-center gap-2 px-4 py-2.5 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            Exporter PDF
        </a>
    </div>

    <!-- Filtres -->
    <form method="GET" action="{{ route('admin.reports.index') }}" class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Date de début</label>
                <input type="date" name="start_date" value="{{ request('start_date', now()->toDateString()) }}"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Date de fin</label>
                <input type="date" name="end_date" value="{{ request('end_date', now()->toDateString()) }}"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
            </div>
            @if(auth()->user()->isSuperAdmin())
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Restaurant</label>
                <select name="restaurant_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                    <option value="">Tous les restaurants</option>
                    @foreach($restaurants as $r)
                        <option value="{{ $r->id }}" {{ request('restaurant_id') == $r->id ? 'selected' : '' }}>{{ $r->name }}</option>
                    @endforeach
                </select>
            </div>
            @endif
            <div>
                <button type="submit" class="w-full bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 text-sm font-medium transition">
                    Actualiser
                </button>
            </div>
        </div>
    </form>

    <!-- Cartes Synthèse (Rapport Z) -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
            <div class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">Ventes Brutes</div>
            <div class="text-2xl font-bold text-gray-800">{{ number_format($salesSummary->gross_revenue ?? 0, 0, ',', ' ') }} <span class="text-sm font-normal text-gray-500">FC</span></div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
            <div class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">Ventes Nettes</div>
            <div class="text-2xl font-bold text-green-700">{{ number_format($paidSummary->net_revenue ?? 0, 0, ',', ' ') }} <span class="text-sm font-normal text-gray-500">FC</span></div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
            <div class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">Annulations</div>
            <div class="text-2xl font-bold text-red-600">{{ number_format($cancelledSummary->cancelled_amount ?? 0, 0, ',', ' ') }} <span class="text-sm font-normal text-gray-400">FC</span></div>
            <div class="text-xs text-gray-500 mt-1">{{ $cancelledSummary->cancelled_count ?? 0 }} ticket(s)</div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
            <div class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">Panier Moyen</div>
            <div class="text-2xl font-bold text-gray-800">{{ number_format($salesSummary->average_order_value ?? 0, 0, ',', ' ') }} <span class="text-sm font-normal text-gray-500">FC</span></div>
            <div class="text-xs text-gray-500 mt-1">{{ $salesSummary->total_orders ?? 0 }} tickets / {{ $paidSummary->paid_orders ?? 0 }} payés</div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">

        <!-- Ventes par Catégorie -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200">
            <div class="px-5 py-4 border-b border-gray-100">
                <h2 class="text-base font-bold text-gray-800">Ventes par Catégorie</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-5 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase">Catégorie</th>
                            <th class="px-5 py-2.5 text-center text-xs font-semibold text-gray-500 uppercase">Qté</th>
                            <th class="px-5 py-2.5 text-right text-xs font-semibold text-gray-500 uppercase">CA</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($salesByCategory as $cat)
                        <tr class="hover:bg-gray-50">
                            <td class="px-5 py-3 font-medium text-gray-800">{{ $cat->category_name }}</td>
                            <td class="px-5 py-3 text-center text-gray-600">{{ $cat->total_quantity }}</td>
                            <td class="px-5 py-3 text-right font-medium text-gray-800">{{ number_format($cat->total_revenue, 0, ',', ' ') }} FC</td>
                        </tr>
                        @empty
                        <tr><td colspan="3" class="px-5 py-8 text-center text-gray-400">Aucune donnée</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Modes de Paiement -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200">
            <div class="px-5 py-4 border-b border-gray-100">
                <h2 class="text-base font-bold text-gray-800">Modes de Paiement</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-5 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase">Mode</th>
                            <th class="px-5 py-2.5 text-center text-xs font-semibold text-gray-500 uppercase">Nb</th>
                            <th class="px-5 py-2.5 text-right text-xs font-semibold text-gray-500 uppercase">Montant</th>
                            <th class="px-5 py-2.5 text-right text-xs font-semibold text-gray-500 uppercase">%</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($salesByPaymentMethod as $pm)
                        <tr class="hover:bg-gray-50">
                            <td class="px-5 py-3 font-medium text-gray-800">{{ $pm->label }}</td>
                            <td class="px-5 py-3 text-center text-gray-600">{{ $pm->transaction_count }}</td>
                            <td class="px-5 py-3 text-right font-medium text-gray-800">{{ number_format($pm->total_amount, 0, ',', ' ') }} FC</td>
                            <td class="px-5 py-3 text-right">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">{{ $pm->percentage }}%</span>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="4" class="px-5 py-8 text-center text-gray-400">Aucune donnée</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Top Produits -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 mb-6">
        <div class="px-5 py-4 border-b border-gray-100">
            <h2 class="text-base font-bold text-gray-800">Top 10 Produits</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-5 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase">#</th>
                        <th class="px-5 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase">Produit</th>
                        <th class="px-5 py-2.5 text-center text-xs font-semibold text-gray-500 uppercase">Qté Vendue</th>
                        <th class="px-5 py-2.5 text-right text-xs font-semibold text-gray-500 uppercase">CA</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($topProducts as $i => $prod)
                    <tr class="hover:bg-gray-50">
                        <td class="px-5 py-3 text-gray-500 font-medium">{{ $i + 1 }}</td>
                        <td class="px-5 py-3 font-medium text-gray-800">{{ $prod->product_name }}</td>
                        <td class="px-5 py-3 text-center text-gray-600 font-bold">{{ $prod->total_quantity }}</td>
                        <td class="px-5 py-3 text-right font-medium text-gray-800">{{ number_format($prod->total_revenue, 0, ',', ' ') }} FC</td>
                    </tr>
                    @empty
                    <tr><td colspan="4" class="px-5 py-8 text-center text-gray-400">Aucune donnée</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Ventes par Restaurant (Super Admin) -->
    @if(auth()->user()->isSuperAdmin() && $salesByRestaurant->isNotEmpty())
    <div class="bg-white rounded-xl shadow-sm border border-gray-200">
        <div class="px-5 py-4 border-b border-gray-100">
            <h2 class="text-base font-bold text-gray-800">Ventes par Restaurant</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-5 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase">Restaurant</th>
                        <th class="px-5 py-2.5 text-center text-xs font-semibold text-gray-500 uppercase">Commandes</th>
                        <th class="px-5 py-2.5 text-right text-xs font-semibold text-gray-500 uppercase">Revenu</th>
                        <th class="px-5 py-2.5 text-right text-xs font-semibold text-gray-500 uppercase">Panier Moyen</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($salesByRestaurant as $sale)
                    <tr class="hover:bg-gray-50">
                        <td class="px-5 py-3 font-medium text-gray-800">{{ $sale->name }}</td>
                        <td class="px-5 py-3 text-center text-gray-600">{{ $sale->order_count }}</td>
                        <td class="px-5 py-3 text-right font-medium text-gray-800">{{ number_format($sale->total_revenue, 0, ',', ' ') }} FC</td>
                        <td class="px-5 py-3 text-right text-gray-600">{{ number_format($sale->average_order, 0, ',', ' ') }} FC</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

</div>
@endsection
