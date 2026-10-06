<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') | {{ config('app.name', 'Scaffolding App') }}</title>
    @if (!empty($appFavicon))
        <link rel="icon" href="{{ $appFavicon }}">
    @endif
    {{-- Outfit Google Font --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@100..900&display=swap" rel="stylesheet">

    {{-- ApexCharts CDN (Loaded before Alpine) --}}
    <script defer src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

    {{-- D3 & Org Chart CDN (Loaded before Alpine) --}}
    <script defer src="https://cdn.jsdelivr.net/npm/d3@7"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/d3-flextree@2.1.2/build/d3-flextree.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/d3-org-chart@3"></script>

    {{-- Livewire Styles --}}
    @livewireStyles


    {{-- Tailwind CSS and custom scripts via Vite --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- Dynamic Active Company Brand Theme CSS Variables --}}
    @if (!empty($activeCompanyThemeVariables))
        <style>
            :root {
                @foreach ($activeCompanyThemeVariables as $cssVarName => $cssVarValue)
                    {{ $cssVarName }}: {{ $cssVarValue }};
                @endforeach
            }
        </style>
    @endif
    @stack('styles')
</head>
<body
    x-data="{
        page: 'dashboard',
        loaded: {{ app()->environment('testing') ? 'false' : 'true' }},
        darkMode: (function() {
            const override = localStorage.getItem('user_theme_override');
            if (override !== null) return override === 'dark';
            const defTheme = '{{ $defaultTheme ?? 'system' }}';
            if (defTheme === 'dark') return true;
            if (defTheme === 'light') return false;
            return window.matchMedia('(prefers-color-scheme: dark)').matches;
        })(),
        toggleTheme() {
            this.darkMode = !this.darkMode;
            localStorage.setItem('user_theme_override', this.darkMode ? 'dark' : 'light');
        },
        stickyMenu: false,
        sidebarToggle: true,
        scrollTop: false
    }"
    :class="{'dark bg-gray-900': darkMode === true}"
>
    {{-- Preloader --}}
    @include('partials.preloader')

    {{-- Page Wrapper --}}
    <div class="flex h-screen overflow-hidden">
        {{-- Sidebar --}}
        @include('partials.sidebar')

        {{-- Content Area --}}
        <div class="relative flex flex-1 flex-col overflow-y-auto overflow-x-hidden custom-scrollbar bg-gray-50 dark:bg-gray-950">
            {{-- Small Device Overlay --}}
            @include('partials.overlay')

            @impersonating
            <div 
                class="sticky top-0 z-50 flex items-center justify-between bg-amber-500 px-4 py-2 text-xs font-semibold text-gray-900 shadow-md md:px-6 md:py-2.5 md:text-sm"
            >
                <div class="flex items-center gap-2">
                    <span class="text-base">🎭</span>
                    <span>You are currently impersonating <strong>{{ auth()->user()->name }}</strong> ({{ auth()->user()->email }}).</span>
                </div>
                <a 
                    href="{{ route('impersonate.leave') }}" 
                    class="inline-flex items-center gap-1.5 rounded bg-gray-900 px-3 py-1 text-xs font-medium text-white transition hover:bg-gray-800 focus:ring-2 focus:ring-gray-900 focus:outline-none"
                >
                    <span>🛑</span>
                    <span>Stop Impersonating</span>
                </a>
            </div>
            @endImpersonating

            {{-- Header --}}
            @include('partials.header')

            {{-- Main Content --}}
            <main>
                <div class="mx-auto max-w-(--breakpoint-2xl) p-4 md:p-6 2xl:p-10">
                    @yield('content')
                </div>
            </main>
        </div>
    </div>

    @stack('scripts')

    <script>
        window.isImpersonationTransition = false;
        window.__isImpersonating = {{ app('impersonate')->isImpersonating() ? 'true' : 'false' }};
        if (!window.__tabId) {
            window.__tabId = 'tab_' + Math.random().toString(36).substring(2, 9) + '_' + Date.now();
        }
    </script>

    {{-- Session Expiry Monitor & Modal (only for authenticated users not currently impersonating) --}}
    @if(auth()->check() && !app('impersonate')->isImpersonating())
        @include('partials.session-monitor')
    @endif

    @auth
    <script>
        (function () {
            if (!('BroadcastChannel' in window)) return;

            if (!window.__impersonationChannelInit) {
                window.__impersonationChannelInit = true;
                const bc = new BroadcastChannel('impersonation_channel');

                bc.onmessage = function (event) {
                    if (event.data && event.data.type === 'IMPERSONATION_CHANGED' && event.data.senderTabId && event.data.senderTabId !== window.__tabId) {
                        window.isImpersonationTransition = true;
                        window.location.replace("{{ route('dashboard') }}");
                    }
                };

                window.broadcastImpersonationChange = function () {
                    window.isImpersonationTransition = true;
                    try {
                        bc.postMessage({
                            type: 'IMPERSONATION_CHANGED',
                            senderTabId: window.__tabId,
                            timestamp: Date.now()
                        });
                    } catch (e) {}
                };
            }

            @if(session('impersonation_switched'))
                if (typeof window.broadcastImpersonationChange === 'function') {
                    window.broadcastImpersonationChange();
                }
            @endif
        })();
    </script>
    @endauth

    @livewireScripts
</body>
</html>
