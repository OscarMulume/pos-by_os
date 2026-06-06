@extends('layouts.superadmin')
@section('title', 'Tables — '.$restaurant->name)

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center gap-4">
        <a href="{{ route('superadmin.restaurants.show', $restaurant) }}" class="p-2 text-gray-500 hover:text-amber-600 dark:text-gray-400 dark:hover:text-amber-400 transition rounded-lg hover:bg-gray-100 dark:hover:bg-slate-700">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        </a>
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Tables — {{ $restaurant->name }}</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ $tables->count() }} table(s) · Gérez la numérotation, les zones et la capacité</p>
        </div>
    </div>

    <!-- Formulaire d'ajout rapide -->
    <div class="bg-white/80 dark:bg-slate-800/80 backdrop-blur-xl rounded-xl shadow-sm border border-gray-100 dark:border-slate-700/50">
        <div class="p-6 border-b border-gray-100 dark:border-slate-700/50">
            <h2 class="text-lg font-bold text-gray-900 dark:text-white">Ajouter des tables en masse</h2>
        </div>
        <div class="p-6">
            <form method="POST" action="{{ route('superadmin.restaurants.tables.bulk', $restaurant) }}" class="flex flex-col sm:flex-row gap-4">
                @csrf
                <div class="flex-1">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Zone</label>
                    <select name="zone" required class="w-full px-4 py-2 border border-gray-200 dark:border-slate-600 dark:bg-slate-700 dark:text-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-amber-500 outline-none">
                        <option value="Salle">Salle</option>
                        <option value="Terrasse">Terrasse</option>
                        <option value="VIP">VIP</option>
                        <option value="Bar">Bar</option>
                        <option value="Mezzanine">Mezzanine</option>
                    </select>
                </div>
                <div class="w-24">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">De</label>
                    <input type="number" name="from" value="1" min="1" required class="w-full px-4 py-2 border border-gray-200 dark:border-slate-600 dark:bg-slate-700 dark:text-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-amber-500 outline-none">
                </div>
                <div class="w-24">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">À</label>
                    <input type="number" name="to" value="10" min="1" required class="w-full px-4 py-2 border border-gray-200 dark:border-slate-600 dark:bg-slate-700 dark:text-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-amber-500 outline-none">
                </div>
                <div class="w-24">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Capacité</label>
                    <input type="number" name="capacity" value="4" min="1" max="20" class="w-full px-4 py-2 border border-gray-200 dark:border-slate-600 dark:bg-slate-700 dark:text-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-amber-500 outline-none">
                </div>
                <div class="flex items-end">
                    <button type="submit" class="px-5 py-2 bg-amber-500 text-white text-sm font-medium rounded-lg hover:bg-amber-600 transition whitespace-nowrap">
                        Créer les tables
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Liste des tables existantes -->
    <div class="bg-white/80 dark:bg-slate-800/80 backdrop-blur-xl rounded-xl shadow-sm border border-gray-100 dark:border-slate-700/50">
        <div class="p-6 border-b border-gray-100 dark:border-slate-700/50 flex items-center justify-between">
            <h2 class="text-lg font-bold text-gray-900 dark:text-white">Tables existantes</h2>
            <a href="{{ route('superadmin.restaurants.show', $restaurant) }}" class="text-sm text-amber-600 dark:text-amber-400 hover:underline">
                ← Retour au restaurant
            </a>
        </div>
        <div class="divide-y divide-gray-50 dark:divide-slate-700/30">
            @forelse($tables->sortBy('zone')->sortBy('name') as $table)
            <div class="p-4 flex items-center justify-between hover:bg-gray-50 dark:hover:bg-slate-700/20 transition">
                <div class="flex items-center gap-4">
                    <!-- Numéro visuel -->
                    <div class="w-12 h-12 rounded-lg {{ $table->status === 'libre' ? 'bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800' : 'bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800' }} flex items-center justify-center">
                        <span class="text-lg font-bold {{ $table->status === 'libre' ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">{{ explode(' ', $table->name)[1] ?? $table->id }}</span>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $table->name }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            {{ $table->zone ?? 'Sans zone' }} · {{ $table->capacity ?? '?' }} pers.
                            · <span class="{{ $table->status === 'libre' ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">{{ ucfirst($table->status) }}</span>
                        </p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <!-- Formulaire de renommage rapide -->
                    <form method="POST" action="{{ route('superadmin.restaurants.tables.update', [$restaurant, $table]) }}" class="flex items-center gap-2">
                        @csrf
                        @method('PUT')
                        <input type="text" name="name" value="{{ $table->name }}" class="w-24 px-2 py-1 border border-gray-200 dark:border-slate-600 dark:bg-slate-700 dark:text-gray-200 rounded text-xs">
                        <input type="number" name="capacity" value="{{ $table->capacity }}" class="w-14 px-2 py-1 border border-gray-200 dark:border-slate-600 dark:bg-slate-700 dark:text-gray-200 rounded text-xs" placeholder="Cap.">
                        <select name="status" class="px-2 py-1 border border-gray-200 dark:border-slate-600 dark:bg-slate-700 dark:text-gray-200 rounded text-xs">
                            <option value="libre" {{ $table->status === 'libre' ? 'selected' : '' }}>Libre</option>
                            <option value="occupee" {{ $table->status === 'occupee' ? 'selected' : '' }}>Occupée</option>
                            <option value="reservee" {{ $table->status === 'reservee' ? 'selected' : '' }}>Réservée</option>
                        </select>
                        <button type="submit" class="px-3 py-1 bg-slate-800 dark:bg-slate-600 text-white text-xs rounded hover:bg-slate-700 transition">OK</button>
                    </form>
                    <!-- Supprimer -->
                    <form method="POST" action="{{ route('superadmin.restaurants.tables.destroy', [$restaurant, $table]) }}" onsubmit="return confirm('Supprimer cette table ?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="p-1.5 text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 rounded transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        </button>
                    </form>
                </div>
            </div>
            @empty
            <div class="p-12 text-center text-gray-400 dark:text-gray-500">
                <svg class="w-12 h-12 mx-auto mb-3 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                <p class="font-medium">Aucune table configurée</p>
                <p class="text-sm mt-1">Utilisez le formulaire ci-dessus pour créer des tables en masse.</p>
            </div>
            @endforelse
        </div>
    </div>
</div>
@endsection
