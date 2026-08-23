<?php

declare(strict_types=1);

namespace App\Actions\Settings;

use App\Mail\TestEmail;
use App\Services\SettingService;
use Illuminate\Support\Facades\Mail;

final class SendTestEmailAction
{
    public function __construct(
        private readonly SettingService $settingService
    ) {}

    /**
     * Dispatch live test email and return status message.
     */
    public function execute(string $recipientEmail): string
    {
        $this->settingService->applyRuntimeMailConfig();

        $activeDriver = (string) config('mail.default');
        $details = match ($activeDriver) {
            'oauth' => (string) $this->settingService->get('mail_oauth_provider', null, 'microsoft365') === 'microsoft365'
                ? 'OAuth2 (Microsoft 365 Exchange Graph API Tenant: '.($this->settingService->get('mail_oauth_tenant_id', null, 'common') ?: 'common').')'
                : ((string) $this->settingService->get('mail_oauth_provider', null, '') === 'google'
                    ? 'OAuth2 (Google Workspace XOAUTH2)'
                    : 'OAuth2 (Generic XOAUTH2 SMTP)'),
            'smtp' => 'SMTP ('.config('mail.mailers.smtp.host').':'.config('mail.mailers.smtp.port').' '.strtoupper((string) (config('mail.mailers.smtp.encryption') ?? 'plain')).')',
            default => strtoupper($activeDriver),
        };

        Mail::to($recipientEmail)->send(new TestEmail(
            'Scaffolding App — Mailer Connection Test',
            "This is a live test email sent using the {$details} transport driver.",
        ));

        return __('Test email successfully dispatched to :email using the :details transport driver.', [
            'email' => $recipientEmail,
            'details' => $details,
        ]);
    }
}
