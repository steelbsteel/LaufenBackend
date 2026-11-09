<?php

declare(strict_types=1);

namespace App\GraphQL\Builders;

use App\Models\Run;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;

final class RunPeriodFilter
{
    /**
     * @param  Builder<Run>  $builder
     * @return Builder<Run>
     */
    public function apply(Builder $builder, string $period): Builder
    {
        $from = match ($period) {
            'WEEK' => now()->startOfWeek(CarbonInterface::MONDAY),
            'MONTH' => now()->startOfMonth(),
            'YEAR' => now()->startOfYear(),
        };

        return $builder->where('started_at', '>=', $from);
    }
}
