<?php

use App\Actions\Approvals\CreateWorkflowTemplateAction;
use App\Actions\Approvals\UpdateWorkflowTemplateAction;
use App\Enums\TemplateScope;
use App\Helpers\ApprovableTypes;
use App\Models\Approval\WorkflowTemplate;
use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    public ?int $templateId = null;

    public ?int $companyId = null;

    public string $name = '';

    public ?string $description = null;

    public ?string $approvableType = null;

    public string $scope = 'this_company';

    public bool $isActive = true;

    /**
     * @var array<int, array{
     *     name: string,
     *     type: string,
     *     min_approvals: int|string|null,
     *     steps: array<int, array{
     *         label: string|null,
     *         resolver_type: string,
     *         resolver_payload: array<string, mixed>
     *     }>
     * }>
     */
    public array $groups = [];

    public function mount(?int $templateId = null): void
    {
        /** @var User|null $user */
        $user = auth()->user();
        $activeCompany = $user?->getActiveCompany();

        if ($templateId !== null) {
            $template = WorkflowTemplate::with(['groups.steps'])->findOrFail($templateId);
            $this->authorizeTemplate($template);

            $this->templateId = $template->id;
            $this->companyId = $template->company_id;
            $this->name = $template->name;
            $this->description = $template->description;
            $this->approvableType = $template->approvable_type;
            $this->scope = $template->scope->value;
            $this->isActive = $template->is_active;

            $this->groups = [];
            foreach ($template->groups as $group) {
                $steps = [];
                foreach ($group->steps as $step) {
                    $steps[] = [
                        'label' => $step->label,
                        'resolver_type' => $step->resolver_type->value,
                        'resolver_payload' => $step->resolver_payload ?? ['user_id' => null],
                    ];
                }

                $this->groups[] = [
                    'name' => $group->name,
                    'type' => $group->type->value,
                    'min_approvals' => $group->min_approvals,
                    'steps' => $steps,
                ];
            }
        } else {
            $this->companyId = $activeCompany?->id;
            $this->scope = TemplateScope::ThisCompany->value;
            $this->isActive = true;

            // Default initial group with 1 N+1 manager step
            $this->groups = [
                [
                    'name' => 'Group 1',
                    'type' => 'sequential',
                    'min_approvals' => null,
                    'steps' => [
                        [
                            'label' => 'Direct Manager Approval',
                            'resolver_type' => 'N+1',
                            'resolver_payload' => ['user_id' => null],
                        ],
                    ],
                ],
            ];
        }
    }

    public function addGroup(): void
    {
        $groupNumber = count($this->groups) + 1;
        $this->groups[] = [
            'name' => "Group {$groupNumber}",
            'type' => 'sequential',
            'min_approvals' => null,
            'steps' => [
                [
                    'label' => 'Step Approval',
                    'resolver_type' => 'N+1',
                    'resolver_payload' => ['user_id' => null],
                ],
            ],
        ];
    }

    public function removeGroup(int $groupIndex): void
    {
        if (count($this->groups) <= 1) {
            $this->dispatch('notify', message: 'Workflow must have at least one step group.', type: 'warning');
            return;
        }

        unset($this->groups[$groupIndex]);
        $this->groups = array_values($this->groups);
    }

    public function moveGroupUp(int $groupIndex): void
    {
        if ($groupIndex <= 0) {
            return;
        }

        $prev = $this->groups[$groupIndex - 1];
        $this->groups[$groupIndex - 1] = $this->groups[$groupIndex];
        $this->groups[$groupIndex] = $prev;
    }

    public function moveGroupDown(int $groupIndex): void
    {
        if ($groupIndex >= count($this->groups) - 1) {
            return;
        }

        $next = $this->groups[$groupIndex + 1];
        $this->groups[$groupIndex + 1] = $this->groups[$groupIndex];
        $this->groups[$groupIndex] = $next;
    }

    public function addStep(int $groupIndex): void
    {
        if (! isset($this->groups[$groupIndex])) {
            return;
        }

        $this->groups[$groupIndex]['steps'][] = [
            'label' => 'Step Approval',
            'resolver_type' => 'N+1',
            'resolver_payload' => ['user_id' => null],
        ];
    }

    public function removeStep(int $groupIndex, int $stepIndex): void
    {
        if (! isset($this->groups[$groupIndex]['steps'])) {
            return;
        }

        if (count($this->groups[$groupIndex]['steps']) <= 1) {
            $this->dispatch('notify', message: 'Each group must have at least one step.', type: 'warning');
            return;
        }

        unset($this->groups[$groupIndex]['steps'][$stepIndex]);
        $this->groups[$groupIndex]['steps'] = array_values($this->groups[$groupIndex]['steps']);
    }

    public function moveStepUp(int $groupIndex, int $stepIndex): void
    {
        if (! isset($this->groups[$groupIndex]['steps']) || $stepIndex <= 0) {
            return;
        }

        $prev = $this->groups[$groupIndex]['steps'][$stepIndex - 1];
        $this->groups[$groupIndex]['steps'][$stepIndex - 1] = $this->groups[$groupIndex]['steps'][$stepIndex];
        $this->groups[$groupIndex]['steps'][$stepIndex] = $prev;
    }

    public function moveStepDown(int $groupIndex, int $stepIndex): void
    {
        if (! isset($this->groups[$groupIndex]['steps']) || $stepIndex >= count($this->groups[$groupIndex]['steps']) - 1) {
            return;
        }

        $next = $this->groups[$groupIndex]['steps'][$stepIndex + 1];
        $this->groups[$groupIndex]['steps'][$stepIndex + 1] = $this->groups[$groupIndex]['steps'][$stepIndex];
        $this->groups[$groupIndex]['steps'][$stepIndex] = $next;
    }

    public function save(
        CreateWorkflowTemplateAction $createAction,
        UpdateWorkflowTemplateAction $updateAction
    ) {
        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'approvableType' => ['nullable', 'string'],
            'scope' => ['required', 'in:this_company,subsidiaries,global'],
            'isActive' => ['boolean'],
            'groups' => ['required', 'array', 'min:1'],
            'groups.*.name' => ['required', 'string', 'max:255'],
            'groups.*.type' => ['required', 'in:sequential,parallel'],
            'groups.*.min_approvals' => ['nullable', 'integer', 'min:1'],
            'groups.*.steps' => ['required', 'array', 'min:1'],
            'groups.*.steps.*.label' => ['nullable', 'string', 'max:255'],
            'groups.*.steps.*.resolver_type' => ['required', 'in:N+1,specific_user,N+1_of_previous'],
            'groups.*.steps.*.resolver_payload.user_id' => [
                'nullable',
                function ($attribute, $value, $fail) {
                    preg_match('/groups\.(\d+)\.steps\.(\d+)\.resolver_payload\.user_id/', (string) $attribute, $matches);
                    if (! empty($matches)) {
                        $gIdx = (int) $matches[1];
                        $sIdx = (int) $matches[2];
                        $step = $this->groups[$gIdx]['steps'][$sIdx] ?? null;
                        if ($step && $step['resolver_type'] === 'specific_user' && empty($value)) {
                            $fail('Please select a specific user approver.');
                        }
                    }
                },
            ],
        ]);

        /** @var User|null $user */
        $user = auth()->user();
        $activeCompany = $user?->getActiveCompany();

        $payload = [
            'name' => $this->name,
            'description' => $this->description,
            'approvable_type' => $this->approvableType === '' ? null : $this->approvableType,
            'scope' => $this->scope,
            'is_active' => $this->isActive,
            'groups' => array_map(function ($group) {
                return [
                    'name' => $group['name'],
                    'type' => $group['type'],
                    'min_approvals' => ($group['type'] === 'parallel' && ! empty($group['min_approvals'])) ? (int) $group['min_approvals'] : null,
                    'steps' => array_map(function ($step) {
                        return [
                            'label' => $step['label'],
                            'resolver_type' => $step['resolver_type'],
                            'resolver_payload' => $step['resolver_type'] === 'specific_user'
                                ? ['user_id' => (int) ($step['resolver_payload']['user_id'] ?? 0)]
                                : null,
                        ];
                    }, $group['steps']),
                ];
            }, $this->groups),
        ];

        if ($this->templateId !== null) {
            $template = WorkflowTemplate::findOrFail($this->templateId);
            $this->authorizeTemplate($template);
            $updateAction->execute($template, $payload);
            session()->flash('success', 'Workflow template updated successfully.');
        } else {
            if (! $activeCompany) {
                abort(403, 'No active company context.');
            }
            $createAction->execute($activeCompany, $payload);
            session()->flash('success', 'Workflow template created successfully.');
        }

        return $this->redirect(route('admin.approval-workflows.index'), navigate: true);
    }

    private function authorizeTemplate(WorkflowTemplate $template): void
    {
        /** @var User|null $user */
        $user = auth()->user();
        if ($user?->hasPlatformAccess()) {
            return;
        }

        $company = $user?->getActiveCompany();
        if (! $company || $template->company_id !== $company->id) {
            abort(403, 'Unauthorized to manage this workflow template.');
        }
    }

    #[Computed]
    public function approvableTypeOptions(): array
    {
        return ApprovableTypes::options();
    }

    #[Computed]
    public function availableUsers(): Collection
    {
        /** @var User|null $user */
        $user = auth()->user();
        $company = $user?->getActiveCompany();

        if (! $company) {
            return new Collection;
        }

        $companyIds = collect([$company->id])
            ->merge($company->allAncestors()->pluck('id'));

        return User::whereHas('companies', fn ($q) => $q->whereIn('companies.id', $companyIds))
            ->with(['companies' => fn ($q) => $q->whereIn('companies.id', $companyIds)])
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function currentCompany(): ?Company
    {
        if ($this->companyId) {
            return Company::find($this->companyId);
        }

        return auth()->user()?->getActiveCompany();
    }
};
?>

