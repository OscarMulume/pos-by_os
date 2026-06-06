@extends('layouts.guest')

@section('title', 'Réinitialiser le mot de passe')

@section('content')
<div class="w-full max-w-md mx-auto">
    <div class="bg-white/80 dark:bg-slate-800/80 backdrop-blur-xl rounded-2xl shadow-lg border border-gray-100 dark:border-slate-700/50 p-8">
        <div class="text-center mb-6">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Nouveau mot de passe</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">
                Saisissez et confirmez votre nouveau mot de passe.
            </p>
        </div>

        @if(session('error'))
        <div class="mb-4 p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg text-sm text-red-700 dark:text-red-300">
            {{ session('error') }}
        </div>
        @endif

        <form method="POST" action="{{ route('password.update') }}" class="space-y-4">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Email</label>
                <input type="email" name="email" value="{{ old('email', $email) }}" required readonly
                       class="w-full px-4 py-3 border border-gray-200 dark:border-slate-600 dark:bg-slate-700 dark:text-gray-200 rounded-lg text-sm bg-gray-50 dark:bg-slate-800">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nouveau mot de passe</label>
                <input type="password" name="password" required minlength="6"
                       class="w-full px-4 py-3 border border-gray-200 dark:border-slate-600 dark:bg-slate-700 dark:text-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-amber-500 outline-none"
                       placeholder="Min. 6 caractères">
                @error('password')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Confirmer le mot de passe</label>
                <input type="password" name="password_confirmation" required minlength="6"
                       class="w-full px-4 py-3 border border-gray-200 dark:border-slate-600 dark:bg-slate-700 dark:text-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-amber-500 outline-none"
                       placeholder="Retapez le mot de passe">
            </div>

            <button type="submit"
                    class="w-full py-3 bg-amber-500 text-white font-medium rounded-lg hover:bg-amber-600 transition shadow-sm">
                Réinitialiser le mot de passe
            </button>
        </form>
    </div>
</div>
@endsection
