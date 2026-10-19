<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations;

use App\Models\User;
use App\Services\AuthService;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

final class Logout
{
    public function __construct(private AuthService $auth) {}

    /**
     * @param  array{refreshToken: string}  $args
     */
    public function __invoke(mixed $_, array $args, GraphQLContext $context): bool
    {
        /** @var User $user */
        $user = $context->user();

        $this->auth->logout($user, $args['refreshToken']);

        return true;
    }
}
