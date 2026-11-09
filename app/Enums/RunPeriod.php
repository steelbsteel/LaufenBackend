<?php

declare(strict_types=1);

namespace App\Enums;

enum RunPeriod: string
{
    case WEEK = 'week';
    case MONTH = 'month';
    case YEAR = 'year';
}
