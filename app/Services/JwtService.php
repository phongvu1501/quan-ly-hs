<?php

namespace App\Services;

use App\Models\User;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class JwtService
{
    public function issue(User $user, bool $remember = false): string
    {
        $issuedAt = now()->timestamp;
        $ttl = $this->ttl($remember);

        return JWT::encode([
            'iss' => config('app.url'),
            'aud' => config('app.url'),
            'sub' => (string) $user->getKey(),
            'iat' => $issuedAt,
            'nbf' => $issuedAt,
            'exp' => $issuedAt + ($ttl * 60),
            'jti' => (string) Str::uuid(),
            'ver' => (int) $user->jwt_version,
        ], $this->key(), config('jwt.algorithm', 'HS256'));
    }

    public function userFromToken(?string $token): ?User
    {
        if (blank($token)) {
            return null;
        }

        try {
            $claims = JWT::decode($token, new Key($this->key(), config('jwt.algorithm', 'HS256')));
            if (($claims->iss ?? null) !== config('app.url') || ($claims->aud ?? null) !== config('app.url')) {
                return null;
            }

            $user = User::find((int) ($claims->sub ?? 0));
            if (! $user || (int) ($claims->ver ?? -1) !== (int) $user->jwt_version) {
                return null;
            }

            return $user;
        } catch (Throwable) {
            return null;
        }
    }

    public function ttl(bool $remember = false): int
    {
        return max(1, (int) config($remember ? 'jwt.remember_ttl' : 'jwt.ttl'));
    }

    public function cookieName(): string
    {
        return (string) config('jwt.cookie', 'school_access_token');
    }

    private function key(): string
    {
        $key = (string) config('jwt.secret');
        if (str_starts_with($key, 'base64:')) {
            $decoded = base64_decode(substr($key, 7), true);
            $key = $decoded === false ? '' : $decoded;
        }

        if (strlen($key) < 32) {
            throw new RuntimeException('JWT_SECRET or APP_KEY must contain at least 32 bytes.');
        }

        return $key;
    }
}
