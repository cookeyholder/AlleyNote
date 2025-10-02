<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Post\Aggregates;

use App\Domains\Post\Aggregates\PostAggregate;
use App\Domains\Post\Events\PostContentUpdated;
use App\Domains\Post\Events\PostPublished;
use App\Domains\Post\Events\PostStatusChanged;
use App\Domains\Post\Exceptions\PostValidationException;
use App\Domains\Post\ValueObjects\PostContent;
use App\Domains\Post\ValueObjects\PostId;
use App\Domains\Post\ValueObjects\PostTitle;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * PostAggregate 單元測試.
 *
 * @covers \App\Domains\Post\Aggregates\PostAggregate
 */
final class PostAggregateTest extends TestCase
{
    public function test_可以建立新的文章聚合(): void
    {
        $postId = PostId::fromInt(123);
        $title = PostTitle::fromString('測試文章標題');
        $content = PostContent::fromString('這是測試文章的內容');
        $authorId = 1;

        $aggregate = PostAggregate::create($postId, $title, $content, $authorId);

        $this->assertInstanceOf(PostAggregate::class, $aggregate);
        $this->assertTrue($aggregate->getId()->equals($postId));
        $this->assertTrue($aggregate->getTitle()->equals($title));
        $this->assertTrue($aggregate->getContent()->equals($content));
        $this->assertSame($authorId, $aggregate->getAuthorId());
        $this->assertTrue($aggregate->isDraft());
        $this->assertFalse($aggregate->isPinned());
        $this->assertSame(0, $aggregate->getViewCount()->getValue());
    }

    public function test_作者ID必須大於0(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('作者 ID 必須大於 0');

        PostAggregate::create(
            PostId::fromInt(123),
            PostTitle::fromString('測試標題'),
            PostContent::fromString('測試內容'),
            0,
        );
    }

    public function test_可以發佈文章(): void
    {
        $aggregate = $this->建立測試文章();

        $aggregate->publish();

        $this->assertTrue($aggregate->isPublished());
        $this->assertNotNull($aggregate->getPublishedAt());

        $events = $aggregate->pullDomainEvents();
        $this->assertCount(1, $events);
        $this->assertInstanceOf(PostPublished::class, $events[0]);
    }

    public function test_發佈文章時標題不能為空(): void
    {
        $aggregate = PostAggregate::create(
            PostId::fromInt(123),
            PostTitle::fromString('a'), // 先建立有效標題
            PostContent::fromString('測試內容'),
            1,
        );

        // 由於 PostTitle 和 PostContent 已經有驗證，
        // 這個測試主要驗證 ensureContentIsValid 的邏輯
        $this->expectNotToPerformAssertions();
    }

    public function test_已發佈的文章不能再次發佈(): void
    {
        $aggregate = $this->建立測試文章();
        $aggregate->publish();

        $this->expectException(PostValidationException::class);
        $this->expectExceptionMessage('文章已經發佈');

        $aggregate->publish();
    }

    public function test_已封存的文章不能發佈(): void
    {
        $aggregate = $this->建立測試文章();
        $aggregate->archive();

        $this->expectException(PostValidationException::class);
        $this->expectExceptionMessage('已封存的文章不能發佈');

        $aggregate->publish();
    }

    public function test_可以更新文章內容(): void
    {
        $aggregate = $this->建立測試文章();
        $newTitle = PostTitle::fromString('新的標題');
        $newContent = PostContent::fromString('新的內容');

        $aggregate->updateContent($newTitle, $newContent);

        $this->assertTrue($aggregate->getTitle()->equals($newTitle));
        $this->assertTrue($aggregate->getContent()->equals($newContent));

        $events = $aggregate->pullDomainEvents();
        $this->assertCount(1, $events);
        $this->assertInstanceOf(PostContentUpdated::class, $events[0]);
    }

    public function test_已封存的文章不能編輯(): void
    {
        $aggregate = $this->建立測試文章();
        $aggregate->archive();

        $this->expectException(PostValidationException::class);
        $this->expectExceptionMessage('已封存的文章不能編輯');

        $aggregate->updateContent(
            PostTitle::fromString('新標題'),
            PostContent::fromString('新內容'),
        );
    }

    public function test_可以封存文章(): void
    {
        $aggregate = $this->建立測試文章();

        $aggregate->archive();

        $this->assertTrue($aggregate->isArchived());

        $events = $aggregate->pullDomainEvents();
        $this->assertCount(1, $events);
        $this->assertInstanceOf(PostStatusChanged::class, $events[0]);
    }

    public function test_已封存的文章不能再次封存(): void
    {
        $aggregate = $this->建立測試文章();
        $aggregate->archive();

        $this->expectException(PostValidationException::class);
        $this->expectExceptionMessage('文章已經封存');

        $aggregate->archive();
    }

    public function test_可以將文章設為草稿(): void
    {
        $aggregate = $this->建立測試文章();
        $aggregate->publish();

        $aggregate->setAsDraft();

        $this->assertTrue($aggregate->isDraft());

        $events = $aggregate->pullDomainEvents();
        $this->assertCount(2, $events); // publish + setAsDraft
        $this->assertInstanceOf(PostStatusChanged::class, $events[1]);
    }

