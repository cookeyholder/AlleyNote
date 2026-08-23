<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Post\Services;

use App\Domains\Post\Models\Post;
use App\Domains\Post\Services\PostCacheInvalidator;
use App\Domains\Post\Services\PostCacheKeyService;
use App\Shared\Contracts\CacheServiceInterface;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tests\Support\UnitTestCase;

/**
 * PostCacheKeyService 與 PostCacheInvalidator 測試.
 */
final class PostCacheServiceTest extends UnitTestCase
{
    use MockeryPHPUnitIntegration;

    public function test_快取鍵格式符合預期(): void
    {
        $this->assertSame('alleynote:post:5', PostCacheKeyService::post(5));
        $this->assertSame('alleynote:post:uuid:abc-123', PostCacheKeyService::postByUuid('abc-123'));
        $this->assertSame('alleynote:posts:published:page:2', PostCacheKeyService::postList(2));
        $this->assertSame('alleynote:posts:draft:page:1', PostCacheKeyService::postList(1, 'draft'));
        $this->assertSame('alleynote:posts:pinned', PostCacheKeyService::pinnedPosts());
        $this->assertSame('alleynote:post:9:tags', PostCacheKeyService::postTags(9));
        $this->assertSame('alleynote:post:9:views', PostCacheKeyService::postViews(9));
    }

    public function test_分類與標籤快取鍵支援頁碼預設值(): void
    {
        $this->assertSame('alleynote:posts:category:news:page:1', PostCacheKeyService::postsByCategory('news'));
        $this->assertSame('alleynote:posts:category:news:page:3', PostCacheKeyService::postsByCategory('news', 3));
        $this->assertSame('alleynote:tag:7:posts:page:1', PostCacheKeyService::tagPosts(7));
        $this->assertSame('alleynote:tag:7:posts:page:4', PostCacheKeyService::tagPosts(7, 4));
    }

    public function test_模式快取鍵以萬用字元結尾(): void
    {
        $this->assertSame('alleynote:user:12*', PostCacheKeyService::userPattern(12));
        $this->assertSame('alleynote:post:34*', PostCacheKeyService::postPattern(34));
        $this->assertSame('alleynote:posts*', PostCacheKeyService::postsListPattern());
    }

    /**
     * 建立測試用文章模型.
     */
    private function makePost(int $id, int $userId): Post
    {
        return Post::fromArray([
            'id'      => $id,
            'uuid'    => 'uuid-' . $id,
            'title'   => '快取測試文章',
            'content' => '快取測試內容',
            'user_id' => $userId,
            'status'  => 'published',
        ]);
    }

    public function test_invalidatePost清除單篇文章相關快取(): void
    {
        $cache = Mockery::mock(CacheServiceInterface::class);
        $cache->shouldReceive('delete')->with('alleynote:post:11')->once();
        $cache->shouldReceive('delete')->with('alleynote:post:uuid:uuid-11')->once();
        $cache->shouldReceive('delete')->with('alleynote:post:11:tags')->once();
        $cache->shouldReceive('delete')->with('alleynote:post:11:views')->once();
        $cache->shouldReceive('delete')->with('alleynote:posts:pinned')->once();
        $cache->shouldReceive('deletePattern')->with('alleynote:posts*')->once();
        $cache->shouldReceive('deletePattern')->with('alleynote:user:77*')->once();

        $invalidator = new PostCacheInvalidator($cache);
        $invalidator->invalidatePost($this->makePost(11, 77));

        // Mockery 於 tearDown 驗證呼叫次數
        $this->addToAssertionCount(1);
    }

    public function test_invalidatePost使用者為零時略過使用者模式清除(): void
    {
        $cache = Mockery::mock(CacheServiceInterface::class);
        $cache->shouldReceive('delete')->times(5);
        $cache->shouldReceive('deletePattern')->with('alleynote:posts*')->once();

        $invalidator = new PostCacheInvalidator($cache);
        $invalidator->invalidatePost($this->makePost(12, 0));

        $this->addToAssertionCount(1);
    }

    public function test_invalidateList清除最新列表快取(): void
    {
        $cache = Mockery::mock(CacheServiceInterface::class);
        $cache->shouldReceive('delete')->with('posts:latest')->once();

        $invalidator = new PostCacheInvalidator($cache);
        $invalidator->invalidateList();

        $this->addToAssertionCount(1);
    }

    public function test_invalidatePinned清除置頂快取(): void
    {
        $cache = Mockery::mock(CacheServiceInterface::class);
        $cache->shouldReceive('delete')->with('alleynote:posts:pinned')->once();

        $invalidator = new PostCacheInvalidator($cache);
        $invalidator->invalidatePinned();

        $this->addToAssertionCount(1);
    }

    public function test_invalidateAnalytics清除列表模式快取(): void
    {
        $cache = Mockery::mock(CacheServiceInterface::class);
        $cache->shouldReceive('deletePattern')->with('alleynote:posts*')->once();

        $invalidator = new PostCacheInvalidator($cache);
        $invalidator->invalidateAnalytics();

        $this->addToAssertionCount(1);
    }
}
