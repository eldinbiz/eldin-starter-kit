<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Settings;

use App\Actions\Settings\UpdateGeneralSettingsAction;
use App\Services\SettingService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;

final class GeneralSettings extends Component
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

    public function render(): View
    {
        $timezones = [
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
            'Y-m-d' => 'YYYY-MM-DD (e.g. 2026-08-26)',
            'd/m/Y' => 'DD/MM/YYYY (e.g. 26/08/2026)',
            'm/d/Y' => 'MM/DD/YYYY (e.g. 08/26/2026)',
            'd M Y' => 'DD Mon YYYY (e.g. 26 Aug 2026)',
            'M d, Y' => 'Mon DD, YYYY (e.g. Aug 26, 2026)',
            'F j, Y' => 'Month D, YYYY (e.g. August 26, 2026)',
        ];

        return view('livewire.admin.settings.general-settings', [
            'timezones' => $timezones,
            'dateFormats' => $dateFormats,
        ]);
    }
}
