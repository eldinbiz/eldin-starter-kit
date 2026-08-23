<?php

declare(strict_types=1);

namespace App\Actions\EmailTemplates;

use App\Models\EmailTemplate;
use Illuminate\Validation\ValidationException;

final class CreateEmailTemplateAction
{
    /**
     * Create a new email template or company override.
     *
     * @param array{
     *     company_id?: int|string|null,
     *     event: string,
     *     name: string,
     *     subject: string,
     *     body_html: string,
     *     body_text?: string|null,
     *     is_active?: bool|int|string|null,
     *     variables?: array<int, string>|null
     * } $data
     *
     * @throws ValidationException
     */
    public function execute(array $data): EmailTemplate
    {
        $companyId = ! empty($data['company_id']) ? (int) $data['company_id'] : null;
        $event = (string) $data['event'];

        $exists = EmailTemplate::where('company_id', $companyId)->where('event', $event)->exists();
        if ($exists) {
            $message = $companyId !== null
                ? __('A template override for event ":event" already exists for this company.', ['event' => $event])
                : __('A global template for event ":event" already exists.', ['event' => $event]);

            throw ValidationException::withMessages(['event' => $message]);
        }

        $defaultVariables = ['user_name', 'user_email', 'company_name', 'title', 'approval_link', 'action_url', 'action_text', 'message', 'app_name', 'current_year'];

        return EmailTemplate::create([
            'company_id' => $companyId,
            'event' => $event,
            'name' => (string) $data['name'],
            'subject' => (string) $data['subject'],
            'body_html' => (string) $data['body_html'],
            'body_text' => isset($data['body_text']) ? (string) $data['body_text'] : null,
            'variables' => $data['variables'] ?? $defaultVariables,
            'is_active' => isset($data['is_active']) ? (bool) $data['is_active'] : true,
        ]);
    }
}
