<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Models\AuthProviderSetting;
use App\Models\User;
use App\Services\Auth\Contracts\AuthProviderInterface;
use App\Services\Auth\Drivers\LdapAuthProvider;
use App\Services\Auth\Drivers\LocalAuthProvider;
use App\Services\Auth\Drivers\SamlAuthProvider;
use App\Services\Auth\Drivers\SocialiteAuthProvider;
use InvalidArgumentException;

class AuthProviderManager
{
    /**
     * Resolve the auth driver instance for the given provider key.
     */
    public function driver(string $provider): AuthProviderInterface
    {
        return match ($provider) {
            'local' => new LocalAuthProvider(),
            'ldap' => new LdapAuthProvider(),
            'google', 'microsoft', 'auth0', 'saml2' => new SocialiteAuthProvider($provider),
            default => throw new InvalidArgumentException("Unsupported authentication provider [{$provider}]."),
        };
    }

    /**
     * Get provider settings for a specific provider.
     */
    public function getSetting(string $provider): ?AuthProviderSetting
    {
        return AuthProviderSetting::forProvider($provider);
    }

    /**
     * Determine if a provider is enabled.
     */
    public function isProviderEnabled(string $provider): bool
    {
        if ($provider === 'local') {
            return true;
        }

        $setting = $this->getSetting($provider);

        return $setting !== null && $setting->is_enabled;
    }
}
