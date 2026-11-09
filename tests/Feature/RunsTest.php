<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Run;
use App\Models\RunPoint;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Testing\TestResponse;
use Nuwave\Lighthouse\Testing\MakesGraphQLRequests;
use Tests\TestCase;

final class RunsTest extends TestCase
{
    use MakesGraphQLRequests;
    use RefreshDatabase;

    private const RUN_CREATE = /** @lang GraphQL */ '
        mutation ($input: RunCreateInput!) {
            runCreate(input: $input) {
                id
                startedAt
                durationSec
                distanceM
                avgPaceSecPerKm
                calories
            }
        }
    ';

    protected function setUp(): void
    {
        parent::setUp();

        // Среда, 11 ноября 2026, 12:00 UTC. Неделя началась 9-го, месяц — 1-го.
        $this->travelTo(Carbon::parse('2026-11-11 12:00:00'));
    }

    // ---------- Создание ----------

    public function test_create_saves_run_with_track(): void
    {
        $user = User::factory()->create();

        $response = $this->graphQLAs($user, self::RUN_CREATE, ['input' => $this->runInput()]);

        $response->assertJsonPath('data.runCreate.startedAt', '2026-11-11 10:00:00');
        $response->assertJsonPath('data.runCreate.distanceM', 5000);
        $response->assertJsonPath('data.runCreate.avgPaceSecPerKm', 360);
        $response->assertJsonPath('data.runCreate.calories', null);

        $run = Run::sole();
        $this->assertSame($user->id, $run->user_id);
        $this->assertSame(3, $run->points()->count());
    }

    public function test_create_requires_access_token(): void
    {
        $response = $this->graphQL(self::RUN_CREATE, ['input' => $this->runInput()]);

        $response->assertJsonPath('errors.0.message', 'Unauthenticated.');
        $this->assertSame(0, Run::count());
    }

    public function test_create_validates_coordinates_and_track_length(): void
    {
        $user = User::factory()->create();
        $input = $this->runInput();
        $input['points'] = [['lat' => 1000, 'lng' => 49.1, 'recordedAt' => '2026-11-11 10:00:10']];

        $response = $this->graphQLAs($user, self::RUN_CREATE, ['input' => $input]);

        $errors = $response->json('errors.0.extensions.validation');
        $this->assertArrayHasKey('input.points', $errors);
        $this->assertArrayHasKey('input.points.0.lat', $errors);
        $this->assertSame(0, Run::count());
    }

    public function test_create_rejects_track_outside_run_time_and_saves_nothing(): void
    {
        $user = User::factory()->create();
        $input = $this->runInput();
        $input['points'][0]['recordedAt'] = '2026-11-01 00:00:00';

        $response = $this->graphQLAs($user, self::RUN_CREATE, ['input' => $input]);

        $response->assertJsonPath('errors.0.extensions.code', 2002);
        $this->assertSame(0, Run::count());
        $this->assertSame(0, RunPoint::count());
    }

    // ---------- Список ----------

    public function test_runs_returns_only_own_runs_newest_first(): void
    {
        $user = User::factory()->create();
        $older = Run::factory()->for($user)->create(['started_at' => '2026-11-09 08:00:00']);
        $newer = Run::factory()->for($user)->create(['started_at' => '2026-11-10 08:00:00']);
        Run::factory()->create(['started_at' => '2026-11-10 09:00:00']); // чужая

        $response = $this->graphQLAs($user, '{ runs { data { id } } }');

        $this->assertSame(
            [(string) $newer->id, (string) $older->id],
            $response->json('data.runs.data.*.id'),
        );
    }

