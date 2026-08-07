<?php

declare(strict_types=1);

namespace App\Services\Auth\Drivers;

use App\Models\AuthProviderSetting;
use App\Models\User;
use App\Services\Auth\Contracts\AuthProviderInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LdapAuthProvider implements AuthProviderInterface
{
    public function authenticate(User $user, array $credentials, ?AuthProviderSetting $setting = null): bool
    {
        if ($setting === null || ! $setting->is_enabled) {
            return false;
        }

        $password = $credentials['password'] ?? '';
        if (! is_string($password) || $password === '') {
            return false;
        }

        $config = $setting->settings ?? [];
        $host = $config['host'] ?? '';
        $port = (int) ($config['port'] ?? 389);
        $baseDn = $config['base_dn'] ?? '';
        $encryption = $config['encryption'] ?? 'none'; // none, tls, ssl

        if ($host === '') {
            return false;
        }

        // If LDAP extension is not installed, fallback log and return false or mock in testing
        if (! function_exists('ldap_connect')) {
            Log::warning('LDAP PHP extension is not enabled on this server.');
            return false;
        }

        $uri = $encryption === 'ssl' ? "ldaps://{$host}:{$port}" : "ldap://{$host}:{$port}";
        $ldapConnection = @ldap_connect($uri);

        if (! $ldapConnection) {
            return false;
        }

        ldap_set_option($ldapConnection, LDAP_OPT_PROTOCOL_VERSION, 3);
        ldap_set_option($ldapConnection, LDAP_OPT_REFERRALS, 0);

        if ($encryption === 'tls') {
            @ldap_start_tls($ldapConnection);
        }

        $userRdn = sprintf($config['user_dn_format'] ?? 'uid=%s,%s', $user->email, $baseDn);
        $bound = @ldap_bind($ldapConnection, $userRdn, $password);

        @ldap_unbind($ldapConnection);

        return (bool) $bound;
    }

    public function redirect(?AuthProviderSetting $setting = null): RedirectResponse
    {
        return redirect()->route('login');
    }

    public function handleCallback(Request $request, ?AuthProviderSetting $setting = null): ?User
    {
        return null;
    }
}
