<?php

declare(strict_types=1);

namespace App\Actions\Settings;

use App\Models\AuthProviderSetting;
use App\Services\Auth\AuthProviderManager;
use InvalidArgumentException;

final class TestAuthProviderAction
{
    public const VALID_PROVIDERS = ['local', 'ldap', 'google', 'microsoft', 'auth0', 'saml2'];

    public function __construct(
        private readonly AuthProviderManager $authManager
    ) {}

    /**
     * Test connection parameters for a specific provider.
     *
     * @param  array<string, mixed>|null  $config
     * @return array<string, mixed>
     *
     * @throws InvalidArgumentException
     */
    public function execute(string $provider, ?array $config = null): array
    {
        if (! in_array($provider, self::VALID_PROVIDERS, true)) {
            throw new InvalidArgumentException(__('Invalid provider selected.'));
        }

        if (empty($config)) {
            $savedSetting = AuthProviderSetting::forProvider($provider);
            $config = $savedSetting?->settings ?? [];
        }

        $driver = $this->authManager->driver($provider);

        return (array) $driver->testConnection($config);
    }
}
