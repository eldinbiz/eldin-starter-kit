<?php

declare(strict_types=1);

namespace App\Actions\EmailTemplates;

use App\Models\EmailTemplate;

final class DeleteEmailTemplateAction
{
    /**
     * Delete an email template or remove a company override.
     *
     * @return array{
     *     company_id: int|null,
     *     is_override: bool,
     *     name: string
     * }
     */
    public function execute(EmailTemplate $emailTemplate): array
    {
        $companyId = $emailTemplate->company_id;
        $isOverride = $companyId !== null;
        $name = $emailTemplate->name;

        $emailTemplate->delete();

        return [
            'company_id' => $companyId,
            'is_override' => $isOverride,
            'name' => $name,
        ];
    }
}
