<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Post\Specifications;

use App\Domains\Post\Aggregates\PostAggregate;
use App\Domains\Post\Specifications\AbstractPostSpecification;
use App\Domains\Post\Specifications\DraftPostSpecification;
use App\Domains\Post\Specifications\PublishedPostSpecification;
use App\Domains\Post\ValueObjects\PostContent;
use App\Domains\Post\ValueObjects\PostId;
use App\Domains\Post\ValueObjects\PostTitle;
use Tests\Support\UnitTestCase;

/**
 * AbstractPostSpecification 組合邏輯（AND / OR / NOT）測試.
 */
final class AbstractPostSpecificationTest extends UnitTestCase
{
    /**
     * 建立新草稿聚合.
     */
    private function createAggregate(): PostAggregate
    {
        return PostAggregate::create(
            new PostId(3),
            new PostTitle('組合規格測試'),
            new PostContent('組合規格測試內容。'),
            9,
        );
    }

    /**
     * 建立固定回傳值的匿名規格.
     */
    private function fakeSpec(bool $result): AbstractPostSpecification
    {
        return new class($result) extends AbstractPostSpecification {
            public function __construct(
                private readonly bool $result,
            ) {}

            public function isSatisfiedBy(PostAggregate $post): bool
            {
                return $this->result;
            }
        };
    }

    public function test_and_returns_true_only_when_both_satisfied(): void
    {
        $aggregate = $this->createAggregate();

        $bothTrue = $this->fakeSpec(true)->and($this->fakeSpec(true));
        $oneFalse = $this->fakeSpec(true)->and($this->fakeSpec(false));

        $this->assertTrue($bothTrue->isSatisfiedBy($aggregate));
        $this->assertFalse($oneFalse->isSatisfiedBy($aggregate));
    }

    public function test_or_returns_true_when_either_satisfied(): void
    {
        $aggregate = $this->createAggregate();

        $oneTrue = $this->fakeSpec(false)->or($this->fakeSpec(true));
        $bothFalse = $this->fakeSpec(false)->or($this->fakeSpec(false));

        $this->assertTrue($oneTrue->isSatisfiedBy($aggregate));
        $this->assertFalse($bothFalse->isSatisfiedBy($aggregate));
    }

    public function test_not_inverts_result(): void
    {
        $aggregate = $this->createAggregate();

        $invertedTrue = $this->fakeSpec(false)->not();
        $invertedFalse = $this->fakeSpec(true)->not();

        $this->assertTrue($invertedTrue->isSatisfiedBy($aggregate));
        $this->assertFalse($invertedFalse->isSatisfiedBy($aggregate));
    }

    public function test_concrete_specifications_support_composition(): void
    {
        // 草稿 且 未發布：成立；草稿 且 已發布：不成立
        $aggregate = $this->createAggregate();
        $draft = new DraftPostSpecification();
        $published = new PublishedPostSpecification();

        $draftAndNotPublished = $draft->and($published->not());
        $draftAndPublished = $draft->and($published);

        $this->assertTrue($draftAndNotPublished->isSatisfiedBy($aggregate));
        $this->assertFalse($draftAndPublished->isSatisfiedBy($aggregate));

        // 草稿 或 已發布：成立
        $draftOrPublished = $draft->or($published);
        $this->assertTrue($draftOrPublished->isSatisfiedBy($aggregate));
    }
}
