<?php

declare(strict_types=1);

namespace App\GraphQL\Builders;

use App\Enums\RunPeriod;
use App\Models\Run;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;

final class RunPeriodFilter
{
    /**
     * Lighthouse вызывает builder и для явно переданного `period: null` — тогда фильтра нет.
     *
     * @param  Builder<Run>  $builder
     * @return Builder<Run>
     */
    public function apply(Builder $builder, ?string $period): Builder
    {
        if ($period === null) {
            return $builder;
        }

        $from = match (RunPeriod::from($period)) {
            RunPeriod::WEEK => now()->startOfWeek(CarbonInterface::MONDAY),
            RunPeriod::MONTH => now()->startOfMonth(),
            RunPeriod::YEAR => now()->startOfYear(),
        };

        return $builder->where('started_at', '>=', $from);
    }
}
