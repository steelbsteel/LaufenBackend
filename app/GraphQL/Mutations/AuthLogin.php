<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations;

use App\Entity\AuthTokens;
use App\Services\AuthService;

final class AuthLogin
{
    public function __construct(private AuthService $auth) {}

    /**
     * @param  array{email: string, password: string}  $args
     */
    public function __invoke(mixed $_, array $args): AuthTokens
    {
        return $this->auth->login($args['email'], $args['password']);
    }
}
