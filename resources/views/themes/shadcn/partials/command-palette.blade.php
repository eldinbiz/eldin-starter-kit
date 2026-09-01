{{-- shadcn Command Palette Modal (⌘K / Ctrl+K) --}}
<div
    x-data="{
        search: '',
        selectedIndex: 0,
        items: [
            { title: 'Dashboard', url: '{{ url('/') }}', group: 'Navigation', icon: 'layout' },
            { title: 'Purchase Requests', url: '{{ route('purchase-requests.index') }}', group: 'Navigation', icon: 'cart' },
            @can('view-approval-inbox')
            { title: 'Approval Inbox', url: '{{ route('approvals.inbox') }}', group: 'Approvals', icon: 'inbox' },
            @endcan
            @can('view-approval-history')
            { title: 'Approval History', url: '{{ route('approvals.history') }}', group: 'Approvals', icon: 'history' },
            @endcan
            @can('manage-users')
            { title: 'User Management', url: '{{ route('admin.users.index') }}', group: 'Administration', icon: 'users' },
            @endcan
            @can('manage-roles')
            { title: 'User Roles & Permissions', url: '{{ route('admin.roles.index') }}', group: 'Administration', icon: 'shield' },
            @endcan
            @can('manage-companies')
            { title: 'Company Management', url: '{{ route('admin.companies.index') }}', group: 'Administration', icon: 'building' },
            @endcan
            @can('manage-org-chart')
            { title: 'Organization Structure', url: '{{ route('admin.org-chart.index') }}', group: 'Administration', icon: 'org' },
            @endcan
            @can('manage-system-settings')
            { title: 'System Settings', url: '{{ route('admin.settings.general') }}', group: 'System', icon: 'settings' },
            @endcan
            @can('manage-auth-providers')
            { title: 'Auth Providers (SSO)', url: '{{ route('admin.settings.auth-providers.index') }}', group: 'System', icon: 'key' },
            @endcan
            @feature('api_access')
            @can('manage-api-tokens')
            { title: 'API Tokens & Documentation', url: '{{ route('admin.api-tokens.index') }}', group: 'System', icon: 'code' },
            @endcan
            @endfeature
            @feature('audit_logs')
            @can('view-audit-logs')
            { title: 'Audit Trail Logs', url: '{{ route('audit-log.dashboard') }}', group: 'System', icon: 'file' },
            @endcan
            @endfeature
        ],
        get filteredItems() {
            if (!this.search) return this.items;
            const query = this.search.toLowerCase();
            return this.items.filter(item => item.title.toLowerCase().includes(query) || item.group.toLowerCase().includes(query));
        },
        selectItem(index) {
            const item = this.filteredItems[index];
            if (item) {
                this.commandPaletteOpen = false;
                window.location.href = item.url;
            }
        },
        moveNext() {
            if (this.selectedIndex < this.filteredItems.length - 1) {
                this.selectedIndex++;
            } else {
                this.selectedIndex = 0;
            }
        },
        movePrev() {
            if (this.selectedIndex > 0) {
                this.selectedIndex--;
            } else {
                this.selectedIndex = this.filteredItems.length - 1;
            }
        }
    }"
    @keydown.window.prevent.cmd.k="commandPaletteOpen = true; $nextTick(() => $refs.searchInput.focus())"
    @keydown.window.prevent.ctrl.k="commandPaletteOpen = true; $nextTick(() => $refs.searchInput.focus())"
    @keydown.escape.window="commandPaletteOpen = false"
    x-show="commandPaletteOpen"
    x-cloak
    class="fixed inset-0 z-99999 flex items-start justify-center pt-20 sm:pt-28 px-4"
    role="dialog"
    aria-modal="true"
