{{-- Right Inspector Panel: Contextual Properties Configuration --}}
<aside class="flex w-80 shrink-0 flex-col border-l border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900 overflow-hidden">
    {{-- Inspector Header --}}
    <div class="flex items-center justify-between border-b border-gray-200 px-4 py-3 dark:border-gray-800">
        <div>
            <h3 class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                Properties
            </h3>
            <p class="text-[11px] text-gray-400 dark:text-gray-500" x-text="selected ? (selected.type === 'group' ? `Stage #${selected.gIdx + 1}` : `Step #${selected.sIdx + 1} in Stage #${selected.gIdx + 1}`) : 'Template Configuration'">
            </p>
        </div>
        <button
            type="button"
            @click="deselectAll()"
            x-show="selected !== null"
            class="text-[11px] font-medium text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 cursor-pointer"
        >
            Close
        </button>
    </div>

    <div class="flex-1 overflow-y-auto p-4 space-y-5">
        {{-- CASE 1: STAGE / GROUP SELECTED --}}
        <template x-if="selected && selected.type === 'group' && groups[selected.gIdx]">
            <div class="space-y-4">
                {{-- Stage Name --}}
                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Stage Name <span class="text-rose-500">*</span>
                    </label>
                    <input
                        type="text"
                        x-model="groups[selected.gIdx].name"
                        placeholder="e.g. Finance & Compliance Review"
                        class="h-8.5 w-full rounded-lg border border-gray-300 bg-white px-3 text-xs text-gray-800 shadow-theme-xs focus:border-brand-500 focus:outline-hidden dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                    />
                </div>

                {{-- Execution Mode (Sequential vs Parallel) --}}
                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                        Execution Mode
                    </label>
                    <div class="grid grid-cols-2 gap-2">
                        <button
                            type="button"
                            @click="groups[selected.gIdx].type = 'sequential'; groups[selected.gIdx].min_approvals = null"
                            class="flex flex-col items-center justify-center rounded-xl border p-2.5 text-center transition cursor-pointer"
                            :class="groups[selected.gIdx].type === 'sequential' ? 'border-brand-500 bg-brand-50/50 text-brand-700 dark:border-brand-500 dark:bg-brand-900/20 dark:text-brand-300' : 'border-gray-200 bg-white text-gray-600 hover:bg-gray-50 dark:border-gray-800 dark:bg-gray-800 dark:text-gray-400'"
                        >
                            <svg class="h-4 w-4 mb-1 text-current" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                            </svg>
                            <span class="text-xs font-semibold">Sequential</span>
                            <span class="text-[10px] text-gray-400 mt-0.5">Ordered steps</span>
                        </button>

                        <button
                            type="button"
                            @click="groups[selected.gIdx].type = 'parallel'"
                            class="flex flex-col items-center justify-center rounded-xl border p-2.5 text-center transition cursor-pointer"
                            :class="groups[selected.gIdx].type === 'parallel' ? 'border-brand-500 bg-brand-50/50 text-brand-700 dark:border-brand-500 dark:bg-brand-900/20 dark:text-brand-300' : 'border-gray-200 bg-white text-gray-600 hover:bg-gray-50 dark:border-gray-800 dark:bg-gray-800 dark:text-gray-400'"
                        >
                            <svg class="h-4 w-4 mb-1 text-current" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6Z" />
                            </svg>
                            <span class="text-xs font-semibold">Parallel</span>
                            <span class="text-[10px] text-gray-400 mt-0.5">Simultaneous</span>
                        </button>
                    </div>
                </div>

                {{-- Parallel Minimum Approvals Threshold --}}
                <div x-show="groups[selected.gIdx].type === 'parallel'" class="space-y-1.5 rounded-xl border border-gray-200 bg-gray-50/50 p-3 dark:border-gray-800 dark:bg-gray-800/40">
                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">
                        Minimum Approvals Required
                    </label>
                    <input
                        type="number"
                        min="1"
                        :max="groups[selected.gIdx].steps.length || 1"
                        x-model="groups[selected.gIdx].min_approvals"
                        placeholder="All required (Leave empty)"
                        class="h-8.5 w-full rounded-md border border-gray-300 bg-white px-2.5 text-xs text-gray-800 shadow-theme-xs focus:border-brand-500 focus:outline-hidden dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                    />
                    <p class="text-[11px] text-gray-500 dark:text-gray-400">
                        Leave blank to require unanimous sign-off by all approvers in this group.
                    </p>
                </div>

                {{-- Stage Reorder & Actions --}}
                <div class="pt-3 border-t border-gray-200 dark:border-gray-800 flex items-center justify-between">
                    <div class="flex items-center gap-1">
                        <button
                            type="button"
                            @click="moveGroup(selected.gIdx, -1)"
                            :disabled="selected.gIdx === 0"
                            class="rounded-lg border border-gray-200 px-2.5 py-1 text-xs font-medium text-gray-600 hover:bg-gray-50 disabled:opacity-30 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800 cursor-pointer"
                        >
                            ↑ Move Up
                        </button>
                        <button
                            type="button"
                            @click="moveGroup(selected.gIdx, 1)"
                            :disabled="selected.gIdx >= groups.length - 1"
                            class="rounded-lg border border-gray-200 px-2.5 py-1 text-xs font-medium text-gray-600 hover:bg-gray-50 disabled:opacity-30 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800 cursor-pointer"
                        >
                            ↓ Move Down
                        </button>
                    </div>

                    <button
                        type="button"
                        @click="removeGroup(selected.gIdx); deselectAll()"
                        class="text-xs font-semibold text-rose-500 hover:text-rose-600 dark:hover:text-rose-400 cursor-pointer"
                    >
                        Delete Stage
                    </button>
                </div>
            </div>
        </template>

        {{-- CASE 2: STEP SELECTED --}}
        <template x-if="selected && selected.type === 'step' && groups[selected.gIdx]?.steps[selected.sIdx]">
            <div class="space-y-4">
                {{-- Step Label --}}
                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Step Label <span class="text-rose-500">*</span>
                    </label>
                    <input
                        type="text"
                        x-model="groups[selected.gIdx].steps[selected.sIdx].label"
                        placeholder="e.g. Line Manager Approval"
                        class="h-8.5 w-full rounded-lg border border-gray-300 bg-white px-3 text-xs text-gray-800 shadow-theme-xs focus:border-brand-500 focus:outline-hidden dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                    />
                </div>

                {{-- Resolver Type --}}
                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Approver Resolver Type
                    </label>
                    <select
                        x-model="groups[selected.gIdx].steps[selected.sIdx].resolver_type"
                        class="h-8.5 w-full rounded-lg border border-gray-300 bg-white px-2.5 text-xs text-gray-800 shadow-theme-xs focus:border-brand-500 focus:outline-hidden dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                    >
                        <option value="N+1">Direct Manager (N+1)</option>
                        <option value="N+1_of_previous">Manager of Previous Approver</option>
                        <option value="specific_user">Specific Named User</option>
                    </select>
                </div>

                {{-- Specific User Assignment (if resolver_type === specific_user) --}}
                <div x-show="groups[selected.gIdx].steps[selected.sIdx].resolver_type === 'specific_user'" class="space-y-2">
                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">
                        Assigned User Account <span class="text-rose-500">*</span>
                    </label>
                    
                    <select
                        x-model="groups[selected.gIdx].steps[selected.sIdx].resolver_payload.user_id"
                        @change="checkInspectorUserConflict($event.target.value)"
                        class="h-8.5 w-full rounded-lg border border-gray-300 bg-white px-2 text-xs text-gray-800 shadow-theme-xs focus:border-brand-500 focus:outline-hidden dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                    >
                        <option value="">Select approver user...</option>
                        <template x-for="u in users" :key="u.id">
                            <option
                                :value="u.id"
                                x-text="`${u.name} (${u.email})${isUserInWorkflow(u.id) && groups[selected.gIdx].steps[selected.sIdx].resolver_payload?.user_id != u.id ? ' — [Already Added]' : ''}`"
                                :selected="groups[selected.gIdx].steps[selected.sIdx].resolver_payload?.user_id == u.id"
                            ></option>
                        </template>
                    </select>

                    <p class="text-[11px] text-gray-400">
                        You can also drag users directly from the sidebar directory onto any stage.
                    </p>
                </div>

                {{-- Step Actions --}}
                <div class="pt-3 border-t border-gray-200 dark:border-gray-800 flex items-center justify-between">
                    <div class="flex items-center gap-1">
                        <button
                            type="button"
                            @click="moveStep(selected.gIdx, selected.sIdx, -1)"
                            :disabled="selected.sIdx === 0"
                            class="rounded-lg border border-gray-200 px-2.5 py-1 text-xs font-medium text-gray-600 hover:bg-gray-50 disabled:opacity-30 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800 cursor-pointer"
                        >
                            ↑ Move Up
                        </button>
                        <button
                            type="button"
                            @click="moveStep(selected.gIdx, selected.sIdx, 1)"
                            :disabled="selected.sIdx >= groups[selected.gIdx].steps.length - 1"
                            class="rounded-lg border border-gray-200 px-2.5 py-1 text-xs font-medium text-gray-600 hover:bg-gray-50 disabled:opacity-30 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800 cursor-pointer"
                        >
                            ↓ Move Down
                        </button>
                    </div>

                    <button
                        type="button"
                        @click="removeStep(selected.gIdx, selected.sIdx); deselectAll()"
                        class="text-xs font-semibold text-rose-500 hover:text-rose-600 dark:hover:text-rose-400 cursor-pointer"
                    >
                        Delete Step
                    </button>
                </div>
            </div>
        </template>

        {{-- CASE 3: NO SELECTION (TEMPLATE GENERAL CONFIG) --}}
        <template x-if="!selected">
            <div class="space-y-4">
                <div class="rounded-xl bg-gray-50/80 p-3 dark:bg-gray-800/40 border border-gray-100 dark:border-gray-800">
                    <p class="text-xs font-semibold text-gray-700 dark:text-gray-200">Template Overview</p>
                    <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-1">
                        Click on any Stage Group or Approver Step on the canvas to configure its properties.
                    </p>
                </div>

                {{-- Template Scope --}}
                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                        Template Scope
                    </label>
                    <div class="space-y-2">
                        <label class="flex items-center gap-2 cursor-pointer text-xs text-gray-700 dark:text-gray-300">
                            <input type="radio" x-model="scope" value="this_company" class="text-brand-500 focus:ring-brand-500" />
                            <span>This Company Only</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer text-xs text-gray-700 dark:text-gray-300">
                            <input type="radio" x-model="scope" value="subsidiaries" class="text-brand-500 focus:ring-brand-500" />
                            <span>Company &amp; Subsidiaries</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer text-xs text-gray-700 dark:text-gray-300">
                            <input type="radio" x-model="scope" value="global" class="text-brand-500 focus:ring-brand-500" />
                            <span>Global (All Tenants)</span>
                        </label>
                    </div>
                </div>

                {{-- Description --}}
                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Description
                    </label>
                    <textarea
                        x-model="description"
                        rows="3"
                        placeholder="Briefly describe when and how this workflow is used..."
                        class="w-full rounded-lg border border-gray-300 bg-white p-2.5 text-xs text-gray-800 shadow-theme-xs placeholder-gray-400 focus:border-brand-500 focus:outline-hidden dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                    ></textarea>
                </div>

                {{-- Active Template Toggle --}}
                <div class="pt-2">
                    <label class="flex items-center gap-2 cursor-pointer text-xs font-medium text-gray-700 dark:text-gray-300">
                        <input
                            type="checkbox"
                            x-model="isActive"
                            class="h-4 w-4 rounded-sm border-gray-300 text-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-800"
                        />
                        <span>Active Template</span>
                    </label>
                </div>
            </div>
        </template>
    </div>
</aside>
