<?php

declare(strict_types=1);

namespace App\GraphQL\Mutations;

use App\Models\Run;
use App\Models\User;
use App\Services\RunsManager;
use Carbon\CarbonInterface;
use Illuminate\Support\Arr;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

final class RunCreate
{
    public function __construct(private RunsManager $runs) {}

    /**
     * @param array{
     *     started_at: CarbonInterface,
     *     duration_sec: int,
     *     distance_m: int,
     *     calories?: int|null,
     *     points: list<array{lat: float, lng: float, recorded_at: CarbonInterface}>
     * } $args
     */
    public function __invoke(mixed $_, array $args, GraphQLContext $context): Run
    {
        /** @var User $user */
        $user = $context->user();

        return $this->runs->create(
            $user,
            Arr::except($args, ['points']),
            $args['points'],
        );
    }
}
