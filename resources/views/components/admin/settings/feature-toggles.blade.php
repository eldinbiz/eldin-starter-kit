<?php

use App\Actions\Settings\UpdateFeatureSettingsAction;
use App\Models\Company;
use App\Models\SystemSetting;
use App\Services\FeatureService;
use App\Services\SettingService;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;

new class extends Component
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
};
?>

<div class="space-y-6">
    {{-- Flash Notifications --}}
    @if ($flashSuccess)
        <div class="rounded-xl border border-success-200 bg-success-50 p-4 text-sm text-success-700 dark:border-success-800/30 dark:bg-success-500/10 dark:text-success-400 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <svg class="h-5 w-5 shrink-0" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                </svg>
                <span>{{ $flashSuccess }}</span>
            </div>
            <button type="button" wire:click="dismissFlash" class="text-success-600 hover:text-success-800 dark:text-success-400 dark:hover:text-success-200 cursor-pointer">
                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                </svg>
            </button>
        </div>
    @endif

    {{-- 1. GLOBAL DEFAULTS SECTION --}}
    <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-theme-xs dark:border-gray-800 dark:bg-gray-900/30">
        <div class="mb-4">
            <h4 class="text-sm font-semibold text-gray-800 dark:text-white/90">Global Defaults</h4>
            <p class="text-xs text-gray-500 dark:text-gray-400">
                Platform-wide default state. Companies inherit these values unless specifically overridden below.
            </p>
        </div>

        <div class="divide-y divide-gray-100 dark:divide-gray-800">
            @foreach ($this->features as $key => $feature)
                @php
                    $isEnabled = $globalValues[$key] ?? false;
                @endphp
                <div class="flex items-center justify-between gap-4 py-3">
                    <div>
                        <span class="font-medium text-sm text-gray-800 dark:text-white/90">{{ $feature['label'] }}</span>
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $feature['description'] }}</p>
                    </div>

                    <button
                        type="button"
                        wire:click="toggleGlobal('{{ $key }}')"
                        class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-hidden {{ $isEnabled ? 'bg-brand-500' : 'bg-gray-200 dark:bg-gray-700' }}"
                        role="switch"
                        aria-checked="{{ $isEnabled ? 'true' : 'false' }}"
                    >
                        <span class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow-sm ring-0 transition duration-200 ease-in-out {{ $isEnabled ? 'translate-x-5' : 'translate-x-0' }}"></span>
                    </button>
                </div>
            @endforeach
        </div>
    </div>

    {{-- 2. PER-COMPANY OVERRIDES SECTION --}}
    <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-theme-xs dark:border-gray-800 dark:bg-gray-900/30">
        <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h4 class="text-sm font-semibold text-gray-800 dark:text-white/90">Per-Company Overrides</h4>
                <p class="text-xs text-gray-500 dark:text-gray-400">
                    "Use Global" inherits the platform default, including future changes to it.
                </p>
            </div>

            <div class="flex items-center gap-2">
                <label for="company_override_select" class="text-xs font-medium text-gray-700 dark:text-gray-300">
                    Company:
                </label>
                <select
                    id="company_override_select"
                    wire:model.live="selectedCompanyId"
                    class="h-9.5 rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs text-gray-800 shadow-theme-xs focus:border-brand-500 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
                >
                    @foreach ($this->companies as $comp)
                        <option value="{{ $comp->id }}">
                            {{ $comp->name }} ({{ $comp->type ?? 'Company' }})
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        @if ($selectedCompanyId)
            <div class="divide-y divide-gray-100 dark:divide-gray-800">
                @foreach ($this->features as $key => $feature)
                    <div class="flex flex-col gap-2 py-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <span class="font-medium text-sm text-gray-800 dark:text-white/90">{{ $feature['label'] }}</span>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $feature['description'] }}</p>
                        </div>
                        <select
                            wire:model="companyValues.{{ $key }}"
                            class="h-9.5 w-full rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs text-gray-800 shadow-theme-xs focus:border-brand-500 focus:outline-hidden sm:w-44 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
                        >
                            <option value="">Use Global ({{ ($globalValues[$key] ?? false) ? 'On' : 'Off' }})</option>
                            <option value="1">Force Enabled (On)</option>
                            <option value="0">Force Disabled (Off)</option>
                        </select>
                    </div>
                @endforeach
            </div>

            <div class="mt-4 flex justify-end border-t border-gray-100 pt-4 dark:border-gray-800">
                <button
                    type="button"
                    wire:click="saveCompany"
                    class="inline-flex items-center justify-center rounded-lg bg-brand-500 px-4 py-2 text-xs font-medium text-white shadow-theme-xs hover:bg-brand-600 focus:outline-hidden cursor-pointer"
                >
                    <span wire:loading.remove wire:target="saveCompany">Save Overrides</span>
                    <span wire:loading wire:target="saveCompany">Saving...</span>
                </button>
            </div>
        @else
            <div class="rounded-lg bg-gray-50 p-4 text-center text-xs text-gray-500 dark:bg-gray-900/50 dark:text-gray-400">
                Select a company above to manage its feature overrides.
            </div>
        @endif
    </div>
</div>
