@extends('layouts.pos')

@section('content')
<div class="flex-1 flex items-center justify-center bg-gray-100">
    <div class="bg-white rounded-2xl shadow-lg border border-gray-200 p-8 max-w-md text-center">
        <!-- Icône d'alerte -->
        <div class="w-20 h-20 bg-amber-100 rounded-full flex items-center justify-center mx-auto mb-5">
            <svg class="w-10 h-10 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
            </svg>
        </div>

        <h1 class="text-xl font-bold text-gray-800 mb-3">Aucun restaurant assigné</h1>

        <p class="text-gray-600 mb-6 leading-relaxed">
            {{ $message ?? 'Vous n\'êtes affecté à aucun restaurant. Veuillez contacter votre manager ou l\'administrateur pour être affecté à un restaurant.' }}
        </p>

        <div class="bg-gray-50 rounded-lg p-4 mb-6 text-left">
            <p class="text-sm text-gray-500 mb-2">Que faire ?</p>
            <ul class="text-sm text-gray-600 space-y-1">
                <li>1. Contactez votre <strong>manager</strong> ou <strong>administrateur</strong></li>
                <li>2. Demandez à être affecté à un restaurant</li>
                <li>3. Reconnectez-vous une fois l'affectation faite</li>
            </ul>
        </div>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="w-full bg-slate-800 text-white py-3 rounded-lg font-semibold hover:bg-slate-700 transition">
                Se déconnecter
            </button>
        </form>
    </div>
</div>
@endsection
