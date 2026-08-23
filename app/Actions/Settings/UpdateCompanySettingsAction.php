<?php

declare(strict_types=1);

namespace App\Actions\Settings;

use App\Models\Company;
use App\Services\SettingService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

final class UpdateCompanySettingsAction
{
    public function __construct(
        private readonly SettingService $settingService
    ) {}

    /**
     * Update company preferences, branding theme, and logo.
     *
     * @param array{
     *     company_id: int|string,
     *     brand_theme: string,
     *     default_timezone?: string|null,
     *     date_format?: string|null
     * } $data
     */
    public function execute(array $data, ?UploadedFile $logo = null): int
    {
        $companyId = (int) $data['company_id'];

        $this->settingService->set('brand_theme', (string) $data['brand_theme'], 'text', 'company', $companyId);
        $this->settingService->set('default_timezone', (string) ($data['default_timezone'] ?? ''), 'text', 'localization', $companyId);
        $this->settingService->set('date_format', (string) ($data['date_format'] ?? ''), 'text', 'localization', $companyId);

        if ($logo !== null) {
            $logoPath = $logo->storeAs('companies/logos', $logo->getClientOriginalName(), 'public');
            $logoUrl = Storage::url($logoPath);
            $this->settingService->set('company_logo', $logoUrl, 'text', 'company', $companyId);

            Company::where('id', $companyId)->update(['logo' => $logoUrl]);
        }

        return $companyId;
    }
}
