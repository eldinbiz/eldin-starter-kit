<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Settings;

use App\Actions\Settings\CheckCustomVariableKeyAction;
use App\Actions\Settings\DeleteCustomVariableAction;
use App\Actions\Settings\SaveCustomVariableAction;
use App\Models\Company;
use App\Models\SystemSetting;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;

final class CustomVariables extends Component
{
    #[Url(as: 'company_id')]
    public ?int $selectedCompanyId = null;

    #[Url(as: 'q')]
    public string $search = '';

    public bool $modalOpen = false;

    public ?int $editingId = null;

    public string $formKey = '';

    public string $formType = 'text';

    public mixed $formValue = '';

    public ?string $formDescription = null;

    public ?int $formCompanyId = null;

    public bool $keyExistsWarning = false;

    public ?int $variableToDeleteId = null;

    public ?string $variableToDeleteKey = null;

    public ?string $flashSuccess = null;

    public ?string $flashError = null;

    public function openCreateModal(): void
    {
        $this->editingId = null;
        $this->formKey = '';
        $this->formType = 'text';
        $this->formValue = '';
        $this->formDescription = null;
        $this->formCompanyId = $this->selectedCompanyId;
        $this->keyExistsWarning = false;
        $this->modalOpen = true;
    }

    public function openEditModal(int $id): void
    {
        $variable = SystemSetting::customVariables()->find($id);
        if (! $variable) {
            return;
        }

        $this->editingId = $variable->id;
        $this->formKey = $variable->key;
        $this->formType = $variable->type ?? 'text';
        $this->formValue = is_array($variable->typed_value) ? json_encode($variable->typed_value, JSON_PRETTY_PRINT) : (string) $variable->typed_value;
        $this->formDescription = $variable->description;
        $this->formCompanyId = $variable->company_id;
        $this->keyExistsWarning = false;
        $this->modalOpen = true;
    }

    public function closeModal(): void
    {
        $this->modalOpen = false;
        $this->editingId = null;
        $this->formKey = '';
        $this->formValue = '';
        $this->formDescription = null;
        $this->keyExistsWarning = false;
    }

    public function updatedFormKey(CheckCustomVariableKeyAction $action): void
    {
        if ($this->editingId === null && $this->formKey !== '') {
            $result = $action->execute($this->formKey, $this->formCompanyId);
            $this->keyExistsWarning = (bool) ($result['exists'] ?? false);
        }
    }

    public function save(SaveCustomVariableAction $action): void
    {
        $this->authorize('manage-system-settings');

        $this->validate([
            'formKey' => ['required', 'string', 'max:100', 'regex:/^[a-zA-Z0-9_\-\.]+$/'],
            'formType' => ['required', 'in:text,numeric,json,boolean'],
            'formDescription' => ['nullable', 'string', 'max:255'],
        ]);

        $val = $this->formValue;
        if ($this->formType === 'json' && is_string($val)) {
            $decoded = json_decode($val, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->addError('formValue', 'Invalid JSON syntax: '.json_last_error_msg());

                return;
            }
            $val = $decoded;
        } elseif ($this->formType === 'numeric') {
            if (! is_numeric($val)) {
                $this->addError('formValue', 'The value must be a valid number.');

                return;
            }
            $val = str_contains((string) $val, '.') ? (float) $val : (int) $val;
        }

        $wasOverwrite = $action->execute([
            'key' => $this->formKey,
            'type' => $this->formType,
            'value' => $val,
            'company_id' => $this->formCompanyId,
            'description' => $this->formDescription,
        ]);

        $this->closeModal();
        $this->flashSuccess = $wasOverwrite
            ? "Custom variable '{$this->formKey}' was updated."
            : "Custom variable '{$this->formKey}' created successfully.";
    }

    public function promptDelete(int $id): void
    {
        $var = SystemSetting::customVariables()->find($id);
        if ($var) {
            $this->variableToDeleteId = $var->id;
            $this->variableToDeleteKey = $var->key;
        }
    }

    public function cancelDelete(): void
    {
        $this->variableToDeleteId = null;
        $this->variableToDeleteKey = null;
    }

    public function deleteVariable(DeleteCustomVariableAction $action): void
    {
        $this->authorize('manage-system-settings');

        if ($this->variableToDeleteId) {
            $key = $this->variableToDeleteKey;
            $action->execute($this->variableToDeleteId);
            $this->cancelDelete();
            $this->flashSuccess = "Custom variable '{$key}' was deleted.";
        }
    }

    public function dismissFlash(): void
    {
        $this->flashSuccess = null;
        $this->flashError = null;
    }

    #[Computed]
    public function companies(): Collection
    {
        return Company::where('is_active', true)->orderBy('name')->get();
    }

    #[Computed]
    public function variables(): Collection
    {
        $query = SystemSetting::customVariables()->with('company');

        if ($this->selectedCompanyId) {
            $query->where('company_id', $this->selectedCompanyId);
        } else {
            $query->whereNull('company_id');
        }

        if ($this->search !== '') {
            $term = $this->search;
            $query->where(function (Builder $sub) use ($term): void {
                $sub->where('key', 'like', "%{$term}%")
                    ->orWhere('description', 'like', "%{$term}%")
                    ->orWhere('value', 'like', "%{$term}%");
            });
        }

        return $query->orderBy('key')->get();
    }

    public function render(): View
    {
        return view('livewire.admin.settings.custom-variables');
    }
}
