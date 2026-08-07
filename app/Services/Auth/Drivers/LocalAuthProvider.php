<?php

declare(strict_types=1);

namespace App\Services\Auth\Drivers;

use App\Models\AuthProviderSetting;
use App\Models\User;
use App\Services\Auth\Contracts\AuthProviderInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class LocalAuthProvider implements AuthProviderInterface
{
    public function authenticate(User $user, array $credentials, ?AuthProviderSetting $setting = null): bool
    {
        $password = $credentials['password'] ?? '';

        if (! is_string($password) || $password === '' || $user->password === null) {
            return false;
        }

        return Hash::check($password, $user->password);
    }

    public function redirect(?AuthProviderSetting $setting = null): RedirectResponse
    {
        return redirect()->route('login');
    }

    public function handleCallback(Request $request, ?AuthProviderSetting $setting = null): ?User
    {
        return null;
    }

    public function testConnection(array $config): array
    {
        return [
            'success' => true,
            'message' => __('Local authentication refers to database accounts. Local auth driver is operational.'),
        ];
    }
}
