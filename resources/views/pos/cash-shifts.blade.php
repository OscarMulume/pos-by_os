@extends('layouts.pos')

@section('title', 'Historique Caisse')

@push('styles')
<style>
    html, body { overflow: auto !important; }
</style>
@endpush

@section('content')
<div class="p-6 max-w-4xl mx-auto" style="height: calc(100vh - 3.5rem); overflow-y: auto;">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Historique des Shifts de Caisse</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ $shifts->total() }} shift(s) au total</p>
        </div>
        <a href="{{ route('pos.index') }}" class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-200 dark:bg-slate-700 rounded-lg hover:bg-gray-300 dark:hover:bg-slate-600 transition">
            ← Retour au POS
        </a>
    </div>

    @forelse($shifts as $shift)
        @php
            $salesCount = $shift->orders()->whereIn('status', ['paid'])->count();
            $salesTotal = $shift->orders()->whereIn('status', ['paid'])->sum('total_amount');
            $duration = $shift->opened_at->diffForHumans($shift->closed_at ?? now(), true);
        @endphp
        <div class="bg-white/90 dark:bg-slate-800/90 backdrop-blur-xl rounded-xl shadow-sm border border-gray-100 dark:border-slate-700/50 p-5 mb-4">
            <div class="flex items-center justify-between mb-3">
                <div class="flex items-center gap-3">
                    <span class="text-lg font-bold text-gray-900 dark:text-white">{{ $shift->user->name }}</span>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $shift->isOpen() ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' : 'bg-gray-100 text-gray-600 dark:bg-slate-700 dark:text-gray-400' }}">
                        {{ $shift->isOpen() ? 'Ouvert' : 'Fermé' }}
                    </span>
                </div>
                <span class="text-sm text-gray-500 dark:text-gray-400">{{ $shift->opened_at->format('d/m/Y H:i') }}</span>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-5 gap-4 text-sm">
                <div>
                    <p class="text-gray-500 dark:text-gray-400 text-xs">Fond de caisse</p>
                    <p class="font-semibold text-gray-900 dark:text-white">{{ number_format($shift->start_amount, 0, ',', ' ') }}</p>
                </div>
                <div>
                    <p class="text-gray-500 dark:text-gray-400 text-xs">Ventes</p>
                    <p class="font-semibold text-gray-900 dark:text-white">{{ $salesCount }} cmd · {{ number_format($salesTotal, 0, ',', ' ') }}</p>
                </div>
                <div>
                    <p class="text-gray-500 dark:text-gray-400 text-xs">Attendu</p>
                    <p class="font-semibold text-gray-900 dark:text-white">{{ number_format($shift->end_amount_expected ?? $shift->calculateExpected(), 0, ',', ' ') }}</p>
                </div>
                <div>
                    <p class="text-gray-500 dark:text-gray-400 text-xs">Compté</p>
                    <p class="font-semibold {{ $shift->end_amount_counted !== null ? 'text-gray-900 dark:text-white' : 'text-gray-400' }}">
                        {{ $shift->end_amount_counted !== null ? number_format($shift->end_amount_counted, 0, ',', ' ') : '—' }}
                    </p>
                </div>
                <div>
                    <p class="text-gray-500 dark:text-gray-400 text-xs">Écart</p>
                    <p class="font-semibold {{ ($shift->difference ?? 0) > 0 ? 'text-green-600 dark:text-green-400' : (($shift->difference ?? 0) < 0 ? 'text-red-600 dark:text-red-400' : 'text-gray-500') }}">
                        @if($shift->difference !== null)
                            {{ $shift->difference > 0 ? '+' : '' }}{{ number_format($shift->difference, 0, ',', ' ') }}
                        @else
                            —
                        @endif
                    </p>
                </div>
            </div>

            <div class="mt-3 pt-3 border-t border-gray-100 dark:border-slate-700/50 flex items-center justify-between text-xs text-gray-400">
                <span>Durée : {{ $duration }}</span>
                @if($shift->posTerminal)
                    <span>Terminal : {{ $shift->posTerminal->name ?? $shift->posTerminal->id }}</span>
                @endif
            </div>
        </div>
    @empty
        <div class="text-center py-16">
            <svg class="w-16 h-16 text-gray-300 dark:text-gray-600 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <p class="text-gray-400 dark:text-gray-500 font-medium">Aucun shift de caisse enregistré</p>
            <p class="text-gray-400 dark:text-gray-500 text-sm mt-1">Ouvrez une caisse pour commencer à vendre</p>
        </div>
    @endforelse

    @if($shifts->hasPages())
        <div class="mt-6 flex justify-center">
            {{ $shifts->links() }}
        </div>
    @endif
</div>
@endsection
