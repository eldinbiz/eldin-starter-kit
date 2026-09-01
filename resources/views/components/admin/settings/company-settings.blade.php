<?php

use App\Actions\Settings\UpdateCompanySettingsAction;
use App\Enums\BrandTheme;
use App\Models\Company;
use App\Services\SettingService;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;

new class extends Component
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

        session()->flash('success', 'Company preferences and theme updated successfully.');

        $this->redirect(route('admin.settings.company', ['company_id' => $this->selectedCompanyId]), navigate: false);
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

    public function themeOptions(): array
    {
        return BrandTheme::options();
    }

    public function timezones(): array
    {
        return [
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
    }

    public function dateFormats(): array
    {
        return [
            '' => 'System Default (Inherited)',
            'Y-m-d' => 'YYYY-MM-DD (e.g. 2026-08-26)',
            'd/m/Y' => 'DD/MM/YYYY (e.g. 26/08/2026)',
            'm/d/Y' => 'MM/DD/YYYY (e.g. 08/26/2026)',
            'd M Y' => 'DD Mon YYYY (e.g. 26 Aug 2026)',
            'M d, Y' => 'Mon DD, YYYY (e.g. Aug 26, 2026)',
            'F j, Y' => 'Month D, YYYY (e.g. August 26, 2026)',
        ];
    }
};
?>

<div
    x-data="{
        themes: {{ Js::from($this->themeOptions()) }},
        applyThemeVariables(themeKey) {
            const theme = this.themes[themeKey];
            if (!theme || !theme.variables) return;
            for (const [prop, val] of Object.entries(theme.variables)) {
                document.documentElement.style.setProperty(prop, val);
            }
        }
    }"
