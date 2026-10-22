<?php

declare(strict_types=1);

namespace Nrv\Infrastructure\Token;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

final class JwtTokenService
{
    public function __construct(
        private readonly string $secret,
        private readonly int $ttl = 3600,
    ) {
        if (strlen($this->secret) < 32) {
            throw new \InvalidArgumentException(
                'La clé JWT doit contenir au moins 32 caractères.'
            );
        }
    }

    public function generate(int $userId, string $role): string
    {
        $now = time();

        $payload = [
            'iss' => 'nrv-api',
            'iat' => $now,
            'exp' => $now + $this->ttl,
            'sub' => (string) $userId,
            'role' => $role,
        ];

        return JWT::encode($payload, $this->secret, 'HS256');
    }

    public function verify(string $token): array
    {
        $decoded = JWT::decode(
            $token,
            new Key($this->secret, 'HS256')
        );

        return (array) $decoded;
    }
}
