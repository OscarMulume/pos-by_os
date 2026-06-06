@extends('layouts.superadmin')
@section('title', 'Dashboard Super-Admin')

@section('content')
<!-- Cartes statistiques -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <!-- Restaurants -->
    <div class="bg-white/80 dark:bg-slate-800/80 backdrop-blur-xl rounded-xl shadow-sm border border-gray-100 dark:border-slate-700/50 p-5">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">Restaurants</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ $stats['total_restaurants'] }}</p>
            </div>
            <div class="w-12 h-12 bg-blue-50 dark:bg-blue-900/30 rounded-xl flex items-center justify-center">
                <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
            </div>
        </div>
        <div class="mt-3 flex items-center text-xs">
            <span class="text-green-600 dark:text-green-400 font-medium">{{ $stats['active_restaurants'] }} actifs</span>
            @if($stats['suspended_restaurants'] > 0)
                <span class="text-red-500 ml-2">{{ $stats['suspended_restaurants'] }} suspendu(s)</span>
            @endif
        </div>
    </div>

    <!-- Utilisateurs -->
    <div class="bg-white/80 dark:bg-slate-800/80 backdrop-blur-xl rounded-xl shadow-sm border border-gray-100 dark:border-slate-700/50 p-5">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">Utilisateurs</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ $stats['total_users'] }}</p>
            </div>
            <div class="w-12 h-12 bg-purple-50 dark:bg-purple-900/30 rounded-xl flex items-center justify-center">
                <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
            </div>
        </div>
        <div class="mt-3 text-xs text-gray-400">Tous restaurants confondus</div>
    </div>

    <!-- Commandes du jour -->
    <div class="bg-white/80 dark:bg-slate-800/80 backdrop-blur-xl rounded-xl shadow-sm border border-gray-100 dark:border-slate-700/50 p-5">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">Commandes (jour)</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ $stats['today_orders'] }}</p>
            </div>
            <div class="w-12 h-12 bg-green-50 dark:bg-green-900/30 rounded-xl flex items-center justify-center">
                <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
            </div>
        </div>
        <div class="mt-3 text-xs text-green-600 dark:text-green-400 font-medium">{{ number_format($stats['today_revenue'], 0, ',', ' ') }} {{ $restaurants->first()?->currency ?? 'FC' }}</div>
    </div>

    <!-- CA Total -->
    <div class="bg-white/80 dark:bg-slate-800/80 backdrop-blur-xl rounded-xl shadow-sm border border-gray-100 dark:border-slate-700/50 p-5">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">Chiffre d'Affaires</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ number_format($stats['total_revenue'], 0, ',', ' ') }}</p>
            </div>
            <div class="w-12 h-12 bg-amber-50 dark:bg-amber-900/30 rounded-xl flex items-center justify-center">
                <svg class="w-6 h-6 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
        </div>
        <div class="mt-3 text-xs text-gray-400">Depuis le début</div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
    <!-- Graphique CA 7 jours -->
    <div class="lg:col-span-2 bg-white/80 dark:bg-slate-800/80 backdrop-blur-xl rounded-xl shadow-sm border border-gray-100 dark:border-slate-700/50 p-5">
        <h2 class="text-base font-semibold text-gray-800 dark:text-gray-100 mb-4">Chiffre d'Affaires — 7 derniers jours</h2>
        <div class="h-64">
            <canvas id="revenueChart"></canvas>
        </div>
    </div>

    <!-- Top Restaurants -->
    <div class="bg-white/80 dark:bg-slate-800/80 backdrop-blur-xl rounded-xl shadow-sm border border-gray-100 dark:border-slate-700/50 p-5">
        <h2 class="text-base font-semibold text-gray-800 dark:text-gray-100 mb-4">Top Restaurants</h2>
        <div class="space-y-3">
            @forelse($topRestaurants as $index => $restaurant)
                <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-slate-700/50 rounded-lg">
                    <div class="flex items-center">
                        <span class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold {{ $index === 0 ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-300' : ($index === 1 ? 'bg-gray-200 text-gray-600 dark:bg-slate-600 dark:text-gray-300' : ($index === 2 ? 'bg-orange-100 text-orange-700 dark:bg-orange-900/50 dark:text-orange-300' : 'bg-gray-100 text-gray-500 dark:bg-slate-700 dark:text-gray-400')) }}">
                            {{ $index + 1 }}
                        </span>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-gray-800 dark:text-gray-200">{{ $restaurant->name }}</p>
                            <p class="text-xs text-gray-400">{{ $restaurant->orders_count }} commandes</p>
                        </div>
                    </div>
                    <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">{{ number_format($restaurant->revenue ?? 0, 0, ',', ' ') }}</span>
                </div>
            @empty
                <p class="text-sm text-gray-400 text-center py-4">Aucune donnée</p>
            @endforelse
        </div>
    </div>
</div>

<!-- Tableau des restaurants -->
<div class="bg-white/80 dark:bg-slate-800/80 backdrop-blur-xl rounded-xl shadow-sm border border-gray-100 dark:border-slate-700/50">
    <div class="flex items-center justify-between p-5 border-b border-gray-100 dark:border-slate-700/50">
        <h2 class="text-base font-semibold text-gray-800 dark:text-gray-100">Tous les Restaurants</h2>
        <a href="{{ route('superadmin.restaurants.create') }}" class="inline-flex items-center px-4 py-2 bg-amber-500 text-white text-sm font-medium rounded-lg hover:bg-amber-600 transition">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Nouveau Restaurant
        </a>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead>
                <tr class="text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider border-b border-gray-100 dark:border-slate-700/50">
                    <th class="px-5 py-3">Restaurant</th>
                    <th class="px-5 py-3">Type</th>
                    <th class="px-5 py-3">Statut</th>
                    <th class="px-5 py-3 text-center">Utilisateurs</th>
                    <th class="px-5 py-3 text-center">Terminaux</th>
                    <th class="px-5 py-3 text-center">Commandes</th>
                    <th class="px-5 py-3 text-right">CA Total</th>
                    <th class="px-5 py-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50 dark:divide-slate-700/30">
                @forelse($restaurants as $restaurant)
                    <tr class="hover:bg-gray-50 dark:hover:bg-slate-700/30 transition">
                        <td class="px-5 py-4">
                            <div class="flex items-center">
                                @if($restaurant->logo_path)
                                    <img src="{{ asset('storage/' . $restaurant->logo_path) }}" alt="" class="w-9 h-9 rounded-lg object-cover">
                                @else
                                    <div class="w-9 h-9 rounded-lg bg-slate-100 dark:bg-slate-700 flex items-center justify-center">
                                        <span class="text-sm font-bold text-slate-500 dark:text-gray-400">{{ strtoupper(substr($restaurant->name, 0, 1)) }}</span>
                                    </div>
                                @endif
                                <div class="ml-3">
                                    <p class="text-sm font-medium text-gray-800 dark:text-gray-200">{{ $restaurant->name }}</p>
                                    <p class="text-xs text-gray-400">{{ $restaurant->email ?? '—' }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-5 py-4">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $restaurant->type === 'permanent' ? 'bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300' : 'bg-purple-50 text-purple-700 dark:bg-purple-900/30 dark:text-purple-300' }}">
                                {{ $restaurant->type === 'permanent' ? 'Permanent' : 'Éphémère' }}
                            </span>
                        </td>
                        <td class="px-5 py-4">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                {{ $restaurant->status === 'active' ? 'bg-green-50 text-green-700 dark:bg-green-900/30 dark:text-green-300' : '' }}
                                {{ $restaurant->status === 'inactive' ? 'bg-gray-100 text-gray-600 dark:bg-slate-700 dark:text-gray-400' : '' }}
                                {{ $restaurant->status === 'suspended' ? 'bg-red-50 text-red-700 dark:bg-red-900/30 dark:text-red-300' : '' }}
                                {{ $restaurant->status === 'ferme_temporairement' ? 'bg-yellow-50 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-300' : '' }}">
                                <span class="w-1.5 h-1.5 rounded-full mr-1.5
                                    {{ $restaurant->status === 'active' ? 'bg-green-500' : '' }}
                                    {{ $restaurant->status === 'inactive' ? 'bg-gray-400' : '' }}
                                    {{ $restaurant->status === 'suspended' ? 'bg-red-500' : '' }}
                                    {{ $restaurant->status === 'ferme_temporairement' ? 'bg-yellow-500' : '' }}">
                                </span>
                                {{ $restaurant->getStatusLabel() }}
                            </span>
                        </td>
                        <td class="px-5 py-4 text-center text-sm text-gray-600 dark:text-gray-400">{{ $restaurant->users_count }}</td>
                        <td class="px-5 py-4 text-center text-sm text-gray-600 dark:text-gray-400">{{ $restaurant->pos_terminals_count }}</td>
                        <td class="px-5 py-4 text-center text-sm text-gray-600 dark:text-gray-400">{{ $restaurant->orders_count }}</td>
                        <td class="px-5 py-4 text-right text-sm font-medium text-gray-800 dark:text-gray-200">{{ number_format($restaurant->total_revenue ?? 0, 0, ',', ' ') }}</td>
                        <td class="px-5 py-4 text-right">
                            <a href="{{ route('superadmin.restaurants.show', $restaurant) }}" class="text-amber-600 hover:text-amber-800 dark:text-amber-400 dark:hover:text-amber-300 text-sm font-medium">Détails</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-5 py-12 text-center">
                            <svg class="w-12 h-12 text-gray-300 dark:text-gray-600 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                            <p class="text-gray-400 text-sm">Aucun restaurant enregistré</p>
                            <a href="{{ route('superadmin.restaurants.create') }}" class="text-amber-600 dark:text-amber-400 text-sm font-medium mt-2 inline-block">Créer le premier restaurant</a>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('revenueChart');
    if (!ctx) return;
    const labels = {!! json_encode(array_column($revenueChart, 'date')) !!};
    const data = {!! json_encode(array_column($revenueChart, 'revenue')) !!};
    const isDark = document.documentElement.classList.contains('dark');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Chiffre d\'Affaires',
                data: data,
                backgroundColor: 'rgba(245, 158, 11, 0.7)',
                borderColor: 'rgba(245, 158, 11, 1)',
                borderWidth: 1,
                borderRadius: 6,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: isDark ? '#1e293b' : '#fff',
                    titleColor: isDark ? '#f1f5f9' : '#1e293b',
                    bodyColor: isDark ? '#cbd5e1' : '#475569',
                    borderColor: isDark ? '#334155' : '#e2e8f0',
                    borderWidth: 1,
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: isDark ? 'rgba(255,255,255,0.05)' : 'rgba(0,0,0,0.05)' },
                    ticks: { color: isDark ? '#94a3b8' : '#64748b' }
                },
                x: {
                    grid: { display: false },
                    ticks: { color: isDark ? '#94a3b8' : '#64748b' }
                }
            }
        }
    });
});
</script>
@endpush
