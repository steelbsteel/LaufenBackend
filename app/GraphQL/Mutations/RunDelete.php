<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations;

use App\Models\User;
use App\Services\RunsService;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

final class RunDelete
{
    public function __construct(private RunsService $runs) {}

    /** @param array{id: string} $args */
    public function __invoke(mixed $_, array $args, GraphQLContext $context): bool
    {
        /** @var User $user */
        $user = $context->user();

        $this->runs->delete($user, (int) $args['id']);

        return true;
    }
}
