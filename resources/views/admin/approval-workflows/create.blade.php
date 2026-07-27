@extends('layouts.app')

@section('title', 'Create Approval Workflow')

@section('content')
<div x-data="{ pageName: 'Approval Workflows' }">
    @include('partials.breadcrumb')
</div>

<div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-sm dark:border-gray-800 dark:bg-white/[0.03] md:p-6"
     x-data="workflowBuilder({{ Js::from(old('groups', [['name' => 'Group 1', 'type' => 'sequential', 'steps' => [['label' => '', 'resolver_type' => 'N+1', 'resolver_payload' => []]]]])) }})">
    <div class="mb-6">
        <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Create Workflow Template</h3>
        <p class="text-sm text-gray-500 dark:text-gray-400">Define groups and approvers for the approval pipeline.</p>
    </div>

    @if ($errors->any())
        <div class="mb-4 rounded-lg bg-error-50 p-4 text-sm text-error-600 dark:bg-error-500/10 dark:text-error-400">
            <ul class="list-disc pl-4">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('admin.approval-workflows.store') }}" method="POST">
        @csrf

        {{-- Template Info --}}
        <div class="grid grid-cols-1 gap-4 mb-6 md:grid-cols-2">
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Name</label>
                <input type="text" name="name" value="{{ old('name') }}" required
                    class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 placeholder-gray-400 focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500 dark:border-gray-700 dark:bg-white/[0.03] dark:text-gray-300 dark:placeholder-gray-500">
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Applies To (Model Class)</label>
                <input type="text" name="approvable_type" value="{{ old('approvable_type') }}" placeholder="e.g. App\\Models\\PurchaseOrder"
                    class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 placeholder-gray-400 focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500 dark:border-gray-700 dark:bg-white/[0.03] dark:text-gray-300 dark:placeholder-gray-500">
                <p class="mt-1 text-xs text-gray-400">Leave blank to apply to any approvable type.</p>
            </div>
            <div class="md:col-span-2">
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Description</label>
                <textarea name="description" rows="2"
                    class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 placeholder-gray-400 focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500 dark:border-gray-700 dark:bg-white/[0.03] dark:text-gray-300 dark:placeholder-gray-500">{{ old('description') }}</textarea>
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Scope</label>
                <div class="space-y-2">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="scope" value="this_company" {{ old('scope', 'this_company') === 'this_company' ? 'checked' : '' }}
                            class="h-3.5 w-3.5 border-gray-300 text-brand-500 focus:ring-brand-500 dark:border-gray-600">
                        <span class="text-sm text-gray-700 dark:text-gray-300">This Company</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="scope" value="subsidiaries" {{ old('scope') === 'subsidiaries' ? 'checked' : '' }}
                            class="h-3.5 w-3.5 border-gray-300 text-brand-500 focus:ring-brand-500 dark:border-gray-600">
                        <span class="text-sm text-gray-700 dark:text-gray-300">This Company & Subsidiaries</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="scope" value="global" {{ old('scope') === 'global' ? 'checked' : '' }}
                            class="h-3.5 w-3.5 border-gray-300 text-brand-500 focus:ring-brand-500 dark:border-gray-600">
                        <span class="text-sm text-gray-700 dark:text-gray-300">Global (All Companies)</span>
                    </label>
                </div>
            </div>
            <div>
                <label class="flex items-center gap-2 text-sm font-medium text-gray-700 dark:text-gray-300">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1" checked
                        class="h-4 w-4 rounded border-gray-300 text-brand-500 focus:ring-brand-500 dark:border-gray-700">
                    Active
                </label>
            </div>
        </div>

        {{-- Groups & Steps Builder --}}
        <div class="mb-6">
            <div class="flex items-center justify-between mb-4">
                <h4 class="text-base font-semibold text-gray-800 dark:text-white/90">Groups & Approvers</h4>
                <button type="button" @click="addGroup()"
                    class="inline-flex items-center gap-1 rounded-lg border border-brand-300 bg-brand-50 px-3 py-1.5 text-xs font-medium text-brand-700 hover:bg-brand-100 dark:border-brand-700 dark:bg-brand-500/10 dark:text-brand-400">
                    <svg width="14" height="14" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd"/></svg>
                    Add Group
                </button>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <template x-for="(group, gi) in groups" :key="gi">
                    <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-white/[0.02]">
                        {{-- Group header --}}
                        <div class="flex items-center gap-2 mb-3">
                            <span class="text-xs font-semibold text-gray-500 dark:text-gray-400" x-text="'Group ' + (gi + 1)"></span>
                            <input type="text" :name="'groups[' + gi + '][name]'" x-model="group.name" placeholder="Group name" required
                                class="flex-1 rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-sm text-gray-700 focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500 dark:border-gray-700 dark:bg-white/[0.03] dark:text-gray-300">
                            <select :name="'groups[' + gi + '][type]'" x-model="group.type"
                                class="rounded-lg border border-gray-300 bg-white px-2 py-1.5 text-sm text-gray-700 focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500 dark:border-gray-700 dark:bg-white/[0.03] dark:text-gray-300">
                                <option value="sequential">Sequential</option>
                                <option value="parallel">Parallel</option>
                            </select>
                        </div>

                        {{-- Steps within this group --}}
                        <div class="space-y-3">
                            <template x-for="(step, si) in group.steps" :key="si">
                                <div>
                                    {{-- Divider between steps --}}
                                    <div x-show="si > 0" class="border-t border-gray-200 dark:border-gray-700 mb-3"></div>

                                    {{-- Step label --}}
                                    <div class="mb-2">
                                        <span class="text-xs font-medium text-gray-500 dark:text-gray-400" x-text="'Approver No.' + (si + 1)"></span>
                                        <input type="text" :name="'groups[' + gi + '][steps][' + si + '][label]'" x-model="step.label"
                                            placeholder="Steps Label. Defaults to Approver's Job Title"
                                            class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-sm text-gray-700 focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500 dark:border-gray-700 dark:bg-white/[0.03] dark:text-gray-300">
                                    </div>

                                    {{-- Resolver type radios with tooltips --}}
                                    <div class="space-y-1.5">
                                        {{-- N+1 --}}
                                        <div x-data="{ tooltip: false }" class="relative">
                                            <label class="flex items-center gap-2 cursor-pointer">
                                                <input type="radio" :name="'groups[' + gi + '][steps][' + si + '][resolver_type]'" value="N+1"
                                                    x-model="step.resolver_type"
                                                    class="h-3.5 w-3.5 border-gray-300 text-brand-500 focus:ring-brand-500 dark:border-gray-600">
                                                <span class="text-xs text-gray-700 dark:text-gray-300">N+1 (Manager)</span>
                                                <span @mouseenter="tooltip = true" @mouseleave="tooltip = false" @click="tooltip = !tooltip"
                                                    class="inline-flex h-4 w-4 items-center justify-center rounded-full bg-gray-200 text-[10px] font-bold text-gray-500 cursor-help dark:bg-gray-700 dark:text-gray-400">?</span>
                                            </label>
                                            <div x-show="tooltip" x-cloak
                                                class="absolute left-0 top-full mt-1 z-10 w-48 rounded-lg bg-gray-800 px-2.5 py-1.5 text-xs text-white shadow-lg dark:bg-gray-700">
                                                The requestor's direct manager within the company.
                                            </div>
                                        </div>

                                        {{-- N+1 of Previous Step --}}
                                        <div x-data="{ tooltip: false }" class="relative">
                                            <label class="flex items-center gap-2 cursor-pointer">
                                                <input type="radio" :name="'groups[' + gi + '][steps][' + si + '][resolver_type]'" value="N+1_of_previous"
                                                    x-model="step.resolver_type"
                                                    class="h-3.5 w-3.5 border-gray-300 text-brand-500 focus:ring-brand-500 dark:border-gray-600">
                                                <span class="text-xs text-gray-700 dark:text-gray-300">N+1 of Previous Step</span>
                                                <span @mouseenter="tooltip = true" @mouseleave="tooltip = false" @click="tooltip = !tooltip"
                                                    class="inline-flex h-4 w-4 items-center justify-center rounded-full bg-gray-200 text-[10px] font-bold text-gray-500 cursor-help dark:bg-gray-700 dark:text-gray-400">?</span>
                                            </label>
                                            <div x-show="tooltip" x-cloak
                                                class="absolute left-0 top-full mt-1 z-10 w-48 rounded-lg bg-gray-800 px-2.5 py-1.5 text-xs text-white shadow-lg dark:bg-gray-700">
                                                The direct manager of the previous step's approver.
                                            </div>
                                        </div>

                                        {{-- Specific User --}}
                                        <div x-data="{ tooltip: false }" class="relative">
                                            <label class="flex items-center gap-2 cursor-pointer">
                                                <input type="radio" :name="'groups[' + gi + '][steps][' + si + '][resolver_type]'" value="specific_user"
                                                    x-model="step.resolver_type"
                                                    class="h-3.5 w-3.5 border-gray-300 text-brand-500 focus:ring-brand-500 dark:border-gray-600">
                                                <span class="text-xs text-gray-700 dark:text-gray-300">Specific User</span>
                                                <span @mouseenter="tooltip = true" @mouseleave="tooltip = false" @click="tooltip = !tooltip"
                                                    class="inline-flex h-4 w-4 items-center justify-center rounded-full bg-gray-200 text-[10px] font-bold text-gray-500 cursor-help dark:bg-gray-700 dark:text-gray-400">?</span>
                                            </label>
                                            <div x-show="tooltip" x-cloak
                                                class="absolute left-0 top-full mt-1 z-10 w-48 rounded-lg bg-gray-800 px-2.5 py-1.5 text-xs text-white shadow-lg dark:bg-gray-700">
                                                Assign a specific named user from the company as approver.
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Specific user picker --}}
                                    <template x-if="step.resolver_type === 'specific_user'">
                                        <div class="mt-2">
                                            <select :name="'groups[' + gi + '][steps][' + si + '][resolver_payload][user_id]'"
                                            x-model="step.resolver_payload.user_id"
                                            class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-sm text-gray-700 focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500 dark:border-gray-700 dark:bg-white/[0.03] dark:text-gray-300">
                                            <option value="">Select user...</option>
                                            @foreach ($users as $user)
                                                @php
                                                    $membership = $user->companies->first();
                                                    $jobTitle = $membership?->pivot?->job_title;
                                                    $companyName = $membership?->name;
                                                    $label = $user->name;
                                                    if ($jobTitle) {
                                                        $label .= ' — ' . $jobTitle;
                                                    }
                                                    if ($companyName) {
                                                        $label .= ' (' . $companyName . ')';
                                                    }
                                                @endphp
                                                <option value="{{ $user->id }}">{{ $label }}</option>
                                            @endforeach
                                            </select>
                                        </div>
                                    </template>

                                    {{-- Remove step button --}}
                                    <div class="mt-2 text-right" x-show="group.steps.length > 1">
                                        <button type="button" @click="removeStep(gi, si)"
                                            class="text-xs text-gray-400 hover:text-error-500 dark:hover:text-error-400">
                                            Remove Approver
                                        </button>
                                    </div>
                                </div>
                            </template>
                        </div>

                        {{-- Add Approver button --}}
                        <button type="button" @click="addStep(gi)"
                            class="mt-3 w-full inline-flex items-center justify-center gap-1 rounded-lg border border-dashed border-gray-300 px-3 py-1.5 text-xs font-medium text-gray-500 hover:border-brand-400 hover:text-brand-600 dark:border-gray-700 dark:text-gray-400 dark:hover:border-brand-400 dark:hover:text-brand-400">
                            <svg width="14" height="14" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd"/></svg>
                            Add Approver
                        </button>

                        {{-- Delete group button --}}
                        <div class="mt-3 text-right" x-show="groups.length > 1">
                            <button type="button" @click="removeGroup(gi)"
                                class="inline-flex items-center gap-1 rounded-lg px-2 py-1 text-xs font-medium text-gray-400 hover:bg-error-50 hover:text-error-600 dark:hover:bg-error-500/10 dark:hover:text-error-400">
                                <svg width="12" height="12" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                                Delete Group
                            </button>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <div class="flex justify-end gap-3 border-t border-gray-200 pt-5 dark:border-gray-700">
            <a href="{{ route('admin.approval-workflows.index') }}"
                class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-white/[0.03] dark:text-gray-300 dark:hover:bg-white/[0.05]">
                Cancel
            </a>
            <button type="submit"
                class="inline-flex items-center justify-center rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white shadow-theme-xs hover:bg-brand-600 focus:outline-hidden">
                Save Template
            </button>
        </div>
    </form>
</div>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('workflowBuilder', (initialGroups) => ({
            groups: initialGroups,
            addGroup() {
                this.groups.push({ name: 'Group ' + (this.groups.length + 1), type: 'sequential', steps: [{ label: '', resolver_type: 'N+1', resolver_payload: {} }] });
            },
            removeGroup(index) {
                if (this.groups.length > 1) this.groups.splice(index, 1);
            },
            addStep(groupIndex) {
                this.groups[groupIndex].steps.push({ label: '', resolver_type: 'N+1', resolver_payload: {} });
            },
            removeStep(groupIndex, stepIndex) {
                if (this.groups[groupIndex].steps.length > 1) this.groups[groupIndex].steps.splice(stepIndex, 1);
            }
        }));
    });
</script>
@endsection
