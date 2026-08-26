<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Settings\EmailTemplates;

use App\Actions\EmailTemplates\DeleteEmailTemplateAction;
use App\Models\Company;
use App\Models\EmailTemplate;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;

final class TemplateList extends Component
{
    #[Url(as: 'company_id')]
    public ?int $selectedCompanyId = null;

    #[Url(as: 'search')]
    public string $search = '';

    public ?int $templateToDeleteId = null;

    public ?string $templateToDeleteName = null;

    public ?string $flashSuccess = null;

    public ?string $flashError = null;

    public function mount(?int $selectedCompanyId = null, string $search = ''): void
    {
        if ($selectedCompanyId !== null) {
            $this->selectedCompanyId = $selectedCompanyId;
        } elseif (request()->filled('company_id')) {
            $this->selectedCompanyId = (int) request()->query('company_id');
        }

        if ($search !== '') {
            $this->search = $search;
        } elseif (request()->filled('search')) {
            $this->search = trim((string) request()->query('search'));
        } elseif (request()->filled('q')) {
            $this->search = trim((string) request()->query('q'));
        }
    }

    public function promptDelete(int $id): void
    {
        $template = EmailTemplate::find($id);
        if ($template) {
            $this->templateToDeleteId = $template->id;
            $this->templateToDeleteName = $template->name;
        }
    }

    public function cancelDelete(): void
    {
        $this->templateToDeleteId = null;
        $this->templateToDeleteName = null;
    }

    public function deleteTemplate(DeleteEmailTemplateAction $action): void
    {
        $this->authorize('manage-system-settings');

        if ($this->templateToDeleteId) {
            $name = $this->templateToDeleteName;
            $template = EmailTemplate::findOrFail($this->templateToDeleteId);
            $action->execute($template);
            $this->cancelDelete();
            $this->flashSuccess = "Email template '{$name}' was deleted successfully.";
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
    public function templates(): Collection
    {
        $query = EmailTemplate::query();

        if ($this->selectedCompanyId !== null) {
            $query->where(function (Builder $q): void {
                $q->where('company_id', $this->selectedCompanyId)->orWhereNull('company_id');
            });
        } elseif ($this->search === '') {
            $query->whereNull('company_id');
        }

        if ($this->search !== '') {
            $term = $this->search;
            $query->where(function (Builder $q) use ($term): void {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhere('event', 'like', "%{$term}%")
                    ->orWhere('subject', 'like', "%{$term}%");
            });
        }

        return $query->with('company')->orderBy('event')->get();
    }

    #[Computed]
    public function companyOverrideEvents(): array
    {
        if ($this->selectedCompanyId === null) {
            return [];
        }

        return EmailTemplate::where('company_id', $this->selectedCompanyId)->pluck('event')->toArray();
    }

    public function render(): View
    {
        return view('livewire.admin.settings.email-templates.template-list');
    }
}
