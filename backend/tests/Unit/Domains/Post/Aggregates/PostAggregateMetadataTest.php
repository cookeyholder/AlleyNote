<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Post\Aggregates;

use App\Domains\Post\Aggregates\PostAggregate;
use App\Domains\Post\Enums\PostStatus;
use App\Domains\Post\ValueObjects\PostContent;
use App\Domains\Post\ValueObjects\PostId;
use App\Domains\Post\ValueObjects\PostTitle;
use DateTimeImmutable;
use Tests\Support\UnitTestCase;

/**
 * PostAggregate 中繼資料存取測試.
 */
final class PostAggregateMetadataTest extends UnitTestCase
{
    public function test_getStatus回傳目前狀態(): void
    {
        $aggregate = PostAggregate::create(
            new PostId(8),
            new PostTitle('狀態存取測試'),
            new PostContent('狀態存取內容。'),
            2,
        );

        // 新建立的聚合應為草稿
        $this->assertSame(PostStatus::DRAFT, $aggregate->getStatus());

        $aggregate->publish();
        $this->assertSame(PostStatus::PUBLISHED, $aggregate->getStatus());
    }

    public function test_getCreatedAt回傳建立時間(): void
    {
        $before = new DateTimeImmutable('-1 second');
        $aggregate = PostAggregate::create(
            new PostId(9),
            new PostTitle('時間戳記測試'),
            new PostContent('時間戳記內容。'),
            2,
        );
        $after = new DateTimeImmutable('+1 second');

        $this->assertGreaterThanOrEqual($before->getTimestamp(), $aggregate->getCreatedAt()->getTimestamp());
        $this->assertLessThanOrEqual($after->getTimestamp(), $aggregate->getCreatedAt()->getTimestamp());
    }
}
