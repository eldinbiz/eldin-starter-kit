<?php

declare(strict_types=1);

namespace App\Enums;

enum ResolverType: string
{
    case NPlus1 = 'N+1';
    case SpecificUser = 'specific_user';
    case NPlus1OfPrevious = 'N+1_of_previous';
}
