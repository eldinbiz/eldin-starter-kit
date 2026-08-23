<?php

declare(strict_types=1);

namespace App\Actions\Settings;

use App\Services\SettingService;

final class UpdateMailSettingsAction
{
    public function __construct(
        private readonly SettingService $settingService
    ) {}

    /**
     * Update mail and transport settings.
     *
     * @param  array<string, mixed>  $data
     */
    public function execute(array $data): void
    {
        $this->settingService->set('mail_mailer', (string) $data['mail_mailer'], 'text', 'mail');

        if (array_key_exists('mail_host', $data)) {
            $this->settingService->set('mail_host', (string) ($data['mail_host'] ?? ''), 'text', 'mail');
        }

        if (array_key_exists('mail_port', $data)) {
            $this->settingService->set('mail_port', (string) ($data['mail_port'] ?? '587'), 'numeric', 'mail');
        }

        if (array_key_exists('mail_username', $data)) {
            $this->settingService->set('mail_username', (string) ($data['mail_username'] ?? ''), 'text', 'mail');
        }

        if (! empty($data['mail_password'])) {
            $this->settingService->set('mail_password', (string) $data['mail_password'], 'encrypted', 'mail');
        }

        if (array_key_exists('mail_encryption', $data)) {
            $this->settingService->set('mail_encryption', (string) ($data['mail_encryption'] ?? 'tls'), 'text', 'mail');
        }

        $this->settingService->set('mail_from_address', (string) $data['mail_from_address'], 'text', 'mail');
        $this->settingService->set('mail_from_name', (string) $data['mail_from_name'], 'text', 'mail');

        if (array_key_exists('mail_oauth_provider', $data)) {
            $this->settingService->set('mail_oauth_provider', (string) ($data['mail_oauth_provider'] ?? 'microsoft365'), 'text', 'mail');
        }

        if (array_key_exists('mail_oauth_tenant_id', $data)) {
            $this->settingService->set('mail_oauth_tenant_id', (string) ($data['mail_oauth_tenant_id'] ?? ''), 'text', 'mail');
        }

        if (array_key_exists('mail_oauth_client_id', $data)) {
            $this->settingService->set('mail_oauth_client_id', (string) ($data['mail_oauth_client_id'] ?? ''), 'text', 'mail');
        }

        if (array_key_exists('mail_oauth_token_url', $data)) {
            $this->settingService->set('mail_oauth_token_url', (string) ($data['mail_oauth_token_url'] ?? ''), 'text', 'mail');
        }

        if (! empty($data['mail_oauth_client_secret'])) {
            $this->settingService->set('mail_oauth_client_secret', (string) $data['mail_oauth_client_secret'], 'encrypted', 'mail');
        }

        if (! empty($data['mail_oauth_refresh_token'])) {
            $this->settingService->set('mail_oauth_refresh_token', (string) $data['mail_oauth_refresh_token'], 'encrypted', 'mail');
        }
    }
}
