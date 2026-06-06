@extends('layouts.superadmin')
@section('title', 'Restaurants')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Gestion des Restaurants</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ $restaurants->total() }} restaurant(s) enregistré(s)</p>
        </div>
        <a href="{{ route('superadmin.restaurants.create') }}" class="inline-flex items-center px-4 py-2.5 bg-amber-500 text-white text-sm font-medium rounded-lg hover:bg-amber-600 transition shadow-sm">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Nouveau Restaurant
        </a>
    </div>

    <!-- Filtres -->
    <div class="bg-white/80 dark:bg-slate-800/80 backdrop-blur-xl rounded-xl shadow-sm border border-gray-100 dark:border-slate-700/50 p-4">
        <form method="GET" action="{{ route('superadmin.restaurants.index') }}" class="flex flex-col sm:flex-row gap-3">
            <div class="flex-1">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Rechercher par nom, email, téléphone..."
                       class="w-full px-4 py-2 border border-gray-200 dark:border-slate-600 dark:bg-slate-700 dark:text-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none">
            </div>
            <select name="status" class="px-4 py-2 border border-gray-200 dark:border-slate-600 dark:bg-slate-700 dark:text-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none">
                <option value="all" {{ request('status') === 'all' ? 'selected' : '' }}>Tous les statuts</option>
                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Actif</option>
                <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactif</option>
                <option value="suspended" {{ request('status') === 'suspended' ? 'selected' : '' }}>Suspendu</option>
                <option value="ferme_temporairement" {{ request('status') === 'ferme_temporairement' ? 'selected' : '' }}>Fermé temporairement</option>
            </select>
            <button type="submit" class="px-5 py-2 bg-slate-800 dark:bg-slate-600 text-white text-sm font-medium rounded-lg hover:bg-slate-700 dark:hover:bg-slate-500 transition">
                Filtrer
            </button>
        </form>
    </div>

    <!-- Liste des restaurants -->
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
        @forelse($restaurants as $restaurant)
            @php
                $statusColors = [
                    'active' => 'bg-green-50 text-green-700 dark:bg-green-900/30 dark:text-green-400',
                    'inactive' => 'bg-gray-100 text-gray-600 dark:bg-slate-700 dark:text-gray-400',
                    'suspended' => 'bg-red-50 text-red-700 dark:bg-red-900/30 dark:text-red-400',
                    'ferme_temporairement' => 'bg-yellow-50 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400',
                ];
                $statusLabels = [
                    'active' => 'Actif',
                    'inactive' => 'Inactif',
                    'suspended' => 'Suspendu',
                    'ferme_temporairement' => 'Fermé temporairement',
                ];
            @endphp
            <div class="bg-white/80 dark:bg-slate-800/80 backdrop-blur-xl rounded-xl shadow-sm border border-gray-100 dark:border-slate-700/50 overflow-hidden hover:shadow-md transition-all group">
                <!-- Header carte -->
                <div class="p-5 pb-4">
                    <div class="flex items-start justify-between mb-3">
                        <div class="flex items-center gap-3 min-w-0">
                            @if($restaurant->logo_path)
                                <img src="{{ asset('storage/' . $restaurant->logo_path) }}" alt="" class="w-12 h-12 rounded-xl object-cover border border-gray-100 dark:border-slate-600 flex-shrink-0">
                            @else
                                <div class="w-12 h-12 rounded-xl bg-slate-100 dark:bg-slate-700 flex items-center justify-center flex-shrink-0">
                                    <span class="text-lg font-bold text-slate-500 dark:text-gray-400">{{ strtoupper(substr($restaurant->name, 0, 1)) }}</span>
                                </div>
                            @endif
                            <div class="min-w-0">
                                <a href="{{ route('superadmin.restaurants.show', $restaurant) }}" class="text-base font-bold text-gray-900 dark:text-white hover:text-amber-600 dark:hover:text-amber-400 transition truncate block">
                                    {{ $restaurant->name }}
                                </a>
                                <p class="text-xs text-gray-400 truncate">{{ $restaurant->email ?? 'Pas d\'email' }}</p>
                            </div>
                        </div>
                        <!-- Menu 3 points -->
                        <div class="relative" x-data="{ open: false }">
                            <button @click="open = !open" class="p-1.5 rounded-lg hover:bg-gray-100 dark:hover:bg-slate-700 transition">
                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"/></svg>
                            </button>
                            <div x-show="open" @click.away="open = false" x-transition class="absolute right-0 top-8 w-48 bg-white dark:bg-slate-800 rounded-xl shadow-lg border border-gray-100 dark:border-slate-700 z-20 overflow-hidden">
                                <a href="{{ route('superadmin.restaurants.show', $restaurant) }}" class="flex items-center px-4 py-2.5 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-slate-700 transition">
                                    <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    Voir détails
                                </a>
                                <a href="{{ route('superadmin.restaurants.edit', $restaurant) }}" class="flex items-center px-4 py-2.5 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-slate-700 transition">
                                    <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    Modifier
                                </a>
                                <a href="{{ route('superadmin.restaurants.tables.index', $restaurant) }}" class="flex items-center px-4 py-2.5 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-slate-700 transition">
                                    <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
                                    Tables
                                </a>
                                <form method="POST" action="{{ route('superadmin.restaurants.toggle-status', $restaurant) }}" class="block">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="status" value="{{ $restaurant->status === 'active' ? 'suspended' : 'active' }}">
                                    <button type="submit" class="flex items-center w-full px-4 py-2.5 text-sm {{ $restaurant->status === 'active' ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400' }} hover:bg-gray-50 dark:hover:bg-slate-700 transition">
                                        @if($restaurant->status === 'active')
                                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                                            Suspendre
                                        @else
                                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                            Activer
                                        @endif
                                    </button>
                                </form>
                                <div class="border-t border-gray-100 dark:border-slate-700"></div>
                                <form method="POST" action="{{ route('superadmin.restaurants.destroy', $restaurant) }}" class="block" onsubmit="return confirm('Supprimer ce restaurant ? Cette action est irréversible.')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="flex items-center w-full px-4 py-2.5 text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 transition">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        Supprimer
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Statut -->
                    <div class="flex items-center gap-2 mb-4">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusColors[$restaurant->status] ?? 'bg-gray-100 text-gray-600' }}">
                            <span class="w-1.5 h-1.5 rounded-full mr-1.5 {{ $restaurant->status === 'active' ? 'bg-green-500' : ($restaurant->status === 'suspended' ? 'bg-red-500' : ($restaurant->status === 'ferme_temporairement' ? 'bg-yellow-500' : 'bg-gray-400')) }}"></span>
                            {{ $statusLabels[$restaurant->status] ?? $restaurant->status }}
                        </span>
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400">
                            {{ $restaurant->type === 'permanent' ? 'Permanent' : 'Éphémère' }}
                        </span>
                    </div>

                    <!-- Stats rapides -->
                    <div class="grid grid-cols-4 gap-2 text-center">
                        <div class="bg-gray-50 dark:bg-slate-700/30 rounded-lg p-2">
                            <p class="text-lg font-bold text-gray-900 dark:text-white">{{ $restaurant->users_count ?? 0 }}</p>
                            <p class="text-[10px] text-gray-400 uppercase">Users</p>
                        </div>
                        <div class="bg-gray-50 dark:bg-slate-700/30 rounded-lg p-2">
                            <p class="text-lg font-bold text-gray-900 dark:text-white">{{ $restaurant->pos_terminals_count ?? 0 }}</p>
                            <p class="text-[10px] text-gray-400 uppercase">Caisses</p>
                        </div>
                        <div class="bg-gray-50 dark:bg-slate-700/30 rounded-lg p-2">
                            <p class="text-lg font-bold text-gray-900 dark:text-white">{{ $restaurant->orders_count ?? 0 }}</p>
                            <p class="text-[10px] text-gray-400 uppercase">Cmds</p>
                        </div>
                        <div class="bg-gray-50 dark:bg-slate-700/30 rounded-lg p-2">
                            <p class="text-sm font-bold text-amber-600 dark:text-amber-400">{{ number_format($restaurant->total_revenue ?? 0, 0, ',', ' ') }}</p>
                            <p class="text-[10px] text-gray-400 uppercase">CA</p>
                        </div>
                    </div>
                </div>

                <!-- Footer avec lien -->
                <div class="px-5 py-3 bg-gray-50/50 dark:bg-slate-900/30 border-t border-gray-100 dark:border-slate-700/50 flex items-center justify-between">
                    <div class="text-xs text-gray-400">
                        @if($restaurant->subscription_ends_at)
                            Expire: {{ $restaurant->subscription_ends_at->format('d/m/Y') }}
                        @else
                            Pas d'abonnement
                        @endif
                    </div>
                    <a href="{{ route('superadmin.restaurants.show', $restaurant) }}" class="text-xs font-medium text-amber-600 dark:text-amber-400 hover:text-amber-700 dark:hover:text-amber-300 transition flex items-center">
                        Détails
                        <svg class="w-3 h-3 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </a>
                </div>
            </div>
        @empty
            <div class="col-span-full text-center py-16">
                <svg class="w-16 h-16 text-gray-300 dark:text-gray-600 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                <p class="text-gray-400 dark:text-gray-500 font-medium">Aucun restaurant enregistré</p>
                <p class="text-gray-400 dark:text-gray-500 text-sm mt-1">Commencez par créer un restaurant</p>
                <a href="{{ route('superadmin.restaurants.create') }}" class="inline-flex items-center mt-4 px-4 py-2 bg-amber-500 text-white text-sm font-medium rounded-lg hover:bg-amber-600 transition">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Créer un restaurant
                </a>
            </div>
        @endforelse
    </div>

    <!-- Pagination -->
    @if($restaurants->hasPages())
        <div class="flex justify-center">
            {{ $restaurants->links() }}
        </div>
    @endif
</div>
@endsection
