<?php

declare(strict_types=1);

namespace App\Services\Auth\Drivers;

use App\Models\AuthProviderSetting;
use App\Models\User;
use App\Services\Auth\Contracts\AuthProviderInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class SocialiteAuthProvider implements AuthProviderInterface
{
    public function __construct(private string $provider)
    {
    }

    public function authenticate(User $user, array $credentials, ?AuthProviderSetting $setting = null): bool
    {
        return false;
    }

    public function redirect(?AuthProviderSetting $setting = null): RedirectResponse
    {
        if ($setting === null || ! $setting->is_enabled) {
            return redirect()->route('login')->withErrors(['email' => __("Authentication provider {$this->provider} is not enabled.")]);
        }

        $this->configureSocialiteService($setting);

        /** @var RedirectResponse */
        return Socialite::driver($this->provider)->redirect();
    }

    public function handleCallback(Request $request, ?AuthProviderSetting $setting = null): ?User
    {
        if ($setting === null || ! $setting->is_enabled) {
            return null;
        }

        $this->configureSocialiteService($setting);

        try {
            $socialiteUser = Socialite::driver($this->provider)->user();
        } catch (\Throwable) {
            return null;
        }

        $email = $socialiteUser->getEmail();
        if (! $email) {
            return null;
        }

        $user = User::where('email', $email)->first();

        if (! $user && $setting->allow_auto_provisioning) {
            $user = User::create([
                'name' => $socialiteUser->getName() ?? Str::before($email, '@'),
                'email' => $email,
                'password' => Hash::make(Str::random(32)),
                'auth_method' => $this->provider,
                'external_id' => $socialiteUser->getId(),
            ]);
        }

        if ($user) {
            $user->update([
                'external_id' => $socialiteUser->getId(),
            ]);
        }

        return $user;
    }

    private function configureSocialiteService(AuthProviderSetting $setting): void
    {
        $config = $setting->settings ?? [];

        if ($this->provider === 'saml2') {
            Config::set('services.saml2', array_filter([
                'metadata' => $config['metadata_url'] ?? null,
                'acs' => $config['sso_url'] ?? null,
                'entityid' => $config['entity_id'] ?? null,
                'certificate' => $config['certificate'] ?? null,
                'sp_entity_id' => config('app.url'),
            ]));

            return;
        }

        Config::set("services.{$this->provider}", [
            'client_id' => $config['client_id'] ?? '',
            'client_secret' => $config['client_secret'] ?? '',
            'redirect' => route('auth.callback', ['provider' => $this->provider]),
            'tenant' => $config['tenant_id'] ?? null,
            'domain' => $config['domain'] ?? null,
        ]);
    }
}
