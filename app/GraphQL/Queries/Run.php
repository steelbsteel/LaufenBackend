<?php

declare(strict_types=1);

namespace App\GraphQL\Queries;

use App\Models\Run as RunModel;
use App\Models\User;
use App\Services\RunsManager;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

final class Run
{
    public function __construct(private RunsManager $runs) {}

    /** @param array{id: string} $args */
    public function __invoke(mixed $_, array $args, GraphQLContext $context): RunModel
    {
        /** @var User $user */
        $user = $context->user();

        return $this->runs->find($user, (int) $args['id']);
    }
}
