<?php

declare(strict_types=1);

namespace App\Domains\Post\Specifications;

use App\Domains\Post\Aggregates\PostAggregate;

/**
 * 置頂文章規格.
 *
 * 檢查文章是否被置頂
 */
final class PinnedPostSpecification extends AbstractPostSpecification
{
    public function isSatisfiedBy(PostAggregate $post): bool
    {
        return $post->isPinned();
    }
}
