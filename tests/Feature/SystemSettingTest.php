<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\SystemSetting;
use App\Services\SettingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Crypt;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->settingService = app(SettingService::class);
});

test('it casts setting values according to their data types', function (): void {
    // 1. Text
    $textSetting = SystemSetting::create([
        'key' => 'app_slogan',
        'value' => 'Enterprise Starter Kit',
        'type' => 'text',
        'group' => 'general',
    ]);
    expect($textSetting->typed_value)->toBe('Enterprise Starter Kit');

    // 2. Numeric integer & float
    $intSetting = SystemSetting::create([
        'key' => 'session_lifetime',
        'value' => '120',
        'type' => 'numeric',
        'group' => 'general',
    ]);
    expect($intSetting->typed_value)->toBe(120);

    $floatSetting = SystemSetting::create([
        'key' => 'tax_rate',
        'value' => '11.5',
        'type' => 'numeric',
        'group' => 'general',
    ]);
    expect($floatSetting->typed_value)->toBe(11.5);

    // 3. Boolean
    $boolSetting = SystemSetting::create([
        'key' => 'registration_enabled',
        'value' => '1',
        'type' => 'boolean',
        'group' => 'general',
    ]);
    expect($boolSetting->typed_value)->toBeTrue();

    // 4. JSON
    $jsonSetting = SystemSetting::create([
        'key' => 'social_links',
        'value' => json_encode(['github' => 'https://github.com', 'twitter' => 'https://twitter.com']),
        'type' => 'json',
        'group' => 'general',
    ]);
    expect($jsonSetting->typed_value)->toBeArray()
        ->and($jsonSetting->typed_value['github'])->toBe('https://github.com');

    // 5. Encrypted
    $secretSetting = SystemSetting::create([
        'key' => 'api_secret_key',
        'value' => Crypt::encryptString('super-secret-token-12345'),
        'type' => 'encrypted',
        'group' => 'mail',
    ]);
    expect($secretSetting->typed_value)->toBe('super-secret-token-12345');
});

test('it resolves settings with cascading tenant-to-global fallback hierarchy', function (): void {
    $companyA = Company::factory()->create(['name' => 'Acme Holding']);
    $companyB = Company::factory()->create(['name' => 'Beta Logistics']);

    // Global default setting
    $this->settingService->set('default_timezone', 'UTC', 'text', 'localization');

    // Company A override
    $this->settingService->set('default_timezone', 'Asia/Jakarta', 'text', 'localization', $companyA->id);

    // 1. Company A resolves to its override
    expect($this->settingService->get('default_timezone', $companyA->id))->toBe('Asia/Jakarta');

    // 2. Company B has no override, resolves to global setting
    expect($this->settingService->get('default_timezone', $companyB->id))->toBe('UTC');

    // 3. No company specified resolves to global setting
    expect($this->settingService->get('default_timezone'))->toBe('UTC');

    // 4. Unset key resolves to provided default
    expect($this->settingService->get('non_existent_key', $companyA->id, 'default_fallback'))->toBe('default_fallback');
});

test('it invalidates cache on setting update or deletion', function (): void {
    $this->settingService->set('app_name', 'Initial Name', 'text', 'general');

    // Fetch once to populate cache
    expect($this->settingService->get('app_name'))->toBe('Initial Name');

    // Update via service
    $this->settingService->set('app_name', 'Updated Name', 'text', 'general');

    // Cache should reflect updated value immediately
    expect($this->settingService->get('app_name'))->toBe('Updated Name');

    // Delete
    $this->settingService->delete('app_name');
    expect($this->settingService->get('app_name', null, 'Fallback App'))->toBe('Fallback App');
});

test('it dynamically applies runtime mail configuration overrides', function (): void {
    $this->settingService->set('mail_mailer', 'smtp', 'text', 'mail');
    $this->settingService->set('mail_host', 'smtp.custom-mail.com', 'text', 'mail');
    $this->settingService->set('mail_port', 2525, 'numeric', 'mail');
    $this->settingService->set('mail_username', 'custom_user', 'text', 'mail');
    $this->settingService->set('mail_password', 'secret-smtp-pass', 'encrypted', 'mail');
    $this->settingService->set('mail_encryption', 'tls', 'text', 'mail');
    $this->settingService->set('mail_from_address', 'system@custom-domain.com', 'text', 'mail');
    $this->settingService->set('mail_from_name', 'Custom System Mailer', 'text', 'mail');

    $this->settingService->applyRuntimeMailConfig(force: true);

    expect(Config::get('mail.default'))->toBe('smtp')
        ->and(Config::get('mail.mailers.smtp.host'))->toBe('smtp.custom-mail.com')
        ->and(Config::get('mail.mailers.smtp.port'))->toBe(2525)
        ->and(Config::get('mail.mailers.smtp.username'))->toBe('custom_user')
        ->and(Config::get('mail.mailers.smtp.password'))->toBe('secret-smtp-pass')
        ->and(Config::get('mail.mailers.smtp.encryption'))->toBe('tls')
        ->and(Config::get('mail.from.address'))->toBe('system@custom-domain.com')
        ->and(Config::get('mail.from.name'))->toBe('Custom System Mailer');

    Config::set('mail.default', 'array');
});

test('it dynamically applies runtime OAuth mail configuration overrides', function (): void {
    $this->settingService->set('mail_mailer', 'oauth', 'text', 'mail');
    $this->settingService->set('mail_oauth_provider', 'microsoft365', 'text', 'mail');
    $this->settingService->set('mail_oauth_tenant_id', 'tenant-123', 'text', 'mail');
    $this->settingService->set('mail_oauth_client_id', 'client-456', 'text', 'mail');
    $this->settingService->set('mail_oauth_client_secret', 'secret-789', 'encrypted', 'mail');
    $this->settingService->set('mail_from_address', 'oauth@domain.com', 'text', 'mail');
    $this->settingService->set('mail_from_name', 'OAuth Mailer', 'text', 'mail');

    $this->settingService->applyRuntimeMailConfig(force: true);

    expect(Config::get('mail.default'))->toBe('oauth')
        ->and(Config::get('mail.mailers.oauth.transport'))->toBe('oauth')
        ->and(Config::get('mail.from.address'))->toBe('oauth@domain.com')
        ->and(Config::get('mail.from.name'))->toBe('OAuth Mailer');

    Config::set('mail.default', 'array');
});
