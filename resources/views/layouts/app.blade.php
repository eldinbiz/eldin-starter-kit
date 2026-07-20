<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') | TailAdmin Laravel</title>
    <!-- Outfit Google Font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@100..900&display=swap" rel="stylesheet">

    <!-- ApexCharts CDN (Loaded before Alpine) -->
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

    <!-- Alpine.js (via CDN, deferred, zero-build) -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <!-- Tailwind CSS and custom scripts via Vite -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body
    x-data="{ page: 'dashboard', loaded: true, darkMode: false, stickyMenu: false, sidebarToggle: true, scrollTop: false }"
    x-init="
        darkMode = JSON.parse(localStorage.getItem('darkMode')) || false;
        $watch('darkMode', value => localStorage.setItem('darkMode', JSON.stringify(value)))
    "
    :class="{'dark bg-gray-900': darkMode === true}"
>
    <!-- Preloader -->
    @include('partials.preloader')Uncaught (in promise) TypeError: this.$watch is not a function
    result https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js line 5 > AsyncFunction:34
    anonymous https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js line 5 > AsyncFunction:39
    Vn https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js:5
    ar https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js:1
    T https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js:5
    <anonymous> https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js:5
    v https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js:5
    r https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js:5
    n https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js:5
    br https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js:5
    S https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js:5
    Or https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js:5
    Or https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js:5
    <anonymous> https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js:5
cdn.min.js line 5 > AsyncFunction:34:14


    <!-- Page Wrapper -->
    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar -->
        @include('partials.sidebar')

        <!-- Content Area -->
        <div class="relative flex flex-1 flex-col overflow-y-auto overflow-x-hidden custom-scrollbar bg-gray-50 dark:bg-gray-950">
            <!-- Small Device Overlay -->
            @include('partials.overlay')

            <!-- Header -->
            @include('partials.header')

            <!-- Main Content -->
            <main>
                <div class="mx-auto max-w-(--breakpoint-2xl) p-4 md:p-6 2xl:p-10">
                    @yield('content')
                </div>
            </main>
        </div>
    </div>

    @stack('scripts')

    <!-- Session Expiry Monitor & Modal -->
    @include('partials.session-monitor')
</body>
</html>
