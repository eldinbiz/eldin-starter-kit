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

        $query = EmailTemplate::query();

        if ($selectedCompanyId !== null) {
            $query->where(function ($q) use ($selectedCompanyId): void {
                $q->where('company_id', $selectedCompanyId)->orWhereNull('company_id');
            });
        } else {
            $query->whereNull('company_id');
        }

        $templates = $query->orderBy('event')->get();

        return view('admin.settings.email-templates.index', [
            'templates' => $templates,
            'companies' => $companies,
            'selectedCompanyId' => $selectedCompanyId,
        ]);
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
            'subject' => ['required', 'string', 'max:255'],
            'body_html' => ['required', 'string'],
            'body_text' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $emailTemplate->update([
            'subject' => $validated['subject'],
            'body_html' => $validated['body_html'],
            'body_text' => $validated['body_text'] ?? null,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('admin.settings.email-templates.index', ['company_id' => $emailTemplate->company_id])
            ->with('success', __("Email template ':name' updated successfully.", ['name' => $emailTemplate->name]));
    }

    /**
     * Live preview of rendered template.
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
