<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\BrandTheme;
use App\Http\Controllers\Controller;
use App\Mail\TestEmail;
use App\Models\Company;
use App\Models\SystemSetting;
use App\Services\FeatureService;
use App\Services\SettingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Throwable;

class SystemSettingController extends Controller
{
    public function __construct(
        private readonly SettingService $settingService,
        private readonly FeatureService $featureService
    ) {}

    /**
     * Display general and branding settings page.
     */
    public function general(): View
    {
        $settings = [
            'app_name' => (string) $this->settingService->get('app_name', null, config('app.name', 'Scaffolding App')),
            'app_logo' => (string) $this->settingService->get('app_logo', null, ''),
            'app_favicon' => (string) $this->settingService->get('app_favicon', null, ''),
            'default_timezone' => (string) $this->settingService->get('default_timezone', null, config('app.timezone', 'UTC')),
            'date_format' => (string) $this->settingService->get('date_format', null, 'Y-m-d'),
            'time_format' => (string) $this->settingService->get('time_format', null, '24h'),
            'default_theme' => (string) $this->settingService->get('default_theme', null, 'system'),
        ];

        $timezones = [
            'UTC' => 'UTC (Universal Time Coordinated)',
            'America/New_York' => 'Eastern Time (US & Canada)',
            'America/Chicago' => 'Central Time (US & Canada)',
            'America/Denver' => 'Mountain Time (US & Canada)',
            'America/Los_Angeles' => 'Pacific Time (US & Canada)',
            'Europe/London' => 'London, Edinburgh, Dublin',
            'Europe/Paris' => 'Paris, Berlin, Rome, Madrid',
            'Asia/Jakarta' => 'Jakarta, Bangkok, Hanoi (UTC+7)',
            'Asia/Singapore' => 'Singapore, Kuala Lumpur, Hong Kong (UTC+8)',
            'Asia/Tokyo' => 'Tokyo, Seoul (UTC+9)',
            'Australia/Sydney' => 'Sydney, Melbourne (UTC+10/11)',
        ];

        $dateFormats = [
            'Y-m-d' => 'YYYY-MM-DD (2026-08-09)',
            'd/m/Y' => 'DD/MM/YYYY (09/08/2026)',
            'm/d/Y' => 'MM/DD/YYYY (08/09/2026)',
            'd M Y' => 'DD MMM YYYY (09 Aug 2026)',
            'M d, Y' => 'MMM DD, YYYY (Aug 09, 2026)',
        ];

        return view('admin.settings.general', [
            'settings' => $settings,
            'timezones' => $timezones,
            'dateFormats' => $dateFormats,
        ]);
    }

