<?php

declare(strict_types=1);

namespace App\Actions\Settings;

use App\Services\FeatureService;
use App\Services\SettingService;

final class UpdateFeatureSettingsAction
{
    public function __construct(
        private readonly SettingService $settingService,
        private readonly FeatureService $featureService
    ) {}

    /**
     * Update feature toggles and flush cached status.
     *
     * @param array{
     *     company_id?: int|string|null,
     *     features: array<string, string|int|bool|null>
     * } $data
     */
    public function execute(array $data): ?int
    {
        $companyId = ! empty($data['company_id']) ? (int) $data['company_id'] : null;

        foreach ($this->featureService->all() as $key => $definition) {
            $value = (string) ($data['features'][$key] ?? '');

            if ($value === '') {
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

        return $companyId;
    }
}
