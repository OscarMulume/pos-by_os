<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ auth()->user()->restaurant->name ?? config('app.name', 'POS') }} — POS</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <script src="/js/offline-manager.js" defer></script>

        <style>
            html, body {
                height: 100%;
                overflow: hidden;
                overscroll-behavior: none;
                -webkit-tap-highlight-color: transparent;
                touch-action: manipulation;
            }
        </style>
    </head>
    <body class="font-sans antialiased h-full" x-data="{ dark: localStorage.getItem('darkMode') === 'true', sidebarOpen: false }" :class="{ 'dark': dark }" x-init="$watch('dark', val => localStorage.setItem('darkMode', val))">
        <!-- Floating orbs (subtle on POS) -->
        <div class="bg-orb bg-orb-1 opacity-50"></div>
        <div class="bg-orb bg-orb-2 opacity-50"></div>

        <!-- Pinned dark header -->
        <header class="bg-slate-900/95 dark:bg-slate-950/95 backdrop-blur-xl text-white flex items-center justify-between px-4 py-2 h-14 select-none relative z-30">
            <!-- Left: Restaurant name -->
            <div class="flex items-center gap-3 min-w-0">
                <span class="text-lg font-semibold truncate">{{ auth()->user()->restaurant->name ?? 'POS' }}</span>
            </div>

            <!-- Center: Live clock -->
            <div class="text-2xl font-mono tabular-nums tracking-wider" x-text="clock" x-data="posClock()" x-init="init()"></div>

            <!-- Right: Cashier + Dark toggle + Logout -->
            <div class="flex items-center gap-3">
                <span class="text-sm text-slate-300 truncate hidden sm:block">{{ auth()->user()->name }}</span>

                <!-- Dark mode toggle -->
                <button @click="dark = !dark" class="p-1.5 rounded-lg hover:bg-slate-700 transition" title="Mode sombre">
                    <svg x-show="!dark" class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
                    <svg x-show="dark" class="w-5 h-5 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                </button>

                <form method="POST" action="{{ route('logout') }}" class="inline">
                    @csrf
                    <button type="submit"
                            class="text-xs bg-slate-700 hover:bg-slate-600 text-slate-200 px-3 py-1.5 rounded transition-colors">
                        Logout
                    </button>
                </form>
            </div>
        </header>

        <!-- Flash messages -->
        @if(session()->has('success') || session()->has('error') || session()->has('warning'))
            <div class="px-4 py-2">
                @if(session('success'))
                    <div class="bg-green-100/90 dark:bg-green-900/40 border border-green-300 dark:border-green-700 text-green-800 dark:text-green-300 px-3 py-2 rounded text-sm backdrop-blur-sm">
                        {{ session('success') }}
                    </div>
                @endif
                @if(session('error'))
                    <div class="bg-red-100/90 dark:bg-red-900/40 border border-red-300 dark:border-red-700 text-red-800 dark:text-red-300 px-3 py-2 rounded text-sm backdrop-blur-sm">
                        {{ session('error') }}
                    </div>
                @endif
                @if(session('warning'))
                    <div class="bg-yellow-100/90 dark:bg-amber-900/40 border border-yellow-300 dark:border-amber-700 text-yellow-800 dark:text-amber-300 px-3 py-2 rounded text-sm backdrop-blur-sm">
                        {{ session('warning') }}
                    </div>
                @endif
            </div>
        @endif

        <!-- Main content fills remaining height -->
        <main class="overflow-hidden bg-animated-gradient dark:bg-animated-gradient" style="height: calc(100vh - 3.5rem);">
            @yield('content')
        </main>

        <script>
            function posClock() {
                return {
                    clock: '',
                    init() {
                        this.updateClock();
                        setInterval(() => this.updateClock(), 1000);
                    },
                    updateClock() {
                        const now = new Date();
                        const h = String(now.getHours()).padStart(2, '0');
                        const m = String(now.getMinutes()).padStart(2, '0');
                        const s = String(now.getSeconds()).padStart(2, '0');
                        this.clock = h + ':' + m + ':' + s;
                    }
                };
            }
        </script>
    </body>
</html>