>
    {{-- Flash Notifications --}}
    @if ($flashSuccess)
        <div class="mb-6 flex items-center justify-between rounded-lg bg-success-50 p-4 text-sm text-success-700 dark:bg-success-500/10 dark:text-success-400">
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

    @if ($flashError)
        <div class="mb-6 flex items-center justify-between rounded-lg bg-error-50 p-4 text-sm text-error-700 dark:bg-error-500/10 dark:text-error-400">
            <div class="flex items-center gap-2">
                <svg class="h-5 w-5 shrink-0" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                </svg>
                <span>{{ $flashError }}</span>
            </div>
            <button type="button" wire:click="dismissFlash" class="text-error-600 hover:text-error-800 dark:text-error-400 dark:hover:text-error-200 cursor-pointer">
                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                </svg>
            </button>
        </div>
    @endif

    {{-- Company Selector Bar --}}
    <div class="mb-6 rounded-xl border border-gray-200 bg-gray-50/50 p-4 dark:border-gray-800 dark:bg-gray-900/50">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-3">
                <label for="company_id_select" class="text-sm font-medium text-gray-700 dark:text-gray-300">
                    Selected Company:
                </label>
                <select
                    id="company_id_select"
                    wire:model.live="selectedCompanyId"
                    class="h-10 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-800 shadow-theme-xs focus:border-brand-500 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
                >
                    @foreach ($this->companies as $comp)
                        <option value="{{ $comp->id }}">
                            {{ $comp->name }} ({{ $comp->type ?? 'Company' }})
                        </option>
                    @endforeach
                </select>
            </div>
            @if ($this->selectedCompany)
                <span class="text-xs text-gray-500 dark:text-gray-400">
                    Editing preferences for <strong>{{ $this->selectedCompany->name }}</strong>
                </span>
            @endif
        </div>
    </div>

    @if ($this->selectedCompany)
        <form wire:submit="save" class="space-y-6">
            {{-- 1. Company Identity & Logo Section --}}
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-theme-xs dark:border-gray-800 dark:bg-gray-900/30">
                <div class="mb-4">
                    <h4 class="text-sm font-semibold text-gray-800 dark:text-white/90">
                        1. Company Logo & Identity
                    </h4>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        Upload a tenant-specific logo displayed in the company switcher, headers, and subsidiary hierarchy.
                    </p>
                </div>

                <div class="flex flex-col gap-4 sm:flex-row sm:items-center">
                    <div class="flex h-20 w-36 items-center justify-center rounded-lg border border-dashed border-gray-300 bg-gray-50 p-2 dark:border-gray-700 dark:bg-gray-800/50 overflow-hidden">
                        @if ($logo)
                            <img src="{{ $logo->temporaryUrl() }}" alt="New Logo Preview" class="max-h-16 max-w-full object-contain" />
                        @elseif (!empty($existing_logo))
                            <img src="{{ $existing_logo }}" alt="Company Logo" class="max-h-16 max-w-full object-contain" />
                        @else
                            <span class="text-xs text-gray-400">No Custom Logo</span>
                        @endif
                    </div>
                    <div class="flex-1">
                        <input
                            type="file"
                            id="company_logo"
                            wire:model="logo"
                            accept="image/png,image/jpeg,image/svg+xml,image/webp"
                            class="block w-full text-xs text-gray-500 file:mr-3 file:rounded-lg file:border-0 file:bg-brand-50 file:py-2 file:px-3 file:text-xs file:font-semibold file:text-brand-600 hover:file:bg-brand-100 dark:file:bg-brand-500/10 dark:file:text-brand-400"
                        />
                        <div wire:loading wire:target="logo" class="text-xs text-brand-500 mt-1">Uploading logo preview...</div>
                        @error('logo') <p class="mt-1 text-xs text-error-500">{{ $message }}</p> @enderror
                        <p class="mt-1 text-xs text-gray-500">PNG, SVG, JPG or WEBP (Max 2MB). Falls back to global application logo if not set.</p>
                    </div>
                </div>
            </div>

            {{-- 2. Brand Theme Palette (Instant Live Preview) --}}
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-theme-xs dark:border-gray-800 dark:bg-gray-900/30">
                <div class="mb-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h4 class="text-sm font-semibold text-gray-800 dark:text-white/90">
                                2. Brand Theme Palette
                            </h4>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                Select a curated primary color palette for this company. Clicking any theme previews it live in your browser!
                            </p>
                        </div>
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-brand-50 px-2.5 py-1 text-xs font-semibold text-brand-700 dark:bg-brand-500/15 dark:text-brand-400">
                            Active: {{ $this->themeOptions()[$brand_theme]['label'] ?? ucfirst($brand_theme) }}
                        </span>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5">
                    @foreach ($this->themeOptions() as $key => $opt)
                        @php
                            $isSelected = $brand_theme === $key;
                        @endphp
                        <button
                            type="button"
                            wire:click="setTheme('{{ $key }}')"
                            @click="applyThemeVariables('{{ $key }}')"
                            class="relative flex flex-col items-start gap-2 rounded-xl border p-3 text-left transition-all cursor-pointer {{ $isSelected ? 'border-brand-500 bg-brand-50/50 ring-2 ring-brand-500/20 dark:border-brand-400 dark:bg-brand-500/10' : 'border-gray-200 bg-white hover:border-gray-300 dark:border-gray-800 dark:bg-gray-900/50 dark:hover:border-gray-700' }}"
                        >
                            <div class="flex w-full items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <span class="h-4 w-4 rounded-full border border-black/10 shadow-xs shrink-0" style="background-color: {{ $opt['dot'] }}"></span>
                                    <span class="text-xs font-medium text-gray-800 dark:text-white/90 truncate">{{ $opt['label'] }}</span>
                                </div>
                                @if ($isSelected)
                                    <span class="h-2 w-2 rounded-full bg-brand-500"></span>
                                @endif
                            </div>
                        </button>
                    @endforeach
                </div>
            </div>

            {{-- 3. Localization Overrides Section --}}
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-theme-xs dark:border-gray-800 dark:bg-gray-900/30">
                <div class="mb-4">
                    <h4 class="text-sm font-semibold text-gray-800 dark:text-white/90">
                        3. Localization Overrides
                    </h4>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        Configure company-specific timezone and date formatting to override system defaults for users in this tenant.
                    </p>
                </div>

                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    {{-- Timezone Override --}}
                    <div>
                        <label for="company_timezone" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                            Company Timezone Override
                        </label>
                        <select
                            id="company_timezone"
                            wire:model="default_timezone"
                            class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs focus:border-brand-500 focus:outline-hidden focus:ring-1 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
                        >
                            @foreach ($this->timezones() as $tzKey => $tzLabel)
                                <option value="{{ $tzKey }}">{{ $tzLabel }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Date Format Override --}}
                    <div>
                        <label for="company_date_format" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                            Company Date Format Override
                        </label>
                        <select
                            id="company_date_format"
                            wire:model="date_format"
                            class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs focus:border-brand-500 focus:outline-hidden focus:ring-1 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
                        >
                            @foreach ($this->dateFormats() as $dfKey => $dfLabel)
                                <option value="{{ $dfKey }}">{{ $dfLabel }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            {{-- Save Action --}}
            <div class="flex items-center justify-end pt-4 border-t border-gray-200 dark:border-gray-800 gap-3">
                <button
                    type="submit"
                    class="inline-flex items-center justify-center rounded-lg bg-brand-500 px-5 py-2.5 text-center text-sm font-medium text-white shadow-theme-xs hover:bg-brand-600 focus:outline-hidden focus:ring-2 focus:ring-brand-500/50 cursor-pointer"
                >
                    <span wire:loading.remove wire:target="save">Save Company Preferences</span>
                    <span wire:loading wire:target="save" class="flex items-center gap-2">
                        <svg class="animate-spin h-4 w-4" viewBox="0 0 24 24" fill="none">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                        </svg>
                        Saving...
                    </span>
                </button>
            </div>
        </form>
    @else
        <div class="rounded-xl border border-gray-200 bg-gray-50 p-6 text-center text-sm text-gray-500 dark:border-gray-800 dark:bg-gray-900/50 dark:text-gray-400">
            No companies found. Please create a company first.
        </div>
    @endif
</div>
