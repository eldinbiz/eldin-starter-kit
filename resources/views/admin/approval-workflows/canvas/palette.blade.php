{{-- Left Palette Sidebar: Building Blocks & Searchable User Directory --}}
<aside class="flex w-72 shrink-0 flex-col border-r border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900 overflow-hidden">
    {{-- Palette Header --}}
    <div class="border-b border-gray-200 px-4 py-3 dark:border-gray-800">
        <h3 class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">
            Components &amp; Approvers
        </h3>
        <p class="text-[11px] text-gray-400 dark:text-gray-500">
            Drag items into the canvas or stage groups
        </p>
    </div>

    <div class="flex-1 overflow-y-auto p-4 space-y-6">
        {{-- Section 1: Workflow Building Blocks --}}
        <div>
            <div class="flex items-center justify-between mb-2.5">
                <span class="text-xs font-semibold text-gray-800 dark:text-white">Workflow Blocks</span>
                <span class="text-[10px] text-gray-400">Drag to add</span>
            </div>

            <div id="palette-blocks" class="space-y-2">
                {{-- Group Container Block --}}
                <div
                    data-block-type="group"
                    class="palette-item group flex items-center gap-3 rounded-xl border border-gray-200 bg-gray-50/70 p-2.5 text-left transition hover:border-brand-500 hover:bg-brand-50/30 hover:shadow-xs dark:border-gray-800 dark:bg-gray-800/60 dark:hover:border-brand-500 cursor-grab active:cursor-grabbing"
                >
                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-indigo-100 text-indigo-700 dark:bg-indigo-900/50 dark:text-indigo-300">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z" />
                        </svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-xs font-semibold text-gray-800 dark:text-white truncate">Stage Group</p>
                        <p class="text-[10px] text-gray-500 dark:text-gray-400 truncate">Sequential / Parallel stage</p>
                    </div>
                </div>

                {{-- Direct Line Manager (N+1) --}}
                <div
                    data-block-type="step-n1"
                    class="palette-item group flex items-center gap-3 rounded-xl border border-gray-200 bg-gray-50/70 p-2.5 text-left transition hover:border-brand-500 hover:bg-brand-50/30 hover:shadow-xs dark:border-gray-800 dark:bg-gray-800/60 dark:hover:border-brand-500 cursor-grab active:cursor-grabbing"
                >
                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                        </svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-1.5">
                            <p class="text-xs font-semibold text-gray-800 dark:text-white truncate">Direct Manager</p>
                            <span class="rounded bg-blue-100 px-1 py-0.2 text-[9px] font-bold text-blue-700 dark:bg-blue-900/60 dark:text-blue-300">N+1</span>
                        </div>
                        <p class="text-[10px] text-gray-500 dark:text-gray-400 truncate">Requester's line manager</p>
                    </div>
                </div>

                {{-- Manager of Previous Approver (N+1 of previous) --}}
                <div
                    data-block-type="step-n1-prev"
                    class="palette-item group flex items-center gap-3 rounded-xl border border-gray-200 bg-gray-50/70 p-2.5 text-left transition hover:border-brand-500 hover:bg-brand-50/30 hover:shadow-xs dark:border-gray-800 dark:bg-gray-800/60 dark:hover:border-brand-500 cursor-grab active:cursor-grabbing"
                >
                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-teal-100 text-teal-700 dark:bg-teal-900/50 dark:text-teal-300">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z" />
                        </svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-1.5">
                            <p class="text-xs font-semibold text-gray-800 dark:text-white truncate">Manager of Prev</p>
                            <span class="rounded bg-teal-100 px-1 py-0.2 text-[9px] font-bold text-teal-700 dark:bg-teal-900/60 dark:text-teal-300">N+1 Prev</span>
                        </div>
                        <p class="text-[10px] text-gray-500 dark:text-gray-400 truncate">Escalates up reporting line</p>
                    </div>
                </div>

                {{-- Reserved Slot: Conditional Rule (Future Logic) --}}
                <div
                    class="flex items-center gap-3 rounded-xl border border-dashed border-amber-300 bg-amber-50/40 p-2.5 opacity-70 dark:border-amber-700/50 dark:bg-amber-950/20"
                    title="Conditional Rules will be available in an upcoming update"
                >
                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-300">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 21 3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5" />
                        </svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-1.5">
                            <p class="text-xs font-medium text-gray-600 dark:text-gray-300 truncate">Condition / Gate</p>
                            <span class="rounded bg-amber-200/80 px-1 py-0.2 text-[8px] font-bold text-amber-800 dark:bg-amber-800 dark:text-amber-200">SOON</span>
                        </div>
                        <p class="text-[10px] text-gray-400 dark:text-gray-500 truncate">Amount or branch rule</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Section 2: Searchable User Directory --}}
        <div>
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-semibold text-gray-800 dark:text-white">User Directory</span>
                <span class="text-[10px] text-gray-400" x-text="`${filteredUsers.length} available`"></span>
            </div>

            {{-- Live Search Input --}}
            <div class="relative mb-2.5">
                <span class="absolute top-1/2 left-2.5 -translate-y-1/2 text-gray-400 pointer-events-none">
                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                    </svg>
                </span>
                <input
                    type="text"
                    x-model="userSearch"
                    placeholder="Search users..."
                    class="h-8 w-full rounded-lg border border-gray-200 bg-gray-50 py-1 pl-8 pr-2.5 text-xs text-gray-800 placeholder-gray-400 focus:border-brand-500 focus:bg-white focus:outline-hidden dark:border-gray-800 dark:bg-gray-950 dark:text-white dark:focus:border-brand-500 transition"
                />
            </div>

            {{-- Filtered Users List --}}
            <div id="palette-users" class="space-y-1.5 max-h-[340px] overflow-y-auto pr-1">
                <template x-for="user in filteredUsers" :key="user.id">
                    <div
                        :data-user-id="user.id"
                        :data-user-name="user.name"
                        :data-user-email="user.email"
                        class="palette-user-item group flex items-center gap-2.5 rounded-lg border p-2 text-left transition cursor-grab active:cursor-grabbing"
                        :class="isUserInWorkflow(user.id)
                            ? 'border-gray-200 bg-gray-100/70 dark:border-gray-800 dark:bg-gray-800/50'
                            : 'border-gray-100 bg-gray-50/50 hover:border-brand-400 hover:bg-white hover:shadow-xs dark:border-gray-800/80 dark:bg-gray-800/40 dark:hover:border-brand-500 dark:hover:bg-gray-800'"
                    >
                        <div
                            class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-[11px] font-bold"
                            :class="isUserInWorkflow(user.id) ? 'bg-gray-200 text-gray-600 dark:bg-gray-700 dark:text-gray-300' : 'bg-brand-100 text-brand-700 dark:bg-brand-900/50 dark:text-brand-300'"
                        >
                            <span x-text="user.initials || user.name.substring(0, 2).toUpperCase()"></span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-1.5">
                                <p class="text-xs font-medium text-gray-800 dark:text-white truncate" x-text="user.name"></p>
                                <template x-if="isUserInWorkflow(user.id)">
                                    <span class="rounded bg-brand-50 border border-brand-200 px-1 py-0.2 text-[8px] font-bold text-brand-700 dark:bg-brand-950/60 dark:border-brand-800 dark:text-brand-300">Added</span>
                                </template>
                            </div>
                            <p class="text-[10px] text-gray-400 truncate" x-text="user.email"></p>
                        </div>
                        <svg class="h-3.5 w-3.5 text-gray-300 group-hover:text-brand-500 transition shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 9h16.5m-16.5 6.75h16.5" />
                        </svg>
                    </div>
                </template>

                <div x-show="filteredUsers.length === 0" class="py-6 text-center text-xs text-gray-400">
                    No users matching "<span x-text="userSearch"></span>"
                </div>
            </div>
        </div>
    </div>
</aside>
