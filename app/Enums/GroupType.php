<?php

declare(strict_types=1);

namespace App\Enums;

enum GroupType: string
{
    case Sequential = 'sequential';
    case Parallel = 'parallel';
}