>
    {{-- Backdrop --}}
    <div
        x-show="commandPaletteOpen"
        x-transition:enter="ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        @click="commandPaletteOpen = false"
        class="fixed inset-0 bg-black/50 backdrop-blur-xs transition-opacity"
    ></div>

    {{-- Dialog Card --}}
    <div
        x-show="commandPaletteOpen"
        x-transition:enter="ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="ease-in duration-150"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        @click.outside="commandPaletteOpen = false"
        class="relative w-full max-w-xl overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-2xl transition-all dark:border-zinc-800 dark:bg-zinc-900"
    >
        {{-- Search Input Bar --}}
        <div class="flex items-center border-b border-zinc-200 px-3.5 dark:border-zinc-800">
            <svg class="size-4 shrink-0 text-zinc-400 dark:text-zinc-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/>
            </svg>
            <input
                x-ref="searchInput"
                x-model="search"
                @keydown.down.prevent="moveNext()"
                @keydown.up.prevent="movePrev()"
                @keydown.enter.prevent="selectItem(selectedIndex)"
                type="text"
                placeholder="Type a command or search pages..."
                class="w-full border-0 bg-transparent py-3.5 pl-3 pr-8 text-sm text-zinc-900 placeholder:text-zinc-400 focus:outline-hidden focus:ring-0 dark:text-zinc-100 dark:placeholder:text-zinc-500"
            />
            <button @click="commandPaletteOpen = false" class="text-xs text-zinc-400 hover:text-zinc-700 dark:text-zinc-500 dark:hover:text-zinc-300">
                ESC
            </button>
        </div>

        {{-- Results List --}}
        <div class="max-h-80 overflow-y-auto p-2">
            <template x-if="filteredItems.length === 0">
                <div class="py-8 text-center text-sm text-zinc-500 dark:text-zinc-400">
                    No results found for "<span x-text="search"></span>"
                </div>
            </template>

            <template x-for="(item, index) in filteredItems" :key="item.title">
                <div
                    @click="selectItem(index)"
                    @mouseenter="selectedIndex = index"
                    :class="selectedIndex === index ? 'bg-zinc-100 text-zinc-900 dark:bg-zinc-800 dark:text-zinc-100' : 'text-zinc-700 dark:text-zinc-300'"
                    class="group flex cursor-pointer select-none items-center justify-between rounded-lg px-3 py-2 text-sm transition-colors"
                >
                    <div class="flex items-center gap-2.5">
                        <span class="text-zinc-400 dark:text-zinc-500 group-hover:text-zinc-600 dark:group-hover:text-zinc-300">
                            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/>
                            </svg>
                        </span>
                        <span class="font-medium" x-text="item.title"></span>
                    </div>
                    <span class="text-xs font-medium text-zinc-400 dark:text-zinc-500 uppercase tracking-wider" x-text="item.group"></span>
                </div>
            </template>
        </div>

        {{-- Footer Help Bar --}}
        <div class="flex items-center justify-between border-t border-zinc-200 bg-zinc-50/50 px-3.5 py-2 text-xs text-zinc-500 dark:border-zinc-800 dark:bg-zinc-950/50 dark:text-zinc-400">
            <div class="flex items-center gap-3">
                <span class="flex items-center gap-1">
                    <kbd class="rounded border border-zinc-300 bg-white px-1.5 py-0.5 text-[10px] font-mono shadow-xs dark:border-zinc-700 dark:bg-zinc-800">↑</kbd>
                    <kbd class="rounded border border-zinc-300 bg-white px-1.5 py-0.5 text-[10px] font-mono shadow-xs dark:border-zinc-700 dark:bg-zinc-800">↓</kbd>
                    navigate
                </span>
                <span class="flex items-center gap-1">
                    <kbd class="rounded border border-zinc-300 bg-white px-1.5 py-0.5 text-[10px] font-mono shadow-xs dark:border-zinc-700 dark:bg-zinc-800">↵</kbd>
                    select
                </span>
            </div>
            <span class="font-mono text-[11px]">shadcn Command</span>
        </div>
    </div>
</div>
