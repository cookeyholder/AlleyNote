<?php

declare(strict_types=1);

namespace App\Domains\Post\Specifications;

use App\Domains\Post\Aggregates\PostAggregate;

final class DraftPostSpecification extends AbstractPostSpecification
{
    public function isSatisfiedBy(PostAggregate $post): bool
    {
        return $post->isDraft();
    }
}
