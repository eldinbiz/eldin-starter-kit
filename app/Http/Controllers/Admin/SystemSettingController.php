<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Settings\CheckCustomVariableKeyAction;
use App\Actions\Settings\DeleteCustomVariableAction;
use App\Actions\Settings\SaveCustomVariableAction;
use App\Actions\Settings\SendTestEmailAction;
use App\Actions\Settings\UpdateCompanySettingsAction;
use App\Actions\Settings\UpdateFeatureSettingsAction;
use App\Actions\Settings\UpdateGeneralSettingsAction;
use App\Actions\Settings\UpdateMailSettingsAction;
use App\Enums\BrandTheme;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Settings\CheckCustomVariableKeyRequest;
use App\Http\Requests\Admin\Settings\SendTestEmailRequest;
use App\Http\Requests\Admin\Settings\StoreCustomVariableRequest;
use App\Http\Requests\Admin\Settings\UpdateCompanySettingsRequest;
use App\Http\Requests\Admin\Settings\UpdateCustomVariableRequest;
use App\Http\Requests\Admin\Settings\UpdateFeatureSettingsRequest;
use App\Http\Requests\Admin\Settings\UpdateGeneralSettingsRequest;
use App\Http\Requests\Admin\Settings\UpdateMailSettingsRequest;
use App\Models\Company;
use App\Models\SystemSetting;
use App\Services\FeatureService;
use App\Services\SettingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
    public function updateGeneral(
        UpdateGeneralSettingsRequest $request,
        UpdateGeneralSettingsAction $action
    ): RedirectResponse {
        $action->execute(
            $request->validated(),
            $request->file('app_logo'),
            $request->file('app_favicon')
        );

        return redirect()->route('admin.settings.general')
            ->with('success', __('General & branding settings updated successfully.'));
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
    public function updateMail(
        UpdateMailSettingsRequest $request,
        UpdateMailSettingsAction $action
    ): RedirectResponse {
        $action->execute($request->validated());

        return redirect()->route('admin.settings.mail')
            ->with('success', __('Mail & transport settings updated successfully.'));
    }

    /**
     * Dispatch live test email directly to the logged-in user.
     */
    public function testMail(
        SendTestEmailRequest $request,
        SendTestEmailAction $action
    ): JsonResponse|RedirectResponse {
        $recipientEmail = $request->user()?->email ?? $request->validated('recipient_email');

        if (! is_string($recipientEmail) || $recipientEmail === '') {
            $recipientEmail = 'admin@example.com';
        }

        try {
            $message = $action->execute($recipientEmail);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                ]);
            }

            return redirect()->route('admin.settings.mail')->with('success', $message);
        } catch (Throwable $e) {
            $errorMessage = __('Failed to dispatch test email: ').$e->getMessage();

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $errorMessage,
                ], 422);
            }

            return redirect()->route('admin.settings.mail')->with('error', $errorMessage);
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
    public function checkCustomVariableKey(
        CheckCustomVariableKeyRequest $request,
        CheckCustomVariableKeyAction $action
    ): JsonResponse {
        $validated = $request->validated();
        $companyId = ! empty($validated['company_id']) ? (int) $validated['company_id'] : null;

        $result = $action->execute((string) $validated['key'], $companyId);

        return response()->json($result);
    }

    /**
     * Store new custom variable.
     */
    public function storeCustomVariable(
        StoreCustomVariableRequest $request,
        SaveCustomVariableAction $action
    ): RedirectResponse {
        $validated = $request->validated();
        $existing = $action->execute($validated);

        $companyId = ! empty($validated['company_id']) ? (int) $validated['company_id'] : null;
        $flashMessage = $existing
            ? __("Custom variable ':key' was updated with the new value.", ['key' => $validated['key']])
            : __("Custom variable ':key' created successfully.", ['key' => $validated['key']]);

        return redirect()->route('admin.settings.custom-variables', ['company_id' => $companyId])
            ->with('success', $flashMessage);
    }

    /**
     * Update an existing custom variable.
     */
    public function updateCustomVariable(
        UpdateCustomVariableRequest $request,
        int $id,
        SaveCustomVariableAction $action
    ): RedirectResponse {
        $variable = SystemSetting::customVariables()->findOrFail($id);

        $validated = $request->validated();
        $validated['key'] = $variable->key;
        $validated['company_id'] = $variable->company_id;

        $action->execute($validated);

        return redirect()->route('admin.settings.custom-variables', ['company_id' => $variable->company_id])
            ->with('success', __("Custom variable ':key' updated successfully.", ['key' => $variable->key]));
    }

    /**
     * Delete custom variable.
     */
    public function destroyCustomVariable(
        int $id,
        DeleteCustomVariableAction $action
    ): RedirectResponse {
        $result = $action->execute($id);

        return redirect()->route('admin.settings.custom-variables', ['company_id' => $result['company_id']])
            ->with('success', __("Custom variable ':key' deleted successfully.", ['key' => $result['key']]));
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
    public function updateCompany(
        UpdateCompanySettingsRequest $request,
        UpdateCompanySettingsAction $action
    ): RedirectResponse {
        $companyId = $action->execute(
            $request->validated(),
            $request->file('company_logo')
        );

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
    public function updateFeatures(
        UpdateFeatureSettingsRequest $request,
        UpdateFeatureSettingsAction $action
    ): RedirectResponse {
        $companyId = $action->execute($request->validated());

        return redirect()->route('admin.settings.features', $companyId !== null ? ['company_id' => $companyId] : [])
            ->with('success', __('Feature toggles updated successfully.'));
    }
}
