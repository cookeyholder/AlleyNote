<?php

declare(strict_types=1);

namespace App\Domains\Post\Specifications;

use App\Domains\Post\Aggregates\PostAggregate;

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
