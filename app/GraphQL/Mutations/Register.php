<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations;

use App\Entity\AuthTokens;
use App\Services\AuthService;

final class Register
{
    public function __construct(private AuthService $auth) {}

    /**
     * @param  array{name: string, email: string, password: string}  $args
     */
    public function __invoke(mixed $_, array $args): AuthTokens
    {
        return $this->auth->register($args);
    }
}
