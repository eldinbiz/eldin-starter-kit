<?php

declare(strict_types=1);

namespace App\Actions\EmailTemplates;

use App\Models\EmailTemplate;
use App\Services\EmailTemplateService;

final class SendTestEmailTemplateAction
{
    public function __construct(
        private readonly EmailTemplateService $templateService
    ) {}

    /**
     * Dispatch a test email for the given template and recipient.
     */
    public function execute(EmailTemplate $emailTemplate, string $recipientEmail): void
    {
        $this->templateService->sendTest(
            $emailTemplate->event,
            $recipientEmail,
            $emailTemplate->company_id
        );
    }
}
