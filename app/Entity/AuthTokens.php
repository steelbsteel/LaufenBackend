<?php

declare(strict_types=1);

namespace App\Entity;

use App\Models\User;

final readonly class AuthTokens
{
    public function __construct(
        public string $accessToken,
        public string $refreshToken,
        public User $user,
    ) {}
}
