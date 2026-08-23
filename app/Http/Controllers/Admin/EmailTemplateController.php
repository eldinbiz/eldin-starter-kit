<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\EmailTemplates\CreateEmailTemplateAction;
use App\Actions\EmailTemplates\DeleteEmailTemplateAction;
use App\Actions\EmailTemplates\RenderDraftEmailTemplateAction;
use App\Actions\EmailTemplates\SendTestEmailTemplateAction;
use App\Actions\EmailTemplates\UpdateEmailTemplateAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\EmailTemplates\PreviewDraftEmailTemplateRequest;
use App\Http\Requests\Admin\EmailTemplates\SendTestEmailTemplateRequest;
use App\Http\Requests\Admin\EmailTemplates\StoreEmailTemplateRequest;
use App\Http\Requests\Admin\EmailTemplates\UpdateEmailTemplateRequest;
use App\Models\Company;
use App\Models\EmailTemplate;
use App\Services\EmailTemplateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

class EmailTemplateController extends Controller
{
    public function __construct(
        private readonly EmailTemplateService $templateService
    ) {}

    /**
     * Display list of email templates.
     */
    public function index(Request $request): View
    {
        $companies = Company::orderBy('name')->get();
        $selectedCompanyId = $request->has('company_id') && $request->query('company_id') !== ''
            ? (int) $request->query('company_id')
            : null;
        $search = $request->has('search') && $request->query('search') !== ''
            ? trim((string) $request->query('search'))
            : null;

        $query = EmailTemplate::query();

        if ($selectedCompanyId !== null) {
            $query->where(function ($q) use ($selectedCompanyId): void {
                $q->where('company_id', $selectedCompanyId)->orWhereNull('company_id');
            });
        } elseif ($search === null) {
            $query->whereNull('company_id');
        }

        if ($search !== null) {
            $query->where(function ($q) use ($search): void {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('event', 'like', "%{$search}%")
                    ->orWhere('subject', 'like', "%{$search}%");
            });
        }

        $templates = $query->with('company')->orderBy('event')->get();

        // If a specific company is selected, collect existing override event keys for quick action buttons
        $companyOverrideEvents = $selectedCompanyId !== null
            ? EmailTemplate::where('company_id', $selectedCompanyId)->pluck('event')->toArray()
            : [];

        return view('admin.settings.email-templates.index', [
            'templates' => $templates,
            'companies' => $companies,
            'selectedCompanyId' => $selectedCompanyId,
            'search' => $search,
            'companyOverrideEvents' => $companyOverrideEvents,
        ]);
    }

    /**
     * Show form to create a new template or company override.
     */
    public function create(Request $request): View
    {
        $companies = Company::orderBy('name')->get();
        $targetEvent = $request->query('event');
        $targetCompanyId = $request->filled('company_id') ? (int) $request->query('company_id') : null;

        $template = new EmailTemplate([
            'is_active' => true,
            'company_id' => $targetCompanyId,
            'event' => $targetEvent ?? '',
            'variables' => ['user_name', 'user_email', 'company_name', 'title', 'approval_link', 'action_url', 'action_text', 'message', 'app_name', 'current_year'],
        ]);

        // If target event & company provided, clone from global template
        if ($targetEvent && $targetCompanyId) {
            $globalTemplate = EmailTemplate::global()->where('event', $targetEvent)->first();
            $targetCompany = Company::find($targetCompanyId);
            if ($globalTemplate && $targetCompany) {
                $template->name = "{$globalTemplate->name} ({$targetCompany->name} Override)";
                $template->subject = $globalTemplate->subject;
                $template->body_html = $globalTemplate->body_html;
                $template->body_text = $globalTemplate->body_text;
                $template->variables = $globalTemplate->variables ?? $template->variables;
            }
        }

        return view('admin.settings.email-templates.create', [
            'template' => $template,
            'companies' => $companies,
            'selectedCompanyId' => $targetCompanyId,
        ]);
    }

