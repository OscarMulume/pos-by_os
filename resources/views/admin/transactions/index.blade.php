@extends('layouts.app')

@section('title', 'Transactions')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Transactions</h2>
            <p class="text-sm text-gray-500">{{ $transactions->total() }} transaction(s) au total</p>
        </div>
        <a href="{{ route('admin.transactions.index', array_merge(request()->query(), ['export' => 'csv'])) }}"
           class="inline-flex items-center px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 transition shadow-sm">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            Exporter CSV
        </a>
    </div>

    <!-- Filters -->
    <form method="GET" action="{{ route('admin.transactions.index') }}" class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
            <!-- Restaurant -->
            <div>
                <label for="restaurant_id" class="block text-xs font-medium text-gray-500 mb-1">Restaurant</label>
                <select name="restaurant_id" id="restaurant_id"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <option value="">Tous</option>
                    @foreach(\App\Models\Restaurant::orderBy('name')->get() as $restaurant)
                        <option value="{{ $restaurant->id }}" {{ (request('restaurant_id') == $restaurant->id) ? 'selected' : '' }}>
                            {{ $restaurant->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Start Date -->
            <div>
                <label for="start_date" class="block text-xs font-medium text-gray-500 mb-1">Date début</label>
                <input type="date" name="start_date" id="start_date"
                       value="{{ request('start_date') }}"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            </div>

            <!-- End Date -->
            <div>
                <label for="end_date" class="block text-xs font-medium text-gray-500 mb-1">Date fin</label>
                <input type="date" name="end_date" id="end_date"
                       value="{{ request('end_date') }}"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            </div>

            <!-- Payment Method -->
            <div>
                <label for="payment_method" class="block text-xs font-medium text-gray-500 mb-1">Méthode de paiement</label>
                <select name="payment_method" id="payment_method"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <option value="">Toutes</option>
                    <option value="cash" {{ request('payment_method') === 'cash' ? 'selected' : '' }}>Espèces</option>
                    <option value="mobile_money" {{ request('payment_method') === 'mobile_money' ? 'selected' : '' }}>Mobile Money</option>
                    <option value="credit" {{ request('payment_method') === 'credit' ? 'selected' : '' }}>Crédit</option>
                </select>
            </div>

            <!-- Status -->
            <div>
                <label for="status" class="block text-xs font-medium text-gray-500 mb-1">Statut</label>
                <select name="status" id="status"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <option value="">Tous</option>
                    <option value="paid" {{ request('status') === 'paid' ? 'selected' : '' }}>Payé</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>En attente</option>
                    <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Annulé</option>
                </select>
            </div>
        </div>

        <!-- Filter actions -->
        <div class="flex items-center gap-2 mt-4 pt-4 border-t border-gray-100">
            <button type="submit"
                    class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition">
                <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                </svg>
                Filtrer
            </button>
            @if(request()->hasAny(['restaurant_id', 'start_date', 'end_date', 'payment_method', 'status']))
                <a href="{{ route('admin.transactions.index') }}"
                   class="px-4 py-2 bg-gray-100 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-200 transition">
                    Réinitialiser
                </a>
            @endif
        </div>
    </form>

    <!-- Table -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="bg-gray-50 text-xs uppercase text-gray-500 font-semibold">
                    <tr>
                        <th class="px-6 py-3">N° Commande</th>
                        <th class="px-6 py-3">Restaurant</th>
                        <th class="px-6 py-3">Caissier</th>
                        <th class="px-6 py-3">Total</th>
                        <th class="px-6 py-3">Paiement</th>
                        <th class="px-6 py-3">Date</th>
                        <th class="px-6 py-3">Statut</th>
                        <th class="px-6 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($transactions as $order)
                    <tr class="hover:bg-gray-50 transition">
                        <!-- Order # -->
                        <td class="px-6 py-3">
                            <span class="font-mono font-semibold text-gray-900">{{ $order->order_number }}</span>
                        </td>
                        <!-- Restaurant -->
                        <td class="px-6 py-3 text-gray-700">
                            {{ $order->restaurant->name ?? '-' }}
                        </td>
                        <!-- Cashier -->
                        <td class="px-6 py-3 text-gray-700">
                            {{ $order->user->name ?? '-' }}
                        </td>
                        <!-- Total -->
                        <td class="px-6 py-3 font-semibold text-gray-900">
                            {{ number_format($order->total_amount, 2) }} DH
                        </td>
                        <!-- Payment Method -->
                        <td class="px-6 py-3">
                            @php
                                $pmColors = [
                                    'cash' => 'bg-green-100 text-green-800',
                                    'mobile_money' => 'bg-purple-100 text-purple-800',
                                    'credit' => 'bg-orange-100 text-orange-800',
                                ];
                                $pmClass = $pmColors[$order->payment_method] ?? 'bg-gray-100 text-gray-800';
                            @endphp
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $pmClass }}">
                                {{ $order->payment_method_label }}
                            </span>
                        </td>
                        <!-- Date -->
                        <td class="px-6 py-3 text-gray-500">
                            {{ $order->created_at->format('d/m/Y H:i') }}
                        </td>
                        <!-- Status -->
                        <td class="px-6 py-3">
                            @php
                                $statusColors = [
                                    'paid' => 'bg-green-100 text-green-800',
                                    'cancelled' => 'bg-red-100 text-red-800',
                                    'pending' => 'bg-yellow-100 text-yellow-800',
                                ];
                                $sClass = $statusColors[$order->status] ?? 'bg-gray-100 text-gray-800';
                            @endphp
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $sClass }}">
                                {{ $order->status_label }}
                            </span>
                        </td>
                        <!-- Actions -->
                        <td class="px-6 py-3 text-right">
                            <a href="{{ route('admin.transactions.show', $order) }}"
                               class="inline-flex items-center p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition"
                               title="Voir détails">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-6 py-12 text-center">
                            <svg class="w-12 h-12 mx-auto text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                            </svg>
                            <p class="mt-3 text-gray-500 font-medium">Aucune transaction trouvée</p>
                            <p class="text-sm text-gray-400 mt-1">Essayez de modifier vos filtres</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pagination -->
    @if($transactions->hasPages())
    <div class="flex flex-col sm:flex-row items-center justify-between bg-white rounded-xl shadow-sm border border-gray-200 px-4 py-3 gap-3">
        <div class="text-sm text-gray-500">
            Affichage de {{ $transactions->firstItem() }} à {{ $transactions->lastItem() }} sur {{ $transactions->total() }} résultats
        </div>
        <div>
            {{ $transactions->appends(request()->query())->links() }}
        </div>
    </div>
    @endif
</div>
@endsection
