<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Post\Factories;

use App\Domains\Post\Aggregates\PostAggregate;
use App\Domains\Post\Enums\PostStatus;
use App\Domains\Post\Factories\PostFactory;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\UnitTestCase;

/**
 * PostFactory 工廠單元測試.
 *
 * 注意：createDraft 系列方法目前存在已知缺陷（generatePostId 將 UUID 字串
 * 傳入 PostId::fromString，而該方法僅接受數字字串），因此一律擲出
 * InvalidArgumentException。以下測試記錄此現狀行為，修正後應同步更新。
 */
#[CoversClass(PostFactory::class)]
final class PostFactoryTest extends UnitTestCase
{
    private PostFactory $factory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->factory = new PostFactory();
    }

    #[Test]
    public function test_createDraft目前因PostId型別限制擲出例外(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('文章 ID 必須是數字');

        $this->factory->createDraft('標題', '<p>內容</p>', 1);
    }

    #[Test]
    public function test_createFromRequest合法資料目前仍因ID生成失敗(): void
    {
        $data = [
            'title'           => '請求標題',
            'content'         => '請求內容',
            'author_id'       => 3,
            'creation_source' => 'api',
        ];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('文章 ID 必須是數字');

        $this->factory->createFromRequest($data);
    }

    #[Test]
    public function test_createFromRequest缺少標題時擲出例外(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('標題欄位是必需的');

        $this->factory->createFromRequest(['content' => '內容', 'author_id' => 1]);
    }

    #[Test]
    public function test_createFromRequest缺少內容時擲出例外(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('內容欄位是必需的');

        $this->factory->createFromRequest(['title' => '標題', 'author_id' => 1]);
    }

    #[Test]
    public function test_createFromRequest缺少作者時擲出例外(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('作者 ID 欄位是必需的');

        $this->factory->createFromRequest(['title' => '標題', 'content' => '內容']);
    }

    #[Test]
    public function test_createFromRequest標題型別錯誤時擲出例外(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('標題必須是字串');

        $this->factory->createFromRequest(['title' => 123, 'content' => '內容', 'author_id' => 1]);
    }

    #[Test]
    public function test_createFromRequest內容型別錯誤時擲出例外(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('內容必須是字串');

        $this->factory->createFromRequest(['title' => '標題', 'content' => ['a'], 'author_id' => 1]);
    }

    #[Test]
    public function test_createFromRequest作者型別錯誤時擲出例外(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('作者 ID 必須是大於 0 的整數');

        $this->factory->createFromRequest(['title' => '標題', 'content' => '內容', 'author_id' => 'x']);
    }

    #[Test]
    public function test_createFromRequest作者小於等於零時擲出例外(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('作者 ID 必須是大於 0 的整數');

        $this->factory->createFromRequest(['title' => '標題', 'content' => '內容', 'author_id' => 0]);
    }

    #[Test]
    public function test_reconstitute從資料重建聚合(): void
    {
        $aggregate = $this->factory->reconstitute([
            'uuid'            => '42',
            'title'           => '重建的文章',
            'content'         => '<p>重建內容</p>',
            'user_id'         => 7,
            'status'          => 'published',
            'views'           => 33,
            'is_pinned'       => true,
            'seq_number'      => '20260042',
            'creation_source' => 'import',
            'created_at'      => '2026-01-01 08:00:00',
            'updated_at'      => '2026-02-01 09:00:00',
            'publish_date'    => '2026-01-15 10:00:00',
        ]);

        $this->assertInstanceOf(PostAggregate::class, $aggregate);
        $this->assertSame(42, $aggregate->getId()->getValue());
        $this->assertSame('重建的文章', $aggregate->getTitle()->toString());
        $this->assertSame('<p>重建內容</p>', $aggregate->getContent()->toString());
        $this->assertSame(7, $aggregate->getAuthorId());
        $this->assertSame(PostStatus::PUBLISHED, $aggregate->getStatus());
        $this->assertSame('import', $aggregate->getCreationSource());
    }

    #[Test]
    public function test_reconstituteMany批次重建聚合(): void
    {
        $rows = [
            [
                'uuid'      => '11',
                'title'     => '第一篇',
                'content'   => '內容一',
                'user_id'   => 1,
                'status'    => 'draft',
                'views'     => 0,
                'is_pinned' => false,
            ],
            [
                'uuid'      => '22',
                'title'     => '第二篇',
                'content'   => '內容二',
                'user_id'   => 2,
                'status'    => 'archived',
                'views'     => 5,
                'is_pinned' => false,
            ],
        ];

        $aggregates = $this->factory->reconstituteMany($rows);

        $this->assertCount(2, $aggregates);
        $this->assertSame('第一篇', $aggregates[0]->getTitle()->toString());
        $this->assertSame(22, $aggregates[1]->getId()->getValue());
        $this->assertSame(PostStatus::ARCHIVED, $aggregates[1]->getStatus());
    }

    #[Test]
    public function test_createCopy目前因PostId型別限制擲出例外(): void
    {
        $original = $this->factory->reconstitute([
            'uuid'    => '9',
            'title'   => '原始文章',
            'content' => '原始內容',
            'user_id' => 1,
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('文章 ID 必須是數字');

        $this->factory->createCopy($original, 8);
    }

    #[Test]
    public function test_createForTesting目前因PostId型別限制擲出例外(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('文章 ID 必須是數字');

        $this->factory->createForTesting();
    }
}
