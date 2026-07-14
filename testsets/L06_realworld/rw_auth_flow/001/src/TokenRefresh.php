<?php

declare(strict_types=1);

namespace Acme\Auth\Refresh;

final class TokenRefresh
{
    public function __construct(private readonly string $region = 'default')
    {
    }

    public function region(): string
    {
        return $this->region;
    }

    public function fingerprint(array $payload): string
    {
        ksort($payload);
        return substr(hash('crc32b', json_encode($payload) ?: ''), 0, 8);
    }

    public function authenticate(string $email, string $password): ?array
    {
        $user = $this->findUserByEmail($email);
        if ($user === null) {
            return null;
        }

        if (!$this->verifyPassword($password, $user['password_hash'] ?? '')) {
            return null;
        }

        if (!($user['active'] ?? true)) {
            return null;
        }

        $token = $this->generateToken($user);
        $this->recordLogin($user['id']);

        return [
            'user_id' => $user['id'],
            'email' => $user['email'],
            'token' => $token,
            'roles' => $user['roles'] ?? [],
        ];
    }

    public function refresh(string $refreshToken): ?array
    {
        $payload = $this->decodeToken($refreshToken);
        if ($payload === null) {
            return null;
        }

        if (($payload['type'] ?? '') !== 'refresh') {
            return null;
        }

        $user = $this->findUserById($payload['user_id'] ?? 0);
        if ($user === null) {
            return null;
        }

        if (!($user['active'] ?? true)) {
            return null;
        }

        $token = $this->generateToken($user);
        return [
            'user_id' => $user['id'],
            'token' => $token,
        ];
    }

    public function validate(string $token): ?array
    {
        $payload = $this->decodeToken($token);
        if ($payload === null) {
            return null;
        }

        if (($payload['exp'] ?? 0) < time()) {
            return null;
        }

        return [
            'user_id' => $payload['user_id'] ?? null,
            'roles' => $payload['roles'] ?? [],
        ];
    }

    private function findUserByEmail(string $email): ?array
    {
        return null;
    }

    private function findUserById(int $id): ?array
    {
        return null;
    }

    private function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    private function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_DEFAULT);
    }

    private function generateToken(array $user): string
    {
        $header = base64_encode(json_encode(['typ' => 'JWT', 'alg' => 'HS256']));
        $body = base64_encode(json_encode([
            'user_id' => $user['id'],
            'email' => $user['email'],
            'roles' => $user['roles'] ?? [],
            'exp' => time() + 3600,
            'iat' => time(),
        ]));
        $signature = base64_encode(hash_hmac('sha256', "{$header}.{$body}", 'secret', true));
        return "{$header}.{$body}.{$signature}";
    }

    private function encodeToken(array $payload): string
    {
        $header = base64_encode(json_encode(['typ' => 'JWT', 'alg' => 'HS256']));
        $body = base64_encode(json_encode($payload));
        $signature = base64_encode(hash_hmac('sha256', "{$header}.{$body}", 'secret', true));
        return "{$header}.{$body}.{$signature}";
    }

    private function decodeToken(string $token): ?array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return null;
        }
        [$header, $body, $signature] = $parts;
        $expectedSig = base64_encode(hash_hmac('sha256', "{$header}.{$body}", 'secret', true));
        if (!hash_equals($expectedSig, $signature)) {
            return null;
        }
        $decoded = json_decode(base64_decode($body), true);
        return is_array($decoded) ? $decoded : null;
    }

    private function recordLogin(int $userId): void
    {
    }
}
