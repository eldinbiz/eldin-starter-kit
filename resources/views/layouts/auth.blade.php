<!slot>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Auth') | TailAdmin Laravel</title>
    <!-- Outfit Google Font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@100..900&display=swap" rel="stylesheet">
    
    <!-- Alpine.js (via CDN, zero-build) -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <!-- Tailwind CSS and custom scripts via Vite -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body
    x-data="{ page: 'auth', loaded: true, darkMode: false }"
    x-init="
        darkMode = JSON.parse(localStorage.getItem('darkMode')) || false;
        $watch('darkMode', value => localStorage.setItem('darkMode', JSON.stringify(value)))
    "
    :class="{'dark bg-gray-900': darkMode === true, 'bg-gray-50': darkMode === false}"
>
    <!-- Preloader Start -->
    <div
        x-show="!loaded"
        class="fixed left-0 top-0 z-999999 flex h-screen w-screen items-center justify-center bg-white dark:bg-black"
    >
        <div class="h-16 w-16 animate-spin rounded-full border-4 border-solid border-brand-500 border-t-transparent"></div>
    </div>
    <!-- Preloader End -->

    <main class="min-h-screen flex items-center justify-center p-4">
        @yield('content')
    </main>
</body>
</html>
