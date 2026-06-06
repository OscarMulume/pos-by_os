@extends('layouts.app')

@section('title', 'Modifier le produit')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">
    <!-- Header -->
    <div>
        <h2 class="text-2xl font-bold text-gray-800">Modifier le produit</h2>
        <p class="text-sm text-gray-500">Modifiez les informations du produit "{{ $product->name }}".</p>
    </div>

    <!-- Form -->
    <form action="{{ route('admin.products.update', $product) }}" method="POST" enctype="multipart/form-data"
          class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        @csrf
        @method('PUT')

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
                <input type="text" name="name" id="name" value="{{ old('name', $product->name) }}"
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
                          placeholder="Description du produit..." maxlength="500">{{ old('description', $product->description) }}</textarea>
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
                    <option value="{{ $category->id }}" {{ old('category_id', $product->category_id) == $category->id ? 'selected' : '' }}>
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
                    <input type="number" name="price" id="price" value="{{ old('price', $product->price) }}" step="0.01" min="0"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('price') border-red-500 focus:ring-red-500 focus:border-red-500 @enderror"
                           placeholder="0.00" required>
                    @error('price')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="cost_price" class="block text-sm font-medium text-gray-700 mb-1">Prix de revient (DH)</label>
                    <input type="number" name="cost_price" id="cost_price" value="{{ old('cost_price', $product->cost_price) }}" step="0.01" min="0"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('cost_price') border-red-500 focus:ring-red-500 focus:border-red-500 @enderror"
                           placeholder="0.00">
                    @error('cost_price')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Sort order & Available -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="sort_order" class="block text-sm font-medium text-gray-700 mb-1">Ordre d'affichage</label>
                    <input type="number" name="sort_order" id="sort_order" value="{{ old('sort_order', $product->sort_order) }}" min="0"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('sort_order') border-red-500 focus:ring-red-500 focus:border-red-500 @enderror"
                           placeholder="0">
                    @error('sort_order')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div class="flex items-end pb-1">
                    <label class="inline-flex items-center cursor-pointer">
                        <input type="hidden" name="is_available" value="0">
                        <input type="checkbox" name="is_available" id="is_available" value="1" {{ old('is_available', $product->is_available) ? 'checked' : '' }}
                               class="w-5 h-5 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                        <span class="ml-2 text-sm text-gray-700">Produit disponible</span>
                    </label>
                    @error('is_available')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Current Image -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Image du produit</label>
                <div class="flex flex-col gap-4">
                    <div class="flex items-start gap-4">
                        <!-- Current image preview -->
                        <div id="image-preview" class="w-24 h-24 rounded-lg overflow-hidden bg-gray-100 flex-shrink-0">
                            @if($product->image_path)
                                <img src="{{ asset('storage/' . $product->image_path) }}"
                                     alt="{{ $product->name }}"
                                     class="w-full h-full object-cover"
                                     id="current-image">
                            @else
                                <div class="w-full h-full flex items-center justify-center text-gray-400" id="current-image-placeholder">
                                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                </div>
                            @endif
                        </div>
                        <div class="flex-1">
                            <input type="file" name="image" id="image" accept="image/*"
                                   class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
                                   onchange="previewImage(this)">
                            <p class="mt-1 text-xs text-gray-400">PNG, JPG, JPEG. Max 2 Mo. Laissez vide pour conserver l'image actuelle.</p>
                            @error('image')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror

                            <!-- Delete image checkbox -->
                            @if($product->image_path)
                            <label class="inline-flex items-center mt-3 cursor-pointer">
                                <input type="checkbox" name="delete_image" value="1"
                                       class="w-4 h-4 text-red-600 border-gray-300 rounded focus:ring-red-500"
                                       onchange="toggleImageDelete(this)">
                                <span class="ml-2 text-sm text-red-600">Supprimer l'image actuelle</span>
                            </label>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex items-center justify-between">
            <!-- Delete button -->
            <form action="{{ route('admin.products.destroy', $product) }}" method="POST"
                  onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce produit ? Cette action est irréversible.')">
                @csrf
                @method('DELETE')
                <button type="submit"
                        class="px-4 py-2 text-sm font-medium text-red-600 bg-white border border-red-300 rounded-lg hover:bg-red-50 transition">
                    Supprimer le produit
                </button>
            </form>

            <div class="flex items-center gap-3">
                <a href="{{ route('admin.products.index') }}"
                   class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                    Annuler
                </a>
                <button type="submit"
                        class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition shadow-sm">
                    Enregistrer les modifications
                </button>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
function previewImage(input) {
    const previewContainer = document.getElementById('image-preview');
    if (input.files && input.files[0]) {
        // Uncheck delete image if selecting a new one
        const deleteCheck = document.querySelector('input[name="delete_image"]');
        if (deleteCheck) deleteCheck.checked = false;

        // Remove current image or placeholder
        const currentImg = document.getElementById('current-image');
        const currentPlaceholder = document.getElementById('current-image-placeholder');
        if (currentImg) currentImg.remove();
        if (currentPlaceholder) currentPlaceholder.remove();

        const reader = new FileReader();
        reader.onload = function(e) {
            const img = document.createElement('img');
            img.src = e.target.result;
            img.className = 'w-full h-full object-cover';
            img.id = 'new-image-preview';
            previewContainer.appendChild(img);
        };
        reader.readAsDataURL(input.files[0]);
    }
}

function toggleImageDelete(checkbox) {
    const currentImg = document.getElementById('current-image');
    if (checkbox.checked && currentImg) {
        currentImg.style.opacity = '0.3';
    } else if (currentImg) {
        currentImg.style.opacity = '1';
    }
    // Clear file input if delete is checked
    if (checkbox.checked) {
        document.getElementById('image').value = '';
        const newPreview = document.getElementById('new-image-preview');
        if (newPreview) newPreview.remove();
    }
}
</script>
@endpush
