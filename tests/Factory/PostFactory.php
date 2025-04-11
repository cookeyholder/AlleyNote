<?php

declare(strict_types=1);

namespace Tests\Factory;

use App\Models\Post;
use Tests\Factory\Abstracts\AbstractFactory;

class PostFactory extends AbstractFactory
{
    public static function make(array $attributes = []): array
    {
        $sequence = static::sequence('post');
        $now = format_datetime();

        $defaults = [
            'id' => $sequence,
            'uuid' => generate_uuid(),
            'seq_number' => $sequence,
            'title' => "測試文章 #{$sequence}",
            'content' => "這是測試文章內容 #{$sequence}",
            'user_id' => 1,
            'user_ip' => '127.0.0.1',
            'views' => 0,
            'is_pinned' => false,
            'status' => 1,
            'publish_date' => $now,
            'created_at' => $now,
            'updated_at' => $now
        ];

        return array_merge($defaults, $attributes);
    }

    public static function createPost(array $attributes = []): Post
    {
        return Post::fromArray(static::make($attributes));
    }

    public static function createPosts(int $count, array $attributes = []): array
    {
        return array_map(
            fn(array $data) => Post::fromArray($data),
            static::makeMany($count, $attributes)
        );
    }
}
