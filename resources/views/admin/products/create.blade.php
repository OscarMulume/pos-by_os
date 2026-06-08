@extends('layouts.app')

@section('title', 'Ajouter un produit')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">
    <!-- Header -->
    <div>
        <h2 class="text-2xl font-bold text-gray-800">Ajouter un produit</h2>
        <p class="text-sm text-gray-500">Remplissez le formulaire pour créer un nouveau produit.</p>
    </div>

    <!-- Form -->
    <form action="{{ route('admin.products.store') }}" method="POST" enctype="multipart/form-data"
          class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        @csrf

        <div class="p-6 space-y-6">
            <!-- Validation errors -->
            @if($errors->any())
            <div class="p-4 bg-red-50 border border-red-200 rounded-lg">
                <h3 class="text-sm font-medium text-red-800 mb-2">Veuillez corriger les erreurs suivantes :</h3>
                <ul class="list-disc list-inside text-sm text-red-700 space-y-1">
                    @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
            @endif

            <!-- Name -->
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Nom du produit <span class="text-red-500">*</span></label>
                <input type="text" name="name" id="name" value="{{ old('name') }}"
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('name') border-red-500 focus:ring-red-500 focus:border-red-500 @enderror"
                       placeholder="Ex: Couscous Royal" required maxlength="100">
                @error('name')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Description -->
            <div>
                <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                <textarea name="description" id="description" rows="3"
                          class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('description') border-red-500 focus:ring-red-500 focus:border-red-500 @enderror"
                          placeholder="Description du produit..." maxlength="500">{{ old('description') }}</textarea>
                @error('description')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Category -->
            <div>
                <label for="category_id" class="block text-sm font-medium text-gray-700 mb-1">Catégorie</label>
                <select name="category_id" id="category_id"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('category_id') border-red-500 focus:ring-red-500 focus:border-red-500 @enderror">
                    <option value="">-- Sélectionner une catégorie --</option>
                    @foreach($categories as $category)
                    <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>
                        {{ $category->name }}
                    </option>
                    @endforeach
                </select>
                @error('category_id')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Price & Cost -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="price" class="block text-sm font-medium text-gray-700 mb-1">Prix de vente (DH) <span class="text-red-500">*</span></label>
                    <input type="number" name="price" id="price" value="{{ old('price') }}" step="0.01" min="0"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('price') border-red-500 focus:ring-red-500 focus:border-red-500 @enderror"
                           placeholder="0.00" required>
                    @error('price')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="cost_price" class="block text-sm font-medium text-gray-700 mb-1">Prix de revient (DH)</label>
                    <input type="number" name="cost_price" id="cost_price" value="{{ old('cost_price') }}" step="0.01" min="0"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('cost_price') border-red-500 focus:ring-red-500 focus:border-red-500 @enderror"
                           placeholder="0.00">
                    @error('cost_price')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Sort order & Available -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label for="sort_order" class="block text-sm font-medium text-gray-700 mb-1">Ordre d'affichage</label>
                    <input type="number" name="sort_order" id="sort_order" value="{{ old('sort_order', 0) }}" min="0"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('sort_order') border-red-500 @enderror"
                           placeholder="0">
                    @error('sort_order')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <!-- Prep time -->
                <div>
                    <label for="prep_time_minutes" class="block text-sm font-medium text-gray-700 mb-1">Temps préparation (min)</label>
                    <input type="number" name="prep_time_minutes" id="prep_time_minutes" value="{{ old('prep_time_minutes', 15) }}" min="1" max="120"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('prep_time_minutes') border-red-500 @enderror"
                           placeholder="15">
                    <p class="mt-1 text-xs text-gray-400">Temps estimé pour le minuteur KDS</p>
                    @error('prep_time_minutes')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div class="flex items-end pb-1">
                    <label class="inline-flex items-center cursor-pointer">
                        <input type="hidden" name="is_available" value="0">
                        <input type="checkbox" name="is_available" id="is_available" value="1" {{ old('is_available', true) ? 'checked' : '' }}
                               class="w-5 h-5 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                        <span class="ml-2 text-sm text-gray-700">Produit disponible</span>
                    </label>
                </div>
            </div>

            <!-- Routage cuisine -->
            <div>
                <label for="kitchen_route" class="block text-sm font-medium text-gray-700 mb-1">Destination en cuisine</label>
                <select name="kitchen_route" id="kitchen_route"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('kitchen_route') border-red-500 @enderror">
                    <option value="kitchen" {{ old('kitchen_route', 'kitchen') == 'kitchen' ? 'selected' : '' }}>🍳 Cuisine (KDS) — Plats, Entrées</option>
                    <option value="bar" {{ old('kitchen_route') == 'bar' ? 'selected' : '' }}>🍸 Bar — Boissons, Cocktails</option>
                    <option value="counter" {{ old('kitchen_route') == 'counter' ? 'selected' : '' }}>📦 Comptoir — À emporter, Desserts</option>
                </select>
                <p class="mt-1 text-xs text-gray-400">Où ce produit doit-il être préparé/servi ?</p>
                @error('kitchen_route')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Gestion de stock -->
            <div class="border-t border-gray-200 pt-4 mt-4">
                <h3 class="text-sm font-semibold text-gray-700 mb-3">📦 Gestion de stock</h3>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label for="track_inventory" class="inline-flex items-center cursor-pointer">
                            <input type="hidden" name="track_inventory" value="0">
                            <input type="checkbox" name="track_inventory" id="track_inventory" value="1" {{ old('track_inventory', true) ? 'checked' : '' }}
                                   class="w-5 h-5 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                            <span class="ml-2 text-sm text-gray-700">Suivre le stock</span>
                        </label>
                    </div>
                    <div>
                        <label for="stock_alert_threshold" class="block text-sm font-medium text-gray-700 mb-1">Seuil d'alerte critique</label>
                        <input type="number" name="stock_alert_threshold" id="stock_alert_threshold" value="{{ old('stock_alert_threshold', 5) }}" min="1" max="999"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-orange-500 focus:border-orange-500 @error('stock_alert_threshold') border-red-500 @enderror"
                               placeholder="5">
                        <p class="mt-1 text-xs text-gray-400">Alerte orange si stock ≤ cette valeur</p>
                        @error('stock_alert_threshold')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="low_stock_threshold" class="block text-sm font-medium text-gray-700 mb-1">Seuil stock bas</label>
                        <input type="number" name="low_stock_threshold" id="low_stock_threshold" value="{{ old('low_stock_threshold', 10) }}" min="1" max="999"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500 @error('low_stock_threshold') border-red-500 @enderror"
                               placeholder="10">
                        <p class="mt-1 text-xs text-gray-400">Alerte jaune si stock ≤ cette valeur</p>
                        @error('low_stock_threshold')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Image -->
            <div>
                <label for="image" class="block text-sm font-medium text-gray-700 mb-1">Image du produit</label>
                <div class="flex items-center gap-4">
                    <div id="image-preview" class="w-20 h-20 rounded-lg overflow-hidden bg-gray-100 flex-shrink-0 flex items-center justify-center text-gray-400">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <div class="flex-1">
                        <input type="file" name="image" id="image" accept="image/*"
                               class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
                               onchange="previewImage(this)">
                        <p class="mt-1 text-xs text-gray-400">PNG, JPG, JPEG. Max 2 Mo.</p>
                        @error('image')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex items-center justify-end gap-3">
            <a href="{{ route('admin.products.index') }}"
               class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                Annuler
            </a>
            <button type="submit"
                    class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition shadow-sm">
                Créer le produit
            </button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
function previewImage(input) {
    const preview = document.getElementById('image-preview');
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.innerHTML = '<img src="' + e.target.result + '" class="w-full h-full object-cover">';
        };
        reader.readAsDataURL(input.files[0]);
    }
}
</script>
@endpush