    /**
     * Store a newly created template or company override.
     */
    public function store(StoreEmailTemplateRequest $request, CreateEmailTemplateAction $action): RedirectResponse
    {
        $template = $action->execute($request->validated());

        $successMsg = $template->company_id !== null
            ? __("Company override for ':name' created successfully.", ['name' => $template->name])
            : __("Email template ':name' created successfully.", ['name' => $template->name]);

        return redirect()->route('admin.settings.email-templates.index', ['company_id' => $template->company_id])
            ->with('success', $successMsg);
    }

    /**
     * Show editor form for a template.
     */
    public function edit(EmailTemplate $emailTemplate): View
    {
        return view('admin.settings.email-templates.edit', [
            'template' => $emailTemplate,
        ]);
    }

    /**
     * Update an email template.
     */
    public function update(
        UpdateEmailTemplateRequest $request,
        EmailTemplate $emailTemplate,
        UpdateEmailTemplateAction $action
    ): RedirectResponse {
        $template = $action->execute($emailTemplate, $request->validated());

        return redirect()->route('admin.settings.email-templates.index', ['company_id' => $template->company_id])
            ->with('success', __("Email template ':name' updated successfully.", ['name' => $template->name]));
    }

    /**
     * Delete custom template or reset company override to system default.
     */
    public function destroy(EmailTemplate $emailTemplate, DeleteEmailTemplateAction $action): RedirectResponse
    {
        $result = $action->execute($emailTemplate);

        $message = $result['is_override']
            ? __("Company override ':name' has been reset to the system default.", ['name' => $result['name']])
            : __("Email template ':name' deleted successfully.", ['name' => $result['name']]);

        return redirect()->route('admin.settings.email-templates.index', ['company_id' => $result['company_id']])
            ->with('success', $message);
    }

    /**
     * Live preview of rendered unsaved draft template.
     */
    public function previewDraft(
        PreviewDraftEmailTemplateRequest $request,
        RenderDraftEmailTemplateAction $action
    ): JsonResponse {
        $rendered = $action->execute($request->validated());

        return response()->json($rendered);
    }

    /**
     * Live preview of rendered saved template.
     */
    public function preview(Request $request, EmailTemplate $emailTemplate): JsonResponse
    {
        $dummyData = [
            'user_name' => 'Jane Doe',
            'user_email' => 'jane.doe@example.com',
            'company_name' => $emailTemplate->company?->name ?? 'Acme Corporation',
            'title' => 'Purchase Request PR-2026-0042',
            'request_title' => 'Office Workstation Hardware Upgrade',
            'approval_link' => url('/approvals/inbox'),
            'action_url' => url('/dashboard'),
            'action_text' => 'Review Request',
            'message' => 'A new request has been submitted and requires your approval.',
            'app_name' => config('app.name', 'Scaffolding App'),
            'app_url' => config('app.url', 'http://localhost'),
            'current_year' => date('Y'),
        ];

        $rendered = $this->templateService->render(
            $emailTemplate->event,
            $dummyData,
            $emailTemplate->company_id
        );

        return response()->json([
            'subject' => $rendered['subject'],
            'html' => $rendered['html'],
        ]);
    }

    /**
     * Send test email for this template.
     */
    public function test(
        SendTestEmailTemplateRequest $request,
        EmailTemplate $emailTemplate,
        SendTestEmailTemplateAction $action
    ): JsonResponse|RedirectResponse {
        $recipientEmail = (string) $request->validated('recipient_email');

        try {
            $action->execute($emailTemplate, $recipientEmail);

            $message = __("Test email for ':name' dispatched to :email", [
                'name' => $emailTemplate->name,
                'email' => $recipientEmail,
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                ]);
            }

            return redirect()->back()->with('success', $message);
        } catch (Throwable $e) {
            $errorMessage = __('Failed to send test email: ').$e->getMessage();

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $errorMessage,
                ], 422);
            }

            return redirect()->back()->with('error', $errorMessage);
        }
    }
}
