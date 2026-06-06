@extends('layouts.app')

@section('title', 'Modifier - ' . $restaurant->name)

@section('content')
<div class="max-w-3xl mx-auto">
    <!-- Header -->
    <div class="mb-6">
        <a href="{{ route('admin.restaurants.index') }}" class="inline-flex items-center text-sm text-gray-500 hover:text-gray-700 transition">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Retour à la liste
        </a>
        <h2 class="text-2xl font-bold text-gray-800 mt-2">Modifier le restaurant</h2>
        <p class="text-sm text-gray-500 mt-1">Modifiez les informations de « {{ $restaurant->name }} »</p>
    </div>

    <!-- Validation Errors -->
    @if($errors->any())
        <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-xl">
            <div class="flex items-center mb-2">
                <svg class="w-5 h-5 text-red-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span class="text-sm font-semibold text-red-800">Veuillez corriger les erreurs suivantes :</span>
            </div>
            <ul class="list-disc list-inside text-sm text-red-700 space-y-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Form -->
    <form action="{{ route('admin.restaurants.update', $restaurant) }}" method="POST" enctype="multipart/form-data" class="space-y-6">
        @csrf
        @method('PUT')

        <!-- General Info Card -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Informations générales</h3>

            <!-- Name -->
            <div class="mb-4">
                <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Nom du restaurant <span class="text-red-500">*</span></label>
                <input type="text" id="name" name="name" value="{{ old('name', $restaurant->name) }}" required
                       class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition @error('name') border-red-500 @enderror"
                       placeholder="Le Bistrot Parisien">
                @error('name')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Address -->
            <div class="mb-4">
                <label for="address" class="block text-sm font-medium text-gray-700 mb-1">Adresse <span class="text-red-500">*</span></label>
                <textarea id="address" name="address" rows="2" required
                          class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition @error('address') border-red-500 @enderror"
                          placeholder="12 Rue de Rivoli, 75001 Paris">{{ old('address', $restaurant->address) }}</textarea>
                @error('address')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Phone & Email -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">Téléphone <span class="text-red-500">*</span></label>
                    <input type="tel" id="phone" name="phone" value="{{ old('phone', $restaurant->phone) }}" required
                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition @error('phone') border-red-500 @enderror"
                           placeholder="+33 1 23 45 67 89">
                    @error('phone')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                    <input type="email" id="email" name="email" value="{{ old('email', $restaurant->email) }}"
                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition @error('email') border-red-500 @enderror"
                           placeholder="contact@bistrot.fr">
                    @error('email')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Logo -->
            <div>
                <label for="logo" class="block text-sm font-medium text-gray-700 mb-1">Logo</label>
                <div class="flex items-center space-x-4">
                    <div id="logo-preview" class="w-16 h-16 rounded-lg bg-gray-100 border border-gray-200 flex items-center justify-center overflow-hidden">
                        @if($restaurant->logo_path)
                            <img src="{{ asset('storage/' . $restaurant->logo_path) }}"
                                 alt="{{ $restaurant->name }}"
                                 id="current-logo"
                                 class="w-full h-full object-cover rounded-lg">
                        @else
                            <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                        @endif
                    </div>
                    <div class="flex-1">
                        @if($restaurant->logo_path)
                            <p class="text-xs text-gray-500 mb-1">Logo actuel : {{ basename($restaurant->logo_path) }}</p>
                        @endif
                        <input type="file" id="logo" name="logo" accept="image/*"
                               class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 transition">
                        <p class="mt-1 text-xs text-gray-400">PNG, JPG ou SVG. Max 2 Mo. Laissez vide pour conserver le logo actuel.</p>
                    </div>
                </div>
                @error('logo')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <!-- Business Settings Card -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Paramètres commerciaux</h3>

            <!-- Currency & Tax Rate -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label for="currency" class="block text-sm font-medium text-gray-700 mb-1">Devise <span class="text-red-500">*</span></label>
                    <select id="currency" name="currency" required
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition @error('currency') border-red-500 @enderror">
                        <option value="EUR" {{ old('currency', $restaurant->currency) == 'EUR' ? 'selected' : '' }}>EUR (€)</option>
                        <option value="USD" {{ old('currency', $restaurant->currency) == 'USD' ? 'selected' : '' }}>USD ($)</option>
                        <option value="GBP" {{ old('currency', $restaurant->currency) == 'GBP' ? 'selected' : '' }}>GBP (£)</option>
                        <option value="CHF" {{ old('currency', $restaurant->currency) == 'CHF' ? 'selected' : '' }}>CHF</option>
                        <option value="CAD" {{ old('currency', $restaurant->currency) == 'CAD' ? 'selected' : '' }}>CAD ($)</option>
                        <option value="XOF" {{ old('currency', $restaurant->currency) == 'XOF' ? 'selected' : '' }}>XOF (FCFA)</option>
                    </select>
                    @error('currency')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="tax_rate" class="block text-sm font-medium text-gray-700 mb-1">Taux de TVA (%) <span class="text-red-500">*</span></label>
                    <input type="number" id="tax_rate" name="tax_rate" value="{{ old('tax_rate', $restaurant->tax_rate) }}" step="0.01" min="0" max="100" required
                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition @error('tax_rate') border-red-500 @enderror"
                           placeholder="20.00">
                    @error('tax_rate')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Receipt Header -->
            <div class="mb-4">
                <label for="receipt_header" class="block text-sm font-medium text-gray-700 mb-1">En-tête du reçu</label>
                <textarea id="receipt_header" name="receipt_header" rows="2"
                          class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition @error('receipt_header') border-red-500 @enderror"
                          placeholder="Merci de votre visite !">{{ old('receipt_header', $restaurant->receipt_header) }}</textarea>
                @error('receipt_header')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Receipt Footer -->
            <div class="mb-4">
                <label for="receipt_footer" class="block text-sm font-medium text-gray-700 mb-1">Pied du reçu</label>
                <textarea id="receipt_footer" name="receipt_footer" rows="2"
                          class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition @error('receipt_footer') border-red-500 @enderror"
                          placeholder="TVA incluse. Conservez ce reçu.">{{ old('receipt_footer', $restaurant->receipt_footer) }}</textarea>
                @error('receipt_footer')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Is Active -->
            <div class="flex items-center">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', $restaurant->is_active) ? 'checked' : '' }}
                       class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500 transition">
                <label for="is_active" class="ml-2 text-sm font-medium text-gray-700">Restaurant actif</label>
            </div>
        </div>

        <!-- Actions -->
        <div class="flex items-center justify-between">
            <!-- Delete button on the left -->
            <form action="{{ route('admin.restaurants.destroy', $restaurant) }}" method="POST"
                  onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce restaurant ? Cette action est irréversible.');">
                @csrf
                @method('DELETE')
                <button type="submit"
                        class="inline-flex items-center px-4 py-2.5 text-sm font-medium text-red-700 bg-red-50 border border-red-200 rounded-lg hover:bg-red-100 transition">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                    Supprimer
                </button>
            </form>

            <!-- Cancel & Save on the right -->
            <div class="flex items-center space-x-3">
                <a href="{{ route('admin.restaurants.index') }}"
                   class="px-5 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                    Annuler
                </a>
                <button type="submit"
                        class="px-5 py-2.5 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition shadow-sm">
                    Enregistrer les modifications
                </button>
            </div>
        </div>
    </form>
</div>

@push('scripts')
<script>
    document.getElementById('logo').addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(event) {
                document.getElementById('logo-preview').innerHTML = '<img src="' + event.target.result + '" class="w-full h-full object-cover rounded-lg" id="current-logo">';
            };
            reader.readAsDataURL(file);
        }
    });
</script>
@endpush
@endsection
