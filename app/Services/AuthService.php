<?php

declare(strict_types=1);

namespace App\Services;

use App\Entity\AuthTokens;
use App\Exceptions\ErrorCode;
use App\Exceptions\GraphQLException;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;

final class AuthService
{
    /**
     * @param  array{name: string, email: string, password: string}  $data
     */
    public function register(array $data): AuthTokens
    {
        return DB::transaction(function () use ($data): AuthTokens {
            $user = User::create($data);

            return $this->issueTokens($user);
        });
    }

    public function login(string $email, string $password): AuthTokens
    {
        $user = User::where('email', $email)->first();

        if ($user === null || ! Hash::check($password, $user->password)) {
            throw new GraphQLException(ErrorCode::INVALID_CREDENTIALS, 'Неверный email или пароль');
        }

        return $this->issueTokens($user);
    }

    public function refresh(string $refreshToken): AuthTokens
    {
        $token = PersonalAccessToken::findToken($refreshToken);

        if ($token === null || $token->cant('refresh') || $token->expires_at?->isPast()) {
            throw new GraphQLException(ErrorCode::REFRESH_TOKEN_INVALID, 'Сессия истекла, войдите снова');
        }

        /** @var User $user */
        $user = $token->tokenable;

        return DB::transaction(function () use ($token, $user): AuthTokens {
            $token->delete();

            return $this->issueTokens($user);
        });
    }

    public function logout(User $user): void
    {
        $token = $user->currentAccessToken();

        if ($token instanceof PersonalAccessToken) {
            $token->delete();
        }
    }

    private function issueTokens(User $user): AuthTokens
    {
        return new AuthTokens(
            accessToken: $user->createAuthToken()->plainTextToken,
            refreshToken: $user->createRefreshToken()->plainTextToken,
            user: $user,
        );
    }
}
