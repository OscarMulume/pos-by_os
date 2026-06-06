@extends('layouts.superadmin')
@section('title', $restaurant->name)

@section('content')
<div class="space-y-6">
    <!-- Header avec bouton retour -->
    <div class="flex items-center gap-4">
        <a href="{{ route('superadmin.restaurants.index') }}" class="p-2 text-gray-500 hover:text-amber-600 dark:text-gray-400 dark:hover:text-amber-400 transition rounded-lg hover:bg-gray-100 dark:hover:bg-slate-700">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        </a>
        <div class="flex-1">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $restaurant->name }}</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ $restaurant->address ?? 'Adresse non renseignée' }}</p>
        </div>
        <!-- Badge statut -->
        @php
            $statusColors = [
                'active' => 'bg-green-50 text-green-700 dark:bg-green-900/30 dark:text-green-400',
                'inactive' => 'bg-red-50 text-red-700 dark:bg-red-900/30 dark:text-red-400',
                'suspended' => 'bg-amber-50 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
                'ferme_temporairement' => 'bg-gray-100 text-gray-600 dark:bg-slate-700 dark:text-gray-400',
            ];
            $statusLabels = [
                'active' => 'Actif',
                'inactive' => 'Inactif',
                'suspended' => 'Suspendu',
                'ferme_temporairement' => 'Fermé temporairement',
            ];
        @endphp
        <span class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-medium {{ $statusColors[$restaurant->status] ?? $statusColors['active'] }}">
            {{ $statusLabels[$restaurant->status] ?? ucfirst($restaurant->status) }}
        </span>
    </div>

    <!-- Stats rapides -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <div class="bg-white/80 dark:bg-slate-800/80 backdrop-blur-xl rounded-xl shadow-sm border border-gray-100 dark:border-slate-700/50 p-4">
            <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider">Tables</p>
            <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ $restaurant->tables->count() }}</p>
            <p class="text-xs text-gray-400 mt-1">{{ $restaurant->tables->where('status','libre')->count() }} libre(s)</p>
        </div>
        <div class="bg-white/80 dark:bg-slate-800/80 backdrop-blur-xl rounded-xl shadow-sm border border-gray-100 dark:border-slate-700/50 p-4">
            <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider">Terminaux</p>
            <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ $restaurant->posTerminals->count() }}</p>
            <p class="text-xs text-gray-400 mt-1">{{ $restaurant->posTerminals->where('is_active',true)->count() }} actif(s)</p>
        </div>
        <div class="bg-white/80 dark:bg-slate-800/80 backdrop-blur-xl rounded-xl shadow-sm border border-gray-100 dark:border-slate-700/50 p-4">
            <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider">Employés</p>
            <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ $restaurant->users->count() }}</p>
            <p class="text-xs text-gray-400 mt-1">{{ $restaurant->users->where('is_active',true)->count() }} actif(s)</p>
        </div>
        <div class="bg-white/80 dark:bg-slate-800/80 backdrop-blur-xl rounded-xl shadow-sm border border-gray-100 dark:border-slate-700/50 p-4">
            <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider">Produits</p>
            <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ $restaurant->products->count() }}</p>
            <p class="text-xs text-gray-400 mt-1">{{ $restaurant->products->where('is_available',true)->count() }} disponible(s)</p>
        </div>
    </div>

    <!-- Informations du restaurant -->
    <div class="bg-white/80 dark:bg-slate-800/80 backdrop-blur-xl rounded-xl shadow-sm border border-gray-100 dark:border-slate-700/50">
        <div class="p-6 border-b border-gray-100 dark:border-slate-700/50">
            <h2 class="text-lg font-bold text-gray-900 dark:text-white">Informations</h2>
        </div>
        <div class="p-6 grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
            <div><span class="text-gray-500 dark:text-gray-400">Type:</span> <span class="text-gray-900 dark:text-white capitalize">{{ $restaurant->type ?? 'Standard' }}</span></div>
            <div><span class="text-gray-500 dark:text-gray-400">Téléphone:</span> <span class="text-gray-900 dark:text-white">{{ $restaurant->phone ?? '—' }}</span></div>
            <div><span class="text-gray-500 dark:text-gray-400">Email:</span> <span class="text-gray-900 dark:text-white">{{ $restaurant->email ?? '—' }}</span></div>
            <div><span class="text-gray-500 dark:text-gray-400">Devise:</span> <span class="text-gray-900 dark:text-white">{{ $restaurant->currency ?? 'FC' }}</span></div>
            <div><span class="text-gray-500 dark:text-gray-400">Taux de taxe:</span> <span class="text-gray-900 dark:text-white">{{ $restaurant->tax_rate ?? 0 }}%</span></div>
            <div><span class="text-gray-500 dark:text-gray-400">Taux de change:</span> <span class="text-gray-900 dark:text-white">{{ $restaurant->exchange_rate ?? '1' }} ({{ $restaurant->exchange_currency ?? 'USD' }})</span></div>
        </div>
    </div>

    <!-- Section Tables avec actions -->
    <div class="bg-white/80 dark:bg-slate-800/80 backdrop-blur-xl rounded-xl shadow-sm border border-gray-100 dark:border-slate-700/50">
        <div class="p-6 border-b border-gray-100 dark:border-slate-700/50 flex items-center justify-between">
            <div>
                <h2 class="text-lg font-bold text-gray-900 dark:text-white">Tables</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ $restaurant->tables->count() }} table(s) configurée(s)</p>
            </div>
            <a href="{{ route('superadmin.restaurants.tables', $restaurant) }}" class="inline-flex items-center px-4 py-2.5 bg-amber-500 text-white text-sm font-medium rounded-lg hover:bg-amber-600 transition shadow-sm">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Gérer les tables
            </a>
        </div>
        <div class="p-6">
            @if($restaurant->tables->isEmpty())
                <p class="text-sm text-gray-400 dark:text-gray-500 text-center py-8">Aucune configurée. Cliquez sur « Gérer les tables » pour en créer.</p>
            @else
                <div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-6 gap-3">
                    @foreach($restaurant->tables->sortBy('zone')->sortBy('name') as $table)
                        @php
                            $color = $table->status === 'libre' ? 'bg-green-50 border-green-200 text-green-700 dark:bg-green-900/20 dark:border-green-800 dark:text-green-400' : 'bg-red-50 border-red-200 text-red-700 dark:bg-red-900/20 dark:border-red-800 dark:text-red-400';
                        @endphp
                        <div class="rounded-lg border p-3 text-center {{ $color }}">
                            <p class="font-bold text-lg">{{ $table->name }}</p>
                            <p class="text-xs opacity-75">{{ $table->zone ?? 'Sans zone' }}</p>
                            <p class="text-xs mt-1">{{ $table->capacity ?? '?' }} p.</p>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <!-- Section Terminaux POS -->
    <div class="bg-white/80 dark:bg-slate-800/80 backdrop-blur-xl rounded-xl shadow-sm border border-gray-100 dark:border-slate-700/50">
        <div class="p-6 border-b border-gray-100 dark:border-slate-700/50">
            <h2 class="text-lg font-bold text-gray-900 dark:text-white">Terminaux POS</h2>
        </div>
        <div class="p-6">
            @forelse($restaurant->posTerminals as $terminal)
                <div class="flex items-center justify-between py-3 border-b border-gray-50 dark:border-slate-700/30 last:border-0">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-lg bg-slate-100 dark:bg-slate-700 flex items-center justify-center">
                            <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $terminal->name ?? 'Terminal #'.$terminal->id }}</p>
                            <p class="text-xs text-gray-400">ID: {{ $terminal->terminal_uid ?? $terminal->id }}</p>
                        </div>
                    </div>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $terminal->is_active ? 'bg-green-50 text-green-700 dark:bg-green-900/30 dark:text-green-400' : 'bg-gray-100 text-gray-500 dark:bg-slate-700 dark:text-gray-400' }}">
                        {{ $terminal->is_active ? 'Actif' : 'Inactif' }}
                    </span>
                </div>
            @empty
                <p class="text-sm text-gray-400 dark:text-gray-500 text-center py-8">Aucun terminal configuré.</p>
            @endforelse
        </div>
    </div>

    <!-- Actions administrateur -->
    <div class="bg-white/80 dark:bg-slate-800/80 backdrop-blur-xl rounded-xl shadow-sm border border-gray-100 dark:border-slate-700/50">
        <div class="p-6 border-b border-gray-100 dark:border-slate-700/50">
            <h2 class="text-lg font-bold text-gray-900 dark:text-white">Actions Administrateur</h2>
        </div>
        <div class="p-6">
            <div class="flex flex-wrap gap-3">
                <a href="{{ route('superadmin.restaurants.edit', $restaurant) }}" class="inline-flex items-center px-4 py-2 bg-slate-800 dark:bg-slate-600 text-white text-sm font-medium rounded-lg hover:bg-slate-700 transition">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    Modifier le restaurant
                </a>
                <a href="{{ route('admin.inventory.index') }}" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                    Voir les stocks
                </a>
                <a href="{{ route('admin.reports.index') }}" class="inline-flex items-center px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 transition">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    Rapports
                </a>
                <!-- Changer le statut -->
                <form method="POST" action="{{ route('superadmin.restaurants.toggle-status', $restaurant) }}" class="inline">
                    @csrf
                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-amber-500 text-white text-sm font-medium rounded-lg hover:bg-amber-600 transition">
                        {{ $restaurant->status === 'active' ? 'Suspendre' : 'Activer' }}
                    </button>
                </form>
                <!-- Supprimer -->
                <form method="POST" action="{{ route('superadmin.restaurants.destroy', $restaurant) }}" class="inline" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce restaurant ? Cette action est irréversible.')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700 transition">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        Supprimer
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Licence -->
    @if($restaurant->license)
    <div class="bg-white/80 dark:bg-slate-800/80 backdrop-blur-xl rounded-xl shadow-sm border border-gray-100 dark:border-slate-700/50">
        <div class="p-6 border-b border-gray-100 dark:border-slate-700/50 flex items-center justify-between">
            <h2 class="text-lg font-bold text-gray-900 dark:text-white">Licence</h2>
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $restaurant->license->isValid() ? 'bg-green-50 text-green-700 dark:bg-green-900/30 dark:text-green-400' : 'bg-red-50 text-red-700 dark:bg-red-900/30 dark:text-red-400' }}">
                {{ $restaurant->license->isValid() ? 'Valide' : 'Expirée' }}
            </span>
        </div>
        <div class="p-6 grid grid-cols-2 sm:grid-cols-4 gap-4 text-sm">
            <div><span class="text-gray-500 dark:text-gray-400">Plan:</span> <span class="text-gray-900 dark:text-white capitalize font-medium">{{ $restaurant->license->plan }}</span></div>
            <div><span class="text-gray-500 dark:text-gray-400">Expire le:</span> <span class="text-gray-900 dark:text-white">{{ $restaurant->license->expires_at->format('d/m/Y') }}</span></div>
            <div><span class="text-gray-500 dark:text-gray-400">Jours restants:</span> <span class="text-gray-900 dark:text-white {{ $restaurant->license->daysRemaining() <= 7 ? 'text-amber-600 dark:text-amber-400' : '' }}">{{ max(0, $restaurant->license->daysRemaining()) }}</span></div>
            <div><span class="text-gray-500 dark:text-gray-400">Tables max:</span> <span class="text-gray-900 dark:text-white">{{ $restaurant->license->max_tables }}</span></div>
        </div>
    </div>
    @endif
</div>
@endsection
