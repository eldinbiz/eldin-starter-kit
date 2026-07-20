<div
    x-data="{ sessionExpired: false }"
    x-init="
        const channel = new BroadcastChannel('session-expired');

        // Verify session via backend — does NOT renew (route excludes StartSession)
        const check = () => {
            return fetch('{{ route('session.status') }}', {
                headers: { Accept: 'application/json' },
            })
                .then(res => res.json())
                .then(data => {
                    sessionExpired = ! data.authenticated;

                    if (sessionExpired) {
                        // Wake up other tabs so they verify independently
                        channel.postMessage({ type: 'check-now' });
                    }
                })
                .catch(() => {}); // Network error — silently ignore (user may be offline)
        };

        // Listen for wake-up signals from other tabs — verify independently
        channel.onmessage = (e) => {
            if (e.data.type === 'check-now') {
                check();
            }
        };

        // Poll every 30 seconds
        const interval = setInterval(check, 30000);

        // Check immediately when the user switches to this tab
        document.addEventListener('visibilitychange', () => {
            if (! document.hidden) {
                check();
            }
        });

        // Stop polling once expired — no point checking further
        $watch('sessionExpired', (val) => {
            if (val) {
                clearInterval(interval);
            }
        });
    "
>
    <!-- Session Expired Modal Overlay -->
    <div
        x-show="sessionExpired"
        x-cloak
        class="fixed inset-0 z-[99999] flex items-center justify-center overflow-y-auto p-4"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
    >
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-black/50 backdrop-blur-sm transition-opacity"></div>

        <!-- Modal Card -->
        <div
            class="relative w-full max-w-md rounded-2xl bg-white p-8 shadow-xl dark:bg-gray-800 dark:text-white"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
        >
            <!-- Warning Icon -->
            <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-red-100 dark:bg-red-900/30">
                <svg class="h-8 w-8 text-red-600 dark:text-red-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                </svg>
            </div>

            <!-- Title & Description -->
            <div class="mt-6 text-center">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                    Session Expired
                </h3>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                    Your session has expired due to inactivity. Please log in again to continue.
                </p>
            </div>

            <!-- Action Button -->
            <div class="mt-8 flex justify-center">
                <a
                    href="{{ route('login') }}"
                    class="inline-flex items-center justify-center rounded-lg bg-brand-500 px-6 py-2.5 text-sm font-medium text-white shadow-theme-xs transition hover:bg-brand-600 focus:outline-hidden"
                >
                    Log In Again
                </a>
            </div>
        </div>
    </div>
</div>
