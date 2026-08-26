<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Settings\EmailTemplates;

use App\Actions\EmailTemplates\CreateEmailTemplateAction;
use App\Actions\EmailTemplates\RenderDraftEmailTemplateAction;
use App\Actions\EmailTemplates\SendTestEmailTemplateAction;
use App\Actions\EmailTemplates\UpdateEmailTemplateAction;
use App\Models\Company;
use App\Models\EmailTemplate;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Throwable;

final class TemplateEditor extends Component
{
    public ?int $templateId = null;

    public ?int $company_id = null;

    #[Validate(['required', 'string', 'max:100', 'regex:/^[a-zA-Z0-9_\-\.]+$/'])]
    public string $event = '';

    #[Validate(['required', 'string', 'max:255'])]
    public string $name = '';

    #[Validate(['required', 'string', 'max:255'])]
    public string $subject = '';

    #[Validate(['required', 'string'])]
    public string $body_html = '';

    public ?string $body_text = null;

    public array $variables = [];

    public bool $is_active = true;

    public string $newVariable = '';

    public bool $previewModalOpen = false;

    public string $previewSubject = '';

    public string $previewHtml = '';

    public bool $testModalOpen = false;

    public string $testEmail = '';

    public ?string $testStatus = null;

    public ?string $testMessage = null;

    public ?string $flashSuccess = null;

    public ?string $flashError = null;

    public function mount(?int $templateId = null, ?string $targetEvent = null, ?int $targetCompanyId = null): void
    {
        $defaultVariables = ['user_name', 'user_email', 'company_name', 'title', 'approval_link', 'action_url', 'action_text', 'message', 'app_name', 'current_year'];

        if ($templateId) {
            $template = EmailTemplate::findOrFail($templateId);
            $this->templateId = $template->id;
            $this->company_id = $template->company_id;
            $this->event = $template->event;
            $this->name = $template->name;
            $this->subject = $template->subject;
            $this->body_html = $template->body_html;
            $this->body_text = $template->body_text;
            $this->variables = array_values(array_unique(array_merge($defaultVariables, $template->variables ?? [])));
            $this->is_active = (bool) $template->is_active;
        } else {
            $this->company_id = $targetCompanyId;
            $this->event = $targetEvent ?? '';
            $this->variables = $defaultVariables;

            if ($targetEvent && $targetCompanyId) {
                $globalTemplate = EmailTemplate::global()->where('event', $targetEvent)->first();
                $targetCompany = Company::find($targetCompanyId);
                if ($globalTemplate && $targetCompany) {
                    $this->name = "{$globalTemplate->name} ({$targetCompany->name} Override)";
                    $this->subject = $globalTemplate->subject;
                    $this->body_html = $globalTemplate->body_html;
                    $this->body_text = $globalTemplate->body_text;
                    $this->variables = array_values(array_unique(array_merge($defaultVariables, $globalTemplate->variables ?? [])));
                }
            }
        }

        if (auth()->check()) {
            $this->testEmail = (string) auth()->user()->email;
        }
    }

    public function addVariable(): void
    {
        $var = trim($this->newVariable);
        if ($var !== '' && ! in_array($var, $this->variables, true)) {
            $this->variables[] = $var;
            $this->newVariable = '';
        }
    }

    public function removeVariable(string $var): void
    {
        $this->variables = array_values(array_filter($this->variables, fn ($v) => $v !== $var));
    }

    public function openPreview(RenderDraftEmailTemplateAction $action): void
    {
        $rendered = $action->execute([
            'subject' => $this->subject,
            'body_html' => $this->body_html,
            'company_id' => $this->company_id,
        ]);

        $this->previewSubject = $rendered['subject'];
        $this->previewHtml = $rendered['html'];
        $this->previewModalOpen = true;
    }

    public function closePreview(): void
    {
        $this->previewModalOpen = false;
    }

    public function openTestModal(): void
    {
        $this->testStatus = null;
        $this->testMessage = null;
        $this->testModalOpen = true;
    }

    public function closeTestModal(): void
    {
        $this->testModalOpen = false;
    }

    public function sendTestEmail(SendTestEmailTemplateAction $action): void
    {
        $this->authorize('manage-system-settings');

        if (empty($this->testEmail)) {
            $this->testStatus = 'error';
            $this->testMessage = 'Please enter a valid recipient email address.';

            return;
        }

        if (! $this->templateId) {
            $this->testStatus = 'error';
            $this->testMessage = 'Please save the template first before sending a test email.';

            return;
        }

        $template = EmailTemplate::find($this->templateId);
        if (! $template) {
            return;
        }

        try {
            $action->execute($template, $this->testEmail);
            $this->testStatus = 'success';
            $this->testMessage = "Test email successfully dispatched to {$this->testEmail}.";
        } catch (Throwable $e) {
            $this->testStatus = 'error';
            $this->testMessage = 'Failed to dispatch test email: '.$e->getMessage();
        }
    }

    public function save(CreateEmailTemplateAction $createAction, UpdateEmailTemplateAction $updateAction): void
    {
        $this->authorize('manage-system-settings');
        $this->validate();

        $data = [
            'company_id' => $this->company_id,
            'event' => $this->event,
            'name' => $this->name,
            'subject' => $this->subject,
            'body_html' => $this->body_html,
            'body_text' => $this->body_text,
            'variables' => $this->variables,
            'is_active' => $this->is_active,
        ];

        if ($this->templateId) {
            $template = EmailTemplate::findOrFail($this->templateId);
            $updateAction->execute($template, $data);
            $this->flashSuccess = 'Email template updated successfully.';
        } else {
            $created = $createAction->execute($data);
            $this->templateId = $created->id;
            $this->flashSuccess = 'Email template created successfully.';
        }

        $this->redirect(route('admin.settings.email-templates.index'), navigate: true);
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

    public function render(): View
    {
        return view('livewire.admin.settings.email-templates.template-editor');
    }
}
