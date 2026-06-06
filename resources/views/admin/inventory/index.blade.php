@extends('layouts.app')
@section('title', 'Inventaire')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Gestion des Stocks</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ $stats['total_products'] }} produits · {{ $stats['tracked'] }} suivis</p>
        </div>
        <div class="flex items-center gap-3">
            @if($stats['low_stock'] > 0)
                <span class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-medium bg-amber-50 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400">
                    ⚠️ {{ $stats['low_stock'] }} stock(s) bas
                </span>
            @endif
            @if($stats['out_of_stock'] > 0)
                <span class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-medium bg-red-50 text-red-700 dark:bg-red-900/30 dark:text-red-400">
                    🚫 {{ $stats['out_of_stock'] }} en rupture
                </span>
            @endif
        </div>
    </div>

    <!-- Filtres -->
    <div class="bg-white/80 dark:bg-slate-800/80 backdrop-blur-xl rounded-xl shadow-sm border border-gray-100 dark:border-slate-700/50 p-4">
        <div class="flex flex-col sm:flex-row gap-3">
            <form method="GET" action="{{ route('admin.inventory.index') }}" class="flex-1 flex gap-3">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Rechercher un produit..."
                       class="flex-1 px-4 py-2 border border-gray-200 dark:border-slate-600 dark:bg-slate-700 dark:text-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-amber-500 outline-none">
                <button type="submit" class="px-4 py-2 bg-slate-800 dark:bg-slate-600 text-white text-sm rounded-lg hover:bg-slate-700 transition">
                    Rechercher
                </button>
            </form>
            <div class="flex gap-2">
                <a href="{{ route('admin.inventory.index') }}" class="px-4 py-2 text-sm rounded-lg {{ request('filter') !== 'low' ? 'bg-amber-500 text-white' : 'bg-gray-200 dark:bg-slate-700 text-gray-700 dark:text-gray-300' }} transition">
                    Tous
                </a>
                <a href="{{ route('admin.inventory.index', ['filter' => 'low']) }}" class="px-4 py-2 text-sm rounded-lg {{ request('filter') === 'low' ? 'bg-amber-500 text-white' : 'bg-gray-200 dark:bg-slate-700 text-gray-700 dark:text-gray-300' }} transition">
                    Stock bas
                </a>
                <a href="{{ route('admin.inventory.movements') }}" class="px-4 py-2 bg-gray-200 dark:bg-slate-700 text-gray-700 dark:text-gray-300 text-sm rounded-lg hover:bg-gray-300 dark:hover:bg-slate-600 transition">
                    Historique
                </a>
            </div>
        </div>
    </div>

    <!-- Tableau -->
    <div class="bg-white/80 dark:bg-slate-800/80 backdrop-blur-xl rounded-xl shadow-sm border border-gray-100 dark:border-slate-700/50 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider bg-gray-50/80 dark:bg-slate-900/50 border-b border-gray-100 dark:border-slate-700/50">
                        <th class="px-5 py-3">Produit</th>
                        <th class="px-5 py-3">Catégorie</th>
                        <th class="px-5 py-3 text-center">Suivi</th>
                        <th class="px-5 py-3 text-center">Stock</th>
                        <th class="px-5 py-3 text-center">Seuil</th>
                        <th class="px-5 py-3 text-center">Statut</th>
                        <th class="px-5 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50 dark:divide-slate-700/30">
                    @forelse($products as $product)
                        <tr class="hover:bg-gray-50 dark:hover:bg-slate-700/20 transition">
                            <td class="px-5 py-4">
                                <div class="flex items-center">
                                    @if($product->image_path)
                                        <img src="{{ asset('storage/' . $product->image_path) }}" alt="" class="w-10 h-10 rounded-lg object-cover border border-gray-100 dark:border-slate-600">
                                    @else
                                        <div class="w-10 h-10 rounded-lg bg-gray-100 dark:bg-slate-700 flex items-center justify-center">
                                            <span class="text-sm font-bold text-gray-400">{{ strtoupper(substr($product->name, 0, 1)) }}</span>
                                        </div>
                                    @endif
                                    <div class="ml-3">
                                        <p class="text-sm font-medium text-gray-800 dark:text-gray-200">{{ $product->name }}</p>
                                        <p class="text-xs text-gray-400">{{ number_format($product->price, 0, ',', ' ') }} FC</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-4 text-sm text-gray-600 dark:text-gray-400">{{ $product->category?->name ?? '—' }}</td>
                            <td class="px-5 py-4 text-center">
                                <form method="POST" action="{{ route('admin.inventory.settings', $product) }}" class="inline">
                                    @csrf
                                    @method('PUT')
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" name="track_inventory" value="1" {{ $product->track_inventory ? 'checked' : '' }} class="sr-only peer" onchange="this.form.submit()">
                                        <div class="w-9 h-5 bg-gray-200 peer-focus:ring-2 peer-focus:ring-amber-500 rounded-full peer dark:bg-slate-600 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-amber-500"></div>
                                    </label>
                                </form>
                            </td>
                            <td class="px-5 py-4 text-center">
                                @if($product->track_inventory)
                                    <span class="text-sm font-bold {{ $product->stock_quantity <= 0 ? 'text-red-600 dark:text-red-400' : ($product->isLowStock() ? 'text-amber-600 dark:text-amber-400' : 'text-gray-900 dark:text-white') }}">
                                        {{ $product->stock_quantity }}
                                    </span>
                                @else
                                    <span class="text-sm text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="px-5 py-4 text-center text-sm text-gray-600 dark:text-gray-400">
                                {{ $product->track_inventory ? $product->low_stock_threshold : '—' }}
                            </td>
                            <td class="px-5 py-4 text-center">
                                @if(!$product->track_inventory)
                                    <span class="text-xs text-gray-400">Non suivi</span>
                                @elseif($product->stock_quantity <= 0)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-50 text-red-700 dark:bg-red-900/30 dark:text-red-400">Rupture</span>
                                @elseif($product->isLowStock())
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-amber-50 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400">Bas</span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-50 text-green-700 dark:bg-green-900/30 dark:text-green-400">OK</span>
                                @endif
                            </td>
                            <td class="px-5 py-4 text-right">
                                @if($product->track_inventory)
                                    <button onclick="openAdjustModal({{ $product->id }}, '{{ $product->name }}', {{ $product->stock_quantity }})"
                                            class="px-3 py-1.5 text-xs font-medium text-amber-600 bg-amber-50 dark:bg-amber-900/30 dark:text-amber-400 rounded-lg hover:bg-amber-100 dark:hover:bg-amber-900/50 transition">
                                        Ajuster
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-12 text-center text-gray-400">Aucun produit trouvé</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($products->hasPages())
            <div class="p-4 border-t border-gray-100 dark:border-slate-700/50">
                {{ $products->links() }}
            </div>
        @endif
    </div>
