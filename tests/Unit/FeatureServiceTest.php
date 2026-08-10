<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Company;
use App\Services\FeatureService;
use App\Services\SettingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function (): void {
    app(FeatureService::class)->flush();
});

test('all features default to enabled when no rows exist', function (): void {
    $service = app(FeatureService::class);

    expect($service->isEnabled('approvals'))->toBeTrue()
        ->and($service->isEnabled('audit_logs'))->toBeTrue()
        ->and($service->isEnabled('api_access'))->toBeTrue();
});

test('a global row overrides the config default', function (): void {
    app(SettingService::class)->set('feature_approvals', false, 'boolean', 'feature');

    $service = app(FeatureService::class);
    $service->flush();

    expect($service->isEnabled('approvals'))->toBeFalse();
});

test('a company row overrides the global value', function (): void {
    $companyA = Company::factory()->create();
    $companyB = Company::factory()->create();

    app(SettingService::class)->set('feature_audit_logs', false, 'boolean', 'feature');
    app(SettingService::class)->set('feature_audit_logs', true, 'boolean', 'feature', $companyA->id);

    $service = app(FeatureService::class);
    $service->flush();

    expect($service->isEnabled('audit_logs', $companyA->id))->toBeTrue()
        ->and($service->isEnabled('audit_logs', $companyB->id))->toBeFalse();
});

test('memoization returns consistent results for the same company', function (): void {
    $company = Company::factory()->create();
    app(SettingService::class)->set('feature_api_access', false, 'boolean', 'feature', $company->id);

    $service = app(FeatureService::class);
    $service->flush();

    expect($service->isEnabled('api_access', $company->id))->toBeFalse()
        ->and($service->isEnabled('api_access', $company->id))->toBeFalse();
});

test('unknown feature keys fall back safely', function (): void {
    expect(app(FeatureService::class)->isEnabled('not_a_real_feature'))->toBeFalse();
});

test('enabledFeatures returns only enabled feature keys', function (): void {
    app(SettingService::class)->set('feature_approvals', false, 'boolean', 'feature');

    $service = app(FeatureService::class);
    $service->flush();

    expect($service->enabledFeatures())->toContain('audit_logs')
        ->and($service->enabledFeatures())->toContain('api_access')
        ->and($service->enabledFeatures())->not->toContain('approvals');
});

test('all returns the feature registry', function (): void {
    $features = app(FeatureService::class)->all();

    expect(array_keys($features))->toBe(['approvals', 'audit_logs', 'api_access'])
        ->and($features['approvals']['setting_key'])->toBe('feature_approvals');
});
