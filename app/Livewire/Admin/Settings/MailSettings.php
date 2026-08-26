<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Settings;

use App\Actions\Settings\SendTestEmailAction;
use App\Actions\Settings\UpdateMailSettingsAction;
use App\Services\SettingService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Throwable;

final class MailSettings extends Component
{
    #[Validate(['required', 'in:smtp,oauth,log,array'])]
    public string $mail_mailer = 'smtp';

    #[Validate(['nullable', 'string', 'max:255'])]
    public string $mail_host = '';

    #[Validate(['nullable', 'numeric', 'between:1,65535'])]
    public string $mail_port = '587';

    #[Validate(['nullable', 'string', 'max:255'])]
    public string $mail_username = '';

    public string $mail_password = '';

    #[Validate(['nullable', 'in:tls,ssl,none'])]
    public string $mail_encryption = 'tls';

    #[Validate(['required', 'email', 'max:255'])]
    public string $mail_from_address = '';

    #[Validate(['required', 'string', 'max:255'])]
    public string $mail_from_name = '';

    #[Validate(['nullable', 'in:microsoft365,google,generic'])]
    public string $mail_oauth_provider = 'microsoft365';

    #[Validate(['nullable', 'string', 'max:255'])]
    public string $mail_oauth_tenant_id = '';

    #[Validate(['nullable', 'string', 'max:255'])]
    public string $mail_oauth_client_id = '';

    public string $mail_oauth_client_secret = '';

    public string $mail_oauth_refresh_token = '';

    #[Validate(['nullable', 'url', 'max:500'])]
    public string $mail_oauth_token_url = '';

    public bool $showPassword = false;

    #[Validate(['nullable', 'email'])]
    public string $test_recipient = '';

    public ?string $testStatus = null;

    public ?string $testMessage = null;

    public ?string $flashSuccess = null;

    public ?string $flashError = null;

    public function mount(SettingService $settingService): void
    {
        $this->mail_mailer = (string) $settingService->get('mail_mailer', null, 'smtp');
        $this->mail_host = (string) $settingService->get('mail_host', null, '');
        $this->mail_port = (string) $settingService->get('mail_port', null, '587');
        $this->mail_username = (string) $settingService->get('mail_username', null, '');
        $this->mail_encryption = (string) $settingService->get('mail_encryption', null, 'tls');
        $this->mail_from_address = (string) $settingService->get('mail_from_address', null, config('mail.from.address', ''));
        $this->mail_from_name = (string) $settingService->get('mail_from_name', null, config('mail.from.name', ''));
        $this->mail_oauth_provider = (string) $settingService->get('mail_oauth_provider', null, 'microsoft365');
        $this->mail_oauth_tenant_id = (string) $settingService->get('mail_oauth_tenant_id', null, '');
        $this->mail_oauth_client_id = (string) $settingService->get('mail_oauth_client_id', null, '');
        $this->mail_oauth_token_url = (string) $settingService->get('mail_oauth_token_url', null, '');

        if (auth()->check()) {
            $this->test_recipient = (string) auth()->user()->email;
        }
    }

    public function selectMailer(string $mailer): void
    {
        $this->mail_mailer = $mailer;
    }

    public function toggleShowPassword(): void
    {
        $this->showPassword = ! $this->showPassword;
    }

    public function save(UpdateMailSettingsAction $action): void
    {
        $this->authorize('manage-system-settings');
        $this->validate();

        $data = [
            'mail_mailer' => $this->mail_mailer,
            'mail_host' => $this->mail_host,
            'mail_port' => $this->mail_port,
            'mail_username' => $this->mail_username,
            'mail_encryption' => $this->mail_encryption,
            'mail_from_address' => $this->mail_from_address,
            'mail_from_name' => $this->mail_from_name,
            'mail_oauth_provider' => $this->mail_oauth_provider,
            'mail_oauth_tenant_id' => $this->mail_oauth_tenant_id,
            'mail_oauth_client_id' => $this->mail_oauth_client_id,
            'mail_oauth_token_url' => $this->mail_oauth_token_url,
        ];

        if ($this->mail_password !== '') {
            $data['mail_password'] = $this->mail_password;
        }

        if ($this->mail_oauth_client_secret !== '') {
            $data['mail_oauth_client_secret'] = $this->mail_oauth_client_secret;
        }

        if ($this->mail_oauth_refresh_token !== '') {
            $data['mail_oauth_refresh_token'] = $this->mail_oauth_refresh_token;
        }

        $action->execute($data);

        $this->flashSuccess = 'Mail and transport settings saved successfully.';
        $this->flashError = null;
        $this->mail_password = '';
        $this->mail_oauth_client_secret = '';
        $this->mail_oauth_refresh_token = '';
    }

    public function sendTestEmail(SendTestEmailAction $action): void
    {
        $this->authorize('manage-system-settings');

        if (empty($this->test_recipient)) {
            $this->testStatus = 'error';
            $this->testMessage = 'Please enter a valid recipient email address for testing.';

            return;
        }

        try {
            $message = $action->execute($this->test_recipient);
            $this->testStatus = 'success';
            $this->testMessage = $message;
        } catch (Throwable $e) {
            $this->testStatus = 'error';
            $this->testMessage = 'Mailer connection failed: '.$e->getMessage();
        }
    }

    public function dismissFlash(): void
    {
        $this->flashSuccess = null;
        $this->flashError = null;
        $this->testStatus = null;
        $this->testMessage = null;
    }

    public function render(): View
    {
        return view('livewire.admin.settings.mail-settings');
    }
}
