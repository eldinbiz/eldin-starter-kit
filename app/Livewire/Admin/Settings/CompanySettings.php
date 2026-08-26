<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Settings;

use App\Actions\Settings\UpdateCompanySettingsAction;
use App\Enums\BrandTheme;
use App\Models\Company;
use App\Services\SettingService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;

final class CompanySettings extends Component
{
    use WithFileUploads;

    #[Url(as: 'company_id')]
    public ?int $selectedCompanyId = null;

    #[Validate(['required', 'string', 'max:50'])]
    public string $brand_theme = 'brand';

    #[Validate(['nullable', 'string', 'max:100'])]
    public string $default_timezone = '';

    #[Validate(['nullable', 'string', 'max:30'])]
    public string $date_format = '';

    public string $existing_logo = '';

    #[Validate(['nullable', 'image', 'mimes:png,jpg,jpeg,svg,webp', 'max:2048'])]
    public mixed $logo = null;

    public ?string $flashSuccess = null;

    public ?string $flashError = null;

    public function mount(SettingService $settingService): void
    {
        $user = auth()->user();
        $companies = $this->companies;

        if (! $this->selectedCompanyId && $companies->isNotEmpty()) {
            $activeCompany = $user?->getActiveCompany();
            $this->selectedCompanyId = $activeCompany ? $activeCompany->id : $companies->first()->id;
        }

        $this->loadCompanySettings($settingService);
    }

    public function updatedSelectedCompanyId(): void
    {
        $this->loadCompanySettings(app(SettingService::class));
    }

    public function setTheme(string $themeKey): void
    {
        $this->brand_theme = $themeKey;
    }

    public function loadCompanySettings(SettingService $settingService): void
    {
        if (! $this->selectedCompanyId) {
            return;
        }

        $company = Company::find($this->selectedCompanyId);

        $this->brand_theme = (string) $settingService->get('brand_theme', $this->selectedCompanyId, 'brand');
        $this->default_timezone = (string) $settingService->get('default_timezone', $this->selectedCompanyId, '');
        $this->date_format = (string) $settingService->get('date_format', $this->selectedCompanyId, '');
        $this->existing_logo = (string) ($settingService->get('company_logo', $this->selectedCompanyId, '') ?: ($company?->logo ?? ''));
        $this->logo = null;
    }

    public function save(UpdateCompanySettingsAction $action, SettingService $settingService): void
    {
        $this->authorize('manage-company-settings');
        $this->validate();

        if (! $this->selectedCompanyId) {
            $this->flashError = 'Please select a valid company.';

            return;
        }

        $data = [
            'company_id' => $this->selectedCompanyId,
            'brand_theme' => $this->brand_theme,
            'default_timezone' => $this->default_timezone !== '' ? $this->default_timezone : null,
            'date_format' => $this->date_format !== '' ? $this->date_format : null,
        ];

        $logoFile = $this->logo !== null ? $this->logo : null;

        $action->execute($data, $logoFile);

        $this->loadCompanySettings($settingService);

        $this->flashSuccess = 'Company preferences and theme updated successfully.';
        $this->flashError = null;
    }

    public function dismissFlash(): void
    {
        $this->flashSuccess = null;
        $this->flashError = null;
    }

    #[Computed]
    public function companies(): Collection
    {
        $user = auth()->user();
        if ($user?->hasPlatformAccess()) {
            return Company::where('is_active', true)->orderBy('name')->get();
        }

        return $user?->companies()->where('is_active', true)->orderBy('name')->get() ?? collect();
    }

    #[Computed]
    public function selectedCompany(): ?Company
    {
        if (! $this->selectedCompanyId) {
            return null;
        }

        return Company::find($this->selectedCompanyId);
    }

    public function render(): View
    {
        $themeOptions = BrandTheme::options();

        $timezones = [
            '' => 'System Default (Inherited)',
            'UTC' => 'UTC (Universal Time Coordinated)',
            'America/New_York' => 'Eastern Time (US & Canada)',
            'America/Chicago' => 'Central Time (US & Canada)',
            'America/Denver' => 'Mountain Time (US & Canada)',
            'America/Los_Angeles' => 'Pacific Time (US & Canada)',
            'Europe/London' => 'London, Edinburgh, Dublin (GMT/BST)',
            'Europe/Paris' => 'Paris, Berlin, Rome, Madrid (CET)',
            'Asia/Dubai' => 'Dubai, Abu Dhabi (GST)',
            'Asia/Jakarta' => 'Jakarta, Bangkok, Hanoi (WIB)',
            'Asia/Singapore' => 'Singapore, Kuala Lumpur, Hong Kong (SGT)',
            'Asia/Tokyo' => 'Tokyo, Seoul (JST)',
            'Australia/Sydney' => 'Sydney, Melbourne (AEST)',
        ];

        $dateFormats = [
            '' => 'System Default (Inherited)',
            'Y-m-d' => 'YYYY-MM-DD (e.g. 2026-08-26)',
            'd/m/Y' => 'DD/MM/YYYY (e.g. 26/08/2026)',
            'm/d/Y' => 'MM/DD/YYYY (e.g. 08/26/2026)',
            'd M Y' => 'DD Mon YYYY (e.g. 26 Aug 2026)',
            'M d, Y' => 'Mon DD, YYYY (e.g. Aug 26, 2026)',
            'F j, Y' => 'Month D, YYYY (e.g. August 26, 2026)',
        ];

        return view('livewire.admin.settings.company-settings', [
            'themeOptions' => $themeOptions,
            'timezones' => $timezones,
            'dateFormats' => $dateFormats,
        ]);
    }
}
