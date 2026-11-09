<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Exceptions\ErrorCode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Testing\TestResponse;
use Laravel\Sanctum\PersonalAccessToken;
use Nuwave\Lighthouse\Testing\MakesGraphQLRequests;
use Tests\TestCase;

final class AuthTest extends TestCase
{
    use MakesGraphQLRequests;
    use RefreshDatabase;

    private const PAYLOAD = 'accessToken refreshToken user { id email }';

    public function test_register_creates_user_and_returns_tokens(): void
    {
        $response = $this->graphQL(/** @lang GraphQL */ '
            mutation ($input: RegisterInput!) {
                authRegister(input: $input) { '.self::PAYLOAD.' }
            }
        ', [
            'input' => ['name' => 'Runner', 'email' => 'runner@example.com', 'password' => 'secret123'],
        ]);

        $response->assertJsonPath('data.authRegister.user.email', 'runner@example.com');
        $this->assertNotEmpty($response->json('data.authRegister.accessToken'));
        $this->assertNotEmpty($response->json('data.authRegister.refreshToken'));

        $user = User::where('email', 'runner@example.com')->firstOrFail();
        $this->assertTrue(Hash::check('secret123', $user->password));
    }

    public function test_register_rejects_taken_email(): void
    {
        User::factory()->create(['email' => 'runner@example.com']);

        $this->graphQL(/** @lang GraphQL */ '
            mutation ($input: RegisterInput!) {
                authRegister(input: $input) { accessToken }
            }
        ', [
            'input' => ['name' => 'Runner', 'email' => 'runner@example.com', 'password' => 'secret123'],
        ])->assertGraphQLValidationKeys(['input.email']);
    }

    public function test_login_returns_tokens(): void
    {
        User::factory()->create(['email' => 'runner@example.com', 'password' => 'secret123']);

        $this->login('runner@example.com', 'secret123')
            ->assertJsonPath('data.authLogin.user.email', 'runner@example.com');
    }

    public function test_login_with_wrong_password_fails(): void
    {
        User::factory()->create(['email' => 'runner@example.com', 'password' => 'secret123']);

        $this->login('runner@example.com', 'wrong-password')
            ->assertJsonPath('errors.0.extensions.code', ErrorCode::INVALID_CREDENTIALS->value);
    }

    public function test_me_returns_current_user(): void
    {
        $user = User::factory()->create();

        $this->withToken($user->createAuthToken()->plainTextToken)
            ->graphQL(/** @lang GraphQL */ '{ me { id } }')
            ->assertJsonPath('data.me.id', (string) $user->id);
    }

    public function test_me_requires_token(): void
    {
        $this->graphQL(/** @lang GraphQL */ '{ me { id } }')
            ->assertGraphQLErrorMessage('Unauthenticated.');
    }

    public function test_me_rejects_refresh_token(): void
    {
        $user = User::factory()->create();

        $this->withToken($user->createRefreshToken()->plainTextToken)
            ->graphQL(/** @lang GraphQL */ '{ me { id } }')
            ->assertGraphQLErrorMessage('Unauthenticated.');
    }

    public function test_refresh_rotates_refresh_token(): void
    {
        $user = User::factory()->create();
        $refresh = $user->createRefreshToken();

        $this->refresh($refresh->plainTextToken)
            ->assertJsonPath('data.authTokenRefresh.user.id', (string) $user->id);

        $this->assertModelMissing($refresh->accessToken);
    }

    public function test_refresh_rejects_access_token(): void
    {
        $user = User::factory()->create();

        $this->refresh($user->createAuthToken()->plainTextToken)
            ->assertJsonPath('errors.0.extensions.code', ErrorCode::REFRESH_TOKEN_INVALID->value);
    }

    public function test_refresh_rejects_expired_token(): void
    {
        $user = User::factory()->create();
        $refresh = $user->createRefreshToken()->plainTextToken;

        $this->travel((int) config('sanctum.refresh_token_expiration') + 1)->minutes();

        $this->refresh($refresh)
            ->assertJsonPath('errors.0.extensions.code', ErrorCode::REFRESH_TOKEN_INVALID->value);
    }

    public function test_logout_deletes_both_tokens(): void
    {
        $user = User::factory()->create();
        $access = $user->createAuthToken();
        $refresh = $user->createRefreshToken();

        $this->withToken($access->plainTextToken)
            ->graphQL(/** @lang GraphQL */ '
                mutation ($refreshToken: String!) {
                    authLogout(refreshToken: $refreshToken)
                }
            ', ['refreshToken' => $refresh->plainTextToken])
            ->assertJsonPath('data.authLogout', true);

        $this->assertSame(0, PersonalAccessToken::count());
    }

    public function test_logout_requires_token(): void
    {
        $this->graphQL(/** @lang GraphQL */ '
            mutation { authLogout(refreshToken: "any") }
        ')->assertGraphQLErrorMessage('Unauthenticated.');
    }

    private function login(string $email, string $password): TestResponse
    {
        return $this->graphQL(/** @lang GraphQL */ '
            mutation ($email: String!, $password: String!) {
                authLogin(email: $email, password: $password) { '.self::PAYLOAD.' }
            }
        ', ['email' => $email, 'password' => $password]);
    }

    private function refresh(string $refreshToken): TestResponse
    {
        return $this->graphQL(/** @lang GraphQL */ '
            mutation ($refreshToken: String!) {
                authTokenRefresh(refreshToken: $refreshToken) { '.self::PAYLOAD.' }
            }
        ', ['refreshToken' => $refreshToken]);
    }
}
