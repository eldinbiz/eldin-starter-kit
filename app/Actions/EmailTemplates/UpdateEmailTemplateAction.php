<?php

declare(strict_types=1);

namespace App\Actions\EmailTemplates;

use App\Models\EmailTemplate;

final class UpdateEmailTemplateAction
{
    /**
     * Update an email template or company override.
     *
     * @param array{
     *     name?: string|null,
     *     subject: string,
     *     body_html: string,
     *     body_text?: string|null,
     *     is_active?: bool|int|string|null,
     *     variables?: array<int, string>|null
     * } $data
     */
    public function execute(EmailTemplate $emailTemplate, array $data): EmailTemplate
    {
        $emailTemplate->update([
            'name' => $data['name'] ?? $emailTemplate->name,
            'subject' => (string) $data['subject'],
            'body_html' => (string) $data['body_html'],
            'body_text' => isset($data['body_text']) ? (string) $data['body_text'] : null,
            'is_active' => isset($data['is_active']) ? (bool) $data['is_active'] : true,
            'variables' => $data['variables'] ?? $emailTemplate->variables,
        ]);

        return $emailTemplate;
    }
}
