<?php

declare(strict_types=1);

namespace App\Domains\Post\Specifications;

use App\Domains\Post\Aggregates\PostAggregate;

/**
 * 抽象 Post 規格基類.
 *
 * 提供規格組合邏輯的預設實作
 */
abstract class AbstractPostSpecification implements PostSpecificationInterface
{
    public function and(PostSpecificationInterface $other): PostSpecificationInterface
    {
        return new AndPostSpecification($this, $other);
    }

    public function or(PostSpecificationInterface $other): PostSpecificationInterface
    {
        return new OrPostSpecification($this, $other);
    }

    public function not(): PostSpecificationInterface
    {
        return new NotPostSpecification($this);
    }

    abstract public function isSatisfiedBy(PostAggregate $post): bool;
}

/**
 * AND 組合規格.
 */
final class AndPostSpecification extends AbstractPostSpecification
{
    public function __construct(
        private readonly PostSpecificationInterface $left,
        private readonly PostSpecificationInterface $right,
    ) {}

    public function isSatisfiedBy(PostAggregate $post): bool
    {
        return $this->left->isSatisfiedBy($post) && $this->right->isSatisfiedBy($post);
    }
}

/**
 * OR 組合規格.
 */
final class OrPostSpecification extends AbstractPostSpecification
{
    public function __construct(
        private readonly PostSpecificationInterface $left,
        private readonly PostSpecificationInterface $right,
    ) {}

    public function isSatisfiedBy(PostAggregate $post): bool
    {
        return $this->left->isSatisfiedBy($post) || $this->right->isSatisfiedBy($post);
    }
}

/**
 * NOT 反轉規格.
 */
final class NotPostSpecification extends AbstractPostSpecification
{
    public function __construct(
        private readonly PostSpecificationInterface $spec,
    ) {}

    public function isSatisfiedBy(PostAggregate $post): bool
    {
        return !$this->spec->isSatisfiedBy($post);
    }
}