<div class="space-y-6">
    <form wire:submit.prevent="save" class="space-y-6">
        {{-- Top Card: Template General Configuration --}}
        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-sm dark:border-gray-800 dark:bg-white/[0.03] md:p-6 space-y-5">
            <div class="flex items-center justify-between border-b border-gray-200 pb-4 dark:border-gray-800">
                <div>
                    <h3 class="text-base font-semibold text-gray-800 dark:text-white">
                        {{ $templateId ? 'Edit Workflow Template' : 'New Workflow Template' }}
                    </h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        Define multi-tier approval steps, approver resolvers, and execution hierarchy.
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <a
                        href="{{ $templateId ? route('admin.approval-workflows.edit', ['workflow' => $templateId, 'mode' => 'canvas']) : route('admin.approval-workflows.create', ['mode' => 'canvas']) }}"
                        class="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 bg-gray-50 px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-100 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700 transition"
                        title="Switch to Graphical WYSIWYG Canvas Mode"
                    >
                        <svg class="h-3.5 w-3.5 text-brand-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 14.25v2.25m3-4.5v4.5m3-6.75v6.75m3-9v9M6 20.25h12A2.25 2.25 0 0 0 20.25 18V6A2.25 2.25 0 0 0 18 3.75H6A2.25 2.25 0 0 0 3.75 6v12A2.25 2.25 0 0 0 6 20.25Z" />
                        </svg>
                        <span>🎨 Canvas Mode</span>
                    </a>

                    <div class="h-4 w-px bg-gray-200 dark:bg-gray-700"></div>

                    <label class="flex items-center gap-2 cursor-pointer text-xs font-medium text-gray-700 dark:text-gray-300">
                        <input
                            type="checkbox"
                            wire:model="isActive"
                            class="h-4 w-4 rounded-sm border-gray-300 text-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-800"
                        />
                        <span>Active Template</span>
                    </label>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                {{-- Template Name --}}
                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                        Template Name <span class="text-rose-500">*</span>
                    </label>
                    <input
                        type="text"
                        wire:model="name"
                        placeholder="e.g. Standard Purchase Request Approval"
                        class="h-10 w-full rounded-lg border border-gray-300 bg-white px-3.5 text-sm text-gray-800 shadow-theme-xs placeholder-gray-400 focus:border-brand-500 focus:outline-hidden dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                    />
                    @error('name')
                        <p class="mt-1 text-xs text-rose-500">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Applies To (Approvable Type) --}}
                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                        Applies To (Module)
                    </label>
                    <select
                        wire:model="approvableType"
                        class="h-10 w-full rounded-lg border border-gray-300 bg-white px-3.5 text-sm text-gray-800 shadow-theme-xs focus:border-brand-500 focus:outline-hidden dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                    >
                        <option value="">Universal / Any Module</option>
                        @foreach ($this->approvableTypeOptions as $class => $label)
                            <option value="{{ $class }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-[11px] text-gray-400">Leave universal to allow any approvable entity to select this template.</p>
                </div>

                {{-- Description --}}
                <div class="md:col-span-2">
                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                        Description
                    </label>
                    <textarea
                        wire:model="description"
                        rows="2"
                        placeholder="Briefly describe when and how this workflow is used..."
                        class="w-full rounded-lg border border-gray-300 bg-white p-3 text-sm text-gray-800 shadow-theme-xs placeholder-gray-400 focus:border-brand-500 focus:outline-hidden dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                    ></textarea>
                </div>

                {{-- Scope Selector --}}
                <div class="md:col-span-2">
                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Template Availability Scope <span class="text-rose-500">*</span>
                    </label>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <label class="flex items-start gap-3 p-3 rounded-xl border cursor-pointer transition {{ $scope === 'this_company' ? 'border-brand-500 bg-brand-50/40 dark:border-brand-500/30 dark:bg-brand-500/10' : 'border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-800/40' }}">
                            <input
                                type="radio"
                                wire:model.live="scope"
                                value="this_company"
                                class="mt-0.5 text-brand-500 focus:ring-brand-500"
                            />
                            <div>
                                <p class="text-xs font-semibold text-gray-800 dark:text-white">This Company Only</p>
                                <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-0.5">
                                    {{ $this->currentCompany ? $this->currentCompany->name : 'Active tenant only' }}
                                </p>
                            </div>
                        </label>

                        <label class="flex items-start gap-3 p-3 rounded-xl border cursor-pointer transition {{ $scope === 'subsidiaries' ? 'border-brand-500 bg-brand-50/40 dark:border-brand-500/30 dark:bg-brand-500/10' : 'border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-800/40' }}">
                            <input
                                type="radio"
                                wire:model.live="scope"
                                value="subsidiaries"
                                class="mt-0.5 text-brand-500 focus:ring-brand-500"
                            />
                            <div>
                                <p class="text-xs font-semibold text-gray-800 dark:text-white">Company &amp; Subsidiaries</p>
                                <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-0.5">
                                    Available down the organizational hierarchy
                                </p>
                            </div>
                        </label>

                        <label class="flex items-start gap-3 p-3 rounded-xl border cursor-pointer transition {{ $scope === 'global' ? 'border-brand-500 bg-brand-50/40 dark:border-brand-500/30 dark:bg-brand-500/10' : 'border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-800/40' }}">
                            <input
                                type="radio"
                                wire:model.live="scope"
                                value="global"
                                class="mt-0.5 text-brand-500 focus:ring-brand-500"
                            />
                            <div>
                                <p class="text-xs font-semibold text-gray-800 dark:text-white">Global (All Companies)</p>
                                <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-0.5">
                                    Universal cross-company template
                                </p>
                            </div>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        {{-- Interactive Visual Flowchart / DAG Preview --}}
        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-sm dark:border-gray-800 dark:bg-white/[0.03] md:p-6">
            <div class="flex items-center justify-between border-b border-gray-200 pb-3 dark:border-gray-800 mb-4">
                <div class="flex items-center gap-2">
                    <svg class="h-4 w-4 text-brand-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 14.25v2.25m3-4.5v4.5m3-6.75v6.75m3-9v9M6 20.25h12A2.25 2.25 0 0 0 20.25 18V6A2.25 2.25 0 0 0 18 3.75H6A2.25 2.25 0 0 0 3.75 6v12A2.25 2.25 0 0 0 6 20.25Z" />
                    </svg>
                    <h4 class="text-xs font-semibold uppercase tracking-wider text-gray-700 dark:text-gray-300">
                        Live Workflow Pipeline Preview
                    </h4>
                </div>
                <span class="text-[11px] text-gray-400">Updates live as you configure groups and steps</span>
            </div>

            <div class="overflow-x-auto py-3">
                <div class="inline-flex items-center gap-3 min-w-full justify-start sm:justify-center">
                    {{-- Start Node --}}
                    <div class="flex flex-col items-center">
                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-blue-100 text-blue-700 ring-4 ring-blue-50 dark:bg-blue-900/40 dark:text-blue-300 dark:ring-blue-900/20">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                            </svg>
                        </div>
                        <span class="mt-1 text-[11px] font-medium text-gray-600 dark:text-gray-400">Requester</span>
                    </div>

                    {{-- Connector --}}
                    <div class="h-0.5 w-6 bg-gray-300 dark:bg-gray-700"></div>

                    {{-- Groups --}}
                    @foreach ($groups as $gIdx => $group)
                        <div class="flex items-center gap-3">
                            <div class="rounded-xl border border-gray-200 bg-gray-50/60 p-3 text-left shadow-2xs dark:border-gray-700 dark:bg-gray-800/60 min-w-[200px]">
                                <div class="flex items-center justify-between border-b border-gray-200 pb-1.5 dark:border-gray-700 mb-2">
                                    <span class="text-xs font-semibold text-gray-800 dark:text-white">{{ $group['name'] ?: 'Group ' . ($gIdx + 1) }}</span>
                                    <span class="rounded px-1.5 py-0.5 text-[9px] font-bold uppercase {{ $group['type'] === 'sequential' ? 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300' : 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300' }}">
                                        {{ $group['type'] }}
                                    </span>
                                </div>
                                <div class="space-y-1.5">
                                    @foreach ($group['steps'] as $sIdx => $step)
                                        <div class="flex items-center justify-between gap-2 rounded-lg bg-white px-2.5 py-1.5 text-xs shadow-theme-xs border border-gray-100 dark:border-gray-700 dark:bg-gray-800">
                                            <span class="font-medium text-gray-700 dark:text-gray-300 truncate max-w-[120px]">
                                                {{ $step['label'] ?: 'Step ' . ($sIdx + 1) }}
                                            </span>
                                            <span class="text-[10px] text-brand-600 dark:text-brand-400 font-mono">
                                                @if ($step['resolver_type'] === 'N+1')
                                                    N+1 (Mgr)
                                                @elseif ($step['resolver_type'] === 'N+1_of_previous')
                                                    N+1 of Prev
                                                @else
                                                    Specific
                                                @endif
                                            </span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            <div class="h-0.5 w-6 bg-gray-300 dark:bg-gray-700"></div>
                        </div>
                    @endforeach

                    {{-- Completion Node --}}
                    <div class="flex flex-col items-center">
                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-emerald-100 text-emerald-700 ring-4 ring-emerald-50 dark:bg-emerald-900/40 dark:text-emerald-300 dark:ring-emerald-900/20">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                            </svg>
                        </div>
                        <span class="mt-1 text-[11px] font-medium text-emerald-600 dark:text-emerald-400">Approved</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Groups & Steps Configuration --}}
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-semibold text-gray-800 dark:text-white">
                        Workflow Groups &amp; Approver Steps
                    </h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        Configure group execution modes and add specific or hierarchical resolvers.
                    </p>
                </div>
                <button
                    type="button"
                    wire:click="addGroup"
                    class="inline-flex items-center gap-1.5 rounded-lg border border-brand-500 bg-brand-50/50 px-3.5 py-2 text-xs font-medium text-brand-600 hover:bg-brand-100 dark:border-brand-500/30 dark:bg-brand-500/10 dark:text-brand-400 dark:hover:bg-brand-500/20 cursor-pointer transition"
                >
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    <span>Add Step Group</span>
                </button>
            </div>

            @foreach ($groups as $gIdx => $group)
                <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-sm dark:border-gray-800 dark:bg-white/[0.03] space-y-4">
                    {{-- Group Header --}}
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-gray-100 pb-3.5 dark:border-gray-800">
                        <div class="flex items-center gap-3 flex-1">
                            <span class="flex h-6 w-6 items-center justify-center rounded-full bg-brand-100 text-xs font-bold text-brand-700 dark:bg-brand-900/40 dark:text-brand-400">
                                {{ $gIdx + 1 }}
                            </span>
                            <div class="flex-1 max-w-xs">
                                <input
                                    type="text"
                                    wire:model="groups.{{ $gIdx }}.name"
                                    placeholder="Group Name (e.g. Management Review)"
                                    class="h-8.5 w-full rounded-md border border-gray-300 bg-white px-2.5 text-xs font-semibold text-gray-800 shadow-theme-xs focus:border-brand-500 focus:outline-hidden dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                                />
                                @error("groups.{$gIdx}.name")
                                    <p class="mt-0.5 text-[10px] text-rose-500">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div class="flex items-center gap-3">
                            {{-- Group Execution Mode --}}
                            <div class="flex items-center gap-2">
                                <label class="text-[11px] text-gray-500 dark:text-gray-400">Mode:</label>
                                <select
                                    wire:model.live="groups.{{ $gIdx }}.type"
                                    class="h-8.5 rounded-md border border-gray-300 bg-white px-2 text-xs font-medium text-gray-700 shadow-theme-xs focus:border-brand-500 focus:outline-hidden dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300"
                                >
                                    <option value="sequential">Sequential (Ordered)</option>
                                    <option value="parallel">Parallel (Simultaneous)</option>
                                </select>
                            </div>

                            {{-- Min Approvals (if Parallel) --}}
                            @if ($group['type'] === 'parallel')
                                <div class="flex items-center gap-1.5">
                                    <label class="text-[11px] text-gray-500 dark:text-gray-400">Min Approvals:</label>
                                    <input
                                        type="number"
                                        min="1"
                                        max="{{ count($group['steps']) }}"
                                        wire:model="groups.{{ $gIdx }}.min_approvals"
                                        placeholder="All"
                                        class="h-8.5 w-16 rounded-md border border-gray-300 bg-white px-2 text-xs text-center text-gray-800 shadow-theme-xs focus:border-brand-500 focus:outline-hidden dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                                    />
                                </div>
                            @endif

                            {{-- Group Reorder & Remove --}}
                            <div class="flex items-center gap-1 border-l border-gray-200 pl-3 dark:border-gray-700">
                                <button
                                    type="button"
                                    wire:click="moveGroupUp({{ $gIdx }})"
                                    class="rounded p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-gray-700 cursor-pointer disabled:opacity-30"
                                    {{ $gIdx === 0 ? 'disabled' : '' }}
                                    title="Move Group Up"
                                >
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 15.75 7.5-7.5 7.5 7.5" />
                                    </svg>
                                </button>
                                <button
                                    type="button"
                                    wire:click="moveGroupDown({{ $gIdx }})"
                                    class="rounded p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-gray-700 cursor-pointer disabled:opacity-30"
                                    {{ $gIdx === count($groups) - 1 ? 'disabled' : '' }}
                                    title="Move Group Down"
                                >
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                                    </svg>
                                </button>
                                <button
                                    type="button"
                                    wire:click="removeGroup({{ $gIdx }})"
                                    class="rounded p-1 text-rose-500 hover:bg-rose-50 hover:text-rose-600 dark:hover:bg-rose-900/30 cursor-pointer"
                                    title="Delete Group"
                                >
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- Steps in Group --}}
                    <div class="space-y-2.5">
                        @foreach ($group['steps'] as $sIdx => $step)
                            <div class="flex flex-col sm:flex-row sm:items-center gap-3 rounded-xl border border-gray-200 bg-gray-50/50 p-3 dark:border-gray-700/60 dark:bg-white/[0.01]">
                                <span class="text-xs font-mono font-semibold text-gray-400 sm:w-6">
                                    #{{ $sIdx + 1 }}
                                </span>

                                {{-- Step Label --}}
                                <div class="flex-1">
                                    <input
                                        type="text"
                                        wire:model="groups.{{ $gIdx }}.steps.{{ $sIdx }}.label"
                                        placeholder="Step Label (e.g. Line Manager Approval)"
                                        class="h-8.5 w-full rounded-md border border-gray-300 bg-white px-2.5 text-xs text-gray-800 shadow-theme-xs focus:border-brand-500 focus:outline-hidden dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                                    />
                                    @error("groups.{$gIdx}.steps.{$sIdx}.label")
                                        <p class="mt-0.5 text-[10px] text-rose-500">{{ $message }}</p>
                                    @enderror
                                </div>

                                {{-- Resolver Type Selector --}}
                                <div class="sm:w-56">
                                    <select
                                        wire:model.live="groups.{{ $gIdx }}.steps.{{ $sIdx }}.resolver_type"
                                        class="h-8.5 w-full rounded-md border border-gray-300 bg-white px-2.5 text-xs text-gray-800 shadow-theme-xs focus:border-brand-500 focus:outline-hidden dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                                    >
                                        <option value="N+1">N+1 (Direct Line Manager)</option>
                                        <option value="N+1_of_previous">N+1 of Previous Approver</option>
                                        <option value="specific_user">Specific Named User</option>
                                    </select>
                                </div>

                                {{-- Specific User Selector (if resolver_type == specific_user) --}}
                                @if ($step['resolver_type'] === 'specific_user')
                                    <div class="sm:w-60">
                                        <select
                                            wire:model="groups.{{ $gIdx }}.steps.{{ $sIdx }}.resolver_payload.user_id"
                                            class="h-8.5 w-full rounded-md border border-gray-300 bg-white px-2.5 text-xs text-gray-800 shadow-theme-xs focus:border-brand-500 focus:outline-hidden dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                                        >
                                            <option value="">Select Approver User...</option>
                                            @foreach ($this->availableUsers as $u)
                                                <option value="{{ $u->id }}">{{ $u->name }} ({{ $u->email }})</option>
                                            @endforeach
                                        </select>
                                        @error("groups.{$gIdx}.steps.{$sIdx}.resolver_payload.user_id")
                                            <p class="mt-0.5 text-[10px] text-rose-500">{{ $message }}</p>
                                        @enderror
                                    </div>
                                @endif

                                {{-- Step Actions --}}
                                <div class="flex items-center justify-end gap-1">
                                    <button
                                        type="button"
                                        wire:click="moveStepUp({{ $gIdx }}, {{ $sIdx }})"
                                        class="rounded p-1 text-gray-400 hover:bg-gray-200/50 hover:text-gray-600 dark:hover:bg-gray-700 cursor-pointer disabled:opacity-30"
                                        {{ $sIdx === 0 ? 'disabled' : '' }}
                                        title="Move Step Up"
                                    >
                                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 15.75 7.5-7.5 7.5 7.5" />
                                        </svg>
                                    </button>
                                    <button
                                        type="button"
                                        wire:click="moveStepDown({{ $gIdx }}, {{ $sIdx }})"
                                        class="rounded p-1 text-gray-400 hover:bg-gray-200/50 hover:text-gray-600 dark:hover:bg-gray-700 cursor-pointer disabled:opacity-30"
                                        {{ $sIdx === count($group['steps']) - 1 ? 'disabled' : '' }}
                                        title="Move Step Down"
                                    >
                                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                                        </svg>
                                    </button>
                                    <button
                                        type="button"
                                        wire:click="removeStep({{ $gIdx }}, {{ $sIdx }})"
                                        class="rounded p-1 text-rose-500 hover:bg-rose-50 hover:text-rose-600 dark:hover:bg-rose-900/30 cursor-pointer"
                                        title="Remove Step"
                                    >
                                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    {{-- Add Step Button --}}
                    <div>
                        <button
                            type="button"
                            wire:click="addStep({{ $gIdx }})"
                            class="inline-flex items-center gap-1 text-xs font-medium text-brand-600 hover:text-brand-700 dark:text-brand-400 cursor-pointer"
                        >
                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                            </svg>
                            <span>Add Step to {{ $group['name'] ?: 'Group ' . ($gIdx + 1) }}</span>
                        </button>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Form Actions Bar --}}
        <div class="flex items-center justify-between pt-4 border-t border-gray-200 dark:border-gray-800">
            <a
                href="{{ route('admin.approval-workflows.index') }}"
                wire:navigate
                class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 shadow-theme-xs hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
            >
                Cancel
            </a>

            <button
                type="submit"
                wire:loading.attr="disabled"
                class="inline-flex items-center justify-center gap-2 rounded-lg bg-brand-500 px-6 py-2.5 text-sm font-medium text-white shadow-theme-xs hover:bg-brand-600 focus:outline-hidden disabled:opacity-50 transition cursor-pointer"
            >
                <span wire:loading.remove>
                    {{ $templateId ? 'Update Workflow Template' : 'Create Workflow Template' }}
                </span>
                <span wire:loading class="inline-flex items-center gap-2">
                    <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Saving Template...
                </span>
            </button>
        </div>
    </form>
</div>
