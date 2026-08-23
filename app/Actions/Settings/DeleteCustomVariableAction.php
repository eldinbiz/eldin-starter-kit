<?php

declare(strict_types=1);

namespace App\Actions\Settings;

use App\Models\SystemSetting;
use App\Services\SettingService;

final class DeleteCustomVariableAction
{
    public function __construct(
        private readonly SettingService $settingService
    ) {}

    /**
     * Delete custom variable by ID.
     *
     * @return array{
     *     company_id: int|null,
     *     key: string
     * }
     */
    public function execute(int $id): array
    {
        $variable = SystemSetting::customVariables()->findOrFail($id);
        $companyId = $variable->company_id;
        $key = $variable->key;

        $this->settingService->delete($key, $companyId);

        return [
            'company_id' => $companyId,
            'key' => $key,
        ];
    }
}
