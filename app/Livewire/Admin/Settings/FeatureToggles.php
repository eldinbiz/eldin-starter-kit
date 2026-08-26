<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Settings;

use App\Actions\Settings\UpdateFeatureSettingsAction;
use App\Models\Company;
use App\Models\SystemSetting;
use App\Services\FeatureService;
use App\Services\SettingService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;

final class FeatureToggles extends Component
{
    #[Url(as: 'company_id')]
    public ?int $selectedCompanyId = null;

    /**
     * @var array<string, bool>
     */
    public array $globalValues = [];

    /**
     * @var array<string, string>
     */
    public array $companyValues = [];

    public ?string $flashSuccess = null;

    public ?string $flashError = null;

    public function mount(FeatureService $featureService, SettingService $settingService): void
    {
        $companies = $this->companies;
        if (! $this->selectedCompanyId && $companies->isNotEmpty()) {
            $this->selectedCompanyId = $companies->first()->id;
        }

        $this->loadFeatures($featureService, $settingService);
    }

    public function updatedSelectedCompanyId(): void
    {
        $this->loadFeatures(app(FeatureService::class), app(SettingService::class));
    }

    public function loadFeatures(FeatureService $featureService, SettingService $settingService): void
    {
        $allFeatures = $featureService->all();

        // Load Global Values
        $this->globalValues = [];
        foreach ($allFeatures as $key => $feature) {
            $this->globalValues[$key] = (bool) $settingService->get(
                $feature['setting_key'],
                null,
                $feature['default_enabled'] ?? true,
            );
        }

        // Load Company Overrides
        $this->companyValues = [];
        if ($this->selectedCompanyId) {
            $companyOverrides = SystemSetting::forGroup('feature')
                ->forCompany($this->selectedCompanyId)
                ->get()
                ->keyBy('key');

            foreach ($allFeatures as $key => $feature) {
                $override = $companyOverrides->get($feature['setting_key']);
                $this->companyValues[$key] = $override !== null ? ($override->typed_value ? '1' : '0') : '';
            }
        }
    }

    public function toggleGlobal(string $key, UpdateFeatureSettingsAction $action, FeatureService $featureService, SettingService $settingService): void
    {
        $this->authorize('manage-platform');

        $current = $this->globalValues[$key] ?? false;
        $this->globalValues[$key] = ! $current;

        $features = [];
        foreach ($this->globalValues as $k => $val) {
            $features[$k] = $val ? '1' : '0';
        }

        $action->execute([
            'company_id' => null,
            'features' => $features,
        ]);

        $this->loadFeatures($featureService, $settingService);
        $this->flashSuccess = 'Global feature toggles updated.';
    }

    public function saveGlobal(UpdateFeatureSettingsAction $action, FeatureService $featureService, SettingService $settingService): void
    {
        $this->authorize('manage-platform');

        $features = [];
        foreach ($this->globalValues as $k => $val) {
            $features[$k] = $val ? '1' : '0';
        }

        $action->execute([
            'company_id' => null,
            'features' => $features,
        ]);

        $this->loadFeatures($featureService, $settingService);
        $this->flashSuccess = 'Global feature defaults saved successfully.';
    }

    public function saveCompany(UpdateFeatureSettingsAction $action, FeatureService $featureService, SettingService $settingService): void
    {
        $this->authorize('manage-platform');

        if (! $this->selectedCompanyId) {
            return;
        }

        $action->execute([
            'company_id' => $this->selectedCompanyId,
            'features' => $this->companyValues,
        ]);

        $this->loadFeatures($featureService, $settingService);
        $this->flashSuccess = 'Company feature overrides saved successfully.';
    }

    public function dismissFlash(): void
    {
        $this->flashSuccess = null;
        $this->flashError = null;
    }

    #[Computed]
    public function features(): array
    {
        return app(FeatureService::class)->all();
    }

    #[Computed]
    public function companies(): Collection
    {
        return Company::where('is_active', true)->orderBy('name')->get();
    }

    public function render(): View
    {
        return view('livewire.admin.settings.feature-toggles');
    }
}
