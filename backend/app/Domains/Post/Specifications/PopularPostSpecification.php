<?php

declare(strict_types=1);

namespace App\Domains\Post\Specifications;

use App\Domains\Post\Aggregates\PostAggregate;

/**
 * 熱門文章規格.
 *
 * 檢查文章瀏覽次數是否超過指定閾值
 */
final class PopularPostSpecification extends AbstractPostSpecification
{
    public function __construct(
        private readonly int $viewThreshold = 1000,
    ) {}

    public function isSatisfiedBy(PostAggregate $post): bool
    {
        return $post->getViewCount()->isGreaterThan($this->viewThreshold);
    }

    public function getViewThreshold(): int
    {
        return $this->viewThreshold;
    }
}
