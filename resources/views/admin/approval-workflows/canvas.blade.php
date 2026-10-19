@extends('layouts.app')

@section('title', $template ? 'Edit Workflow Template (Canvas)' : 'Create Workflow Template (Canvas)')

@push('styles')
<style>
    /* Prevent text selection during canvas drag */
    .canvas-panning {
        cursor: grabbing !important;
        user-select: none;
    }
    .sortable-ghost {
        opacity: 0.35;
        background: rgba(59, 130, 246, 0.15) !important;
        border: 2px dashed #3b82f6 !important;
    }
    .sortable-chosen {
        cursor: grabbing !important;
    }
    /* Linear Pipeline Connector Styles */
    .pipeline-connector {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        position: relative;
        height: 52px;
        width: 100%;
    }
    .pipeline-connector-line {
        position: absolute;
        top: 0;
        bottom: 0;
        width: 2px;
        background-color: #cbd5e1;
    }
    .dark .pipeline-connector-line {
        background-color: #334155;
    }
    .pipeline-connector-arrow {
        position: absolute;
        bottom: 0;
        width: 0;
        height: 0;
        border-left: 5px solid transparent;
        border-right: 5px solid transparent;
        border-top: 6px solid #94a3b8;
    }
    .dark .pipeline-connector-arrow {
        border-top-color: #64748b;
    }
    .pipeline-connector-btn {
        position: relative;
        z-index: 10;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        height: 24px;
        min-width: 24px;
        border-radius: 9999px;
        border: 1px solid #cbd5e1;
        background-color: #ffffff;
        padding: 0 8px;
        font-size: 11px;
        font-weight: 600;
        color: #64748b;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
        transition: all 150ms ease-in-out;
        cursor: pointer;
    }
    .pipeline-connector-btn:hover {
        border-color: #3b82f6;
        background-color: #eff6ff;
        color: #2563eb;
        transform: scale(1.05);
    }
    .dark .pipeline-connector-btn {
        border-color: #475569;
        background-color: #1e293b;
        color: #94a3b8;
    }
    .dark .pipeline-connector-btn:hover {
        border-color: #3b82f6;
        background-color: #1e3a8a;
        color: #93c5fd;
    }
    .pipeline-stage-wrapper {
        width: 100%;
        display: flex;
        flex-direction: column;
        align-items: center;
    }
</style>
@endpush

@push('scripts')
{{-- Load SortableJS via official CDN (per .agents/rules/cdn-libraries.md) --}}
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
@endpush

@section('content')
<div
    x-data="workflowCanvas({
        templateId: {{ $template ? $template->id : 'null' }},
        initialData: @js($initialData),
        availableUsers: @js($users->map(fn($u) => ['id' => $u->id, 'name' => $u->name, 'email' => $u->email, 'initials' => $u->initials ?? strtoupper(substr($u->name, 0, 2))])),
        storeUrl: '{{ route('admin.approval-workflows.store') }}',
        updateUrl: '{{ $template ? route('admin.approval-workflows.update', $template) : '' }}',
        csrfToken: '{{ csrf_token() }}'
    })"
    x-init="initCanvas()"
    class="flex flex-col h-[calc(100vh-80px)] overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-900"
