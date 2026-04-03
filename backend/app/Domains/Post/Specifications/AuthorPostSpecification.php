<?php

declare(strict_types=1);

namespace App\Domains\Post\Specifications;
use App\Domains\Post\Aggregates\PostAggregate;
final class AuthorPostSpecification extends AbstractPostSpecification
{
    public function __construct(
        private readonly int $authorId,
    ) {}
    public function isSatisfiedBy(PostAggregate $post): bool
    {
        return $post->isAuthoredBy($this->authorId);
    }
    public function getAuthorId(): int
    {
        return $this->authorId;
    }
}
