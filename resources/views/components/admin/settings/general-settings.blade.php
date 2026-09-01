<?php

use App\Actions\Settings\UpdateGeneralSettingsAction;
use App\Services\SettingService;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;

new class extends Component
{
    use WithFileUploads;

    #[Validate(['required', 'string', 'max:100'])]
    public string $app_name = '';

    #[Validate(['required', 'string', 'max:100'])]
    public string $default_timezone = 'UTC';

    #[Validate(['required', 'string', 'max:30'])]
    public string $date_format = 'Y-m-d';

    #[Validate(['required', 'in:12h,24h'])]
    public string $time_format = '24h';

    #[Validate(['required', 'in:light,dark,system'])]
    public string $default_theme = 'system';

    public string $existing_logo = '';

    public string $existing_favicon = '';

    #[Validate(['nullable', 'image', 'mimes:png,jpg,jpeg,svg,webp', 'max:2048'])]
    public mixed $logo = null;

    #[Validate(['nullable', 'file', 'mimes:ico,png,svg', 'max:1024'])]
    public mixed $favicon = null;

    public ?string $flashSuccess = null;

    public ?string $flashError = null;

    public function mount(SettingService $settingService): void
    {
        $this->app_name = (string) $settingService->get('app_name', null, config('app.name', 'Scaffolding App'));
        $this->existing_logo = (string) $settingService->get('app_logo', null, '');
        $this->existing_favicon = (string) $settingService->get('app_favicon', null, '');
        $this->default_timezone = (string) $settingService->get('default_timezone', null, config('app.timezone', 'UTC'));
        $this->date_format = (string) $settingService->get('date_format', null, 'Y-m-d');
        $this->time_format = (string) $settingService->get('time_format', null, '24h');
        $this->default_theme = (string) $settingService->get('default_theme', null, 'system');
    }

    public function save(UpdateGeneralSettingsAction $action, SettingService $settingService): void
    {
        $this->authorize('manage-system-settings');
        $validated = $this->validate();

        $data = [
            'app_name' => $this->app_name,
            'default_timezone' => $this->default_timezone,
            'date_format' => $this->date_format,
            'time_format' => $this->time_format,
            'default_theme' => $this->default_theme,
        ];

        $logoFile = $this->logo !== null ? $this->logo : null;
        $faviconFile = $this->favicon !== null ? $this->favicon : null;

        $action->execute($data, $logoFile, $faviconFile);

        session()->flash('success', 'General & localization settings updated successfully.');

        $this->redirect(route('admin.settings.general'), navigate: false);
    }

    public function dismissFlash(): void
    {
        $this->flashSuccess = null;
        $this->flashError = null;
    }

    public function timezones(): array
    {
        return [
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

<div>
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
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
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

    <form wire:submit="save" class="space-y-6">
        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
            {{-- Application Name --}}
            <div class="md:col-span-2">
                <label for="app_name" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                    Application Name <span class="text-error-500">*</span>
                </label>
                <input
                    type="text"
                    id="app_name"
                    wire:model="app_name"
                    required
                    class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-500 focus:outline-hidden focus:ring-1 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                    placeholder="e.g. Scaffolding App"
                />
                @error('app_name') <p class="mt-1 text-xs text-error-500">{{ $message }}</p> @enderror
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    Displayed in browser title bars, header navbar, and system notification emails.
                </p>
            </div>

            {{-- App Logo Upload & Live Preview --}}
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                    Application Logo
                </label>
                <div class="flex items-center gap-4">
                    <div class="flex h-16 w-28 items-center justify-center rounded-lg border border-dashed border-gray-300 bg-gray-50 p-2 dark:border-gray-700 dark:bg-gray-800/50 overflow-hidden">
                        @if ($logo)
                            <img src="{{ $logo->temporaryUrl() }}" alt="New Logo Preview" class="max-h-12 max-w-full object-contain" />
                        @elseif (!empty($existing_logo))
                            <img src="{{ $existing_logo }}" alt="App Logo" class="max-h-12 max-w-full object-contain" />
                        @else
                            <span class="text-xs text-gray-400">Default Logo</span>
                        @endif
                    </div>
                    <div class="flex-1">
                        <input
                            type="file"
                            id="logo"
                            wire:model="logo"
                            accept="image/png,image/jpeg,image/svg+xml,image/webp"
                            class="block w-full text-xs text-gray-500 file:mr-3 file:rounded-lg file:border-0 file:bg-brand-50 file:py-2 file:px-3 file:text-xs file:font-semibold file:text-brand-600 hover:file:bg-brand-100 dark:file:bg-brand-500/10 dark:file:text-brand-400"
                        />
                        <div wire:loading wire:target="logo" class="text-xs text-brand-500 mt-1">Uploading logo preview...</div>
                        @error('logo') <p class="mt-1 text-xs text-error-500">{{ $message }}</p> @enderror
                        <p class="mt-1 text-xs text-gray-500">PNG, SVG, JPG or WEBP (Max 2MB)</p>
                    </div>
                </div>
            </div>

            {{-- App Favicon Upload & Live Preview --}}
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                    Application Favicon
                </label>
                <div class="flex items-center gap-4">
                    <div class="flex h-16 w-16 items-center justify-center rounded-lg border border-dashed border-gray-300 bg-gray-50 p-2 dark:border-gray-700 dark:bg-gray-800/50 overflow-hidden">
                        @if ($favicon)
                            @if (method_exists($favicon, 'isPreviewable') && $favicon->isPreviewable())
                                <img src="{{ $favicon->temporaryUrl() }}" alt="Favicon Preview" class="h-8 w-8 object-contain" />
                            @else
                                <span class="text-[10px] font-mono font-medium text-gray-500 dark:text-gray-400 text-center uppercase">{{ method_exists($favicon, 'getClientOriginalExtension') ? $favicon->getClientOriginalExtension() : 'FILE' }}</span>
                            @endif
                        @elseif (!empty($existing_favicon))
                            <img src="{{ $existing_favicon }}" alt="Favicon" class="h-8 w-8 object-contain" />
                        @else
                            <span class="text-xs text-gray-400">32x32</span>
                        @endif
                    </div>
                    <div class="flex-1">
                        <input
                            type="file"
                            id="favicon"
                            wire:model="favicon"
                            accept="image/x-icon,image/png,image/svg+xml"
                            class="block w-full text-xs text-gray-500 file:mr-3 file:rounded-lg file:border-0 file:bg-brand-50 file:py-2 file:px-3 file:text-xs file:font-semibold file:text-brand-600 hover:file:bg-brand-100 dark:file:bg-brand-500/10 dark:file:text-brand-400"
                        />
                        <div wire:loading wire:target="favicon" class="text-xs text-brand-500 mt-1">Uploading favicon preview...</div>
                        @error('favicon') <p class="mt-1 text-xs text-error-500">{{ $message }}</p> @enderror
                        <p class="mt-1 text-xs text-gray-500">ICO, PNG or SVG (Max 1MB)</p>
                    </div>
                </div>
            </div>

            {{-- Default Timezone --}}
            <div>
                <label for="default_timezone" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                    Default System Timezone <span class="text-error-500">*</span>
                </label>
                <select
                    id="default_timezone"
                    wire:model="default_timezone"
                    class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs focus:border-brand-500 focus:outline-hidden focus:ring-1 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
                >
                    @foreach ($this->timezones() as $tzValue => $tzLabel)
                        <option value="{{ $tzValue }}">{{ $tzLabel }}</option>
                    @endforeach
                </select>
                @error('default_timezone') <p class="mt-1 text-xs text-error-500">{{ $message }}</p> @enderror
            </div>

            {{-- Default Date Format --}}
            <div>
                <label for="date_format" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                    System Date Format <span class="text-error-500">*</span>
                </label>
                <select
                    id="date_format"
                    wire:model="date_format"
                    class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs focus:border-brand-500 focus:outline-hidden focus:ring-1 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
                >
                    @foreach ($this->dateFormats() as $formatKey => $formatLabel)
                        <option value="{{ $formatKey }}">{{ $formatLabel }}</option>
                    @endforeach
                </select>
                @error('date_format') <p class="mt-1 text-xs text-error-500">{{ $message }}</p> @enderror
            </div>

            {{-- Time Display Format --}}
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                    Time Display Format
                </label>
                <div class="flex items-center gap-6 pt-2">
                    <label class="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300 cursor-pointer">
                        <input
                            type="radio"
                            wire:model="time_format"
                            value="24h"
                            class="h-4 w-4 text-brand-600 focus:ring-brand-500"
                        />
                        <span>24 Hours (e.g. 14:30)</span>
                    </label>
                    <label class="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300 cursor-pointer">
                        <input
                            type="radio"
                            wire:model="time_format"
                            value="12h"
                            class="h-4 w-4 text-brand-600 focus:ring-brand-500"
                        />
                        <span>12 Hours AM/PM (e.g. 02:30 PM)</span>
                    </label>
                </div>
                @error('time_format') <p class="mt-1 text-xs text-error-500">{{ $message }}</p> @enderror
            </div>

            {{-- Default Theme Mode --}}
            <div class="md:col-span-2">
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                    Default Theme Appearance
                </label>
                <div class="flex flex-wrap items-center gap-6 pt-1">
                    <label class="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300 cursor-pointer">
                        <input
                            type="radio"
                            wire:model="default_theme"
                            value="system"
                            class="h-4 w-4 text-brand-600 focus:ring-brand-500"
                        />
                        <span>🖥️ System Adaptive (Auto)</span>
                    </label>
                    <label class="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300 cursor-pointer">
                        <input
                            type="radio"
                            wire:model="default_theme"
                            value="light"
                            class="h-4 w-4 text-brand-600 focus:ring-brand-500"
                        />
                        <span>☀️ Light Mode</span>
                    </label>
                    <label class="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300 cursor-pointer">
                        <input
                            type="radio"
                            wire:model="default_theme"
                            value="dark"
                            class="h-4 w-4 text-brand-600 focus:ring-brand-500"
                        />
                        <span>🌙 Dark Mode</span>
                    </label>
                </div>
                @error('default_theme') <p class="mt-1 text-xs text-error-500">{{ $message }}</p> @enderror
            </div>
        </div>

        {{-- Submit Button --}}
        <div class="flex items-center justify-end pt-4 border-t border-gray-200 dark:border-gray-800 gap-3">
            <button
                type="submit"
                class="inline-flex items-center justify-center rounded-lg bg-brand-500 px-5 py-2.5 text-center text-sm font-medium text-white shadow-theme-xs hover:bg-brand-600 focus:outline-hidden focus:ring-2 focus:ring-brand-500/50 cursor-pointer"
            >
                <span wire:loading.remove wire:target="save">Save General Settings</span>
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
</div>
