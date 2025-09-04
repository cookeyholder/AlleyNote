<?php

declare(strict_types=1);

namespace App\Domains\Post\Exceptions;

use App\Shared\Exceptions\NotFoundException;

class PostNotFoundException extends NotFoundException
{
    public function __construct(int $postId, string $message = '')
    {
        if (empty($message)) {
            $message = "找不到 ID 為 {$postId} 的貼文";
        }

        parent::__construct($message, 404);
    }

    public static function byId(int $postId): self
    {
        return new self($postId);
    }

    public static function byUuid(string $uuid): self
    {
        return new self(0, "找不到 UUID 為 {$uuid} 的貼文");
    }
}