    /**
     * Update general and branding settings.
     */
    public function updateGeneral(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'app_name' => ['required', 'string', 'max:100'],
            'default_timezone' => ['required', 'string', 'max:100'],
            'date_format' => ['required', 'string', 'max:30'],
            'time_format' => ['required', 'in:12h,24h'],
            'default_theme' => ['required', 'in:light,dark,system'],
            'app_logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,svg,webp', 'max:2048'],
            'app_favicon' => ['nullable', 'image', 'mimes:ico,png,svg', 'max:1024'],
        ]);

        $this->settingService->set('app_name', $validated['app_name'], 'text', 'general');
        $this->settingService->set('default_timezone', $validated['default_timezone'], 'text', 'localization');
        $this->settingService->set('date_format', $validated['date_format'], 'text', 'localization');
        $this->settingService->set('time_format', $validated['time_format'], 'text', 'localization');
        $this->settingService->set('default_theme', $validated['default_theme'], 'text', 'general');

        $this->settingService->applyRuntimeAppConfig();

        if ($request->hasFile('app_logo')) {
            $file = $request->file('app_logo');
            $logoPath = $file->storeAs('settings/branding', $file->getClientOriginalName(), 'public');
            $this->settingService->set('app_logo', Storage::url($logoPath), 'text', 'general');
        }

        if ($request->hasFile('app_favicon')) {
            $file = $request->file('app_favicon');
            $faviconPath = $file->storeAs('settings/branding', $file->getClientOriginalName(), 'public');
            $this->settingService->set('app_favicon', Storage::url($faviconPath), 'text', 'general');
        }

        return redirect()->route('admin.settings.general')->with('success', __('General & branding settings updated successfully.'));
    }

    /**
     * Display mail and transport settings page.
     */
    public function mail(): View
    {
        $settings = [
            'mail_mailer' => (string) $this->settingService->get('mail_mailer', null, config('mail.default', 'smtp')),
            'mail_host' => (string) $this->settingService->get('mail_host', null, (string) config('mail.mailers.smtp.host', '')),
            'mail_port' => (string) $this->settingService->get('mail_port', null, (string) config('mail.mailers.smtp.port', '2525')),
            'mail_username' => (string) $this->settingService->get('mail_username', null, (string) config('mail.mailers.smtp.username', '')),
            'mail_password' => (string) $this->settingService->get('mail_password', null, ''),
            'mail_encryption' => (string) $this->settingService->get('mail_encryption', null, (string) config('mail.mailers.smtp.encryption', 'tls')),
            'mail_from_address' => (string) $this->settingService->get('mail_from_address', null, (string) config('mail.from.address', 'noreply@scaffolding.test')),
            'mail_from_name' => (string) $this->settingService->get('mail_from_name', null, (string) config('mail.from.name', 'Scaffolding App')),
            'mail_oauth_provider' => (string) $this->settingService->get('mail_oauth_provider', null, 'microsoft365'),
            'mail_oauth_tenant_id' => (string) $this->settingService->get('mail_oauth_tenant_id', null, ''),
            'mail_oauth_client_id' => (string) $this->settingService->get('mail_oauth_client_id', null, ''),
            'mail_oauth_client_secret' => (string) $this->settingService->get('mail_oauth_client_secret', null, ''),
            'mail_oauth_refresh_token' => (string) $this->settingService->get('mail_oauth_refresh_token', null, ''),
            'mail_oauth_token_url' => (string) $this->settingService->get('mail_oauth_token_url', null, ''),
        ];

        return view('admin.settings.mail', [
            'settings' => $settings,
        ]);
    }

    /**
     * Update mail and transport settings.
     */
    public function updateMail(Request $request): RedirectResponse
    {
        $validated = $request->validate([
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
        ]);

        $this->settingService->set('mail_mailer', $validated['mail_mailer'], 'text', 'mail');

        if ($request->has('mail_host')) {
            $this->settingService->set('mail_host', $validated['mail_host'] ?? '', 'text', 'mail');
        }

        if ($request->has('mail_port')) {
            $this->settingService->set('mail_port', (string) ($validated['mail_port'] ?? '587'), 'numeric', 'mail');
        }

        if ($request->has('mail_username')) {
            $this->settingService->set('mail_username', $validated['mail_username'] ?? '', 'text', 'mail');
        }

        if (! empty($validated['mail_password'])) {
            $this->settingService->set('mail_password', $validated['mail_password'], 'encrypted', 'mail');
        }

        if ($request->has('mail_encryption')) {
            $this->settingService->set('mail_encryption', $validated['mail_encryption'] ?? 'tls', 'text', 'mail');
        }

        $this->settingService->set('mail_from_address', $validated['mail_from_address'], 'text', 'mail');
        $this->settingService->set('mail_from_name', $validated['mail_from_name'], 'text', 'mail');

        if ($request->has('mail_oauth_provider')) {
            $this->settingService->set('mail_oauth_provider', $validated['mail_oauth_provider'] ?? 'microsoft365', 'text', 'mail');
        }

        if ($request->has('mail_oauth_tenant_id')) {
            $this->settingService->set('mail_oauth_tenant_id', $validated['mail_oauth_tenant_id'] ?? '', 'text', 'mail');
        }

        if ($request->has('mail_oauth_client_id')) {
            $this->settingService->set('mail_oauth_client_id', $validated['mail_oauth_client_id'] ?? '', 'text', 'mail');
        }

        if ($request->has('mail_oauth_token_url')) {
            $this->settingService->set('mail_oauth_token_url', $validated['mail_oauth_token_url'] ?? '', 'text', 'mail');
        }

        if (! empty($validated['mail_oauth_client_secret'])) {
            $this->settingService->set('mail_oauth_client_secret', $validated['mail_oauth_client_secret'], 'encrypted', 'mail');
        }

        if (! empty($validated['mail_oauth_refresh_token'])) {
            $this->settingService->set('mail_oauth_refresh_token', $validated['mail_oauth_refresh_token'], 'encrypted', 'mail');
        }

        return redirect()->route('admin.settings.mail')->with('success', __('Mail & transport settings updated successfully.'));
    }

    /**
     * Dispatch live test email directly to the logged-in user.
     */
    public function testMail(Request $request): JsonResponse|RedirectResponse
    {
        $recipientEmail = $request->user()?->email ?? $request->input('recipient_email');

        if (! is_string($recipientEmail) || $recipientEmail === '') {
            $recipientEmail = 'admin@example.com';
        }

        try {
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

            $message = __('Test email successfully dispatched to :email using the :details transport driver.', [
                'email' => $recipientEmail,
                'details' => $details,
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                ]);
            }

            return redirect()->route('admin.settings.mail')->with('success', $message);
        } catch (Throwable $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => __('Failed to dispatch test email: ').$e->getMessage(),
                ], 422);
            }

            return redirect()->route('admin.settings.mail')->with('error', __('Failed to dispatch test email: ').$e->getMessage());
        }
    }

    /**
     * Display custom variables manager.
     */
    public function customVariables(Request $request): View
    {
        $companies = Company::orderBy('name')->get();
        $selectedCompanyId = $request->has('company_id') && $request->query('company_id') !== ''
            ? (int) $request->query('company_id')
            : null;

        $variables = $this->settingService->getCustomVariables($selectedCompanyId);

        return view('admin.settings.custom-variables', [
            'variables' => $variables,
            'companies' => $companies,
            'selectedCompanyId' => $selectedCompanyId,
        ]);
    }

    /**
     * Check if a custom variable key already exists in the database for the given scope.
     */
    public function checkCustomVariableKey(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'key' => ['required', 'string', 'max:100', 'regex:/^[a-zA-Z0-9_\-]+$/'],
            'company_id' => ['nullable', 'exists:companies,id'],
        ]);

        $companyId = ! empty($validated['company_id']) ? (int) $validated['company_id'] : null;

        $existing = SystemSetting::customVariables()
            ->where('company_id', $companyId)
            ->where('key', $validated['key'])
            ->with('company:id,name')
            ->first();

        if (! $existing) {
            return response()->json(['exists' => false]);
        }

        return response()->json([
            'exists' => true,
            'id' => $existing->id,
            'key' => $existing->key,
            'type' => $existing->type,
            'value' => (string) ($existing->getRawOriginal('value') ?? ''),
            'company_id' => $existing->company_id,
            'company_name' => $existing->company?->name ?? __('Global System Defaults'),
            'description' => $existing->description,
        ]);
    }

    /**
     * Store new custom variable.
     */
    public function storeCustomVariable(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'key' => ['required', 'string', 'max:100', 'regex:/^[a-zA-Z0-9_\-]+$/'],
            'type' => ['required', 'in:text,numeric,json'],
            'value' => ['nullable'],
            'company_id' => ['nullable', 'exists:companies,id'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        $type = $validated['type'];
        $value = $validated['value'] ?? '';
        $companyId = ! empty($validated['company_id']) ? (int) $validated['company_id'] : null;

        // Custom validation based on type
        if ($type === 'numeric' && ! empty($value) && ! is_numeric($value)) {
            return redirect()->back()->withErrors(['value' => __('The value must be numeric for Numeric type.')])->withInput();
        }

        if ($type === 'json' && ! empty($value)) {
            json_decode((string) $value);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return redirect()->back()->withErrors(['value' => __('Invalid JSON format: ').json_last_error_msg()])->withInput();
            }
        }

        $existing = SystemSetting::customVariables()
            ->where('company_id', $companyId)
            ->where('key', $validated['key'])
            ->exists();

        $this->settingService->set(
            key: $validated['key'],
            value: $value,
            type: $type,
            group: 'custom',
            companyId: $companyId,
            description: $validated['description'] ?? null
        );

        $flashMessage = $existing
            ? __("Custom variable ':key' was updated with the new value.", ['key' => $validated['key']])
            : __("Custom variable ':key' created successfully.", ['key' => $validated['key']]);

        return redirect()->route('admin.settings.custom-variables', ['company_id' => $companyId])
            ->with('success', $flashMessage);
    }

    /**
     * Update an existing custom variable.
     */
    public function updateCustomVariable(Request $request, int $id): RedirectResponse
    {
        $variable = SystemSetting::customVariables()->findOrFail($id);

        $validated = $request->validate([
            'type' => ['required', 'in:text,numeric,json'],
            'value' => ['nullable'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        $type = $validated['type'];
        $value = $validated['value'] ?? '';

        if ($type === 'numeric' && ! empty($value) && ! is_numeric($value)) {
            return redirect()->back()->withErrors(['value' => __('The value must be numeric for Numeric type.')])->withInput();
        }

        if ($type === 'json' && ! empty($value)) {
            json_decode((string) $value);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return redirect()->back()->withErrors(['value' => __('Invalid JSON format: ').json_last_error_msg()])->withInput();
            }
        }

        $this->settingService->set(
            key: $variable->key,
            value: $value,
            type: $type,
            group: 'custom',
            companyId: $variable->company_id,
            description: $validated['description'] ?? null
        );

        return redirect()->route('admin.settings.custom-variables', ['company_id' => $variable->company_id])
            ->with('success', __("Custom variable ':key' updated successfully.", ['key' => $variable->key]));
    }

    /**
     * Delete custom variable.
     */
    public function destroyCustomVariable(int $id): RedirectResponse
    {
        $variable = SystemSetting::customVariables()->findOrFail($id);
        $companyId = $variable->company_id;
        $key = $variable->key;

        $this->settingService->delete($key, $companyId);

        return redirect()->route('admin.settings.custom-variables', ['company_id' => $companyId])
            ->with('success', __("Custom variable ':key' deleted successfully.", ['key' => $key]));
    }

    /**
     * Display company preferences page.
     */
    public function company(Request $request): View
    {
        $companies = Company::orderBy('name')->get();
        $selectedCompanyId = $request->has('company_id') && $request->query('company_id') !== ''
            ? (int) $request->query('company_id')
            : ($companies->first()?->id ?? null);

        $selectedCompany = $selectedCompanyId ? Company::find($selectedCompanyId) : null;

        $selectedBrandThemeKey = (string) $this->settingService->get('brand_theme', $selectedCompanyId, 'brand');
        $selectedBrandTheme = BrandTheme::tryFrom($selectedBrandThemeKey) ?? BrandTheme::Brand;

        $settings = [
            'company_logo' => (string) $this->settingService->get('company_logo', $selectedCompanyId, ''),
            'brand_theme' => $selectedBrandTheme->value,
            'default_timezone' => (string) $this->settingService->get('default_timezone', $selectedCompanyId, ''),
            'date_format' => (string) $this->settingService->get('date_format', $selectedCompanyId, ''),
        ];

        $timezones = [
            '' => 'Use System Default',
            'UTC' => 'UTC (Universal Time Coordinated)',
            'America/New_York' => 'Eastern Time (US & Canada)',
            'America/Chicago' => 'Central Time (US & Canada)',
            'America/Denver' => 'Mountain Time (US & Canada)',
            'America/Los_Angeles' => 'Pacific Time (US & Canada)',
            'Europe/London' => 'London, Edinburgh, Dublin',
            'Europe/Paris' => 'Paris, Berlin, Rome, Madrid',
            'Asia/Jakarta' => 'Jakarta, Bangkok, Hanoi (UTC+7)',
            'Asia/Singapore' => 'Singapore, Kuala Lumpur, Hong Kong (UTC+8)',
            'Asia/Tokyo' => 'Tokyo, Seoul (UTC+9)',
            'Australia/Sydney' => 'Sydney, Melbourne (UTC+10/11)',
        ];

        $dateFormats = [
            '' => 'Use System Default',
            'Y-m-d' => 'YYYY-MM-DD (2026-08-09)',
            'd/m/Y' => 'DD/MM/YYYY (09/08/2026)',
            'm/d/Y' => 'MM/DD/YYYY (08/09/2026)',
            'd M Y' => 'DD MMM YYYY (09 Aug 2026)',
            'M d, Y' => 'MMM DD, YYYY (Aug 09, 2026)',
        ];

        return view('admin.settings.company', [
            'companies' => $companies,
            'selectedCompany' => $selectedCompany,
            'settings' => $settings,
            'timezones' => $timezones,
            'dateFormats' => $dateFormats,
            'brandThemes' => BrandTheme::cases(),
            'brandThemeOptions' => BrandTheme::options(),
        ]);
    }

    /**
     * Update company preferences.
     */
    public function updateCompany(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'company_id' => ['required', 'exists:companies,id'],
            'brand_theme' => ['required', 'string', Rule::enum(BrandTheme::class)],
            'default_timezone' => ['nullable', 'string', 'max:100'],
            'date_format' => ['nullable', 'string', 'max:30'],
            'company_logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,svg,webp', 'max:2048'],
        ]);

        $companyId = (int) $validated['company_id'];

        $this->settingService->set('brand_theme', $validated['brand_theme'], 'text', 'company', $companyId);
        $this->settingService->set('default_timezone', $validated['default_timezone'] ?? '', 'text', 'localization', $companyId);
        $this->settingService->set('date_format', $validated['date_format'] ?? '', 'text', 'localization', $companyId);

        if ($request->hasFile('company_logo')) {
            $file = $request->file('company_logo');
            $logoPath = $file->storeAs('companies/logos', $file->getClientOriginalName(), 'public');
            $logoUrl = Storage::url($logoPath);
            $this->settingService->set('company_logo', $logoUrl, 'text', 'company', $companyId);

            // Also update Company model's logo column for consistency
            Company::where('id', $companyId)->update(['logo' => $logoUrl]);
        }

        return redirect()->route('admin.settings.company', ['company_id' => $companyId])
            ->with('success', __('Company preferences updated successfully.'));
    }

    /**
     * Display feature toggles page (global defaults and per-company overrides).
     */
    public function features(Request $request): View
    {
        $companies = Company::orderBy('name')->get();
        $selectedCompanyId = $request->has('company_id') && $request->query('company_id') !== ''
            ? (int) $request->query('company_id')
            : null;

        $features = $this->featureService->all();

        $globalValues = [];
        foreach ($features as $key => $definition) {
            $globalValues[$key] = $this->settingService->get(
                $definition['setting_key'],
                null,
                $definition['default_enabled'],
            );
        }

        $companyOverrides = $selectedCompanyId !== null
            ? SystemSetting::forGroup('feature')
                ->forCompany($selectedCompanyId)
                ->get()
                ->keyBy('key')
            : collect();

        $companyValues = [];
        foreach ($features as $key => $definition) {
            $override = $companyOverrides->get($definition['setting_key']);

            $companyValues[$key] = $override?->typed_value;
        }

        return view('admin.settings.features', [
            'features' => $features,
            'companies' => $companies,
            'selectedCompanyId' => $selectedCompanyId,
            'globalValues' => $globalValues,
            'companyValues' => $companyValues,
        ]);
    }

    /**
     * Update feature toggles (platform admin only).
     */
    public function updateFeatures(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'company_id' => ['nullable', 'integer', 'exists:companies,id'],
            'features' => ['required', 'array'],
            'features.*' => ['nullable', 'in:0,1'],
        ]);

        $companyId = ! empty($validated['company_id']) ? (int) $validated['company_id'] : null;

        foreach ($this->featureService->all() as $key => $definition) {
            $value = (string) ($validated['features'][$key] ?? '');

            if ($value === '') {
                // "Use Global" — delete the override row so the cascade resumes.
                $this->settingService->delete($definition['setting_key'], $companyId);

                continue;
            }

            $this->settingService->set(
                $definition['setting_key'],
                (bool) $value,
                'boolean',
                'feature',
                $companyId,
                $definition['description'],
            );
        }

        $this->featureService->flush();

        return redirect()->route('admin.settings.features', $companyId !== null ? ['company_id' => $companyId] : [])
            ->with('success', __('Feature toggles updated successfully.'));
    }
}