    public function test_runs_filters_by_period(): void
    {
        $user = User::factory()->create();
        Run::factory()->for($user)->create(['started_at' => '2026-11-10 08:00:00']); // эта неделя
        Run::factory()->for($user)->create(['started_at' => '2026-11-03 08:00:00']); // этот месяц
        Run::factory()->for($user)->create(['started_at' => '2026-09-15 08:00:00']); // этот год
        Run::factory()->for($user)->create(['started_at' => '2025-12-01 08:00:00']); // прошлый год

        $response = $this->graphQLAs($user, '
            {
                week: runs(period: WEEK) { data { id } }
                month: runs(period: MONTH) { data { id } }
                year: runs(period: YEAR) { data { id } }
                all: runs { data { id } }
            }
        ');

        $this->assertCount(1, $response->json('data.week.data'));
        $this->assertCount(2, $response->json('data.month.data'));
        $this->assertCount(3, $response->json('data.year.data'));
        $this->assertCount(4, $response->json('data.all.data'));
    }

    public function test_runs_paginates_and_limits_page_size(): void
    {
        $user = User::factory()->create();
        Run::factory()->count(25)->for($user)->create();

        $firstPage = $this->graphQLAs($user, '{ runs { data { id } paginatorInfo { hasMorePages } } }');
        $this->assertCount(20, $firstPage->json('data.runs.data'));
        $firstPage->assertJsonPath('data.runs.paginatorInfo.hasMorePages', true);

        $tooMany = $this->graphQLAs($user, '{ runs(first: 100) { data { id } } }');
        $this->assertNotNull($tooMany->json('errors'));
    }

    // ---------- Одна пробежка ----------

    public function test_run_returns_track_in_time_order(): void
    {
        $user = User::factory()->create();
        $run = Run::factory()->for($user)->create();
        RunPoint::factory()->for($run)->create(['recorded_at' => '2026-11-10 08:20:00']);
        RunPoint::factory()->for($run)->create(['recorded_at' => '2026-11-10 08:10:00']);

        $response = $this->graphQLAs($user, '
            query ($id: ID!) { run(id: $id) { points { recordedAt } } }
        ', ['id' => $run->id]);

        $this->assertSame(
            ['2026-11-10 08:10:00', '2026-11-10 08:20:00'],
            $response->json('data.run.points.*.recordedAt'),
        );
    }

    public function test_run_of_another_user_is_not_found(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();
        $run = Run::factory()->for($owner)->create();

        $response = $this->graphQLAs($stranger, '
            query ($id: ID!) { run(id: $id) { id } }
        ', ['id' => $run->id]);

        $response->assertJsonPath('errors.0.extensions.code', 2001);
    }

    // ---------- Удаление ----------

    public function test_delete_removes_run_with_track(): void
    {
        $user = User::factory()->create();
        $run = Run::factory()->for($user)->create();
        RunPoint::factory()->count(3)->for($run)->create();

        $response = $this->graphQLAs($user, '
            mutation ($id: ID!) { runDelete(id: $id) }
        ', ['id' => $run->id]);

        $response->assertJsonPath('data.runDelete', true);
        $this->assertModelMissing($run);
        $this->assertSame(0, RunPoint::count());
    }

    public function test_delete_of_another_users_run_is_not_found_and_keeps_it(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();
        $run = Run::factory()->for($owner)->create();

        $response = $this->graphQLAs($stranger, '
            mutation ($id: ID!) { runDelete(id: $id) }
        ', ['id' => $run->id]);

        $response->assertJsonPath('errors.0.extensions.code', 2001);
        $this->assertModelExists($run);
    }

    // ---------- Помощники ----------

    /** GraphQL-запрос от имени пользователя с настоящим access-токеном. */
    private function graphQLAs(User $user, string $query, array $variables = []): TestResponse
    {
        $token = $user->createAuthToken()->plainTextToken;

        return $this->graphQL($query, $variables, [], ['Authorization' => "Bearer {$token}"]);
    }

    /** Корректная пробежка: 5 км за 30 минут, начало сегодня в 10:00 UTC. */
    private function runInput(): array
    {
        return [
            'startedAt' => '2026-11-11 10:00:00',
            'durationSec' => 1800,
            'distanceM' => 5000,
            'points' => [
                ['lat' => 55.79, 'lng' => 49.10, 'recordedAt' => '2026-11-11 10:00:10'],
                ['lat' => 55.795, 'lng' => 49.105, 'recordedAt' => '2026-11-11 10:15:00'],
                ['lat' => 55.80, 'lng' => 49.11, 'recordedAt' => '2026-11-11 10:29:50'],
            ],
        ];
    }
}
