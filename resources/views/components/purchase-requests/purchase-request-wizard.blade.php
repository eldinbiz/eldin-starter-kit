<?php

use App\Models\Approval\WorkflowTemplate;
use App\Models\User;
use App\Services\ApprovalEngine;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Modules\PurchaseRequests\Models\PurchaseRequest;

new class extends Component
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
};
?>

<div class="space-y-6">
    {{-- Stepper Header --}}
    <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-sm dark:border-gray-800 dark:bg-white/[0.03] md:p-6">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Purchase Request Multi-Step Wizard</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400">Step {{ $currentStep }} of 4: {{ match($currentStep) { 1 => 'General Information', 2 => 'Itemized Procurement Lines', 3 => 'Business Justification', 4 => 'Workflow Review & Submission' } }}</p>
            </div>
            <a href="{{ route('purchase-requests.index') }}" wire:navigate class="text-xs font-medium text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                &larr; Back to Requests
            </a>
        </div>

        {{-- Step Navigation Indicator --}}
        <div class="grid grid-cols-4 gap-2 border-t border-gray-100 pt-4 dark:border-gray-800">
            @foreach ([1 => '1. General Info', 2 => '2. Line Items', 3 => '3. Justification', 4 => '4. Review & Submit'] as $stepNumber => $stepLabel)
                <button
                    type="button"
                    wire:click="goToStep({{ $stepNumber }})"
                    class="flex flex-col items-center sm:items-start text-left cursor-pointer transition-all {{ $currentStep === $stepNumber ? 'opacity-100' : ($currentStep > $stepNumber ? 'opacity-90' : 'opacity-40') }}"
                >
                    <div class="h-1.5 w-full rounded-full {{ $currentStep >= $stepNumber ? 'bg-brand-500' : 'bg-gray-200 dark:bg-gray-700' }} mb-2"></div>
                    <span class="text-xs font-medium {{ $currentStep === $stepNumber ? 'text-brand-600 dark:text-brand-400 font-semibold' : 'text-gray-600 dark:text-gray-400' }}">
                        {{ $stepLabel }}
                    </span>
                </button>
            @endforeach
        </div>
    </div>

    {{-- STEP 1: General Info --}}
    @if ($currentStep === 1)
        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-sm dark:border-gray-800 dark:bg-white/[0.03] md:p-6 space-y-4">
            <h4 class="text-base font-semibold text-gray-900 dark:text-white">Step 1: General Information &amp; Department</h4>

            <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Request Title <span class="text-rose-500">*</span>
                </label>
                <input
                    type="text"
                    wire:model="title"
                    placeholder="e.g., Engineering High-Performance Laptops Q3"
                    class="h-10 w-full rounded-lg border border-gray-300 bg-white px-3 text-sm text-gray-800 shadow-theme-xs focus:border-brand-500 focus:outline-hidden dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                />
                @error('title') <p class="mt-1 text-xs text-rose-500">{{ $message }}</p> @enderror
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Requesting Department <span class="text-rose-500">*</span>
                    </label>
                    <select
                        wire:model="department"
                        class="h-10 w-full rounded-lg border border-gray-300 bg-white px-3 text-xs text-gray-800 shadow-theme-xs focus:border-brand-500 focus:outline-hidden dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                    >
                        <option value="IT">IT &amp; Technology</option>
                        <option value="Finance">Finance &amp; Accounting</option>
                        <option value="Procurement">Procurement</option>
                        <option value="HR">Human Resources</option>
                        <option value="Operations">Operations</option>
                        <option value="Marketing">Marketing &amp; Sales</option>
                    </select>
                    @error('department') <p class="mt-1 text-xs text-rose-500">{{ $message }}</p> @enderror
                </div>
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Description / Procurement Overview
                </label>
                <textarea
                    wire:model="description"
                    rows="3"
                    placeholder="Provide a brief summary of what is being requested and why..."
                    class="w-full rounded-lg border border-gray-300 bg-white p-3 text-xs text-gray-800 shadow-theme-xs focus:border-brand-500 focus:outline-hidden dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                ></textarea>
                @error('description') <p class="mt-1 text-xs text-rose-500">{{ $message }}</p> @enderror
            </div>

            <div class="flex items-center justify-between pt-4 border-t border-gray-100 dark:border-gray-800">
                <a href="{{ route('purchase-requests.index') }}" wire:navigate class="text-xs font-medium text-gray-500 hover:text-gray-700">Cancel</a>
                <button
                    type="button"
                    wire:click="nextStep"
                    class="inline-flex items-center justify-center rounded-lg bg-brand-500 px-5 py-2.5 text-xs font-semibold text-white shadow-theme-xs hover:bg-brand-600 cursor-pointer"
                >
                    Proceed to Line Items &rarr;
                </button>
            </div>
        </div>
    @endif

    {{-- STEP 2: Line Items Calculation --}}
    @if ($currentStep === 2)
        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-sm dark:border-gray-800 dark:bg-white/[0.03] md:p-6 space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h4 class="text-base font-semibold text-gray-900 dark:text-white">Step 2: Line Items &amp; Dynamic Pricing</h4>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Add individual items. Total price is calculated reactively.</p>
                </div>
                <button
                    type="button"
                    wire:click="addLineItem"
                    class="inline-flex items-center gap-1 rounded-lg border border-brand-300 bg-brand-50 px-3 py-1.5 text-xs font-medium text-brand-700 hover:bg-brand-100 dark:border-brand-700 dark:bg-brand-500/10 dark:text-brand-400 cursor-pointer"
                >
                    + Add Item Row
                </button>
            </div>

            @error('lineItems') <p class="text-xs text-rose-500">{{ $message }}</p> @enderror

            <div class="space-y-3">
                @foreach ($lineItems as $index => $item)
                    <div class="rounded-xl border border-gray-200 bg-gray-50/50 p-4 dark:border-gray-700 dark:bg-gray-800/30 flex flex-col md:flex-row items-start md:items-center gap-3">
                        <div class="flex-1 w-full">
                            <label class="block text-[11px] font-medium text-gray-500 dark:text-gray-400 mb-1">Item Description</label>
                            <input
                                type="text"
                                wire:model.live="lineItems.{{ $index }}.description"
                                placeholder="Item name or specifications"
                                class="h-9 w-full rounded-lg border border-gray-300 bg-white px-3 text-xs text-gray-800 shadow-theme-xs focus:border-brand-500 focus:outline-hidden dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                            />
                            @error("lineItems.{$index}.description") <p class="text-[10px] text-rose-500 mt-0.5">{{ $message }}</p> @enderror
                        </div>

                        <div class="w-24">
                            <label class="block text-[11px] font-medium text-gray-500 dark:text-gray-400 mb-1">Quantity</label>
                            <input
                                type="number"
                                min="1"
                                wire:model.live="lineItems.{{ $index }}.quantity"
                                class="h-9 w-full rounded-lg border border-gray-300 bg-white px-2.5 text-xs text-gray-800 shadow-theme-xs focus:border-brand-500 focus:outline-hidden dark:border-gray-700 dark:bg-gray-800 dark:text-white text-right"
                            />
                        </div>

                        <div class="w-32">
                            <label class="block text-[11px] font-medium text-gray-500 dark:text-gray-400 mb-1">Unit Price ($)</label>
                            <input
                                type="number"
                                step="0.01"
                                min="0.01"
                                wire:model.live="lineItems.{{ $index }}.unit_price"
                                class="h-9 w-full rounded-lg border border-gray-300 bg-white px-2.5 text-xs text-gray-800 shadow-theme-xs focus:border-brand-500 focus:outline-hidden dark:border-gray-700 dark:bg-gray-800 dark:text-white text-right"
                            />
                        </div>

                        <div class="w-28 text-right">
                            <span class="block text-[11px] font-medium text-gray-500 dark:text-gray-400 mb-1">Subtotal</span>
                            <span class="text-xs font-semibold text-gray-900 dark:text-white">
                                ${{ number_format(((float) ($item['quantity'] ?? 0)) * ((float) ($item['unit_price'] ?? 0)), 2) }}
                            </span>
                        </div>

                        <div class="pt-5 md:pt-0">
                            @if (count($lineItems) > 1)
                                <button
                                    type="button"
                                    wire:click="removeLineItem({{ $index }})"
                                    class="text-xs text-rose-500 hover:text-rose-700 p-1 cursor-pointer"
                                >
                                    ✕
                                </button>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Grand Total Display Card --}}
            <div class="flex justify-end p-4 rounded-xl bg-brand-50/50 border border-brand-100 dark:bg-brand-500/5 dark:border-brand-500/20">
                <div class="text-right">
                    <span class="text-xs text-gray-600 dark:text-gray-400 font-medium">Estimated Total Requisition Amount:</span>
                    <h3 class="text-2xl font-bold text-brand-600 dark:text-brand-400">
                        ${{ number_format($this->totalAmount, 2) }}
                    </h3>
                </div>
            </div>

            <div class="flex items-center justify-between pt-4 border-t border-gray-100 dark:border-gray-800">
                <button
                    type="button"
                    wire:click="previousStep"
                    class="rounded-lg border border-gray-300 px-4 py-2 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 cursor-pointer"
                >
                    &larr; Back
                </button>
                <button
                    type="button"
                    wire:click="nextStep"
                    class="inline-flex items-center justify-center rounded-lg bg-brand-500 px-5 py-2.5 text-xs font-semibold text-white shadow-theme-xs hover:bg-brand-600 cursor-pointer"
                >
                    Proceed to Justification &rarr;
                </button>
            </div>
        </div>
    @endif

    {{-- STEP 3: Justification & Urgency --}}
    @if ($currentStep === 3)
        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-sm dark:border-gray-800 dark:bg-white/[0.03] md:p-6 space-y-4">
            <h4 class="text-base font-semibold text-gray-900 dark:text-white">Step 3: Procurement Justification &amp; Urgency</h4>

            <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Priority Level <span class="text-rose-500">*</span>
                </label>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                    @foreach (['low' => 'Low Priority', 'normal' => 'Standard / Normal', 'high' => 'High Priority', 'urgent' => 'Immediate / Urgent'] as $val => $lbl)
                        <label class="flex items-center justify-center p-3 rounded-lg border text-xs font-medium cursor-pointer transition {{ $urgency === $val ? 'border-brand-500 bg-brand-50/50 text-brand-700 font-semibold dark:bg-brand-500/10 dark:text-brand-300' : 'border-gray-200 bg-white text-gray-600 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300' }}">
                            <input type="radio" wire:model="urgency" value="{{ $val }}" class="sr-only">
                            <span>{{ $lbl }}</span>
                        </label>
                    @endforeach
                </div>
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Preferred Vendor / Supplier (Optional)
                </label>
                <input
                    type="text"
                    wire:model="vendorName"
                    placeholder="e.g., Apple Enterprise, Dell Direct, Amazon Business"
                    class="h-10 w-full rounded-lg border border-gray-300 bg-white px-3 text-sm text-gray-800 shadow-theme-xs focus:border-brand-500 focus:outline-hidden dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                />
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Detailed Business Justification &amp; ROI
                </label>
                <textarea
                    wire:model="businessJustification"
                    rows="4"
                    placeholder="Explain the business impact, cost benefit, or project alignment for this expenditure..."
                    class="w-full rounded-lg border border-gray-300 bg-white p-3 text-xs text-gray-800 shadow-theme-xs focus:border-brand-500 focus:outline-hidden dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                ></textarea>
            </div>

            <div class="flex items-center justify-between pt-4 border-t border-gray-100 dark:border-gray-800">
                <button
                    type="button"
                    wire:click="previousStep"
                    class="rounded-lg border border-gray-300 px-4 py-2 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 cursor-pointer"
                >
                    &larr; Back
                </button>
                <button
                    type="button"
                    wire:click="nextStep"
                    class="inline-flex items-center justify-center rounded-lg bg-brand-500 px-5 py-2.5 text-xs font-semibold text-white shadow-theme-xs hover:bg-brand-600 cursor-pointer"
                >
                    Proceed to Workflow Review &rarr;
                </button>
            </div>
        </div>
    @endif

    {{-- STEP 4: Workflow Review & Submit --}}
    @if ($currentStep === 4)
        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-sm dark:border-gray-800 dark:bg-white/[0.03] md:p-6 space-y-5">
            <div>
                <h4 class="text-base font-semibold text-gray-900 dark:text-white">Step 4: Approval Workflow Selection &amp; Submission</h4>
                <p class="text-xs text-gray-500 dark:text-gray-400">Review request summary and select the routing template before submitting.</p>
            </div>

            {{-- Summary Card --}}
            <div class="p-4 rounded-xl bg-gray-50/70 border border-gray-200 dark:bg-gray-800/40 dark:border-gray-700 space-y-2">
                <div class="flex justify-between items-center border-b border-gray-200/60 pb-2 dark:border-gray-700">
                    <span class="text-xs text-gray-500">Requisition:</span>
                    <strong class="text-xs text-gray-900 dark:text-white">{{ $title }} ({{ $department }})</strong>
                </div>
                <div class="flex justify-between items-center border-b border-gray-200/60 pb-2 dark:border-gray-700">
                    <span class="text-xs text-gray-500">Total Items:</span>
                    <span class="text-xs font-semibold text-gray-800 dark:text-gray-200">{{ count($lineItems) }} item line(s)</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-xs text-gray-500">Total Amount:</span>
                    <span class="text-base font-bold text-brand-600 dark:text-brand-400">${{ number_format($this->totalAmount, 2) }}</span>
                </div>
            </div>

            {{-- Workflow Template Selector --}}
            <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Select Approval Workflow Template <span class="text-rose-500">*</span>
                </label>
                <select
                    wire:model.live="templateId"
                    class="h-10 w-full rounded-lg border border-gray-300 bg-white px-3 text-xs text-gray-800 shadow-theme-xs focus:border-brand-500 focus:outline-hidden dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                >
                    @foreach ($this->availableTemplates as $t)
                        <option value="{{ $t->id }}">{{ $t->name }} ({{ ucfirst($t->scope->value) }})</option>
                    @endforeach
                </select>
                @error('templateId') <p class="mt-1 text-xs text-rose-500">{{ $message }}</p> @enderror
            </div>

            {{-- Workflow DAG Preview --}}
            @if ($this->selectedTemplate)
                <div class="p-4 rounded-xl border border-dashed border-gray-300 bg-gray-50/40 dark:border-gray-700 dark:bg-gray-800/20">
                    <h5 class="text-xs font-semibold text-gray-700 dark:text-gray-300 mb-2">Approval Pipeline Preview</h5>
                    <div class="flex flex-wrap items-center gap-2 text-xs">
                        <span class="px-2.5 py-1 rounded-md bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300 font-medium">
                            Requester (You)
                        </span>
                        <span class="text-gray-400">&rarr;</span>
                        @foreach ($this->selectedTemplate->groups as $g)
                            <span class="px-2.5 py-1 rounded-md bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300 font-medium">
                                {{ $g->name }} ({{ $g->steps->count() }} step{{ $g->steps->count() > 1 ? 's' : '' }})
                            </span>
                            <span class="text-gray-400">&rarr;</span>
                        @endforeach
                        <span class="px-2.5 py-1 rounded-md bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300 font-medium">
                            Approved &amp; Executed
                        </span>
                    </div>
                </div>
            @endif

            <div class="flex items-center justify-between pt-4 border-t border-gray-100 dark:border-gray-800">
                <button
                    type="button"
                    wire:click="previousStep"
                    class="rounded-lg border border-gray-300 px-4 py-2 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 cursor-pointer"
                >
                    &larr; Back
                </button>
                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        wire:click="saveDraft"
                        class="rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-xs font-semibold text-gray-700 shadow-theme-xs hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 cursor-pointer"
                    >
                        Save as Draft
                    </button>
                    <button
                        type="button"
                        wire:click="submit"
                        class="inline-flex items-center justify-center rounded-lg bg-emerald-600 px-5 py-2.5 text-xs font-semibold text-white shadow-theme-xs hover:bg-emerald-700 cursor-pointer"
                    >
                        ✓ Submit for Approval
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
