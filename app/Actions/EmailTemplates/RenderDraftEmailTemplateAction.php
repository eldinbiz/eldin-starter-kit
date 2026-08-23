<?php

declare(strict_types=1);

namespace App\Actions\EmailTemplates;

use App\Models\Company;
use App\Services\EmailTemplateService;

final class RenderDraftEmailTemplateAction
{
    public function __construct(
        private readonly EmailTemplateService $templateService
    ) {}

    /**
     * Render live preview for draft email template content.
     *
     * @param array{
     *     subject: string,
     *     body_html: string,
     *     company_id?: int|string|null
     * } $data
     * @return array{
     *     subject: string,
     *     html: string
     * }
     */
    public function execute(array $data): array
    {
        $companyId = ! empty($data['company_id']) ? (int) $data['company_id'] : null;
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
            'app_url' => (string) config('app.url', 'http://localhost'),
            'current_year' => date('Y'),
        ];

        return [
            'subject' => $this->templateService->interpolate((string) $data['subject'], $dummyData),
            'html' => $this->templateService->interpolate((string) $data['body_html'], $dummyData),
        ];
    }
}
