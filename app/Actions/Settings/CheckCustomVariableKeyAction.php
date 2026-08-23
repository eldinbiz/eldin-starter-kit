<?php

declare(strict_types=1);

namespace App\Actions\Settings;

use App\Models\SystemSetting;

final class CheckCustomVariableKeyAction
{
    /**
     * Check if a custom variable key exists in the database for the given scope.
     *
     * @return array<string, mixed>
     */
    public function execute(string $key, ?int $companyId = null): array
    {
        $existing = SystemSetting::customVariables()
            ->where('company_id', $companyId)
            ->where('key', $key)
            ->with('company:id,name')
            ->first();

        if (! $existing) {
            return ['exists' => false];
        }

        return [
            'exists' => true,
            'id' => $existing->id,
            'key' => $existing->key,
            'type' => $existing->type,
            'value' => (string) ($existing->getRawOriginal('value') ?? ''),
            'company_id' => $existing->company_id,
            'company_name' => $existing->company?->name ?? __('Global System Defaults'),
            'description' => $existing->description,
        ];
    }
}
