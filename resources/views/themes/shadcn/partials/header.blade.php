{{-- shadcn Top Header --}}
<header
    x-data="{ userDropdown: false, confirmLogout: false }"
    class="sticky top-0 z-40 flex h-14 w-full items-center justify-between border-b border-zinc-200 bg-white/90 px-4 backdrop-blur-md dark:border-zinc-800 dark:bg-zinc-950/90"
>
    {{-- Left: Sidebar Toggle & Breadcrumb --}}
    <div class="flex items-center gap-3">
        {{-- Mobile Hamburger --}}
        <button
            @click="mobileSidebarOpen = !mobileSidebarOpen"
            class="flex size-8 items-center justify-center rounded-md text-zinc-600 hover:bg-zinc-100 hover:text-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-zinc-100 lg:hidden"
            title="Open Sidebar"
        >
            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="4" x2="20" y1="12" y2="12"/><line x1="4" x2="20" y1="6" y2="6"/><line x1="4" x2="20" y1="18" y2="18"/>
            </svg>
        </button>

        {{-- Desktop Sidebar Quick Toggle --}}
        <button
            @click="sidebarCollapsed = !sidebarCollapsed"
            class="hidden size-8 items-center justify-center rounded-md text-zinc-500 hover:bg-zinc-100 hover:text-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-zinc-100 lg:flex"
            title="Toggle Sidebar (⌘B)"
        >
            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect width="18" height="18" x="3" y="3" rx="2"/><path d="M9 3v18"/>
            </svg>
        </button>

        <div class="hidden h-4 w-px bg-zinc-200 dark:bg-zinc-800 sm:block"></div>

        {{-- Breadcrumb trail --}}
        <nav class="hidden items-center gap-1.5 text-xs font-medium text-zinc-500 dark:text-zinc-400 sm:flex">
            <a href="{{ url('/') }}" wire:navigate.hover class="hover:text-zinc-900 dark:hover:text-zinc-100">
                Dashboard
            </a>
            <svg class="size-3.5 text-zinc-400 dark:text-zinc-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="9 18 15 12 9 6"/>
            </svg>
            <span class="text-zinc-900 dark:text-zinc-100 font-semibold" x-text="pageName || '@yield('title', 'Overview')'"></span>
        </nav>
    </div>

    {{-- Center / Right Controls --}}
    <div class="flex items-center gap-2 sm:gap-3">
        {{-- Environment Badge --}}
        @unless(app()->environment('production'))
        @php
            $env = app()->environment();
            $badgeColor = match($env) {
                'local' => 'bg-zinc-100 text-zinc-700 border-zinc-300 dark:bg-zinc-800 dark:text-zinc-300 dark:border-zinc-700',
                'dev', 'development' => 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-400 dark:border-emerald-800',
                'staging' => 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-950/40 dark:text-amber-400 dark:border-amber-800',
                default => 'bg-zinc-100 text-zinc-700 border-zinc-300 dark:bg-zinc-800 dark:text-zinc-300 dark:border-zinc-700',
            };
        @endphp
        <span class="hidden md:inline-flex items-center rounded-md border px-2 py-0.5 text-[11px] font-mono font-semibold {{ $badgeColor }}">
            {{ strtoupper($env) }}
        </span>
        @endunless

        {{-- ⌘K Command Palette Search Trigger Button --}}
        <button
            @click="commandPaletteOpen = true"
            class="flex items-center gap-2 rounded-md border border-zinc-200 bg-zinc-50/50 px-2.5 py-1.5 text-xs text-zinc-500 hover:bg-zinc-100 hover:text-zinc-900 dark:border-zinc-800 dark:bg-zinc-900/50 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-zinc-100 shadow-2xs"
            title="Search actions and pages (⌘K)"
        >
            <svg class="size-3.5 text-zinc-400 dark:text-zinc-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/>
            </svg>
            <span class="hidden sm:inline">Search...</span>
            <kbd class="hidden rounded border border-zinc-300 bg-white px-1.5 py-0.5 text-[10px] font-mono shadow-2xs dark:border-zinc-700 dark:bg-zinc-800 sm:inline">⌘K</kbd>
        </button>

        {{-- Dark Mode Switcher --}}
        <button
            @click="toggleTheme()"
            class="flex size-8 items-center justify-center rounded-md border border-zinc-200 text-zinc-600 hover:bg-zinc-100 hover:text-zinc-900 dark:border-zinc-800 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-zinc-100 shadow-2xs"
            :title="darkMode ? 'Switch to light mode' : 'Switch to dark mode'"
        >
            {{-- Sun Icon --}}
            <svg x-show="darkMode" x-cloak class="size-4 text-amber-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="4"/><path d="M12 2v2"/><path d="M12 20v2"/><path d="m4.93 4.93 1.41 1.41"/><path d="m17.66 17.66 1.41 1.41"/><path d="M2 12h2"/><path d="M20 12h2"/><path d="m6.34 17.66-1.41 1.41"/><path d="m19.07 4.93-1.41 1.41"/>
            </svg>
            {{-- Moon Icon --}}
            <svg x-show="!darkMode" class="size-4 text-zinc-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z"/>
            </svg>
        </button>

        {{-- Notification Bell Livewire Component --}}
        @auth
            <livewire:navigation.notification-bell />
        @endauth

        {{-- Company & Role Selector Livewire Component --}}
        @auth
            <livewire:navigation.company-role-switcher />
        @endauth

        {{-- User Profile Avatar Dropdown --}}
        @auth
        <div class="relative" @click.outside="userDropdown = false">
            <button
                @click="userDropdown = !userDropdown"
                class="flex items-center gap-2 rounded-md p-1 hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors"
            >
                <div class="flex size-7 items-center justify-center rounded-full bg-zinc-900 text-xs font-semibold text-white dark:bg-zinc-100 dark:text-zinc-900 shadow-2xs">
                    {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                </div>
                <div class="hidden text-left sm:block">
                    <span class="block text-xs font-medium text-zinc-900 dark:text-zinc-100 leading-tight">
                        {{ Auth::user()->name }}
                    </span>
                </div>
                <svg class="size-3 text-zinc-400 dark:text-zinc-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="6 9 12 15 18 9"/>
                </svg>
            </button>

            {{-- Dropdown Menu Card --}}
            <div
                x-show="userDropdown"
                x-cloak
                x-transition:enter="transition ease-out duration-100"
                x-transition:enter-start="transform opacity-0 scale-95"
                x-transition:enter-end="transform opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-75"
                x-transition:leave-start="transform opacity-100 scale-100"
                x-transition:leave-end="transform opacity-0 scale-95"
                class="absolute right-0 mt-2 w-56 origin-top-right rounded-lg border border-zinc-200 bg-white p-1 shadow-lg dark:border-zinc-800 dark:bg-zinc-900 text-zinc-800 dark:text-zinc-200 z-50"
            >
                <div class="px-2.5 py-2 border-b border-zinc-100 dark:border-zinc-800">
                    <p class="text-xs font-medium text-zinc-900 dark:text-zinc-100 truncate">
                        {{ Auth::user()->name }}
                    </p>
                    <p class="text-[11px] text-zinc-500 dark:text-zinc-400 truncate">
                        {{ Auth::user()->email }}
                    </p>
                </div>

                <div class="p-1 space-y-0.5 text-xs">
                    <a
                        href="{{ route('admin.settings.general') }}"
                        wire:navigate.hover
                        class="flex items-center gap-2 rounded-md px-2 py-1.5 text-zinc-700 hover:bg-zinc-100 hover:text-zinc-900 dark:text-zinc-300 dark:hover:bg-zinc-800 dark:hover:text-zinc-100"
                    >
                        <svg class="size-3.5 text-zinc-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"/><circle cx="12" cy="12" r="3"/>
                        </svg>
                        Settings
                    </a>
                </div>

                <div class="border-t border-zinc-100 p-1 dark:border-zinc-800">
                    <button
                        type="button"
                        @click="confirmLogout = true; userDropdown = false"
                        class="flex w-full items-center gap-2 rounded-md px-2 py-1.5 text-xs text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-950/40 cursor-pointer"
                    >
                        <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" x2="9" y1="12" y2="12"/>
                        </svg>
                        Log out
                    </button>
                </div>
            </div>
        </div>

        {{-- Logout Confirmation Dialog (shadcn Modal Teleported to Body) --}}
        <template x-teleport="body">
            <div
                x-show="confirmLogout"
                x-cloak
                x-trap.noscroll="confirmLogout"
                class="fixed inset-0 z-99999 flex items-center justify-center p-4"
            >
                <div
                    x-show="confirmLogout"
                    x-transition:enter="ease-out duration-200"
                    x-transition:enter-start="opacity-0"
                    x-transition:enter-end="opacity-100"
                    x-transition:leave="ease-in duration-150"
                    x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0"
                    @click="confirmLogout = false"
                    class="fixed inset-0 bg-black/50 backdrop-blur-xs"
                ></div>

                <div
                    x-show="confirmLogout"
                    x-transition:enter="ease-out duration-200"
                    x-transition:enter-start="opacity-0 scale-95"
                    x-transition:enter-end="opacity-100 scale-100"
                    x-transition:leave="ease-in duration-150"
                    x-transition:leave-start="opacity-100 scale-100"
                    x-transition:leave-end="opacity-0 scale-95"
                    @click.outside="confirmLogout = false"
                    class="relative w-full max-w-sm overflow-hidden rounded-lg border border-zinc-200 bg-white p-6 shadow-xl dark:border-zinc-800 dark:bg-zinc-900"
                >
                    <div class="flex flex-col items-center text-center">
                        <div class="flex size-11 items-center justify-center rounded-full bg-red-50 text-red-600 dark:bg-red-950/50 dark:text-red-400 mb-3.5">
                            <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" x2="9" y1="12" y2="12"/>
                            </svg>
                        </div>
                        <h3 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">
                            Sign out of your account?
                        </h3>
                        <p class="mt-1.5 text-xs text-zinc-500 dark:text-zinc-400">
                            Are you sure you want to log out? Any unsaved changes in progress will be lost.
                        </p>
                    </div>

                    <div class="mt-6 flex items-center justify-end gap-2.5">
                        <button
                            type="button"
                            @click="confirmLogout = false"
                            class="rounded-md border border-zinc-200 bg-white px-3.5 py-1.5 text-xs font-medium text-zinc-700 hover:bg-zinc-50 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-300 dark:hover:bg-zinc-800 transition-colors shadow-2xs"
                        >
                            Cancel
                        </button>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button
                                type="submit"
                                class="rounded-md bg-red-600 px-3.5 py-1.5 text-xs font-medium text-white hover:bg-red-700 dark:bg-red-600 dark:hover:bg-red-700 transition-colors shadow-2xs"
                            >
                                Sign out
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </template>
        @endauth
    </div>
</header>
