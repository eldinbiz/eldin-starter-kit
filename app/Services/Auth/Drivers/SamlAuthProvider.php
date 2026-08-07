<?php

declare(strict_types=1);

namespace App\Services\Auth\Drivers;

use App\Models\AuthProviderSetting;
use App\Models\User;
use App\Services\Auth\Contracts\AuthProviderInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class SamlAuthProvider implements AuthProviderInterface
{
    public function authenticate(User $user, array $credentials, ?AuthProviderSetting $setting = null): bool
    {
        return false;
    }

    public function redirect(?AuthProviderSetting $setting = null): RedirectResponse
    {
        if ($setting === null || ! $setting->is_enabled) {
            return redirect()->route('login')->withErrors(['email' => __('SAML 2.0 authentication provider is not enabled.')]);
        }

        $config = $setting->settings ?? [];
        $ssoUrl = $config['sso_url'] ?? '';

        if ($ssoUrl === '') {
            return redirect()->route('login')->withErrors(['email' => __('SAML 2.0 SSO URL is not configured.')]);
        }

        // Build standard SAML 2.0 Request redirect or query parameters
        $redirectUrl = $ssoUrl.(str_contains($ssoUrl, '?') ? '&' : '?').http_build_query([
            'SAMLRequest' => base64_encode('<samlp:AuthnRequest xmlns:samlp="urn:oasis:names:tc:SAML:2.0:protocol" IssueInstant="'.now()->toIso8601String().'" ID="ID_'.Str::random(16).'"/>'),
            'RelayState' => route('auth.callback', ['provider' => 'saml2']),
        ]);

        return redirect()->away($redirectUrl);
    }

    public function handleCallback(Request $request, ?AuthProviderSetting $setting = null): ?User
    {
        if ($setting === null || ! $setting->is_enabled) {
            return null;
        }

        $samlResponse = $request->input('SAMLResponse');
        if (! is_string($samlResponse) || $samlResponse === '') {
            return null;
        }

        $decodedXml = base64_decode($samlResponse, true);
        if ($decodedXml === false) {
            return null;
        }

        // Parse NameID or Email from XML assertion
        preg_match('/<saml:NameID[^>]*>(.*?)<\/saml:NameID>/s', $decodedXml, $matches);
        $email = $matches[1] ?? null;

        if (! $email || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return null;
        }

        $user = User::where('email', $email)->first();

        if (! $user && $setting->allow_auto_provisioning) {
            $user = User::create([
                'name' => Str::before($email, '@'),
                'email' => $email,
                'password' => Hash::make(Str::random(32)),
                'auth_method' => 'saml2',
                'external_id' => $email,
            ]);
        }

        if ($user) {
            $user->update(['external_id' => $email]);
        }

        return $user;
    }
}
