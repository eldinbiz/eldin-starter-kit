<?php

declare(strict_types=1);

namespace App\Enums;

use App\Models\Company;

enum TemplateScope: string
{
    case ThisCompany = 'this_company';
    case ThisCompanyAndSubsidiaries = 'subsidiaries';
    case Global = 'global';

    public function label(?Company $company = null): string
    {
        return match ($this) {
            self::ThisCompany => $company
                ? "This Company ({$company->name})"
                : 'This Company',
            self::ThisCompanyAndSubsidiaries => $this->subsidiariesLabel($company),
            self::Global => 'Global (All Companies)',
        };
    }

    private function subsidiariesLabel(?Company $company = null): string
    {
        $base = 'This Company & Subsidiaries';

        if (! $company) {
            return $base;
        }

        $company->loadMissing('children');
        $childNames = $company->children->pluck('name');

        if ($childNames->isEmpty()) {
            return "{$base} ({$company->name})";
        }

        $names = $childNames->take(3)->join(', ');
        $suffix = $childNames->count() > 3 ? ', ...' : '';

        return "{$base} ({$company->name} → {$names}{$suffix})";
    }
}
