@extends('layouts.app')

@section('title', 'Modifier Employé')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">
    <div>
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Modifier l'Employé</h2>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ $user->name }} · {{ $user->getRoleLabel() }}</p>
    </div>

    <!-- Formulaire principal -->
    <form action="{{ route('admin.users.update', $user) }}" method="POST"
          class="bg-white/80 dark:bg-slate-800/80 backdrop-blur-xl rounded-xl shadow-sm border border-gray-100 dark:border-slate-700/50 overflow-hidden">
        @csrf
        @method('PUT')

        <div class="p-6 space-y-6">
            @if($errors->any())
            <div class="p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                <h3 class="text-sm font-medium text-red-800 dark:text-red-300 mb-2">Veuillez corriger les erreurs :</h3>
                <ul class="list-disc list-inside text-sm text-red-700 dark:text-red-400 space-y-1">
                    @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
            @endif

            <!-- Section: Informations Personnelles -->
            <div>
                <h3 class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-4">Informations Personnelles</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nom Complet <span class="text-red-500">*</span></label>
                        <input type="text" name="name" value="{{ old('name', $user->name) }}" required maxlength="255"
                               class="w-full px-4 py-2.5 border border-gray-200 dark:border-slate-600 dark:bg-slate-700 dark:text-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-amber-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nom d'utilisateur <span class="text-red-500">*</span></label>
                        <input type="text" name="username" value="{{ old('username', $user->username) }}" required maxlength="50"
                               class="w-full px-4 py-2.5 border border-gray-200 dark:border-slate-600 dark:bg-slate-700 dark:text-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-amber-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Téléphone</label>
                        <input type="tel" name="phone" value="{{ old('phone', $user->phone) }}" maxlength="20"
                               class="w-full px-4 py-2.5 border border-gray-200 dark:border-slate-600 dark:bg-slate-700 dark:text-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-amber-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Email <span class="text-red-500">*</span></label>
                        <input type="email" name="email" value="{{ old('email', $user->email) }}" required maxlength="255"
                               class="w-full px-4 py-2.5 border border-gray-200 dark:border-slate-600 dark:bg-slate-700 dark:text-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-amber-500 outline-none">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Adresse</label>
                        <input type="text" name="address" value="{{ old('address', $user->address) }}" maxlength="255"
                               class="w-full px-4 py-2.5 border border-gray-200 dark:border-slate-600 dark:bg-slate-700 dark:text-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-amber-500 outline-none">
                    </div>
                </div>
            </div>

            <!-- Section: Sécurité -->
            <div>
                <h3 class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-4">Sécurité</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nouveau mot de passe</label>
                        <input type="password" name="password" minlength="6"
                               class="w-full px-4 py-2.5 border border-gray-200 dark:border-slate-600 dark:bg-slate-700 dark:text-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-amber-500 outline-none"
                               placeholder="Laisser vide pour conserver">
                        <p class="text-xs text-gray-400 mt-1">Laissez vide pour conserver le mot de passe actuel</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nouveau PIN</label>
                        <input type="text" name="pin_code" minlength="4" maxlength="8"
                               class="w-full px-4 py-2.5 border border-gray-200 dark:border-slate-600 dark:bg-slate-700 dark:text-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-amber-500 outline-none"
                               placeholder="Laisser vide pour conserver">
                        <p class="text-xs text-gray-400 mt-1">Laissez vide pour conserver le PIN actuel</p>
                    </div>
                </div>
            </div>

            <!-- Section: Affectation -->
            <div>
                <h3 class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-4">Affectation</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Rôle <span class="text-red-500">*</span></label>
                        <select name="role" required
                                class="w-full px-4 py-2.5 border border-gray-200 dark:border-slate-600 dark:bg-slate-700 dark:text-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-amber-500 outline-none">
                            @foreach($roles as $key => $label)
                            <option value="{{ $key }}" {{ old('role', $user->role) === $key ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Terminal POS</label>
                        <select name="pos_terminal_id"
                                class="w-full px-4 py-2.5 border border-gray-200 dark:border-slate-600 dark:bg-slate-700 dark:text-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-amber-500 outline-none">
                            <option value="">-- Non assigné --</option>
                            @foreach($terminals as $terminal)
                            <option value="{{ $terminal->id }}" {{ old('pos_terminal_id', $user->pos_terminal_id) == $terminal->id ? 'selected' : '' }}>
                                {{ $terminal->name }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                @if($user->started_at)
                <p class="text-xs text-gray-400 mt-2">Date d'embauche: {{ $user->started_at->format('d/m/Y H:i') }}</p>
                @endif
            </div>

            <!-- Is Active -->
            <div>
                <label class="inline-flex items-center cursor-pointer">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', $user->is_active) ? 'checked' : '' }}
                           class="w-5 h-5 text-amber-500 border-gray-300 rounded focus:ring-amber-500">
                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Employé actif</span>
                </label>
            </div>
        </div>

        <div class="px-6 py-4 bg-gray-50/80 dark:bg-slate-900/50 border-t border-gray-100 dark:border-slate-700/50 flex items-center justify-between">
            <form action="{{ route('admin.users.destroy', $user) }}" method="POST"
                  onsubmit="return confirm('Supprimer cet employé ? Cette action est irréversible.')">
                @csrf
                @method('DELETE')
                <button type="submit"
                        class="px-4 py-2 text-sm font-medium text-red-600 dark:text-red-400 bg-white dark:bg-slate-700 border border-red-200 dark:border-red-800 rounded-lg hover:bg-red-50 dark:hover:bg-red-900/20 transition">
                    Supprimer
                </button>
            </form>
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.users.index') }}"
                   class="px-4 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-slate-700 border border-gray-200 dark:border-slate-600 rounded-lg hover:bg-gray-50 dark:hover:bg-slate-600 transition">
                    Annuler
                </a>
                <button type="submit"
                        class="px-5 py-2.5 text-sm font-medium text-white bg-amber-500 rounded-lg hover:bg-amber-600 transition shadow-sm">
                    Enregistrer
                </button>
            </div>
        </div>
    </form>

    <!-- Section: Réinitialisation rapide du PIN (Option B) -->
    <div class="bg-white/80 dark:bg-slate-800/80 backdrop-blur-xl rounded-xl shadow-sm border border-gray-100 dark:border-slate-700/50 p-6">
        <h3 class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-3">Réinitialisation Rapide du PIN</h3>
        <p class="text-xs text-gray-400 mb-4">Si l'employé a oublié son PIN, saisissez un nouveau PIN provisoire. Il pourra se connecter immédiatement.</p>
        <form action="{{ route('admin.users.reset-pin', $user) }}" method="POST" class="flex gap-3">
            @csrf
            <input type="text" name="new_pin" required minlength="4" maxlength="8" pattern="[0-9]*"
                   class="flex-1 max-w-xs px-4 py-2.5 border border-gray-200 dark:border-slate-600 dark:bg-slate-700 dark:text-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-amber-500 outline-none"
                   placeholder="Nouveau PIN (ex: 1234)">
            <button type="submit"
                    class="px-5 py-2.5 text-sm font-medium text-white bg-slate-800 dark:bg-slate-600 rounded-lg hover:bg-slate-700 transition">
                Réinitialiser le PIN
            </button>
        </form>
    </div>
</div>
@endsection
