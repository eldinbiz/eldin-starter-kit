{{-- Step Card Component inside Stage Container --}}
<div
    :data-step-index="sIdx"
    :data-group-index="gIdx"
    @click.stop="selectNode('step', gIdx, sIdx)"
    class="step-card group relative flex items-center justify-between gap-3 rounded-xl border p-3 transition cursor-pointer"
    :class="{
        'border-brand-500 bg-brand-50/40 ring-2 ring-brand-500/30 dark:border-brand-500 dark:bg-brand-500/10': isSelected('step', gIdx, sIdx),
        'border-gray-200 bg-white shadow-2xs hover:border-gray-300 dark:border-gray-700/80 dark:bg-gray-800/80 dark:hover:border-gray-600': !isSelected('step', gIdx, sIdx),
        'border-rose-400 bg-rose-50/30 dark:border-rose-700/60 dark:bg-rose-950/20': stepHasError(step)
    }"
>
    {{-- Left: Drag Grip Handle & Step Number --}}
    <div class="flex items-center gap-2">
        <span class="step-drag-handle text-gray-300 group-hover:text-gray-500 dark:text-gray-600 dark:group-hover:text-gray-400 cursor-grab active:cursor-grabbing">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 9h16.5m-16.5 6.75h16.5" />
            </svg>
        </span>
        <span class="flex h-5 w-5 items-center justify-center rounded-full bg-gray-100 text-[10px] font-bold text-gray-500 dark:bg-gray-700 dark:text-gray-300" x-text="sIdx + 1"></span>
    </div>

    {{-- Center: Step Details & Resolvers --}}
    <div class="flex-1 min-w-0">
        <div class="flex items-center gap-2">
            <p class="text-xs font-semibold text-gray-800 dark:text-white truncate" x-text="step.label || 'Step Approval'"></p>
            
            {{-- Resolver Badge --}}
            <template x-if="step.resolver_type === 'N+1'">
                <span class="rounded bg-blue-100 px-1.5 py-0.5 text-[9px] font-bold text-blue-700 dark:bg-blue-900/40 dark:text-blue-300">
                    Direct Mgr (N+1)
                </span>
            </template>
            <template x-if="step.resolver_type === 'N+1_of_previous'">
                <span class="rounded bg-teal-100 px-1.5 py-0.5 text-[9px] font-bold text-teal-700 dark:bg-teal-900/40 dark:text-teal-300">
                    N+1 of Prev
                </span>
            </template>
            <template x-if="step.resolver_type === 'specific_user'">
                <span class="rounded bg-purple-100 px-1.5 py-0.5 text-[9px] font-bold text-purple-700 dark:bg-purple-900/40 dark:text-purple-300">
                    Specific User
                </span>
            </template>
        </div>

        {{-- Specific User Details or Unassigned Warning --}}
        <template x-if="step.resolver_type === 'specific_user'">
            <div class="mt-1 flex items-center gap-1.5">
                <template x-if="getUserName(step.resolver_payload?.user_id)">
                    <span class="inline-flex items-center gap-1 text-[11px] text-gray-600 dark:text-gray-300">
                        <svg class="h-3 w-3 text-brand-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                        </svg>
                        <span class="font-medium truncate" x-text="getUserName(step.resolver_payload?.user_id)"></span>
                    </span>
                </template>
                <template x-if="!getUserName(step.resolver_payload?.user_id)">
                    <span class="inline-flex items-center gap-1 text-[11px] font-semibold text-rose-500">
                        <svg class="h-3 w-3 text-rose-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
                        </svg>
                        <span>Unassigned — Select in Inspector</span>
                    </span>
                </template>
            </div>
        </template>
    </div>

    {{-- Right: Actions & Delete Button --}}
    <div class="flex items-center gap-1">
        <button
            type="button"
            @click.stop="removeStep(gIdx, sIdx)"
            class="flex h-6 w-6 items-center justify-center rounded text-gray-400 hover:bg-rose-50 hover:text-rose-600 dark:hover:bg-rose-950/40 dark:hover:text-rose-400 transition cursor-pointer"
            title="Delete Approver Step"
        >
            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
            </svg>
        </button>
    </div>
</div>
