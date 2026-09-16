<?php

declare(strict_types=1);

namespace Tests;

use App\Services\FeatureService;
use App\Services\SettingService;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function setUp(): void
    {
        parent::setUp();

        app(SettingService::class)->flushRuntimeCache();
        app(FeatureService::class)->flush();
    }

    protected function tearDown(): void
    {
        app(SettingService::class)->flushRuntimeCache();
        app(FeatureService::class)->flush();

        parent::tearDown();
    }
}
