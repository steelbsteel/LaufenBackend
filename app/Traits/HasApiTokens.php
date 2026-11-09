<?php

declare(strict_types=1);

namespace App\Traits;

use Laravel\Sanctum\HasApiTokens as SanctumHasApiTokens;
use Laravel\Sanctum\NewAccessToken;

trait HasApiTokens
{
    use SanctumHasApiTokens;

    public function createAuthToken(): NewAccessToken
    {
        return $this->createToken(
            'auth',
            ['auth'],
            now()->addMinutes((int) config('sanctum.auth_token_expiration')),
        );
    }

    public function createRefreshToken(): NewAccessToken
    {
        return $this->createToken(
            'refresh',
            ['refresh'],
            now()->addMinutes((int) config('sanctum.refresh_token_expiration')),
        );
    }
}
