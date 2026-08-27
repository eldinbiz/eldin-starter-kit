<?php

declare(strict_types=1);

namespace Modules\PurchaseRequests\Livewire;

use App\Models\Approval\WorkflowTemplate;
use App\Models\User;
use App\Services\ApprovalEngine;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Modules\PurchaseRequests\Models\PurchaseRequest;

class PurchaseRequestWizard extends Component
{
    public int $currentStep = 1; // 1: Info, 2: Line Items, 3: Justification, 4: Workflow Review & Submit

    // Step 1: General Info
    public string $title = '';
    public string $department = 'IT';
    public string $description = '';

    // Step 2: Dynamic Line Items
    public array $lineItems = [
        [
            'description' => '',
            'quantity' => 1,
            'unit_price' => 0.00,
        ],
    ];

    // Step 3: Justification & Urgency
    public string $urgency = 'normal'; // 'low' | 'normal' | 'high' | 'urgent'
    public string $vendorName = '';
    public string $businessJustification = '';

    // Step 4: Workflow Template Selection
    public ?int $templateId = null;

    protected function rulesForStep(int $step): array
    {
        return match ($step) {
            1 => [
                'title' => ['required', 'string', 'min:3', 'max:255'],
                'department' => ['required', 'string', 'max:100'],
                'description' => ['nullable', 'string', 'max:1000'],
            ],
            2 => [
                'lineItems' => ['required', 'array', 'min:1'],
                'lineItems.*.description' => ['required', 'string', 'min:2', 'max:255'],
                'lineItems.*.quantity' => ['required', 'numeric', 'min:1'],
                'lineItems.*.unit_price' => ['required', 'numeric', 'min:0.01'],
            ],
            3 => [
                'urgency' => ['required', 'in:low,normal,high,urgent'],
                'vendorName' => ['nullable', 'string', 'max:255'],
                'businessJustification' => ['nullable', 'string', 'max:2000'],
            ],
            4 => [
                'templateId' => ['nullable', 'integer', 'exists:approval_workflow_templates,id'],
            ],
            default => [],
        };
    }

    public function nextStep(): void
    {
        $this->validate($this->rulesForStep($this->currentStep));
        $this->currentStep = min(4, $this->currentStep + 1);
    }

    public function previousStep(): void
    {
        $this->currentStep = max(1, $this->currentStep - 1);
    }

    public function goToStep(int $step): void
    {
        if ($step < $this->currentStep) {
            $this->currentStep = $step;
            return;
        }

        // Validate all preceding steps
        for ($s = 1; $s < $step; $s++) {
            $this->validate($this->rulesForStep($s));
        }

        $this->currentStep = $step;
    }

    // Line item manipulation
    public function addLineItem(): void
    {
        $this->lineItems[] = [
            'description' => '',
            'quantity' => 1,
            'unit_price' => 0.00,
        ];
    }

    public function removeLineItem(int $index): void
    {
        if (count($this->lineItems) > 1) {
            unset($this->lineItems[$index]);
            $this->lineItems = array_values($this->lineItems);
        }
    }

    #[Computed]
    public function totalAmount(): float
    {
        $total = 0.0;
        foreach ($this->lineItems as $item) {
            $qty = (float) ($item['quantity'] ?? 0);
            $price = (float) ($item['unit_price'] ?? 0);
            $total += ($qty * $price);
        }

        return round($total, 2);
    }

    #[Computed]
    public function availableTemplates(): Collection
    {
        /** @var User|null $user */
        $user = auth()->user();
        $company = $user?->getActiveCompany();

        if (! $company) {
            return collect();
        }

        return WorkflowTemplate::availableFor($company)
            ->where('is_active', true)
            ->where(fn ($q) => $q->where('approvable_type', PurchaseRequest::class)->orWhereNull('approvable_type'))
            ->with(['groups.steps'])
            ->get();
    }

    #[Computed]
    public function selectedTemplate(): ?WorkflowTemplate
    {
        if (! $this->templateId) {
            return $this->availableTemplates->first();
        }

        return $this->availableTemplates->firstWhere('id', $this->templateId);
    }

    public function saveDraft()
    {
        $this->validate($this->rulesForStep(1));
        if ($this->currentStep >= 2) {
            $this->validate($this->rulesForStep(2));
        }

        $purchaseRequest = $this->createPurchaseRequestModel('draft');

        session()->flash('success', "Purchase Request {$purchaseRequest->pr_number} saved as draft.");

        return $this->redirect(route('purchase-requests.show', $purchaseRequest->id), navigate: true);
    }

    public function submit(ApprovalEngine $engine)
    {
        $this->validate($this->rulesForStep(1));
        $this->validate($this->rulesForStep(2));
        $this->validate($this->rulesForStep(3));

        $template = $this->selectedTemplate;
        if (! $template) {
            $this->addError('templateId', 'Please select an approval workflow template.');
            return;
        }

        $purchaseRequest = $this->createPurchaseRequestModel('pending_approval');

        $engine->submit($purchaseRequest, $template);

        session()->flash('success', "Purchase Request {$purchaseRequest->pr_number} submitted for approval successfully.");

        return $this->redirect(route('purchase-requests.show', $purchaseRequest->id), navigate: true);
    }

    private function createPurchaseRequestModel(string $status): PurchaseRequest
    {
        /** @var User $user */
        $user = auth()->user();
        $company = $user->getActiveCompany();

        $prNumber = 'PR-'.date('Ym').'-'.str_pad((string) (PurchaseRequest::count() + 1), 4, '0', STR_PAD_LEFT);

        $detailedDescription = trim($this->description);
        if ($this->vendorName !== '') {
            $detailedDescription .= "\nVendor: {$this->vendorName}";
        }
        if ($this->urgency !== '') {
            $detailedDescription .= "\nUrgency: ".strtoupper($this->urgency);
        }
        if ($this->businessJustification !== '') {
            $detailedDescription .= "\nJustification: {$this->businessJustification}";
        }

        return PurchaseRequest::create([
            'company_id' => $company->id,
            'requester_id' => $user->id,
            'pr_number' => $prNumber,
            'title' => $this->title,
            'department' => $this->department,
            'description' => $detailedDescription,
            'total_amount' => $this->totalAmount,
            'status' => $status,
        ]);
    }

    public function render()
    {
        return view('purchase-requests::livewire.purchase-request-wizard');
    }
}
