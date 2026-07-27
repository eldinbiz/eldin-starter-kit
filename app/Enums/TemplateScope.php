<?php

declare(strict_types=1);

namespace App\Enums;

enum TemplateScope: string
{
    case ThisCompany = 'this_company';
    case ThisCompanyAndSubsidiaries = 'subsidiaries';
    case Global = 'global';

    public function label(): string
    {
        return match ($this) {
            self::ThisCompany => 'This Company',
            self::ThisCompanyAndSubsidiaries => 'This Company & Subsidiaries',
            self::Global => 'Global',
        };
    }
}
