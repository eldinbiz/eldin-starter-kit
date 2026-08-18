<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\SystemSetting;
use App\Services\Mail\MicrosoftGraphTransport;
use App\Services\Mail\XOauth2SmtpTransport;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SettingService
{
    private const CACHE_PREFIX = 'system_setting:';

    private const CACHE_KEY_ALL = 'system_settings:all_dictionary';

    private const CACHE_TTL_SECONDS = 3600;

    /**
     * In-memory request-level cache of all system settings indexed by scope_key.
     *
     * @var array<string, mixed>|null
     */
    private static ?array $allSettingsCache = null;

    /**
     * Get a setting value by key with cascading tenant-to-global fallback.
     */
    public function get(string $key, ?int $companyId = null, mixed $default = null): mixed
    {
        $settings = $this->getAllSettings();

        // 1. Try company-specific override if company ID provided
        if ($companyId !== null) {
            $companyScopeKey = "company_{$companyId}:{$key}";
            if (array_key_exists($companyScopeKey, $settings) && $settings[$companyScopeKey] !== null) {
                return $settings[$companyScopeKey];
            }
        }

        // 2. Try global system default
        $globalScopeKey = "global:{$key}";
        if (array_key_exists($globalScopeKey, $settings) && $settings[$globalScopeKey] !== null) {
            return $settings[$globalScopeKey];
        }

        // 3. Return provided default or config fallback
        return $default;
    }

    /**
     * Load all settings into an in-memory dictionary.
     *
     * @return array<string, mixed>
     */
    private function getAllSettings(): array
    {
        if (self::$allSettingsCache !== null) {
            return self::$allSettingsCache;
        }

        self::$allSettingsCache = Cache::remember(self::CACHE_KEY_ALL, self::CACHE_TTL_SECONDS, function (): array {
            try {
                $dictionary = [];
                $settings = SystemSetting::all();

                foreach ($settings as $setting) {
                    $scope = $setting->company_id !== null ? "company_{$setting->company_id}" : 'global';
                    $dictionary["{$scope}:{$setting->key}"] = $setting->typed_value;
                }

                return $dictionary;
            } catch (Throwable) {
                return [];
            }
        });

        return self::$allSettingsCache ?? [];
    }

    /**
     * Get typed custom variable value.
     */
    public function custom(string $key, ?int $companyId = null, mixed $default = null): mixed
    {
        return $this->get($key, $companyId, $default);
    }

    /**
     * Set or update a setting record and clear its cache.
     */
    public function set(
        string $key,
        mixed $value,
        string $type = 'text',
        string $group = 'general',
        ?int $companyId = null,
        ?string $description = null
    ): SystemSetting {
        $storedValue = $this->serializeValue($value, $type);

        $setting = SystemSetting::updateOrCreate(
            [
                'company_id' => $companyId,
                'key' => $key,
            ],
            [
                'value' => $storedValue,
                'type' => $type,
                'group' => $group,
                'description' => $description,
            ]
        );

        $this->forgetCache($key, $companyId);

        return $setting;
    }

    /**
     * Delete a setting record and clear its cache.
     */
    public function delete(string $key, ?int $companyId = null): bool
    {
        $deleted = (bool) SystemSetting::where('company_id', $companyId)
            ->where('key', $key)
            ->delete();

        $this->forgetCache($key, $companyId);

        return $deleted;
    }

    /**
     * Get all settings in a specific group.
     *
     * @return Collection<int, SystemSetting>
     */
    public function getGroup(string $group, ?int $companyId = null): Collection
    {
        return SystemSetting::forGroup($group)
            ->forCompany($companyId)
            ->get();
    }

    /**
     * Get all custom variables.
     *
     * @return Collection<int, SystemSetting>
     */
    public function getCustomVariables(?int $companyId = null): Collection
    {
        $query = SystemSetting::customVariables();

        if ($companyId !== null) {
            $query->where(function ($q) use ($companyId): void {
                $q->where('company_id', $companyId)->orWhereNull('company_id');
            });
        } else {
            $query->whereNull('company_id');
        }

        return $query->with('company')->orderBy('key')->get();
    }

    /**
     * Apply runtime application configuration overrides from database settings.
     */
    public function applyRuntimeAppConfig(): void
    {
        try {
            $appName = (string) $this->get('app_name', null, Config::get('app.name', 'Scaffolding App'));
            if ($appName !== '') {
                Config::set('app.name', $appName);
            }
        } catch (Throwable) {
            // Ignore when database is not migrated yet
        }
    }

    /**
     * Apply runtime mail configuration overrides from database settings.
     */
    public function applyRuntimeMailConfig(bool $force = false): void
    {
        if (! $force && app()->environment('testing')) {
            return;
        }

        try {
            $mailer = (string) $this->get('mail_mailer', null, Config::get('mail.default', 'smtp'));
            $host = (string) $this->get('mail_host', null, Config::get('mail.mailers.smtp.host'));
            $port = $this->get('mail_port', null, Config::get('mail.mailers.smtp.port'));
            $username = (string) $this->get('mail_username', null, Config::get('mail.mailers.smtp.username'));
            $password = (string) $this->get('mail_password', null, Config::get('mail.mailers.smtp.password'));
            $encryption = (string) $this->get('mail_encryption', null, Config::get('mail.mailers.smtp.encryption'));
            $fromAddress = (string) $this->get('mail_from_address', null, Config::get('mail.from.address'));
            $fromName = (string) $this->get('mail_from_name', null, Config::get('mail.from.name'));

            if ($fromAddress !== '') {
                Config::set('mail.from.address', $fromAddress);
            }
            if ($fromName !== '') {
                Config::set('mail.from.name', $fromName);
            }

            if ($mailer === 'oauth') {
                $provider = (string) $this->get('mail_oauth_provider', null, 'microsoft365');
                $tenantId = (string) $this->get('mail_oauth_tenant_id', null, '');
                $clientId = (string) $this->get('mail_oauth_client_id', null, '');
                $clientSecret = (string) $this->get('mail_oauth_client_secret', null, '');
                $refreshToken = (string) $this->get('mail_oauth_refresh_token', null, '');
                $tokenUrl = (string) $this->get('mail_oauth_token_url', null, '');

                Mail::extend('oauth', function () use (
                    $provider,
                    $tenantId,
                    $clientId,
                    $clientSecret,
                    $refreshToken,
                    $tokenUrl,
                    $fromAddress,
                    $host,
                    $port,
                    $encryption
                ) {
                    if ($provider === 'microsoft365') {
                        return new MicrosoftGraphTransport($tenantId, $clientId, $clientSecret, $fromAddress);
                    }

                    if ($provider === 'google') {
                        return new XOauth2SmtpTransport(
                            'smtp.gmail.com',
                            587,
                            true,
                            $clientId,
                            $clientSecret,
                            $refreshToken,
                            'https://oauth2.googleapis.com/token',
                            $fromAddress
                        );
                    }

                    return new XOauth2SmtpTransport(
                        $host ?: 'localhost',
                        (int) ($port ?: 587),
                        $encryption !== 'none',
                        $clientId,
                        $clientSecret,
                        $refreshToken,
                        $tokenUrl,
                        $fromAddress
                    );
                });

                Config::set('mail.default', 'oauth');
                Config::set('mail.mailers.oauth.transport', 'oauth');
            } else {
                Config::set('mail.default', $mailer);

                if ($host !== '') {
                    Config::set('mail.mailers.smtp.host', $host);
                }
                if ($port !== null && $port !== '') {
                    Config::set('mail.mailers.smtp.port', (int) $port);
                }
                Config::set('mail.mailers.smtp.username', $username !== '' ? $username : null);
                Config::set('mail.mailers.smtp.password', $password !== '' ? $password : null);
                Config::set('mail.mailers.smtp.encryption', ($encryption === '' || $encryption === 'none') ? null : $encryption);
            }
        } catch (Throwable) {
            // Ignore when database is not migrated yet
        }
    }

    /**
     * Get cached typed setting value.
     */
    private function getCached(string $key, ?int $companyId): mixed
    {
        return $this->get($key, $companyId);
    }

    /**
     * Forget setting cache.
     */
    public function forgetCache(?string $key = null, ?int $companyId = null): void
    {
        self::$allSettingsCache = null;
        Cache::forget(self::CACHE_KEY_ALL);

        if ($key !== null) {
            Cache::forget($this->cacheKey($key, $companyId));
        }
    }

    /**
     * Clear all in-memory runtime cache.
     */
    public function flushRuntimeCache(): void
    {
        self::$allSettingsCache = null;
        Cache::forget(self::CACHE_KEY_ALL);
    }

    /**
     * Generate cache key for setting.
     */
    private function cacheKey(string $key, ?int $companyId): string
    {
        $scope = $companyId !== null ? "company_{$companyId}" : 'global';

        return self::CACHE_PREFIX."{$scope}:{$key}";
    }

    /**
     * Serialize input value based on setting type.
     */
    private function serializeValue(mixed $value, string $type): ?string
    {
        if ($value === null) {
            return null;
        }

        return match ($type) {
            'json' => is_string($value) ? $value : json_encode($value, JSON_THROW_ON_ERROR),
            'encrypted' => is_string($value) && $value !== '' ? Crypt::encryptString($value) : null,
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN) ? '1' : '0',
            'numeric' => (string) $value,
            default => (string) $value,
        };
    }
}
