{{-- Stage Container Component: Represents a WorkflowGroup in the pipeline --}}
<div
    :id="'stage-container-' + gIdx"
    :data-group-index="gIdx"
    @click.stop="selectNode('group', gIdx)"
    class="stage-container relative w-[420px] rounded-2xl border transition shadow-sm cursor-pointer"
    :class="{
        'border-brand-500 bg-white ring-2 ring-brand-500/20 dark:border-brand-500 dark:bg-gray-900': isSelected('group', gIdx),
        'border-gray-200 bg-white/95 dark:border-gray-800 dark:bg-gray-900/90': !isSelected('group', gIdx),
        'border-amber-400 dark:border-amber-600': groupHasWarning(group)
    }"
>

    {{-- Stage Header --}}
    <div class="flex items-center justify-between border-b border-gray-100 p-3.5 dark:border-gray-800/80">
        <div class="flex items-center gap-2.5 min-w-0">
            {{-- Reorder Grip Handle --}}
            <span
                data-stage-drag-handle
                class="stage-drag-handle text-gray-300 hover:text-gray-500 dark:text-gray-600 dark:hover:text-gray-400 cursor-grab active:cursor-grabbing"
                title="Drag to reorder stages"
            >
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                </svg>
            </span>

            {{-- Group Sequence Badge --}}
            <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-md bg-brand-100 text-[10px] font-bold text-brand-700 dark:bg-brand-900/40 dark:text-brand-300">
                #<span x-text="gIdx + 1"></span>
            </span>

            {{-- Group Name --}}
            <h4 class="text-xs font-bold text-gray-800 dark:text-white truncate" x-text="group.name || `Stage ${gIdx + 1}`"></h4>
        </div>

        {{-- Badges & Controls --}}
        <div class="flex items-center gap-2">
            {{-- Execution Mode Badge --}}
            <span
                class="rounded-md px-2 py-0.5 text-[9px] font-bold uppercase tracking-wider"
                :class="group.type === 'sequential' ? 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300' : 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300'"
                x-text="group.type === 'sequential' ? 'Sequential' : (group.min_approvals ? `Parallel (${group.min_approvals} of ${group.steps.length})` : 'Parallel (All)')"
            ></span>

            {{-- Delete Stage Button --}}
            <button
                type="button"
                @click.stop="removeGroup(gIdx)"
                class="flex h-6 w-6 items-center justify-center rounded text-gray-400 hover:bg-rose-50 hover:text-rose-600 dark:hover:bg-rose-950/40 dark:hover:text-rose-400 transition cursor-pointer"
                title="Delete Stage"
            >
                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                </svg>
            </button>
        </div>
    </div>

    {{-- Stage Body: Steps Drop Zone --}}
    <div class="p-3.5 space-y-2.5">
        {{-- Sortable Steps List Container --}}
        <div
            :id="'stage-steps-' + gIdx"
            :data-group-index="gIdx"
            class="stage-steps-list space-y-2 min-h-[50px] rounded-xl p-1"
        >
            <template x-for="(step, sIdx) in group.steps" :key="step._uid || sIdx">
                @include('admin.approval-workflows.canvas.step-card')
            </template>
        </div>

        {{-- Empty Stage Warning --}}
        <div
            x-show="group.steps.length === 0"
            class="flex flex-col items-center justify-center rounded-xl border border-dashed border-amber-300 bg-amber-50/40 py-5 px-3 text-center dark:border-amber-700/50 dark:bg-amber-950/20"
        >
            <svg class="h-5 w-5 text-amber-500 mb-1" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
            </svg>
            <p class="text-xs font-medium text-amber-800 dark:text-amber-300">Stage has no approver steps</p>
            <p class="text-[11px] text-amber-600 dark:text-amber-400 mt-0.5">Drag an approver from the sidebar or click below</p>
        </div>

        {{-- Quick Add Approver Step Dropdown --}}
        <div class="pt-1 flex items-center justify-between border-t border-gray-100 dark:border-gray-800/60">
            <div class="flex items-center gap-1.5">
                <button
                    type="button"
                    @click.stop="addStep(gIdx, 'N+1')"
                    class="inline-flex items-center gap-1 rounded-md bg-gray-50 px-2 py-1 text-[11px] font-medium text-gray-600 hover:bg-brand-50 hover:text-brand-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-brand-900/30 dark:hover:text-brand-300 transition cursor-pointer"
                >
                    + Direct Mgr (N+1)
                </button>
                <button
                    type="button"
                    @click.stop="addStep(gIdx, 'N+1_of_previous')"
                    class="inline-flex items-center gap-1 rounded-md bg-gray-50 px-2 py-1 text-[11px] font-medium text-gray-600 hover:bg-brand-50 hover:text-brand-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-brand-900/30 dark:hover:text-brand-300 transition cursor-pointer"
                >
                    + N+1 of Prev
                </button>
            </div>
            <button
                type="button"
                @click.stop="selectNode('group', gIdx)"
                class="text-[11px] font-medium text-brand-600 hover:text-brand-700 dark:text-brand-400 flex items-center gap-0.5 cursor-pointer"
            >
                Configure
                <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                </svg>
            </button>
        </div>
    </div>

</div>
