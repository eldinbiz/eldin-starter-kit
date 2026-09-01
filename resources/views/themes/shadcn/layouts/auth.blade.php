<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Authentication') | {{ config('app.name', 'Scaffolding App') }}</title>
    @if (!empty($appFavicon))
        <link rel="icon" href="{{ $appFavicon }}">
    @endif

    {{-- Inter Google Font for shadcn/ui --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100..900&display=swap" rel="stylesheet">
    
    {{-- Livewire Styles --}}
    @livewireStyles

    {{-- Tailwind CSS and custom scripts via Vite --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body
    x-data="{
        page: 'auth',
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
        }
    }"
    :class="{'dark bg-zinc-950 text-zinc-100': darkMode === true, 'bg-zinc-50/50 text-zinc-900': darkMode === false}"
    class="font-inter min-h-screen antialiased flex flex-col justify-between"
>
    {{-- Top Bar with Dark Mode Switcher & Logo --}}
    <header class="flex items-center justify-between p-4 sm:p-6">
        <a href="{{ url('/') }}" class="flex items-center gap-2">
            @if (!empty($appLogo))
                <img src="{{ $appLogo }}" alt="{{ config('app.name', 'Scaffolding App') }}" class="h-7 max-w-[160px] object-contain" />
            @else
                <div class="flex size-7 items-center justify-center rounded-md bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900 shadow-2xs">
                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <polygon points="12 2 2 7 12 12 22 7 12 2"/><polyline points="2 17 12 22 22 17"/><polyline points="2 12 12 17 22 12"/>
                    </svg>
                </div>
                <span class="text-sm font-semibold tracking-tight">{{ config('app.name', 'Scaffolding') }}</span>
            @endif
        </a>

        <button
            @click="toggleTheme()"
            class="flex size-8 items-center justify-center rounded-md border border-zinc-200 text-zinc-600 hover:bg-zinc-100 hover:text-zinc-900 dark:border-zinc-800 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-zinc-100 shadow-2xs transition-colors"
            title="Toggle theme"
        >
            <svg x-show="darkMode" x-cloak class="size-4 text-amber-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="4"/><path d="M12 2v2"/><path d="M12 20v2"/><path d="m4.93 4.93 1.41 1.41"/><path d="m17.66 17.66 1.41 1.41"/><path d="M2 12h2"/><path d="M20 12h2"/><path d="m6.34 17.66-1.41 1.41"/><path d="m19.07 4.93-1.41 1.41"/>
            </svg>
            <svg x-show="!darkMode" class="size-4 text-zinc-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z"/>
            </svg>
        </button>
    </header>

    {{-- Main Auth Body Content --}}
    <main class="flex flex-1 items-center justify-center p-4 sm:p-6">
        <div class="w-full max-w-md">
            @yield('content')
        </div>
    </main>

    {{-- Footer --}}
    <footer class="p-4 text-center text-xs text-zinc-400 dark:text-zinc-500">
        &copy; {{ date('Y') }} {{ config('app.name', 'Scaffolding App') }}. All rights reserved.
    </footer>

    @stack('scripts')
    @livewireScripts
</body>
</html>
