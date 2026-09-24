<?php

namespace App\Services;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Exception;

class JwtService
{
    public static function getSecret(): string
    {
        return env('JWT_SECRET', 'replace_with_a_secure_random_string_minimum_32_characters');
    }

    public static function sign(array $payload, int $ttlSeconds = 604800): string
    {
        $now = time();
        $tokenPayload = array_merge($payload, [
            'iat' => $now,
            'exp' => $now + $ttlSeconds,
        ]);

        return JWT::encode($tokenPayload, self::getSecret(), 'HS256');
    }

    public static function verify(string $token): ?array
    {
        try {
            $decoded = JWT::decode($token, new Key(self::getSecret(), 'HS256'));
            return (array) $decoded;
        } catch (Exception $e) {
            return null;
        }
    }

    public static function verifyToken(string $token): ?array
    {
        return self::verify($token);
    }
}