</div>

<!-- Modal ajustement stock -->
<div id="adjustModal" class="fixed inset-0 z-50 hidden">
    <div class="fixed inset-0 bg-black/50" onclick="closeAdjustModal()"></div>
    <div class="fixed inset-0 flex items-center justify-center p-4">
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-xl w-full max-w-md p-6 relative z-10">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Ajuster le Stock</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Produit : <span id="modalProductName" class="font-medium"></span> (Stock actuel : <span id="modalCurrentStock" class="font-medium"></span>)</p>
            <form method="POST" id="adjustForm">
                @csrf
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Quantité à ajouter</label>
                        <input type="number" name="quantity" value="1" min="1" required
                               class="w-full px-4 py-2.5 border border-gray-200 dark:border-slate-600 dark:bg-slate-700 dark:text-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-amber-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Motif</label>
                        <input type="text" name="reason" placeholder="Ex: Réapprovisionnement, Inventaire..." required
                               class="w-full px-4 py-2.5 border border-gray-200 dark:border-slate-600 dark:bg-slate-700 dark:text-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-amber-500 outline-none">
                    </div>
                </div>
                <div class="flex justify-end gap-3 mt-6">
                    <button type="button" onclick="closeAdjustModal()" class="px-4 py-2 text-sm text-gray-700 dark:text-gray-300 bg-gray-200 dark:bg-slate-700 rounded-lg hover:bg-gray-300 dark:hover:bg-slate-600 transition">Annuler</button>
                    <button type="submit" class="px-4 py-2 text-sm text-white bg-amber-500 rounded-lg hover:bg-amber-600 transition">Ajuster</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function openAdjustModal(productId, productName, currentStock) {
        document.getElementById('modalProductName').textContent = productName;
        document.getElementById('modalCurrentStock').textContent = currentStock;
        document.getElementById('adjustForm').action = '/admin/inventory/' + productId + '/adjust';
        document.getElementById('adjustModal').classList.remove('hidden');
    }
    function closeAdjustModal() {
        document.getElementById('adjustModal').classList.add('hidden');
    }
</script>
@endpush
@endsection
