<div class="mb-6 flex flex-wrap items-center justify-between gap-3">
    <h2 class="text-xl font-semibold text-gray-800 dark:text-white/90 @themeIs('shadcn') text-zinc-900 dark:text-zinc-50 tracking-tight font-bold @endthemeIs" x-text="pageName"></h2>

    <nav>
        <ol class="flex items-center gap-1.5">
            <li>
                <a class="inline-flex items-center gap-1.5 text-sm text-gray-500 dark:text-gray-400 @themeIs('shadcn') text-zinc-500 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-100 @endthemeIs transition-colors" href="{{ url('/') }}">
                    Home
                    <svg class="stroke-current" width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M6 12L10 8L6 4" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </a>
            </li>
            <li class="text-sm font-medium text-gray-800 dark:text-white/90 @themeIs('shadcn') text-zinc-900 dark:text-zinc-200 @endthemeIs" x-text="pageName"></li>
        </ol>
    </nav>
</div>
