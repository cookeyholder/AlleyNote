<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Post\Specifications;

use App\Domains\Post\Aggregates\PostAggregate;
use App\Domains\Post\Specifications\AuthorPostSpecification;
use App\Domains\Post\Specifications\DraftPostSpecification;
use App\Domains\Post\Specifications\PinnedPostSpecification;
use App\Domains\Post\Specifications\PopularPostSpecification;
use App\Domains\Post\Specifications\PublishedPostSpecification;
use App\Domains\Post\ValueObjects\PostContent;
use App\Domains\Post\ValueObjects\PostId;
use App\Domains\Post\ValueObjects\PostTitle;
use Tests\Support\UnitTestCase;

/**
 * 具體文章規格測試.
 */
final class ConcretePostSpecificationsTest extends UnitTestCase
{
    /**
     * 建立指定作者的新草稿聚合.
     */
    private function createDraft(int $authorId = 5): PostAggregate
    {
        return PostAggregate::create(
            new PostId(2),
            new PostTitle('具體規格測試'),
            new PostContent('具體規格測試內容。'),
            $authorId,
        );
    }

    public function test_author_specification_matches_same_author(): void
    {
        $aggregate = $this->createDraft(5);
        $spec = new AuthorPostSpecification(5);

        $this->assertSame(5, $spec->getAuthorId());
        $this->assertTrue($spec->isSatisfiedBy($aggregate));
    }

    public function test_author_specification_rejects_other_author(): void
    {
        $aggregate = $this->createDraft(5);

        $this->assertFalse(new AuthorPostSpecification(6)->isSatisfiedBy($aggregate));
    }

    public function test_draft_specification_matches_new_aggregate(): void
    {
        $aggregate = $this->createDraft();

        $this->assertTrue(new DraftPostSpecification()->isSatisfiedBy($aggregate));
    }

    public function test_draft_specification_rejects_published_post(): void
    {
        $aggregate = $this->createDraft();
        $aggregate->publish();

        $this->assertFalse(new DraftPostSpecification()->isSatisfiedBy($aggregate));
    }

    public function test_published_specification_matches_after_publish(): void
    {
        $aggregate = $this->createDraft();
        $aggregate->publish();

        $this->assertTrue(new PublishedPostSpecification()->isSatisfiedBy($aggregate));
    }

    public function test_published_specification_rejects_draft(): void
    {
        $aggregate = $this->createDraft();

        $this->assertFalse(new PublishedPostSpecification()->isSatisfiedBy($aggregate));
    }

    public function test_pinned_specification_defaults_to_false(): void
    {
        $aggregate = $this->createDraft();

        $this->assertFalse(new PinnedPostSpecification()->isSatisfiedBy($aggregate));
    }

    public function test_popular_specification_uses_view_threshold(): void
    {
        $aggregate = $this->createDraft();
        $spec = new PopularPostSpecification(100);

        $this->assertSame(100, $spec->getViewThreshold());
        // 新聚合瀏覽數為 0，未達門檻
        $this->assertFalse($spec->isSatisfiedBy($aggregate));
    }

    public function test_popular_specification_default_threshold(): void
    {
        $spec = new PopularPostSpecification();

        $this->assertSame(1000, $spec->getViewThreshold());
    }
}
