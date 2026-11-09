<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\ErrorCode;
use App\Exceptions\GraphQLException;
use App\Models\Run;
use App\Models\RunPoint;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

final class RunsManager
{
    /** Допуск по времени точки относительно начала и конца пробежки. */
    private const TRACK_TIME_TOLERANCE_SEC = 60;

    /**
     * Сколько точек вставлять одним запросом: 1000 × 4 колонки = 4000 параметров.
     * Весь трек одним insert нельзя: 20 000 точек × 4 = 80 000 > лимита PostgreSQL в 65 535 параметров.
     */
    private const POINTS_INSERT_CHUNK = 1000;

    /**
     * @param  array{started_at: CarbonInterface, duration_sec: int, distance_m: int, calories?: int|null}  $data
     * @param  list<array{lat: float, lng: float, recorded_at: CarbonInterface}>  $points
     */
    public function create(User $user, array $data, array $points): Run
    {
        $this->assertTrackIsValid($data, $points);

        return DB::transaction(function () use ($user, $data, $points): Run {
            $run = $user->runs()->create($data);

            $rows = array_map(
                static fn (array $point): array => [
                    'run_id' => $run->id,
                    'lat' => $point['lat'],
                    'lng' => $point['lng'],
                    'recorded_at' => $point['recorded_at'],
                ],
                $points,
            );

            foreach (array_chunk($rows, self::POINTS_INSERT_CHUNK) as $chunk) {
                RunPoint::insert($chunk);
            }

            return $run;
        });
    }

    public function find(User $user, int $id): Run
    {
        return $user->runs()->find($id)
            ?? throw new GraphQLException(ErrorCode::RUN_NOT_FOUND, 'Пробежка не найдена');
    }

    public function delete(User $user, int $id): void
    {
        $this->find($user, $id)->delete();
    }

    /**
     * @param  array{started_at: CarbonInterface, duration_sec: int, distance_m: int, calories?: int|null}  $data
     * @param  list<array{lat: float, lng: float, recorded_at: CarbonInterface}>  $points
     */
    private function assertTrackIsValid(array $data, array $points): void
    {
        $from = $data['started_at']->copy()->subSeconds(self::TRACK_TIME_TOLERANCE_SEC);
        $to = $data['started_at']->copy()->addSeconds($data['duration_sec'] + self::TRACK_TIME_TOLERANCE_SEC);

        foreach ($points as $point) {
            if ($point['recorded_at']->lt($from) || $point['recorded_at']->gt($to)) {
                throw new GraphQLException(ErrorCode::RUN_TRACK_INVALID, 'Время точек трека не совпадает со временем пробежки');
            }
        }
    }
}
