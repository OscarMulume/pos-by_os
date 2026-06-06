@extends('layouts.superadmin')
@section('title', 'Licences')

@section('content')
<div class="mb-6">
    <a href="{{ route('superadmin.dashboard') }}" class="text-sm text-gray-500 dark:text-gray-400 hover:text-amber-600 dark:hover:text-amber-400 transition">
        ← Retour au dashboard
    </a>
</div>

<div class="bg-white/80 dark:bg-slate-800/80 backdrop-blur-xl rounded-xl shadow-sm border border-gray-100 dark:border-slate-700/50">
    <div class="p-6 border-b border-gray-100 dark:border-slate-700/50">
        <h2 class="text-xl font-bold text-gray-900 dark:text-white">Gestion des Licences</h2>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Générez et gérez les licences pour chaque restaurant</p>
    </div>

    <div class="p-6">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @php
                $restaurants = \App\Models\Restaurant::with('license')->orderBy('name')->get();
            @endphp
            @forelse($restaurants as $restaurant)
                @php $license = $restaurant->license; @endphp
                <div class="bg-white dark:bg-slate-700/50 rounded-xl border border-gray-200 dark:border-slate-600 p-5">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="font-bold text-gray-900 dark:text-white">{{ $restaurant->name }}</h3>
                        @if($license && $license->isValid())
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-50 text-green-700 dark:bg-green-900/30 dark:text-green-400">
                                Actif
                            </span>
                        @elseif($license && $license->isExpired())
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-50 text-red-700 dark:bg-red-900/30 dark:text-red-400">
                                Expiré
                            </span>
                        @else
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600 dark:bg-slate-600 dark:text-gray-400">
                                Sans licence
                            </span>
                        @endif
                    </div>

                    @if($license)
                        <div class="space-y-2 text-sm mb-4">
                            <div class="flex justify-between">
                                <span class="text-gray-500 dark:text-gray-400">Plan</span>
                                <span class="font-medium text-gray-900 dark:text-white capitalize">{{ $license->plan }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500 dark:text-gray-400">Expire le</span>
                                <span class="font-medium {{ $license->isExpired() ? 'text-red-600 dark:text-red-400' : 'text-gray-900 dark:text-white' }}">
                                    {{ $license->expires_at->format('d/m/Y') }}
                                </span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500 dark:text-gray-400">Jours restants</span>
                                <span class="font-medium {{ $license->daysRemaining() <= 7 ? 'text-amber-600 dark:text-amber-400' : 'text-gray-900 dark:text-white' }}">
                                    {{ max(0, $license->daysRemaining()) }}
                                </span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500 dark:text-gray-400">Tables max</span>
                                <span class="font-medium text-gray-900 dark:text-white">{{ $license->max_tables }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500 dark:text-gray-400">Terminaux max</span>
                                <span class="font-medium text-gray-900 dark:text-white">{{ $license->max_terminals }}</span>
                            </div>
                        </div>
                    @else
                        <p class="text-sm text-gray-400 dark:text-gray-500 mb-4">Aucune licence attribuée</p>
                    @endif

                    <!-- Formulaire de génération -->
                    <form method="POST" action="{{ route('api.license.generate', $restaurant) }}" class="space-y-2">
                        @csrf
                        <div class="flex gap-2">
                            <select name="plan" class="flex-1 px-3 py-1.5 border border-gray-200 dark:border-slate-600 dark:bg-slate-700 dark:text-gray-200 rounded-lg text-xs focus:ring-2 focus:ring-amber-500 outline-none">
                                <option value="basic">Basic (20 tables, 5 terminaux)</option>
                                <option value="pro">Pro (50 tables, 15 terminaux)</option>
                                <option value="enterprise">Enterprise (∞)</option>
                            </select>
                            <input type="number" name="days" value="30" min="1" max="365" class="w-16 px-3 py-1.5 border border-gray-200 dark:border-slate-600 dark:bg-slate-700 dark:text-gray-200 rounded-lg text-xs focus:ring-2 focus:ring-amber-500 outline-none">
                        </div>
                        <button type="submit" class="w-full px-3 py-2 bg-amber-500 text-white text-xs font-medium rounded-lg hover:bg-amber-600 transition">
                            {{ $license ? 'Renouveler' : 'Générer' }} Licence
                        </button>
                    </form>
                </div>
            @empty
                <div class="col-span-full text-center py-12">
                    <p class="text-gray-400 dark:text-gray-500">Aucun restaurant configuré</p>
                </div>
            @endforelse
        </div>
    </div>
</div>
@endsection
