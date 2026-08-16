<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Helpers\DateFormatter;
use App\Models\Company;
use App\Services\SettingService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DateFormatterTest extends TestCase
{
    use RefreshDatabase;

    public function test_format_date_returns_dash_when_date_is_null(): void
    {
        $this->assertSame('—', DateFormatter::formatDate(null));
        $this->assertSame('—', DateFormatter::formatDateTime(null));
    }

    public function test_format_date_applies_system_date_format_and_timezone(): void
    {
        $settingService = app(SettingService::class);
        $settingService->set('date_format', 'd/m/Y', 'text', 'localization');
        $settingService->set('default_timezone', 'Asia/Jakarta', 'text', 'localization');

        // 2026-08-11 12:00:00 UTC = 2026-08-11 19:00:00 Asia/Jakarta (+7)
        $utcDate = Carbon::create(2026, 8, 11, 12, 0, 0, 'UTC');

        $formatted = DateFormatter::formatDate($utcDate);

        $this->assertSame('11/08/2026', $formatted);
    }

    public function test_format_datetime_applies_12h_and_24h_time_formats(): void
    {
        $settingService = app(SettingService::class);
        $settingService->set('date_format', 'Y-m-d', 'text', 'localization');
        $settingService->set('default_timezone', 'America/New_York', 'text', 'localization');

        // 2026-08-11 19:30:00 UTC = 2026-08-11 15:30:00 America/New_York (-4)
        $utcDate = Carbon::create(2026, 8, 11, 19, 30, 0, 'UTC');

        // 24h test
        $settingService->set('time_format', '24h', 'text', 'localization');
        $this->assertSame('2026-08-11 15:30', DateFormatter::formatDateTime($utcDate));

        // 12h test
        $settingService->set('time_format', '12h', 'text', 'localization');
        $this->assertSame('2026-08-11 03:30 PM', DateFormatter::formatDateTime($utcDate));
    }

    public function test_format_date_respects_tenant_timezone_and_date_format_overrides(): void
    {
        $settingService = app(SettingService::class);
        $settingService->set('date_format', 'Y-m-d', 'text', 'localization');
        $settingService->set('default_timezone', 'UTC', 'text', 'localization');

        $company = Company::factory()->create();
        $settingService->set('date_format', 'm/d/Y', 'text', 'localization', $company->id);
        $settingService->set('default_timezone', 'America/New_York', 'text', 'localization', $company->id);

        $utcDate = Carbon::create(2026, 8, 11, 19, 30, 0, 'UTC');

        // Company override: 19:30 UTC -> 15:30 EDT on 2026-08-11 -> format m/d/Y
        $this->assertSame('08/11/2026', DateFormatter::formatDate($utcDate, $company->id));

        // Global default without company override -> format Y-m-d
        $this->assertSame('2026-08-11', DateFormatter::formatDate($utcDate));
    }
}

