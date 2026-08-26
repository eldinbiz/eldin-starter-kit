<!slot>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Auth') | {{ config('app.name', 'Scaffolding App') }}</title>
    @if (!empty($appFavicon))
        <link rel="icon" href="{{ $appFavicon }}">
    @endif
    {{-- Outfit Google Font --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@100..900&display=swap" rel="stylesheet">
    
    {{-- Livewire Styles --}}
    @livewireStyles

    {{-- Tailwind CSS and custom scripts via Vite --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body
    x-data="{
        page: 'auth',
        loaded: true,
        darkMode: (function() {
            const override = localStorage.getItem('user_theme_override');
            if (override !== null) return override === 'dark';
            const defTheme = '{{ $defaultTheme }}';
            if (defTheme === 'dark') return true;
            if (defTheme === 'light') return false;
            return window.matchMedia('(prefers-color-scheme: dark)').matches;
        })(),
        toggleTheme() {
            this.darkMode = !this.darkMode;
            localStorage.setItem('user_theme_override', this.darkMode ? 'dark' : 'light');
        }
    }"
    :class="{'dark bg-gray-900': darkMode === true, 'bg-gray-50': darkMode === false}"
>
    {{-- Preloader Start --}}
    <div
        x-show="!loaded"
        class="fixed left-0 top-0 z-999999 flex h-screen w-screen items-center justify-center bg-white dark:bg-black"
    >
        <div class="h-16 w-16 animate-spin rounded-full border-4 border-solid border-brand-500 border-t-transparent"></div>
    </div>
    {{-- Preloader End --}}

    <main class="min-h-screen flex flex-col items-center justify-center p-4">
        @if (!empty($appLogo))
            <div class="mb-6 flex justify-center">
                <a href="{{ url('/') }}">
                    <img src="{{ $appLogo }}" alt="{{ config('app.name', 'Scaffolding App') }}" class="h-12 max-w-[200px] object-contain" />
                </a>
            </div>
        @endif
        @yield('content')
    </main>

    @livewireScripts
</body>
</html>
