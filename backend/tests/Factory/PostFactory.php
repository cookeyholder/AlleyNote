<?php

declare(strict_types=1);

namespace Tests\Factory;

use App\Infrastructure\Database\DatabaseConnection;
use DateTimeImmutable;
use DateTimeInterface;

class PostFactory
{
    public static function make(array $attributes = []): array
    {
        $now = new DateTimeImmutable()->format(DateTimeInterface::RFC3339);
        
        return array_merge([
            'uuid' => sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x', mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(16384, 20479), mt_rand(32768, 49151), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535)),
            'seq_number' => (string) mt_rand(100000, 999999),
            'title' => '範例文章',
            'content' => '這是一篇範例文章的內容',
            'user_id' => 1,
            'user_ip' => '127.0.0.1',
            'is_pinned' => 0,
            'status' => 'draft',
            'views' => 0,
            'publish_date' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ], $attributes);
    }

    public static function create(array $attributes = []): array
    {
        $data = self::make($attributes);
        $db = DatabaseConnection::getInstance();

        $keys = array_keys($data);
        $fields = implode(', ', $keys);
        $placeholders = implode(', ', array_fill(0, count($keys), '?'));

        $stmt = $db->prepare("INSERT INTO posts ({$fields}) VALUES ({$placeholders})");
        $stmt->execute(array_values($data));

        $data['id'] = (int) $db->lastInsertId();
        
        return $data;
    }
}
