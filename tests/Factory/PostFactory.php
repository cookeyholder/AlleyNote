<?php

declare(strict_types=1);

namespace Tests\Factory;

class PostFactory
{
    public static function make(array $attributes = []): array
    {
        $defaults = [
            'id' => 1,
            'uuid' => '550e8400-e29b-41d4-a716-446655440000',
            'seq_number' => 1,
            'title' => '範例文章',
            'content' => '這是一篇範例文章的內容',
            'user_id' => 1,
            'user_ip' => '127.0.0.1',
            'is_pinned' => false,
            'status' => 'published',
            'publish_date' => '2025-04-11 12:00:00',
            'created_at' => '2025-04-11 12:00:00',
            'updated_at' => '2025-04-11 12:00:00',
            'view_count' => 0
        ];

        return array_merge($defaults, $attributes);
    }
}
