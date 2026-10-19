{{-- Floating Canvas Viewport Controls Toolbar --}}
<div class="absolute bottom-5 right-5 z-20 flex items-center gap-1 rounded-xl border border-gray-200/80 bg-white/95 p-1.5 shadow-lg backdrop-blur-xs dark:border-gray-800 dark:bg-gray-900/95">
    {{-- Zoom Out --}}
    <button
        type="button"
        @click="zoomOut()"
        class="flex h-8 w-8 items-center justify-center rounded-lg text-gray-600 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-gray-800 dark:hover:text-white transition cursor-pointer"
        title="Zoom Out (Ctrl + Scroll Down)"
    >
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 12h-15" />
        </svg>
    </button>

    {{-- Reset / Current Zoom Percentage --}}
    <button
        type="button"
        @click="resetZoom()"
        class="h-8 min-w-[56px] px-2 text-center text-xs font-semibold text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-800 rounded-lg transition cursor-pointer"
        title="Reset Zoom to 100%"
        x-text="`${Math.round(zoom * 100)}%`"
    >
        100%
    </button>

    {{-- Zoom In --}}
    <button
        type="button"
        @click="zoomIn()"
        class="flex h-8 w-8 items-center justify-center rounded-lg text-gray-600 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-gray-800 dark:hover:text-white transition cursor-pointer"
        title="Zoom In (Ctrl + Scroll Up)"
    >
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
        </svg>
    </button>

    <div class="h-4 w-px bg-gray-200 dark:bg-gray-800 my-auto"></div>

    {{-- Fit to Screen --}}
    <button
        type="button"
        @click="fitScreen()"
        class="flex h-8 w-8 items-center justify-center rounded-lg text-gray-600 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-gray-800 dark:hover:text-white transition cursor-pointer"
        title="Fit Pipeline to Screen"
    >
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3.75v4.5m0-4.5h4.5m-4.5 0L9 9M3.75 20.25v-4.5m0 4.5h4.5m-4.5 0L9 15M20.25 3.75h-4.5m4.5 0v4.5m0-4.5L15 9m5.25 11.25h-4.5m4.5 0v-4.5m0 4.5L15 15" />
        </svg>
    </button>

    {{-- Center Origin --}}
    <button
        type="button"
        @click="centerCanvas()"
        class="flex h-8 w-8 items-center justify-center rounded-lg text-gray-600 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-gray-800 dark:hover:text-white transition cursor-pointer"
        title="Center Canvas"
    >
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" />
        </svg>
    </button>
</div>
