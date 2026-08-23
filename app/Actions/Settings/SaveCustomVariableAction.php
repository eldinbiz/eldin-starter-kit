<?php

declare(strict_types=1);

namespace App\Actions\Settings;

use App\Models\SystemSetting;
use App\Services\SettingService;

final class SaveCustomVariableAction
{
    public function __construct(
        private readonly SettingService $settingService
    ) {}

    /**
     * Store or update a custom variable and return whether it was an overwrite.
     *
     * @param array{
     *     key: string,
     *     type: string,
     *     value?: mixed,
     *     company_id?: int|string|null,
     *     description?: string|null
     * } $data
     */
    public function execute(array $data): bool
    {
        $companyId = ! empty($data['company_id']) ? (int) $data['company_id'] : null;
        $key = (string) $data['key'];
        $value = $data['value'] ?? '';
        $type = (string) $data['type'];

        $existing = SystemSetting::customVariables()
            ->where('company_id', $companyId)
            ->where('key', $key)
            ->exists();

        $this->settingService->set(
            key: $key,
            value: $value,
            type: $type,
            group: 'custom',
            companyId: $companyId,
            description: isset($data['description']) ? (string) $data['description'] : null
        );

        return $existing;
    }
}
