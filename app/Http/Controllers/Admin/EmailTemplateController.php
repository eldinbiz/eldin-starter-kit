<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
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
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'company_id' => ['nullable', 'integer', 'exists:companies,id'],
            'event' => ['required', 'string', 'max:100', 'regex:/^[a-z0-9_\-]+$/'],
            'name' => ['required', 'string', 'max:255'],
            'subject' => ['required', 'string', 'max:255'],
            'body_html' => ['required', 'string'],
            'body_text' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $companyId = $request->filled('company_id') ? (int) $validated['company_id'] : null;
        $event = $validated['event'];

        // Prevent duplicate (company_id, event)
        $exists = EmailTemplate::where('company_id', $companyId)->where('event', $event)->exists();
        if ($exists) {
            $message = $companyId
                ? __('A template override for event ":event" already exists for this company.', ['event' => $event])
                : __('A global template for event ":event" already exists.', ['event' => $event]);

            return redirect()->back()
                ->withInput()
                ->withErrors(['event' => $message]);
        }

        $defaultVariables = ['user_name', 'user_email', 'company_name', 'title', 'approval_link', 'action_url', 'action_text', 'message', 'app_name', 'current_year'];

        $template = EmailTemplate::create([
            'company_id' => $companyId,
            'event' => $event,
            'name' => $validated['name'],
            'subject' => $validated['subject'],
            'body_html' => $validated['body_html'],
            'body_text' => $validated['body_text'] ?? null,
            'variables' => $defaultVariables,
            'is_active' => $request->boolean('is_active', true),
        ]);

        $successMsg = $companyId
            ? __("Company override for ':name' created successfully.", ['name' => $template->name])
            : __("Email template ':name' created successfully.", ['name' => $template->name]);

        return redirect()->route('admin.settings.email-templates.index', ['company_id' => $companyId])
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
    public function update(Request $request, EmailTemplate $emailTemplate): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'subject' => ['required', 'string', 'max:255'],
            'body_html' => ['required', 'string'],
            'body_text' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $emailTemplate->update([
            'name' => $validated['name'] ?? $emailTemplate->name,
            'subject' => $validated['subject'],
            'body_html' => $validated['body_html'],
            'body_text' => $validated['body_text'] ?? null,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('admin.settings.email-templates.index', ['company_id' => $emailTemplate->company_id])
            ->with('success', __("Email template ':name' updated successfully.", ['name' => $emailTemplate->name]));
    }

    /**
     * Delete custom template or reset company override to system default.
     */
    public function destroy(EmailTemplate $emailTemplate): RedirectResponse
    {
        $companyId = $emailTemplate->company_id;
        $isOverride = $companyId !== null;
        $templateName = $emailTemplate->name;

        $emailTemplate->delete();

        $message = $isOverride
            ? __("Company override ':name' has been reset to the system default.", ['name' => $templateName])
            : __("Email template ':name' deleted successfully.", ['name' => $templateName]);

        return redirect()->route('admin.settings.email-templates.index', ['company_id' => $companyId])
            ->with('success', $message);
    }

    /**
     * Live preview of rendered unsaved draft template.
     */
    public function previewDraft(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'subject' => ['required', 'string'],
            'body_html' => ['required', 'string'],
            'company_id' => ['nullable', 'integer'],
        ]);

        $companyId = ! empty($validated['company_id']) ? (int) $validated['company_id'] : null;
        $companyName = 'Acme Corporation';
        if ($companyId !== null) {
            $company = Company::find($companyId);
            if ($company) {
                $companyName = $company->name;
            }
        }

        $dummyData = [
            'user_name' => 'Jane Doe',
            'user_email' => 'jane.doe@example.com',
            'company_name' => $companyName,
            'title' => 'Purchase Request PR-2026-0042',
            'request_title' => 'Office Workstation Hardware Upgrade',
            'approval_link' => url('/approvals/inbox'),
            'action_url' => url('/dashboard'),
            'action_text' => 'Review Request',
            'message' => 'A new request has been submitted and requires your approval.',
            'app_name' => (string) config('app.name', 'Scaffolding App'),
            'app_url' => config('app.url', 'http://localhost'),
            'current_year' => date('Y'),
        ];

        $renderedSubject = $this->templateService->interpolate($validated['subject'], $dummyData);
        $renderedHtml = $this->templateService->interpolate($validated['body_html'], $dummyData);

        return response()->json([
            'subject' => $renderedSubject,
            'html' => $renderedHtml,
        ]);
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
    public function test(Request $request, EmailTemplate $emailTemplate): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'recipient_email' => ['required', 'email', 'max:255'],
        ]);

        $recipientEmail = $validated['recipient_email'];

        try {
            $this->templateService->sendTest(
                $emailTemplate->event,
                $recipientEmail,
                $emailTemplate->company_id
            );

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => __("Test email for ':name' dispatched to :email", [
                        'name' => $emailTemplate->name,
                        'email' => $recipientEmail,
                    ]),
                ]);
            }

            return redirect()->back()->with('success', __("Test email for ':name' dispatched to :email", [
                'name' => $emailTemplate->name,
                'email' => $recipientEmail,
            ]));
        } catch (Throwable $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => __('Failed to send test email: ').$e->getMessage(),
                ], 422);
            }

            return redirect()->back()->with('error', __('Failed to send test email: ').$e->getMessage());
        }
    }
}
