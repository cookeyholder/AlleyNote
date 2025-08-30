<?php

declare(strict_types=1);

namespace Tests\Factory;

use DateTimeImmutable;
use DateTimeInterface;

class PostFactory
{
    public static function make(array $attributes = []): array
    {
        return array_merge([
            'id' => 1,
            'uuid' => '550e8400-e29b-41d4-a716-446655440000',
            'seq_number' => '202504001',  // 改為字串型別
            'title' => '範例文章',
            'content' => '這是一篇範例文章的內容',
            'user_id' => 1,
            'user_ip' => '127.0.0.1',
            'is_pinned' => false,
            'status' => 'draft',
            'views' => 0,
            'publish_date' => new DateTimeImmutable()->format(DateTimeInterface::RFC3339),
            'created_at' => new DateTimeImmutable()->format(DateTimeInterface::RFC3339),
            'updated_at' => new DateTimeImmutable()->format(DateTimeInterface::RFC3339),
        ], $attributes);
    }
}
