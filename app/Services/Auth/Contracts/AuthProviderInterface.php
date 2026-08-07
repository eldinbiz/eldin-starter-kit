<?php

declare(strict_types=1);

namespace App\Services\Auth\Contracts;

use App\Models\AuthProviderSetting;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

interface AuthProviderInterface
{
    /**
     * Authenticate user with given credentials.
     */
    public function authenticate(User $user, array $credentials, ?AuthProviderSetting $setting = null): bool;

    /**
     * Redirect to identity provider authentication URL (for SSO/SAML).
     */
    public function redirect(?AuthProviderSetting $setting = null): RedirectResponse;

    /**
     * Handle incoming callback from identity provider.
     */
    public function handleCallback(Request $request, ?AuthProviderSetting $setting = null): ?User;

    /**
     * Test connection reachability and configuration validity.
     *
     * @return array{success: bool, message: string}
     */
    public function testConnection(array $config): array;
}
