<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Services\SettingService;
use Carbon\Carbon;
use DateTimeInterface;

class DateFormatter
{
    /**
     * Format a date string or DateTime instance according to System Settings date format and timezone.
     */
    public static function formatDate(?DateTimeInterface $date, ?int $companyId = null): string
    {
        if ($date === null) {
            return '—';
        }

        $companyId ??= auth()->user()?->getActiveCompany()?->id;

        $settingService = app(SettingService::class);
        $timezone = (string) $settingService->get('default_timezone', $companyId, config('app.timezone', 'UTC'));
        $dateFormat = (string) $settingService->get('date_format', $companyId, 'Y-m-d');

        return Carbon::instance($date)
            ->setTimezone($timezone)
            ->format($dateFormat);
    }

    /**
     * Format a date and time string or DateTime instance according to System Settings date_format, time_format (12h/24h), and timezone.
     */
    public static function formatDateTime(?DateTimeInterface $date, ?int $companyId = null): string
    {
        if ($date === null) {
            return '—';
        }

        $companyId ??= auth()->user()?->getActiveCompany()?->id;

        $settingService = app(SettingService::class);
        $timezone = (string) $settingService->get('default_timezone', $companyId, config('app.timezone', 'UTC'));
        $dateFormat = (string) $settingService->get('date_format', $companyId, 'Y-m-d');
        $timeFormatSetting = (string) $settingService->get('time_format', $companyId, '24h');

        $timePattern = $timeFormatSetting === '12h' ? 'h:i A' : 'H:i';
        $fullPattern = "{$dateFormat} {$timePattern}";

        return Carbon::instance($date)
            ->setTimezone($timezone)
            ->format($fullPattern);
    }
}
