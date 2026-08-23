<?php

declare(strict_types=1);

namespace App\Actions\Settings;

use App\Models\AuthProviderSetting;
use InvalidArgumentException;

final class UpdateAuthProviderAction
{
    public const VALID_PROVIDERS = ['ldap', 'google', 'microsoft', 'auth0', 'saml2'];

    /**
     * Update or create authentication provider settings.
     *
     * @param array{
     *     is_enabled?: bool|int|string|null,
     *     allow_auto_provisioning?: bool|int|string|null,
     *     settings?: array<string, mixed>|null
     * } $data
     *
     * @throws InvalidArgumentException
     */
    public function execute(string $provider, array $data): AuthProviderSetting
    {
        if (! in_array($provider, self::VALID_PROVIDERS, true)) {
            throw new InvalidArgumentException(__('Invalid provider selected.'));
        }

        return AuthProviderSetting::updateOrCreate(
            ['provider' => $provider],
            [
                'is_enabled' => isset($data['is_enabled']) ? (bool) $data['is_enabled'] : false,
                'allow_auto_provisioning' => isset($data['allow_auto_provisioning']) ? (bool) $data['allow_auto_provisioning'] : false,
                'settings' => $data['settings'] ?? [],
            ]
        );
    }
}
