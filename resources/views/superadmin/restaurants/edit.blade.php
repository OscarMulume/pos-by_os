@extends('layouts.superadmin')
@section('title', 'Modifier — ' . $restaurant->name)

@section('content')
<div class="max-w-3xl mx-auto">
    <div class="mb-6">
        <a href="{{ route('superadmin.restaurants.index') }}" class="text-sm text-gray-500 dark:text-gray-400 hover:text-amber-600 dark:hover:text-amber-400 transition">
            ← Retour à la liste
        </a>
    </div>

    <div class="bg-white/80 dark:bg-slate-800/80 backdrop-blur-xl rounded-xl shadow-sm border border-gray-100 dark:border-slate-700/50">
        <div class="p-6 border-b border-gray-100 dark:border-slate-700/50">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white">Modifier : {{ $restaurant->name }}</h2>
        </div>

        <form method="POST" action="{{ route('superadmin.restaurants.update', $restaurant) }}" enctype="multipart/form-data" class="p-6 space-y-6">
            @csrf
            @method('PUT')

            <div>
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-4">Informations Générales</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nom du restaurant <span class="text-red-500">*</span></label>
                        <input type="text" name="name" value="{{ old('name', $restaurant->name) }}" required
                               class="w-full px-4 py-2.5 border border-gray-200 dark:border-slate-600 dark:bg-slate-700 dark:text-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none">
                        @error('name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Adresse</label>
                        <input type="text" name="address" value="{{ old('address', $restaurant->address) }}"
                               class="w-full px-4 py-2.5 border border-gray-200 dark:border-slate-600 dark:bg-slate-700 dark:text-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Téléphone</label>
                        <input type="text" name="phone" value="{{ old('phone', $restaurant->phone) }}"
                               class="w-full px-4 py-2.5 border border-gray-200 dark:border-slate-600 dark:bg-slate-700 dark:text-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Email</label>
                        <input type="email" name="email" value="{{ old('email', $restaurant->email) }}"
                               class="w-full px-4 py-2.5 border border-gray-200 dark:border-slate-600 dark:bg-slate-700 dark:text-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none">
                    </div>
                </div>
            </div>

            <div>
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-4">Configuration</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Type <span class="text-red-500">*</span></label>
                        <select name="type" required class="w-full px-4 py-2.5 border border-gray-200 dark:border-slate-600 dark:bg-slate-700 dark:text-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none">
                            <option value="permanent" {{ old('type', $restaurant->type) === 'permanent' ? 'selected' : '' }}>Permanent</option>
                            <option value="ephemere" {{ old('type', $restaurant->type) === 'ephemere' ? 'selected' : '' }}>Éphémère (Pop-up)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Statut <span class="text-red-500">*</span></label>
                        <select name="status" required class="w-full px-4 py-2.5 border border-gray-200 dark:border-slate-600 dark:bg-slate-700 dark:text-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none">
                            <option value="active" {{ old('status', $restaurant->status) === 'active' ? 'selected' : '' }}>Actif</option>
                            <option value="inactive" {{ old('status', $restaurant->status) === 'inactive' ? 'selected' : '' }}>Inactif</option>
                            <option value="suspended" {{ old('status', $restaurant->status) === 'suspended' ? 'selected' : '' }}>Suspendu</option>
                            <option value="ferme_temporairement" {{ old('status', $restaurant->status) === 'ferme_temporairement' ? 'selected' : '' }}>Fermé temporairement</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Devise</label>
                        <input type="text" name="currency" value="{{ old('currency', $restaurant->currency) }}" maxlength="10"
                               class="w-full px-4 py-2.5 border border-gray-200 dark:border-slate-600 dark:bg-slate-700 dark:text-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Taux de taxe (%)</label>
                        <input type="number" name="tax_rate" value="{{ old('tax_rate', $restaurant->tax_rate) }}" min="0" max="100" step="0.01"
                               class="w-full px-4 py-2.5 border border-gray-200 dark:border-slate-600 dark:bg-slate-700 dark:text-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Fin d'abonnement</label>
                        <input type="date" name="subscription_ends_at" value="{{ old('subscription_ends_at', $restaurant->subscription_ends_at?->format('Y-m-d')) }}"
                               class="w-full px-4 py-2.5 border border-gray-200 dark:border-slate-600 dark:bg-slate-700 dark:text-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none">
                    </div>
                </div>
            </div>

            <div>
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-4">Personnalisation du Reçu</h3>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">En-tête du reçu</label>
                        <input type="text" name="receipt_header" value="{{ old('receipt_header', $restaurant->receipt_header) }}" maxlength="255"
                               class="w-full px-4 py-2.5 border border-gray-200 dark:border-slate-600 dark:bg-slate-700 dark:text-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Pied du reçu</label>
                        <input type="text" name="receipt_footer" value="{{ old('receipt_footer', $restaurant->receipt_footer) }}" maxlength="255"
                               class="w-full px-4 py-2.5 border border-gray-200 dark:border-slate-600 dark:bg-slate-700 dark:text-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none">
                    </div>
                </div>
            </div>

            <div>
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-4">Images</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Logo</label>
                        @if($restaurant->logo_path)
                            <div class="flex items-center space-x-3 mb-2">
                                <img src="{{ asset('storage/' . $restaurant->logo_path) }}" alt="" class="w-12 h-12 rounded-lg object-cover border border-gray-100 dark:border-slate-600">
                                <label class="flex items-center text-xs text-red-600 dark:text-red-400">
                                    <input type="checkbox" name="remove_logo" value="1" class="mr-1"> Supprimer
                                </label>
                            </div>
                        @endif
                        <input type="file" name="logo" accept="image/png,image/jpeg,image/webp"
                               class="w-full px-4 py-2.5 border border-gray-200 dark:border-slate-600 dark:bg-slate-700 rounded-lg text-sm file:mr-4 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:text-xs file:font-medium file:bg-amber-50 file:text-amber-700 hover:file:bg-amber-100 dark:file:bg-amber-900/30 dark:file:text-amber-300">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Photo</label>
                        @if($restaurant->photo_path)
                            <div class="flex items-center space-x-3 mb-2">
                                <img src="{{ asset('storage/' . $restaurant->photo_path) }}" alt="" class="w-12 h-12 rounded-lg object-cover border border-gray-100 dark:border-slate-600">
                                <label class="flex items-center text-xs text-red-600 dark:text-red-400">
                                    <input type="checkbox" name="remove_photo" value="1" class="mr-1"> Supprimer
                                </label>
                            </div>
                        @endif
                        <input type="file" name="photo" accept="image/png,image/jpeg,image/webp"
                               class="w-full px-4 py-2.5 border border-gray-200 dark:border-slate-600 dark:bg-slate-700 rounded-lg text-sm file:mr-4 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:text-xs file:font-medium file:bg-amber-50 file:text-amber-700 hover:file:bg-amber-100 dark:file:bg-amber-900/30 dark:file:text-amber-300">
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-end space-x-3 pt-4 border-t border-gray-100 dark:border-slate-700/50">
                <a href="{{ route('superadmin.restaurants.index') }}" class="px-5 py-2.5 text-sm font-medium text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200 transition">Annuler</a>
                <button type="submit" class="px-6 py-2.5 bg-amber-500 text-white text-sm font-medium rounded-lg hover:bg-amber-600 transition shadow-sm">
                    Mettre à Jour
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
