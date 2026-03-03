<?php

declare(strict_types=1);

namespace Tests\Factory;

use App\Infrastructure\Database\DatabaseConnection;
use DateTimeImmutable;
use DateTimeInterface;

/**
 * 使用者資料工廠.
 */
class UserFactory
{
    /**
     * 產出使用者資料陣列.
     */
    public static function make(array $attributes = []): array
    {
        $now = new DateTimeImmutable()->format(DateTimeInterface::RFC3339);
        $random = bin2hex(random_bytes(4));

        return array_merge([
            'username' => 'user_' . $random,
            'email' => 'user_' . $random . '@example.com',
            'password' => password_hash('password123', PASSWORD_BCRYPT),
            'status' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ], $attributes);
    }

    /**
     * 建立使用者並寫入資料庫.
     */
    public static function create(array $attributes = []): array
    {
        $data = self::make($attributes);
        $db = DatabaseConnection::getInstance();

        $keys = array_keys($data);
        $fields = implode(', ', $keys);
        $placeholders = implode(', ', array_fill(0, count($keys), '?'));

        $stmt = $db->prepare("INSERT INTO users ({$fields}) VALUES ({$placeholders})");
        $stmt->execute(array_values($data));

        $data['id'] = (int) $db->lastInsertId();
        
        return $data;
    }
}
