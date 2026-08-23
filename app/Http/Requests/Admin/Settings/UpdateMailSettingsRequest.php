<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Settings;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateMailSettingsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('manage-system-settings') ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'mail_mailer' => ['required', 'in:smtp,oauth,sendmail,mailgun,ses,postmark,log,array'],
            'mail_host' => ['required_if:mail_mailer,smtp', 'nullable', 'string', 'max:255'],
            'mail_port' => ['required_if:mail_mailer,smtp', 'nullable', 'numeric', 'between:1,65535'],
            'mail_username' => ['nullable', 'string', 'max:255'],
            'mail_password' => ['nullable', 'string', 'max:255'],
            'mail_encryption' => ['nullable', 'in:tls,ssl,none'],
            'mail_from_address' => ['required', 'email', 'max:255'],
            'mail_from_name' => ['required', 'string', 'max:255'],
            'mail_oauth_provider' => ['required_if:mail_mailer,oauth', 'nullable', 'in:microsoft365,google,generic'],
            'mail_oauth_tenant_id' => ['nullable', 'string', 'max:255'],
            'mail_oauth_client_id' => ['required_if:mail_mailer,oauth', 'nullable', 'string', 'max:255'],
            'mail_oauth_client_secret' => ['nullable', 'string', 'max:255'],
            'mail_oauth_refresh_token' => ['nullable', 'string', 'max:1000'],
            'mail_oauth_token_url' => ['nullable', 'url', 'max:255'],
        ];
    }
}
