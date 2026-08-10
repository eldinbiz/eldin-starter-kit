<?php

declare(strict_types=1);

namespace App\Services;

class FeatureService
{
    /**
     * Per-request memoized resolution results.
     *
     * @var array<string, bool>
     */
    private static array $memoized = [];

    public function __construct(
        private readonly SettingService $settingService
    ) {}

    /**
     * Resolve whether a feature is enabled for a company.
     */
    public function isEnabled(string $feature, ?int $companyId = null): bool
    {
        $definition = config("features.{$feature}");

        if (! is_array($definition)) {
            return false;
        }

        $companyId ??= auth()->user()?->getActiveCompany()?->id;

        $memoKey = $companyId === null ? "{$feature}:global" : "{$feature}:{$companyId}";

        if (array_key_exists($memoKey, self::$memoized)) {
            return self::$memoized[$memoKey];
        }

        $enabled = (bool) $this->settingService->get(
            $definition['setting_key'],
            $companyId,
            $definition['default_enabled'] ?? true,
        );

        return self::$memoized[$memoKey] = $enabled;
    }

    /**
     * List of feature keys currently enabled for a company.
     *
     * @return list<string>
     */
    public function enabledFeatures(?int $companyId = null): array
    {
        $enabled = [];

        foreach (array_keys($this->all()) as $key) {
            if ($this->isEnabled($key, $companyId)) {
                $enabled[] = $key;
            }
        }

        return $enabled;
    }

    /**
     * The feature registry.
     *
     * @return array<string, array{label: string, description: string, default_enabled: bool, bypass_permissions: list<string>, setting_key: string}>
     */
    public function all(): array
    {
        return config('features', []);
    }

    /**
     * Clear the per-request memoization (used when toggles change mid-request).
     */
    public function flush(): void
    {
        self::$memoized = [];
    }
}