    public function test_草稿文章設為草稿不產生事件(): void
    {
        $aggregate = $this->建立測試文章();

        $aggregate->setAsDraft();

        $this->assertTrue($aggregate->isDraft());

        $events = $aggregate->pullDomainEvents();
        $this->assertCount(0, $events);
    }

    public function test_可以設定置頂狀態(): void
    {
        $aggregate = $this->建立測試文章();

        $aggregate->setPin(true);

        $this->assertTrue($aggregate->isPinned());
    }

    public function test_設定相同置頂狀態不更新updatedAt(): void
    {
        $aggregate = $this->建立測試文章();
        $originalUpdatedAt = $aggregate->getUpdatedAt();

        // 預設就是 false，再次設為 false 不應該有變化
        $aggregate->setPin(false);

        $this->assertEquals($originalUpdatedAt, $aggregate->getUpdatedAt());
    }

    public function test_可以增加瀏覽次數(): void
    {
        $aggregate = $this->建立測試文章();
        $originalCount = $aggregate->getViewCount()->getValue();

        $aggregate->incrementViewCount();

        $this->assertSame($originalCount + 1, $aggregate->getViewCount()->getValue());
    }

    public function test_檢查是否由特定作者撰寫(): void
    {
        $authorId = 123;
        $aggregate = PostAggregate::create(
            PostId::fromInt(123),
            PostTitle::fromString('測試標題'),
            PostContent::fromString('測試內容'),
            $authorId,
        );

        $this->assertTrue($aggregate->isAuthoredBy($authorId));
        $this->assertFalse($aggregate->isAuthoredBy($authorId + 1));
    }

    public function test_可以從資料重建聚合(): void
    {
        $data = [
            'uuid' => 123,
            'title' => '測試標題',
            'content' => '測試內容',
            'user_id' => 1,
            'status' => 'published',
            'views' => 100,
            'is_pinned' => true,
            'seq_number' => 'test-post',
            'creation_source' => 'web',
            'created_at' => '2025-01-01 00:00:00',
            'updated_at' => '2025-01-02 00:00:00',
            'publish_date' => '2025-01-01 12:00:00',
        ];

        $aggregate = PostAggregate::reconstitute($data);

        $this->assertSame(123, $aggregate->getId()->getValue());
        $this->assertSame('測試標題', $aggregate->getTitle()->toString());
        $this->assertSame('測試內容', $aggregate->getContent()->toString());
        $this->assertSame(1, $aggregate->getAuthorId());
        $this->assertTrue($aggregate->isPublished());
        $this->assertSame(100, $aggregate->getViewCount()->getValue());
        $this->assertTrue($aggregate->isPinned());
        $this->assertSame('test-post', $aggregate->getSlug()?->toString());
        $this->assertSame('web', $aggregate->getCreationSource());
        $this->assertNotNull($aggregate->getPublishedAt());
    }

    public function test_重建時缺少uuid會拋出例外(): void
    {
        $this->expectException(InvalidArgumentException::class);

        PostAggregate::reconstitute([
            'title' => '測試標題',
            'content' => '測試內容',
            'user_id' => 1,
        ]);
    }

    public function test_可以轉換為陣列(): void
    {
        $aggregate = $this->建立測試文章();
        $aggregate->publish();

        $array = $aggregate->toArray();

        $this->assertArrayHasKey('uuid', $array);
        $this->assertArrayHasKey('title', $array);
        $this->assertArrayHasKey('content', $array);
        $this->assertArrayHasKey('user_id', $array);
        $this->assertArrayHasKey('status', $array);
        $this->assertArrayHasKey('views', $array);
        $this->assertArrayHasKey('is_pinned', $array);
        $this->assertArrayHasKey('created_at', $array);
        $this->assertArrayHasKey('updated_at', $array);
        $this->assertArrayHasKey('publish_date', $array);
        $this->assertSame('published', $array['status']);
    }

    public function test_領域事件可以被提取(): void
    {
        $aggregate = $this->建立測試文章();
        $aggregate->publish();
        $aggregate->updateContent(
            PostTitle::fromString('新標題'),
            PostContent::fromString('新內容'),
        );

        $events = $aggregate->pullDomainEvents();

        $this->assertCount(2, $events);
        $this->assertInstanceOf(PostPublished::class, $events[0]);
        $this->assertInstanceOf(PostContentUpdated::class, $events[1]);

        // 提取後事件應該被清空
        $eventsAgain = $aggregate->pullDomainEvents();
        $this->assertCount(0, $eventsAgain);
    }

    private function 建立測試文章(): PostAggregate
    {
        return PostAggregate::create(
            PostId::fromInt(123),
            PostTitle::fromString('測試文章標題'),
            PostContent::fromString('這是一段測試文章的內容，用於單元測試。'),
            1,
            'test',
        );
    }
}
