<?php

declare(strict_types=1);

namespace App\Exceptions;

enum ErrorCode: int
{
    // Authentication errors
    case INVALID_CREDENTIALS = 1001;
    case REFRESH_TOKEN_INVALID = 1002;

    // Run errors
    case RUN_NOT_FOUND = 2001;
    case RUN_TRACK_INVALID = 2002;
}
