<?php

declare(strict_types=1);

namespace App\Domains\Post\Specifications;

use App\Domains\Post\Aggregates\PostAggregate;

/**
 * Post 規格介面.
 *
 * 定義檢查 Post 是否滿足某個業務規則的契約
 */
interface PostSpecificationInterface
{
    /**
     * 檢查 Post 是否滿足規格.
     *
     * @param PostAggregate $post 要檢查的 Post
     */
    public function isSatisfiedBy(PostAggregate $post): bool;

    /**
     * AND 組合規格.
     *
     * @param PostSpecificationInterface $other 另一個規格
     */
    public function and(PostSpecificationInterface $other): PostSpecificationInterface;

    /**
     * OR 組合規格.
     *
     * @param PostSpecificationInterface $other 另一個規格
     */
    public function or(PostSpecificationInterface $other): PostSpecificationInterface;

    /**
     * NOT 反轉規格.
     */
    public function not(): PostSpecificationInterface;
}
