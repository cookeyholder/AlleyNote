<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Post\Models;

use App\Domains\Post\Models\Tag;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\UnitTestCase;

/**
 * Tag 模型單元測試.
 */
#[CoversClass(Tag::class)]
final class TagTest extends UnitTestCase
{
    #[Test]
    public function test_建構子使用預設值(): void
    {
        $tag = new Tag(1, '公告');

        $this->assertSame(1, $tag->getId());
        $this->assertSame('公告', $tag->getName());
        $this->assertNull($tag->getSlug());
        $this->assertNull($tag->getDescription());
        $this->assertNull($tag->getColor());
        $this->assertSame(0, $tag->getUsageCount());
        $this->assertInstanceOf(DateTimeImmutable::class, $tag->getCreatedAt());
        $this->assertNull($tag->getUpdatedAt());
    }

    #[Test]
    public function test_建構子接受完整參數(): void
    {
        $createdAt = new DateTimeImmutable('2026-01-01 08:30:00');
        $updatedAt = new DateTimeImmutable('2026-02-01 10:00:00');

        $tag = new Tag(
            id: 5,
            name: '活動',
            slug: 'event',
            description: '活動相關公告',
            color: '#ff5500',
            usageCount: 12,
            createdAt: $createdAt,
            updatedAt: $updatedAt,
        );

        $this->assertSame(5, $tag->getId());
        $this->assertSame('活動', $tag->getName());
        $this->assertSame('event', $tag->getSlug());
        $this->assertSame('活動相關公告', $tag->getDescription());
        $this->assertSame('#ff5500', $tag->getColor());
        $this->assertSame(12, $tag->getUsageCount());
        $this->assertSame($createdAt, $tag->getCreatedAt());
        $this->assertSame($updatedAt, $tag->getUpdatedAt());
    }

    #[Test]
    public function test_toArray回傳前端相容格式(): void
    {
        $createdAt = new DateTimeImmutable('2026-01-01 08:30:00');
        $updatedAt = new DateTimeImmutable('2026-02-01 10:00:00');

        $tag = new Tag(
            id: 3,
            name: '系統',
            slug: 'system',
            description: null,
            color: null,
            usageCount: 7,
            createdAt: $createdAt,
            updatedAt: $updatedAt,
        );

        $array = $tag->toArray();

        $this->assertSame([
            'id'          => 3,
            'name'        => '系統',
            'slug'        => 'system',
            'description' => null,
            'color'       => null,
            'usage_count' => 7,
            'post_count'  => 7,
            'created_at'  => $createdAt->format('c'),
            'updated_at'  => $updatedAt->format('c'),
        ], $array);
    }

    #[Test]
    public function test_toArray更新時間為null時回傳null(): void
    {
        $tag = new Tag(1, '未更新', createdAt: new DateTimeImmutable('2026-01-01 08:30:00'));

        $array = $tag->toArray();

        $this->assertNull($array['updated_at']);
    }

    #[Test]
    public function test_jsonSerialize等同toArray(): void
    {
        $tag = new Tag(2, '測試', slug: 'test');

        $this->assertSame($tag->toArray(), $tag->jsonSerialize());
    }
}
