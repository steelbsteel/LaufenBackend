<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations;

use App\Entity\AuthTokens;
use App\Services\AuthService;

final class AuthTokenRefresh
{
    public function __construct(private AuthService $auth) {}

    /**
     * @param  array{refreshToken: string}  $args
     */
    public function __invoke(mixed $_, array $args): AuthTokens
    {
        return $this->auth->refresh($args['refreshToken']);
    }
}
