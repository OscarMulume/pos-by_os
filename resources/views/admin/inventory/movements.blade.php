@extends('layouts.app')
@section('title', 'Mouvements de Stock')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Historique des Mouvements</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ $movements->total() }} mouvement(s)</p>
        </div>
        <a href="{{ route('admin.inventory.index') }}" class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-200 dark:bg-slate-700 rounded-lg hover:bg-gray-300 dark:hover:bg-slate-600 transition">
            ← Retour à l'inventaire
        </a>
    </div>

    <div class="bg-white/80 dark:bg-slate-800/80 backdrop-blur-xl rounded-xl shadow-sm border border-gray-100 dark:border-slate-700/50 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider bg-gray-50/80 dark:bg-slate-900/50 border-b border-gray-100 dark:border-slate-700/50">
                        <th class="px-5 py-3">Date</th>
                        <th class="px-5 py-3">Produit</th>
                        <th class="px-5 py-3">Type</th>
                        <th class="px-5 py-3 text-center">Quantité</th>
                        <th class="px-5 py-3 text-center">Avant</th>
                        <th class="px-5 py-3 text-center">Après</th>
                        <th class="px-5 py-3">Motif</th>
                        <th class="px-5 py-3">Utilisateur</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50 dark:divide-slate-700/30">
                    @forelse($movements as $m)
                        <tr class="hover:bg-gray-50 dark:hover:bg-slate-700/20 transition">
                            <td class="px-5 py-3 text-sm text-gray-600 dark:text-gray-400">{{ $m->created_at->format('d/m/Y H:i') }}</td>
                            <td class="px-5 py-3 text-sm font-medium text-gray-800 dark:text-gray-200">{{ $m->product?->name ?? '—' }}</td>
                            <td class="px-5 py-3">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                                    {{ $m->type === 'sale' ? 'bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400' : '' }}
                                    {{ $m->type === 'adjustment' ? 'bg-amber-50 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400' : '' }}
                                    {{ $m->type === 'return' ? 'bg-green-50 text-green-700 dark:bg-green-900/30 dark:text-green-400' : '' }}
                                    {{ $m->type === 'initial' ? 'bg-gray-100 text-gray-600 dark:bg-slate-700 dark:text-gray-400' : '' }}">
                                    {{ $m->type === 'sale' ? 'Vente' : ($m->type === 'adjustment' ? 'Ajustement' : ($m->type === 'return' ? 'Retour' : 'Initial')) }}
                                </span>
                            </td>
                            <td class="px-5 py-3 text-center text-sm font-bold {{ $m->quantity < 0 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400' }}">
                                {{ $m->quantity > 0 ? '+' : '' }}{{ $m->quantity }}
                            </td>
                            <td class="px-5 py-3 text-center text-sm text-gray-600 dark:text-gray-400">{{ $m->stock_before }}</td>
                            <td class="px-5 py-3 text-center text-sm font-medium text-gray-900 dark:text-white">{{ $m->stock_after }}</td>
                            <td class="px-5 py-3 text-sm text-gray-600 dark:text-gray-400">{{ $m->reason ?? '—' }}</td>
                            <td class="px-5 py-3 text-sm text-gray-600 dark:text-gray-400">{{ $m->user?->name ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-5 py-12 text-center text-gray-400">Aucun mouvement enregistré</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($movements->hasPages())
            <div class="p-4 border-t border-gray-100 dark:border-slate-700/50">
                {{ $movements->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
