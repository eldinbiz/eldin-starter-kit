<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Approvals;

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

class WorkflowTemplateEditor extends Component
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
                    // Extract group and step index from attribute name: groups.0.steps.0.resolver_payload.user_id
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

    public function render()
    {
        return view('livewire.admin.approvals.workflow-template-editor');
    }
}
