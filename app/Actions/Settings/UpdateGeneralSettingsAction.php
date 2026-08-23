<?php

declare(strict_types=1);

namespace App\Actions\Settings;

use App\Services\SettingService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

final class UpdateGeneralSettingsAction
{
    public function __construct(
        private readonly SettingService $settingService
    ) {}

    /**
     * Update general branding and localization settings.
     *
     * @param array{
     *     app_name: string,
     *     default_timezone: string,
     *     date_format: string,
     *     time_format: string,
     *     default_theme: string
     * } $data
     */
    public function execute(
        array $data,
        ?UploadedFile $logo = null,
        ?UploadedFile $favicon = null
    ): void {
        $this->settingService->set('app_name', (string) $data['app_name'], 'text', 'general');
        $this->settingService->set('default_timezone', (string) $data['default_timezone'], 'text', 'localization');
        $this->settingService->set('date_format', (string) $data['date_format'], 'text', 'localization');
        $this->settingService->set('time_format', (string) $data['time_format'], 'text', 'localization');
        $this->settingService->set('default_theme', (string) $data['default_theme'], 'text', 'general');

        $this->settingService->applyRuntimeAppConfig();

        if ($logo !== null) {
            $logoPath = $logo->storeAs('settings/branding', $logo->getClientOriginalName(), 'public');
            $this->settingService->set('app_logo', Storage::url($logoPath), 'text', 'general');
        }

        if ($favicon !== null) {
            $faviconPath = $favicon->storeAs('settings/branding', $favicon->getClientOriginalName(), 'public');
            $this->settingService->set('app_favicon', Storage::url($faviconPath), 'text', 'general');
        }
    }
}
