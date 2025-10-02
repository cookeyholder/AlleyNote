<?php

declare(strict_types=1);

namespace App\Domains\Post\Specifications;

use App\Domains\Post\Aggregates\PostAggregate;

/**
 * 已發佈文章規格.
 *
 * 檢查文章是否已發佈
 */
final class PublishedPostSpecification extends AbstractPostSpecification
{
    public function isSatisfiedBy(PostAggregate $post): bool
    {
        return $post->isPublished();
    }
}
