<?php

declare(strict_types=1);

namespace App\Services;

use App\Mail\TestEmail;
use App\Models\EmailTemplate;
use Illuminate\Support\Facades\Mail;

class EmailTemplateService
{
    public function __construct(
        private readonly SettingService $settingService
    ) {}

    /**
     * Render an email template with variable substitutions.
     *
     * @param  array<string, mixed>  $variables
     * @return array{subject: string, html: string, text: ?string}
     */
    public function render(string $event, array $variables = [], ?int $companyId = null): array
    {
        $template = $this->resolveTemplate($event, $companyId);

        $mergedVariables = array_merge([
            'app_name' => (string) $this->settingService->get('app_name', $companyId, config('app.name', 'Scaffolding App')),
            'app_url' => config('app.url'),
            'current_year' => date('Y'),
        ], $variables);

        if ($template) {
            $subject = $this->interpolate($template->subject, $mergedVariables);
            $html = $this->interpolate($template->body_html, $mergedVariables);
            $text = $template->body_text ? $this->interpolate($template->body_text, $mergedVariables) : null;

            return [
                'subject' => $subject,
                'html' => $html,
                'text' => $text,
            ];
        }

        // Fallback default rendering if no template exists in database
        return $this->renderFallback($event, $mergedVariables);
    }

    /**
     * Send a test email for a specific template.
     */
    public function sendTest(string $event, string $recipientEmail, ?int $companyId = null): void
    {
        $this->settingService->applyRuntimeMailConfig();

        $dummyData = [
            'user_name' => 'Jane Doe',
            'user_email' => $recipientEmail,
            'company_name' => 'Acme Corporation',
            'request_title' => 'Sample Equipment Purchase #1024',
            'approval_link' => url('/approvals/inbox'),
            'action_url' => url('/dashboard'),
            'action_text' => 'View Details',
            'message' => 'This is a test notification dispatched from the System Configuration Email Template manager.',
        ];

        $rendered = $this->render($event, $dummyData, $companyId);

        Mail::to($recipientEmail)->send(new TestEmail(
            '[Test Email] '.$rendered['subject'],
            $rendered['html'],
        ));
    }

    /**
     * Resolve template for company or fallback to global.
     */
    public function resolveTemplate(string $event, ?int $companyId = null): ?EmailTemplate
    {
        if ($companyId !== null) {
            $companyTemplate = EmailTemplate::active()
                ->where('company_id', $companyId)
                ->where('event', $event)
                ->first();

            if ($companyTemplate) {
                return $companyTemplate;
            }
        }

        return EmailTemplate::active()
            ->global()
            ->where('event', $event)
            ->first();
    }

    /**
     * Interpolate placeholder variables in string template.
     *
     * @param  array<string, mixed>  $variables
     */
    public function interpolate(string $content, array $variables): string
    {
        return (string) preg_replace_callback('/\{\{\s*([a-zA-Z0-9_\.]+)\s*\}\}/', function (array $matches) use ($variables): string {
            $key = $matches[1];

            return isset($variables[$key]) ? (string) $variables[$key] : $matches[0];
        }, $content);
    }

    /**
     * Render hardcoded fallback when template is missing.
     *
     * @param  array<string, mixed>  $variables
     * @return array{subject: string, html: string, text: ?string}
     */
    private function renderFallback(string $event, array $variables): array
    {
        $appName = (string) ($variables['app_name'] ?? 'Scaffolding App');
        $subject = "[{$appName}] Notification: ".ucwords(str_replace('_', ' ', $event));

        $html = '<div style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">'
            .'<h2 style="color: #4f46e5;">'.htmlspecialchars($appName).'</h2>'
            .'<p>'.htmlspecialchars((string) ($variables['message'] ?? 'You have a new notification.')).'</p>'
            .(isset($variables['action_url']) ? '<p><a href="'.htmlspecialchars((string) $variables['action_url']).'" style="background: #4f46e5; color: #fff; padding: 10px 20px; text-decoration: none; border-radius: 6px; display: inline-block;">'.htmlspecialchars((string) ($variables['action_text'] ?? 'View Details')).'</a></p>' : '')
            .'<hr style="border: none; border-top: 1px solid #e5e7eb; margin: 20px 0;" />'
            .'<p style="font-size: 12px; color: #6b7280;">&copy; '.date('Y').' '.htmlspecialchars($appName).'. All rights reserved.</p>'
            .'</div>';

        return [
            'subject' => $subject,
            'html' => $html,
            'text' => strip_tags($html),
        ];
    }
}
