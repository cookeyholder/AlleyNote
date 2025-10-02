<?php

declare(strict_types=1);

namespace App\Domains\Post\Specifications;

use App\Domains\Post\Aggregates\PostAggregate;

/**
 * 草稿文章規格.
 *
 * 檢查文章是否為草稿狀態
 */
final class DraftPostSpecification extends AbstractPostSpecification
{
    public function isSatisfiedBy(PostAggregate $post): bool
    {
        return $post->isDraft();
    }
}
