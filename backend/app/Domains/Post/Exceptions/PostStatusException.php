<?php

declare(strict_types=1);

namespace App\Domains\Post\Exceptions;

use App\Shared\Exceptions\StateTransitionException;

class PostStatusException extends StateTransitionException
{
    public function __construct(string $message = '', int $code = 400)
    {
        parent::__construct($message, $code);
    }

    public static function invalidStatus(string $status): self
    {
        return new self("無效的貼文狀態：{$status}");
    }

    public static function cannotTransition(string $from, string $to): self
    {
        return new self("無法將貼文狀態從「{$from}」變更為「{$to}」");
    }

    public static function cannotPublish(string $reason): self
    {
        return new self("無法發布貼文：{$reason}");
    }

    public static function cannotArchive(string $reason): self
    {
        return new self("無法封存貼文：{$reason}");
    }

    public static function cannotDelete(string $reason): self
    {
        return new self("無法刪除貼文：{$reason}");
    }
}
