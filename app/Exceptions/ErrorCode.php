<?php

declare(strict_types=1);

namespace App\Exceptions;

enum ErrorCode: int
{
    case INVALID_CREDENTIALS = 1001;
    case REFRESH_TOKEN_INVALID = 1002;
}
