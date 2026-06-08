@extends('layouts.app')

@section('content')
<div x-data="floorPlanSupervision()" x-init="init()" class="h-full flex flex-col">
    <!-- Header -->
    <div class="bg-white dark:bg-slate-800 border-b border-gray-200 dark:border-slate-700 px-6 py-4 flex items-center justify-between flex-shrink-0">
        <div>
            <h1 class="text-xl font-bold text-gray-800 dark:text-white">Supervision de Salle</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ $restaurant->name ?? 'Restaurant' }} — Temps réel</p>
        </div>
        <div class="flex items-center gap-4">
            <!-- Legende -->
            <div class="flex items-center gap-3 text-xs">
                <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-green-500"></span> Libre</span>
                <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-yellow-500 animate-pulse"></span> En cuisine</span>
                <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-blue-500 animate-pulse"></span> À encaisser</span>
                <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-red-500"></span> Occupée</span>
                <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-red-600 animate-ping"></span> SLA dépassé</span>
            </div>
            <button @click="refreshData()" class="px-4 py-2 bg-slate-100 dark:bg-slate-700 rounded-lg text-sm font-medium hover:bg-slate-200 dark:hover:bg-slate-600 transition">
                🔄 Actualiser
            </button>
        </div>
    </div>

    <!-- Stats rapides -->
    <div class="grid grid-cols-5 gap-4 px-6 py-4 bg-gray-50 dark:bg-slate-900/50 flex-shrink-0">
        <div class="bg-white dark:bg-slate-800 rounded-xl p-4 border border-gray-200 dark:border-slate-700 text-center">
            <div class="text-2xl font-bold text-green-600" x-text="stats.available"></div>
            <div class="text-xs text-gray-500 mt-1">Libres</div>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl p-4 border border-gray-200 dark:border-slate-700 text-center">
            <div class="text-2xl font-bold text-yellow-600" x-text="stats.kitchen_processing"></div>
            <div class="text-xs text-gray-500 mt-1">En cuisine</div>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl p-4 border border-gray-200 dark:border-slate-700 text-center">
            <div class="text-2xl font-bold text-blue-600" x-text="stats.served_unpaid"></div>
            <div class="text-xs text-gray-500 mt-1">À encaisser</div>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl p-4 border border-gray-200 dark:border-slate-700 text-center">
            <div class="text-2xl font-bold text-red-600" x-text="stats.occupied"></div>
            <div class="text-xs text-gray-500 mt-1">Occupées</div>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-xl p-4 border border-gray-200 dark:border-slate-700 text-center">
            <div class="text-2xl font-bold text-red-700" x-text="stats.sla_breached"></div>
            <div class="text-xs text-gray-500 mt-1">SLA dépassé</div>
        </div>
    </div>

    <!-- Plan de salle -->
    <div class="flex-1 overflow-y-auto p-6">
        @forelse($tables->groupBy('zone') as $zone => $zoneTables)
            <div class="mb-8">
                <h3 class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-3">{{ $zone ?: 'Sans zone' }}</h3>
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4">
                    @foreach($zoneTables as $table)
                        @php
                            $tc = $table->getStatusColor();
                            $tLabel = $table->getStatusLabel();
                            $waitMins = $table->getWaitMinutes();
                            $slaBreached = $table->isSlaBreached($restaurant->sla_warning_minutes ?? 30);
                        @endphp
                        <div class="relative rounded-2xl border-2 p-4 transition-all cursor-pointer hover:shadow-lg
                            {{ $tc === 'green' ? 'border-green-500/50 bg-green-50 dark:bg-green-900/20' : '' }}
                            {{ $tc === 'yellow' ? 'border-yellow-500/50 bg-yellow-50 dark:bg-yellow-900/20' : '' }}
                            {{ $tc === 'blue' ? 'border-blue-500/50 bg-blue-50 dark:bg-blue-900/20' : '' }}
                            {{ $tc === 'red' ? 'border-red-500/50 bg-red-50 dark:bg-red-900/20' : '' }}
                            {{ $slaBreached ? 'animate-pulse ring-2 ring-red-500 ring-offset-2' : '' }}"
                            onclick="showTableDetails({{ $table->id }})">

                            <!-- Indicateur SLA -->
                            @if($slaBreached)
                                <div class="absolute -top-2 -right-2 w-6 h-6 bg-red-600 rounded-full flex items-center justify-center">
                                    <span class="text-white text-xs font-bold">!</span>
                                </div>
                            @endif

                            <div class="text-center">
                                <div class="text-lg font-bold {{ $tc === 'green' ? 'text-green-700 dark:text-green-400' : ($tc === 'yellow' ? 'text-yellow-700 dark:text-yellow-400' : ($tc === 'blue' ? 'text-blue-700 dark:text-blue-400' : 'text-red-700 dark:text-red-400')) }}">
                                    {{ $table->name }}
                                </div>
                                <div class="text-xs mt-1 {{ $tc === 'green' ? 'text-green-600' : ($tc === 'yellow' ? 'text-yellow-600' : ($tc === 'blue' ? 'text-blue-600' : 'text-red-600')) }}">
                                    {{ $tLabel }}
                                </div>

                                <!-- Chronomètre SLA -->
                                @if($waitMins !== null && in_array($table->status, ['kitchen_processing', 'served_unpaid']))
                                    <div class="mt-2 text-center">
                                        <div class="inline-flex items-center gap-1 px-2 py-1 rounded-lg text-xs font-mono font-bold
                                            {{ $waitMins < 15 ? 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400' : '' }}
                                            {{ $waitMins >= 15 && $waitMins < 30 ? 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-400' : '' }}
                                            {{ $waitMins >= 30 ? 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400 animate-pulse' : '' }}">
                                            ⏳ {{ $waitMins }} min
                                        </div>
                                    </div>
                                @endif

                                <!-- Commande active -->
                                @if($table->currentOrder)
                                    <div class="mt-2 text-xs text-gray-500 dark:text-gray-400 truncate">
                                        #{{ $table->currentOrder->order_number }}
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @empty
            <div class="text-center py-12 text-gray-400">
                <p class="text-lg">Aucune table configurée</p>
                <p class="text-sm mt-1">Ajoutez des tables depuis les paramètres du restaurant</p>
            </div>
        @endforelse
    </div>
</div>

<script>
function floorPlanSupervision() {
    return {
        stats: {
            available: {{ $tables->where('status', 'available')->count() }},
            kitchen_processing: {{ $tables->where('status', 'kitchen_processing')->count() }},
            served_unpaid: {{ $tables->where('status', 'served_unpaid')->count() }},
            occupied: {{ $tables->where('status', 'occupied')->count() }},
            sla_breached: {{ $tables->filter(fn($t) => $t->isSlaBreached(30))->count() }},
        },

        init() {
            // Polling toutes les 10 secondes pour mise à jour temps réel
            setInterval(() => this.refreshData(), 10000);
        },

        async refreshData() {
            try {
                const response = await fetch('{{ route("admin.floor-plan.data") }}', {
                    headers: { 'Accept': 'application/json' }
                });
                if (response.ok) {
                    const data = await response.json();
                    if (data.stats) this.stats = data.stats;
                    if (data.tables) {
                        // Recharger la page pour mettre à jour les timers
                        location.reload();
                    }
                }
            } catch (e) {
                console.error('Refresh error:', e);
            }
        }
    };
}

function showTableDetails(tableId) {
    // Ouvrir une modale ou rediriger vers les détails de la table
    window.location.href = '/admin/floor-plan/table/' + tableId;
}
</script>
@endsection
