@extends('layouts.guest')

@section('title', 'Mot de passe oublié')

@section('content')
<div class="w-full max-w-md mx-auto">
    <div class="bg-white/80 dark:bg-slate-800/80 backdrop-blur-xl rounded-2xl shadow-lg border border-gray-100 dark:border-slate-700/50 p-8">
        <div class="text-center mb-6">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Mot de passe oublié?</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">
                Entrez votre adresse email professionnelle.<br>
                Vous recevrez un lien sécurisé valable 15 minutes.
            </p>
        </div>

        @if(session('success'))
        <div class="mb-4 p-3 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg text-sm text-green-700 dark:text-green-300">
            {{ session('success') }}
        </div>
        @endif
        @if(session('error'))
        <div class="mb-4 p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg text-sm text-red-700 dark:text-red-300">
            {{ session('error') }}
        </div>
        @endif

        <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Adresse Email</label>
                <input type="email" name="email" value="{{ old('email') }}" required autofocus
                       class="w-full px-4 py-3 border border-gray-200 dark:border-slate-600 dark:bg-slate-700 dark:text-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-amber-500 outline-none"
                       placeholder="votre@email.com">
                @error('email')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
            <button type="submit"
                    class="w-full py-3 bg-amber-500 text-white font-medium rounded-lg hover:bg-amber-600 transition shadow-sm">
                Envoyer le lien de réinitialisation
            </button>
        </form>

        <div class="mt-4 text-center">
            <a href="{{ route('login') }}" class="text-sm text-amber-600 dark:text-amber-400 hover:underline">
                ← Retour à la connexion
            </a>
        </div>
    </div>
</div>
@endsection