>
    {{-- Top Bar: Workflow Metadata, Mode Switcher, Validation & Actions --}}
    <header class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-200 px-5 py-3 dark:border-gray-800 bg-white dark:bg-gray-900 z-30">
        {{-- Left: Breadcrumb / Back & Template Name Input --}}
        <div class="flex items-center gap-3 flex-1 min-w-[280px]">
            <a
                href="{{ route('admin.approval-workflows.index') }}"
                class="flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 text-gray-500 hover:bg-gray-50 hover:text-gray-800 dark:border-gray-800 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-white transition"
                title="Back to Workflow Templates"
            >
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                </svg>
            </a>

            <div class="flex-1 max-w-md">
                <input
                    type="text"
                    x-model="name"
                    placeholder="Workflow Template Name (Required)"
                    class="h-8.5 w-full rounded-lg border border-gray-300 bg-white px-3 text-xs font-bold text-gray-800 shadow-theme-xs placeholder-gray-400 focus:border-brand-500 focus:outline-hidden dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                />
            </div>

            {{-- Approvable Type Dropdown --}}
            <div class="hidden sm:block w-48">
                <select
                    x-model="approvableType"
                    class="h-8.5 w-full rounded-lg border border-gray-300 bg-white px-2.5 text-xs text-gray-700 shadow-theme-xs focus:border-brand-500 focus:outline-hidden dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300"
                >
                    <option value="">Universal / Any Module</option>
                    @foreach ($approvableTypes as $class => $label)
                        <option value="{{ $class }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        {{-- Right: Validation Pill, Mode Switcher, and Save Template Button --}}
        <div class="flex items-center gap-3">
            {{-- Validation Health Indicator --}}
            <div>
                <template x-if="validationIssues.length === 0">
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-1 text-[11px] font-semibold text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800/60">
                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                        <span x-text="`${groups.length} Stage${groups.length > 1 ? 's' : ''} Ready`"></span>
                    </span>
                </template>
                <template x-if="validationIssues.length > 0">
                    <button
                        type="button"
                        @click="jumpToFirstIssue()"
                        class="inline-flex items-center gap-1.5 rounded-full bg-amber-50 px-2.5 py-1 text-[11px] font-semibold text-amber-800 dark:bg-amber-950/40 dark:text-amber-300 border border-amber-200 dark:border-amber-800/60 hover:bg-amber-100 transition cursor-pointer"
                        title="Click to resolve issues"
                    >
                        <span class="h-1.5 w-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                        <span x-text="`${validationIssues.length} Issue${validationIssues.length > 1 ? 's' : ''} to resolve`"></span>
                    </button>
                </template>
            </div>

            {{-- Mode Switcher (Omit wire:navigate to trigger full-page load) --}}
            <div class="flex items-center rounded-lg border border-gray-200 bg-gray-50 p-0.5 dark:border-gray-800 dark:bg-gray-800/60">
                <a
                    href="{{ $template ? route('admin.approval-workflows.edit', ['workflow' => $template->id, 'mode' => 'form']) : route('admin.approval-workflows.create', ['mode' => 'form']) }}"
                    class="rounded-md px-2.5 py-1 text-xs font-medium text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-white transition"
                    title="Switch to Form View"
                >
                    📄 Form View
                </a>
                <span class="rounded-md bg-white px-2.5 py-1 text-xs font-bold text-brand-600 shadow-2xs dark:bg-gray-800 dark:text-brand-400">
                    🎨 Canvas Mode
                </span>
            </div>

            {{-- Save Template Action Button --}}
            <button
                type="button"
                @click="submitForm()"
                :disabled="isSubmitting"
                class="inline-flex items-center justify-center gap-1.5 rounded-lg bg-brand-500 px-4 py-2 text-xs font-semibold text-white shadow-theme-xs hover:bg-brand-600 focus:outline-hidden disabled:opacity-50 transition cursor-pointer"
            >
                <span x-show="!isSubmitting">
                    {{ $template ? 'Update Template' : 'Save Template' }}
                </span>
                <span x-show="isSubmitting" class="inline-flex items-center gap-1.5">
                    <svg class="h-3.5 w-3.5 animate-spin" viewBox="0 0 24 24" fill="none">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Saving...
                </span>
            </button>
        </div>
    </header>

    {{-- Main Workspace: Palette (Left), Canvas Viewport (Center), Inspector (Right) --}}
    <div class="flex flex-1 overflow-hidden relative">
        {{-- 1. Left Palette & User Directory --}}
        @include('admin.approval-workflows.canvas.palette')

        {{-- 2. Center Viewport --}}
        @include('admin.approval-workflows.canvas.viewport')

        {{-- 3. Right Contextual Inspector --}}
        @include('admin.approval-workflows.canvas.inspector')
    </div>

    {{-- Floating Toast Alert Notification --}}
    <div
        x-show="toast.show"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 -translate-y-4"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 -translate-y-4"
        class="fixed top-20 left-1/2 -translate-x-1/2 z-50 flex items-center gap-3 rounded-xl border px-4 py-2.5 shadow-lg max-w-md pointer-events-auto"
        :class="{
            'border-amber-300 bg-amber-50 text-amber-900 dark:border-amber-700/60 dark:bg-amber-950 dark:text-amber-200': toast.type === 'warning',
            'border-rose-300 bg-rose-50 text-rose-900 dark:border-rose-700/60 dark:bg-rose-950 dark:text-rose-200': toast.type === 'error',
            'border-brand-300 bg-brand-50 text-brand-900 dark:border-brand-700/60 dark:bg-brand-950 dark:text-brand-200': toast.type === 'info',
        }"
        style="display: none;"
    >
        <div class="shrink-0">
            <svg class="h-4 w-4 text-current" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
            </svg>
        </div>
        <p class="text-xs font-medium leading-relaxed" x-text="toast.message"></p>
        <button
            type="button"
            @click="hideToast()"
            class="shrink-0 text-current opacity-60 hover:opacity-100 transition cursor-pointer"
        >
            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
            </svg>
        </button>
    </div>

    {{-- Hidden Form used for standard HTTP submission --}}
    <form id="canvas-submit-form" method="POST" :action="templateId ? updateUrl : storeUrl" class="hidden">
        <input type="hidden" name="_token" :value="csrfToken" />
        <template x-if="templateId">
            <input type="hidden" name="_method" value="PUT" />
        </template>
        <div id="hidden-form-inputs"></div>
    </form>
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('workflowCanvas', (config) => ({
        templateId: config.templateId,
        storeUrl: config.storeUrl,
        updateUrl: config.updateUrl,
        csrfToken: config.csrfToken,

        // Core Template Fields
        name: config.initialData.name || '',
        description: config.initialData.description || '',
        approvableType: config.initialData.approvable_type || '',
        scope: config.initialData.scope || 'this_company',
        isActive: config.initialData.is_active ?? true,

        // Stages Tree with Unique Step UIDs
        groups: (config.initialData.groups || []).map(g => ({
            ...g,
            steps: (g.steps || []).map(s => ({
                ...s,
                _uid: s._uid || ('step_' + Math.random().toString(36).substr(2, 9))
            }))
        })),

        // Users & Search
        users: config.availableUsers || [],
        userSearch: '',

        // Viewport Pan/Zoom State
        zoom: 1.0,
        panX: 0,
        panY: 0,
        isPanning: false,
        startX: 0,
        startY: 0,

        // Active Selection
        selected: null, // { type: 'group'|'step', gIdx: number, sIdx?: number }

        // Dynamic SVG Connections & Validation
        connectorPaths: [],
        validationIssues: [],
        isSubmitting: false,

        // Toast Alert Notification State
        toast: {
            show: false,
            message: '',
            type: 'warning',
            timeout: null
        },

        showToast(message, type = 'warning') {
            if (this.toast.timeout) clearTimeout(this.toast.timeout);
            this.toast.message = message;
            this.toast.type = type;
            this.toast.show = true;
            this.toast.timeout = setTimeout(() => {
                this.toast.show = false;
            }, 4500);
        },

        hideToast() {
            this.toast.show = false;
            if (this.toast.timeout) clearTimeout(this.toast.timeout);
        },

        // Real-time Approver Check
        isUserInWorkflow(userId) {
            if (!userId) return false;
            return this.groups.some(group =>
                (group.steps || []).some(step =>
                    step.resolver_type === 'specific_user' &&
                    step.resolver_payload?.user_id == userId
                )
            );
        },

        get filteredUsers() {
            if (!this.userSearch.trim()) return this.users;
            const q = this.userSearch.toLowerCase();
            return this.users.filter(u => u.name.toLowerCase().includes(q) || u.email.toLowerCase().includes(q));
        },

        initCanvas() {
            this.$nextTick(() => {
                this.initAllSortables();
                this.updateConnectors();
                this.validateWorkflow();
            });

            window.addEventListener('resize', () => {
                this.updateConnectors();
            });
        },

        // SortableJS Drag & Drop Initialization
        initAllSortables() {
            if (typeof Sortable === 'undefined') {
                setTimeout(() => this.initAllSortables(), 100);
                return;
            }

            const self = this;

            // 1. Palette Blocks Sortable (Clones into Stages)
            const paletteBlocksEl = document.getElementById('palette-blocks');
            if (paletteBlocksEl) {
                new Sortable(paletteBlocksEl, {
                    group: {
                        name: 'approver-items',
                        pull: 'clone',
                        put: false
                    },
                    draggable: '.palette-item',
                    revertOnSpill: true,
                    sort: false,
                    animation: 150,
                    onEnd(evt) {
                        // When cloned into a stage, restore the original DOM element in the palette
                        if (evt.pullMode === 'clone' && evt.clone && evt.clone.parentNode) {
                            evt.clone.parentNode.replaceChild(evt.item, evt.clone);
                        }
                    }
                });
            }

            // 2. Palette Users Sortable (Clones user account directly)
            const paletteUsersEl = document.getElementById('palette-users');
            if (paletteUsersEl) {
                new Sortable(paletteUsersEl, {
                    group: {
                        name: 'approver-items',
                        pull: 'clone',
                        put: false
                    },
                    draggable: '.palette-user-item',
                    revertOnSpill: true,
                    sort: false,
                    animation: 150,
                    onEnd(evt) {
                        // When cloned into a stage, restore the original DOM element in the palette
                        // so Alpine's x-for doesn't retain an unmanaged, empty clone.
                        if (evt.pullMode === 'clone' && evt.clone && evt.clone.parentNode) {
                            evt.clone.parentNode.replaceChild(evt.item, evt.clone);
                        }
                    }
                });
            }

            // 3. Pipeline Stages Reorder Sortable
            const pipelineEl = document.getElementById('pipeline-stages');
            if (pipelineEl) {
                new Sortable(pipelineEl, {
                    handle: '[data-stage-drag-handle]',
                    draggable: '.pipeline-stage-wrapper',
                    animation: 200,
                    onEnd(evt) {
                        if (evt.oldIndex !== evt.newIndex) {
                            const moved = self.groups.splice(evt.oldIndex, 1)[0];
                            self.groups.splice(evt.newIndex, 0, moved);
                            self.$nextTick(() => {
                                self.validateWorkflow();
                            });
                        }
                    }
                });
            }

            // 4. Initialize Each Stage Steps Drop Zone
            this.groups.forEach((group, gIdx) => {
                this.initStageSortable(gIdx);
            });
        },

        initStageSortable(gIdx) {
            const self = this;
            const container = document.getElementById(`stage-steps-${gIdx}`);
            if (!container) return;

            new Sortable(container, {
                group: {
                    name: 'approver-items',
                    put: true
                },
                handle: '.step-drag-handle',
                animation: 150,
                onAdd(evt) {
                    const itemEl = evt.item;
                    const blockType = itemEl.getAttribute('data-block-type');
                    const userId = itemEl.getAttribute('data-user-id');
                    const userName = itemEl.getAttribute('data-user-name');

                    let newStep = null;
                    if (blockType === 'step-n1') {
                        newStep = {
                            _uid: 'step_' + Math.random().toString(36).substr(2, 9),
                            label: 'Direct Line Manager Approval',
                            resolver_type: 'N+1',
                            resolver_payload: { user_id: null }
                        };
                    } else if (blockType === 'step-n1-prev') {
                        newStep = {
                            _uid: 'step_' + Math.random().toString(36).substr(2, 9),
                            label: 'Senior Manager Approval',
                            resolver_type: 'N+1_of_previous',
                            resolver_payload: { user_id: null }
                        };
                    } else if (userId) {
                        const parsedId = parseInt(userId, 10);
                        if (self.isUserInWorkflow(parsedId)) {
                            // Reject duplicate user addition: remove dragged element from stage, notify user
                            itemEl.remove();
                            self.showToast(`User "${userName}" has already been added as an approver in this workflow.`, 'warning');
                            return;
                        }

                        newStep = {
                            _uid: 'step_' + Math.random().toString(36).substr(2, 9),
                            label: `${userName} Sign-off`,
                            resolver_type: 'specific_user',
                            resolver_payload: { user_id: parsedId }
                        };
                    } else if (blockType === 'group') {
                        // User dropped group block into stage -> create new stage right after
                        self.addGroup();
                        itemEl.remove();
                        return;
                    }

                    // Remove clone DOM element injected by Sortable; Alpine will render state
                    itemEl.remove();

                    if (newStep) {
                        // Calculate insertion index safely ignoring template tag if present
                        let insertIdx = evt.newIndex;
                        const templateTag = container.querySelector('template');
                        if (templateTag && insertIdx > 0 && Array.from(container.children).indexOf(templateTag) < insertIdx) {
                            insertIdx = Math.max(0, insertIdx - 1);
                        }
                        insertIdx = Math.min(insertIdx, self.groups[gIdx].steps.length);

                        self.groups[gIdx].steps.splice(insertIdx, 0, newStep);
                        self.selectNode('step', gIdx, insertIdx);
                        self.$nextTick(() => {
                            self.updateConnectors();
                            self.validateWorkflow();
                        });
                    }
                },
                onEnd(evt) {
                    if (evt.oldIndex !== evt.newIndex && evt.from === evt.to) {
                        const moved = self.groups[gIdx].steps.splice(evt.oldIndex, 1)[0];
                        self.groups[gIdx].steps.splice(evt.newIndex, 0, moved);
                        self.$nextTick(() => {
                            self.updateConnectors();
                            self.validateWorkflow();
                        });
                    }
                }
            });
        },

        // Stage Management
        addGroup() {
            this.insertGroup(null);
        },

        insertGroup(afterIndex = null) {
            const nextNum = this.groups.length + 1;
            const newGroup = {
                name: `Stage ${nextNum}`,
                type: 'sequential',
                min_approvals: null,
                steps: [
                    {
                        _uid: 'step_' + Math.random().toString(36).substr(2, 9),
                        label: 'Step Approval',
                        resolver_type: 'N+1',
                        resolver_payload: { user_id: null }
                    }
                ]
            };

            if (afterIndex === null || afterIndex >= this.groups.length - 1) {
                this.groups.push(newGroup);
                const newIndex = this.groups.length - 1;
                this.selectNode('group', newIndex);
                this.$nextTick(() => {
                    this.initStageSortable(newIndex);
                    this.validateWorkflow();
                });
            } else {
                this.groups.splice(afterIndex + 1, 0, newGroup);
                const newIndex = afterIndex + 1;
                this.selectNode('group', newIndex);
                this.$nextTick(() => {
                    this.initAllSortables();
                    this.validateWorkflow();
                });
            }
        },

        removeGroup(gIdx) {
            if (this.groups.length <= 1) {
                alert('Workflow must contain at least one stage.');
                return;
            }
            this.groups.splice(gIdx, 1);
            if (this.selected?.gIdx === gIdx) {
                this.deselectAll();
            }
            this.$nextTick(() => {
                this.updateConnectors();
                this.validateWorkflow();
            });
        },

        moveGroup(gIdx, delta) {
            const target = gIdx + delta;
            if (target < 0 || target >= this.groups.length) return;
            const moved = this.groups.splice(gIdx, 1)[0];
            this.groups.splice(target, 0, moved);
            if (this.selected?.type === 'group') {
                this.selected.gIdx = target;
            }
            this.$nextTick(() => {
                this.updateConnectors();
                this.validateWorkflow();
            });
        },

        // Step Management
        addStep(gIdx, type = 'N+1') {
            if (!this.groups[gIdx]) return;
            this.groups[gIdx].steps.push({
                _uid: 'step_' + Math.random().toString(36).substr(2, 9),
                label: type === 'N+1' ? 'Direct Line Manager Approval' : (type === 'N+1_of_previous' ? 'Senior Manager Approval' : 'Specific User Sign-off'),
                resolver_type: type,
                resolver_payload: { user_id: null }
            });
            const newSIdx = this.groups[gIdx].steps.length - 1;
            this.selectNode('step', gIdx, newSIdx);
            this.$nextTick(() => {
                this.updateConnectors();
                this.validateWorkflow();
            });
        },

        removeStep(gIdx, sIdx) {
            if (!this.groups[gIdx]) return;
            if (this.groups[gIdx].steps.length <= 1) {
                alert('A stage must contain at least one step. Delete the stage instead if not needed.');
                return;
            }
            this.groups[gIdx].steps.splice(sIdx, 1);
            if (this.selected?.gIdx === gIdx && this.selected?.sIdx === sIdx) {
                this.deselectAll();
            }
            this.$nextTick(() => {
                this.updateConnectors();
                this.validateWorkflow();
            });
        },

        moveStep(gIdx, sIdx, delta) {
            const steps = this.groups[gIdx]?.steps;
            if (!steps) return;
            const target = sIdx + delta;
            if (target < 0 || target >= steps.length) return;
            const moved = steps.splice(sIdx, 1)[0];
            steps.splice(target, 0, moved);
            if (this.selected?.type === 'step') {
                this.selected.sIdx = target;
            }
            this.$nextTick(() => {
                this.updateConnectors();
                this.validateWorkflow();
            });
        },

        // Selection & Inspector
        selectNode(type, gIdx, sIdx = null) {
            this.selected = { type, gIdx, sIdx };
        },

        deselectAll() {
            this.selected = null;
        },

        isSelected(type, gIdx, sIdx = null) {
            if (!this.selected) return false;
            if (this.selected.type !== type || this.selected.gIdx !== gIdx) return false;
            if (type === 'step') return this.selected.sIdx === sIdx;
            return true;
        },

        // Helpers
        getUserName(userId) {
            if (!userId) return null;
            const user = this.users.find(u => u.id == userId);
            return user ? user.name : null;
        },

        checkInspectorUserConflict(selectedId) {
            if (!selectedId) return;
            let count = 0;
            this.groups.forEach(g => {
                (g.steps || []).forEach(s => {
                    if (s.resolver_type === 'specific_user' && s.resolver_payload?.user_id == selectedId) {
                        count++;
                    }
                });
            });
            if (count > 1) {
                const userName = this.getUserName(selectedId);
                this.showToast(`User "${userName}" is already assigned to another step in this workflow.`, 'warning');
            }
        },

        stepHasError(step) {
            return step.resolver_type === 'specific_user' && !step.resolver_payload?.user_id;
        },

        groupHasWarning(group) {
            if (group.steps.length === 0) return true;
            if (group.type === 'parallel' && group.min_approvals && group.min_approvals > group.steps.length) return true;
            return group.steps.some(s => this.stepHasError(s));
        },

        // Connectors are now managed via reactive DOM pipeline components
        updateConnectors() {},

        // Viewport Pan & Zoom Controls
        zoomIn() {
            this.setZoom(this.zoom + 0.1);
        },

        zoomOut() {
            this.setZoom(this.zoom - 0.1);
        },

        resetZoom() {
            this.setZoom(1.0);
            this.panX = 0;
            this.panY = 0;
            this.$nextTick(() => this.updateConnectors());
        },

        setZoom(val) {
            this.zoom = Math.min(1.5, Math.max(0.5, Math.round(val * 10) / 10));
            this.$nextTick(() => this.updateConnectors());
        },

        fitScreen() {
            this.zoom = 0.9;
            this.panX = 0;
            this.panY = 0;
            this.$nextTick(() => this.updateConnectors());
        },

        centerCanvas() {
            this.panX = 0;
            this.panY = 0;
            this.$nextTick(() => this.updateConnectors());
        },

        startPan(e) {
            if (e.target.closest('.stage-container, .palette-item, button, input, select, textarea')) {
                return;
            }
            this.isPanning = true;
            this.startX = e.clientX - this.panX;
            this.startY = e.clientY - this.panY;
            document.body.classList.add('canvas-panning');
        },

        doPan(e) {
            if (!this.isPanning) return;
            this.panX = e.clientX - this.startX;
            this.panY = e.clientY - this.startY;
            this.updateConnectors();
        },

        endPan() {
            this.isPanning = false;
            document.body.classList.remove('canvas-panning');
        },

        onWheel(e) {
            if (e.ctrlKey || e.metaKey) {
                const delta = e.deltaY < 0 ? 0.08 : -0.08;
                this.setZoom(this.zoom + delta);
            } else {
                this.panY -= e.deltaY * 0.7;
                this.panX -= e.deltaX * 0.7;
                this.updateConnectors();
            }
        },

        // Validation & Issues Tracking
        validateWorkflow() {
            const issues = [];
            if (!this.name.trim()) {
                issues.push({ message: 'Template name is required', focus: 'name' });
            }
            if (this.groups.length === 0) {
                issues.push({ message: 'At least one stage is required', focus: 'stage-0' });
            }
            this.groups.forEach((g, gIdx) => {
                if (g.steps.length === 0) {
                    issues.push({ message: `Stage #${gIdx + 1} has no steps`, focus: `stage-${gIdx}`, gIdx });
                }
                if (g.type === 'parallel' && g.min_approvals && g.min_approvals > g.steps.length) {
                    issues.push({ message: `Stage #${gIdx + 1} min approvals exceed available steps`, focus: `stage-${gIdx}`, gIdx });
                }
                g.steps.forEach((s, sIdx) => {
                    if (s.resolver_type === 'specific_user' && !s.resolver_payload?.user_id) {
                        issues.push({ message: `Step #${sIdx + 1} in Stage #${gIdx + 1} requires a specific user`, focus: `step-${gIdx}-${sIdx}`, gIdx, sIdx });
                    }
                });
            });

            this.validationIssues = issues;
            return issues.length === 0;
        },

        jumpToFirstIssue() {
            if (this.validationIssues.length === 0) return;
            const issue = this.validationIssues[0];
            if (issue.focus === 'name') {
                const nameInput = document.querySelector('input[x-model="name"]');
                nameInput?.focus();
            } else if (issue.sIdx !== undefined) {
                this.selectNode('step', issue.gIdx, issue.sIdx);
            } else if (issue.gIdx !== undefined) {
                this.selectNode('group', issue.gIdx);
            }
        },

        // Form Serialization and Submission
        submitForm() {
            if (!this.validateWorkflow()) {
                this.jumpToFirstIssue();
                alert(`Please resolve configuration issues:\n- ` + this.validationIssues.map(i => i.message).join('\n- '));
                return;
            }

            this.isSubmitting = true;
            const formContainer = document.getElementById('hidden-form-inputs');
            if (!formContainer) return;
            formContainer.innerHTML = '';

            const appendInput = (name, value) => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = name;
                input.value = value ?? '';
                formContainer.appendChild(input);
            };

            appendInput('name', this.name);
            appendInput('description', this.description);
            appendInput('approvable_type', this.approvableType);
            appendInput('scope', this.scope);
            appendInput('is_active', this.isActive ? '1' : '0');

            this.groups.forEach((group, gIdx) => {
                appendInput(`groups[${gIdx}][name]`, group.name);
                appendInput(`groups[${gIdx}][type]`, group.type);
                if (group.type === 'parallel' && group.min_approvals) {
                    appendInput(`groups[${gIdx}][min_approvals]`, group.min_approvals);
                }

                group.steps.forEach((step, sIdx) => {
                    appendInput(`groups[${gIdx}][steps][${sIdx}][label]`, step.label || 'Step Approval');
                    appendInput(`groups[${gIdx}][steps][${sIdx}][resolver_type]`, step.resolver_type);
                    if (step.resolver_type === 'specific_user' && step.resolver_payload?.user_id) {
                        appendInput(`groups[${gIdx}][steps][${sIdx}][resolver_payload][user_id]`, step.resolver_payload.user_id);
                    }
                });
            });

            document.getElementById('canvas-submit-form').submit();
        }
    }));
});
</script>
@endsection
