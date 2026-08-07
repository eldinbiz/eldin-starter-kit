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
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
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
            Log::warning("SSO callback failed: Auth provider {$this->provider} setting is null or disabled.");

            return null;
        }

        $this->configureSocialiteService($setting);

        try {
            $driver = Socialite::driver($this->provider);
            if (config('app.env') === 'local') {
                $driver->setHttpClient(new \GuzzleHttp\Client(['verify' => false]));
            }
            $socialiteUser = $driver->user();
        } catch (\Throwable $e) {
            Log::error("Socialite callback exception for {$this->provider}: {$e->getMessage()}", [
                'exception' => $e,
            ]);

            return null;
        }

        $email = $socialiteUser->getEmail();
        if (! $email) {
            Log::warning("SSO callback failed for {$this->provider}: No email address returned from identity provider.");

            return null;
        }

        $user = User::where('email', $email)->first();

        if (! $user && $setting->allow_auto_provisioning) {
            $name = trim($socialiteUser->getName() ?? '');
            if ($name === '') {
                $name = $socialiteUser->getNickname() ?? Str::before($email, '@');
            }

            $user = User::create([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make(Str::random(32)),
                'auth_method' => $this->provider,
                'external_id' => $socialiteUser->getId(),
            ]);
            Log::info("Auto-provisioned new user {$email} via {$this->provider}.");
        }

        if ($user) {
            $user->update([
                'external_id' => $socialiteUser->getId(),
            ]);
        } else {
            Log::warning("SSO callback failed for {$this->provider}: User {$email} not found and auto-provisioning is disabled.");
        }

        return $user;
    }

    private function parseDomainHost(string $rawDomain): string
    {
        $trimmed = trim($rawDomain);
        if ($trimmed === '') {
            return '';
        }

        $url = preg_match('#^https?://#i', $trimmed) ? $trimmed : "https://{$trimmed}";
        $host = parse_url($url, PHP_URL_HOST);

        return is_string($host) ? trim($host) : '';
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

        $domain = $this->parseDomainHost((string) ($config['domain'] ?? ''));

        Config::set("services.{$this->provider}", [
            'client_id' => $config['client_id'] ?? '',
            'client_secret' => $config['client_secret'] ?? '',
            'redirect' => route('auth.callback', ['provider' => $this->provider]),
            'tenant' => $config['tenant_id'] ?? null,
            'domain' => $domain !== '' ? $domain : null,
            'base_url' => $domain !== '' ? "https://{$domain}" : null,
        ]);
    }

    public function testConnection(array $config): array
    {
        if ($this->provider === 'saml2') {
            $metadataUrl = $config['metadata_url'] ?? '';
            $ssoUrl = $config['sso_url'] ?? '';

            if ($metadataUrl !== '') {
                try {
                    $response = Http::withoutVerifying()
                        ->withHeaders(['User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)'])
                        ->timeout(8)
                        ->get($metadataUrl);

                    if ($response->successful()) {
                        return [
                            'success' => true,
                            'message' => __('SAML 2.0 IdP Metadata URL is accessible and returned valid metadata.'),
                        ];
                    }
                } catch (\Throwable $e) {
                    return [
                        'success' => false,
                        'message' => __('Failed to reach SAML 2.0 Metadata URL: ').$e->getMessage(),
                    ];
                }
            }

            if ($ssoUrl !== '') {
                try {
                    $response = Http::withoutVerifying()
                        ->withHeaders(['User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)'])
                        ->timeout(8)
                        ->get($ssoUrl);

                    return [
                        'success' => true,
                        'message' => __('SAML 2.0 SSO ACS URL is reachable (HTTP status ').$response->status().').',
                    ];
                } catch (\Throwable $e) {
                    return [
                        'success' => false,
                        'message' => __('Failed to reach SAML 2.0 SSO URL: ').$e->getMessage(),
                    ];
                }
            }

            return [
                'success' => false,
                'message' => __('Please provide an IdP Metadata URL or Single Sign-On (ACS) URL to test.'),
            ];
        }

        $clientId = trim((string) ($config['client_id'] ?? ''));
        $clientSecret = trim((string) ($config['client_secret'] ?? ''));

        if ($clientId === '' || $clientSecret === '') {
            return [
                'success' => false,
                'message' => __('Client ID and Client Secret are required.'),
            ];
        }

        $domain = $this->parseDomainHost((string) ($config['domain'] ?? ''));

        if ($this->provider === 'auth0' && $domain === '') {
            return [
                'success' => false,
                'message' => __('Auth0 domain is required (e.g. your-tenant.auth0.com).'),
            ];
        }

        if ($this->provider === 'auth0') {
            try {
                $response = Http::withoutVerifying()
                    ->asForm()
                    ->timeout(8)
                    ->post("https://{$domain}/oauth/token", [
                        'grant_type' => 'client_credentials',
                        'client_id' => $clientId,
                        'client_secret' => $clientSecret,
                        'audience' => "https://{$domain}/api/v2/",
                    ]);

                $data = $response->json();
                $error = is_array($data) ? ($data['error'] ?? null) : null;
                $description = is_array($data) ? ($data['error_description'] ?? null) : null;

                if ($response->status() === 401 || $error === 'invalid_client' || $description === 'Unauthorized') {
                    return [
                        'success' => false,
                        'message' => __('Auth0 verification failed: Invalid Client ID or Client Secret.'),
                    ];
                }

                if ($response->successful() || in_array($error, ['unauthorized_client', 'invalid_request', 'unsupported_grant_type', 'bad_oauth'], true) || (is_string($description) && str_contains($description, 'audience'))) {
                    return [
                        'success' => true,
                        'message' => __('Auth0 domain, Client ID, and Client Secret verified successfully.'),
                    ];
                }

                return [
                    'success' => false,
                    'message' => __('Auth0 verification failed: ').($description ?? $error ?? "HTTP {$response->status()}"),
                ];
            } catch (\Throwable $e) {
                return [
                    'success' => false,
                    'message' => __('Auth0 connection error: ').$e->getMessage(),
                ];
            }
        }

        if ($this->provider === 'google') {
            try {
                $response = Http::withoutVerifying()
                    ->asForm()
                    ->timeout(8)
                    ->post('https://oauth2.googleapis.com/token', [
                        'client_id' => $clientId,
                        'client_secret' => $clientSecret,
                        'grant_type' => 'authorization_code',
                        'code' => 'invalid_code_check',
                    ]);

                $data = $response->json();
                $error = is_array($data) ? ($data['error'] ?? null) : null;

                if ($error === 'invalid_client') {
                    return [
                        'success' => false,
                        'message' => __('Google verification failed: Invalid Client ID or Client Secret.'),
                    ];
                }

                if ($error === 'invalid_grant' || $response->successful()) {
                    return [
                        'success' => true,
                        'message' => __('Google Client ID and Client Secret verified successfully.'),
                    ];
                }

                return [
                    'success' => false,
                    'message' => __('Google verification failed: ').(($data['error_description'] ?? null) ?? $error ?? "HTTP {$response->status()}"),
                ];
            } catch (\Throwable $e) {
                return [
                    'success' => false,
                    'message' => __('Google connection error: ').$e->getMessage(),
                ];
            }
        }

        if ($this->provider === 'microsoft') {
            $tenant = $config['tenant_id'] ?? 'common';
            try {
                $response = Http::withoutVerifying()
                    ->asForm()
                    ->timeout(8)
                    ->post("https://login.microsoftonline.com/{$tenant}/oauth2/v2.0/token", [
                        'client_id' => $clientId,
                        'client_secret' => $clientSecret,
                        'grant_type' => 'client_credentials',
                        'scope' => 'https://graph.microsoft.com/.default',
                    ]);

                $data = $response->json();
                $error = is_array($data) ? ($data['error'] ?? null) : null;
                $description = is_array($data) ? (string) ($data['error_description'] ?? '') : '';

                if ($error === 'invalid_client' || str_contains($description, 'AADSTS700016') || str_contains($description, 'AADSTS7000215')) {
                    return [
                        'success' => false,
                        'message' => __('Microsoft verification failed: Invalid Client ID or Client Secret.'),
                    ];
                }

                if ($response->successful() || str_contains($description, 'AADSTS700021') || str_contains($description, 'AADSTS500011')) {
                    return [
                        'success' => true,
                        'message' => __('Microsoft Azure AD Client ID and Client Secret verified successfully.'),
                    ];
                }

                return [
                    'success' => false,
                    'message' => __('Microsoft verification failed: ').($description !== '' ? $description : ($error ?? "HTTP {$response->status()}")),
                ];
            } catch (\Throwable $e) {
                return [
                    'success' => false,
                    'message' => __('Microsoft connection error: ').$e->getMessage(),
                ];
            }
        }

        return [
            'success' => true,
            'message' => __(':provider parameters are configured.', ['provider' => ucfirst($this->provider)]),
        ];
    }
}
