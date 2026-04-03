<?php

declare(strict_types=1);

namespace App\Domains\Post\Enums;

enum PostStatus: string
{
    case DRAFT = 'draft';
    case PUBLISHED = 'published';
    case ARCHIVED = 'archived';

    public function getLabel(): string
    {
        return match ($this) {
            self::DRAFT => '草稿',
            self::PUBLISHED => '已發布',
            self::ARCHIVED => '已封存',
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return match ($this) {
            self::DRAFT => $target !== self::DRAFT,
            self::PUBLISHED => $target === self::ARCHIVED,
            self::ARCHIVED => false,
        };
    }

    public static function isValid(string $value): bool
    {
        return self::tryFrom($value) !== null;
    }
}
