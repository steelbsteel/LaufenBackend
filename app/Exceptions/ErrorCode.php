<?php

namespace App\Exceptions;

enum ErrorCode: int
{
    case INVALID_CREDENTIALS = 1001;
    case REFRESH_TOKEN_EXPIRED = 1002;
}