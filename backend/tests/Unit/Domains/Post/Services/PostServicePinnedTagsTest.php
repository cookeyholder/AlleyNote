<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Post\Services;

use App\Domains\Post\Contracts\PostRepositoryInterface;
use App\Domains\Post\Models\Post;
use App\Domains\Post\Services\PostService;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use Tests\Support\UnitTestCase;

/**
 * PostService 置頂與標籤查詢測試.
 */
final class PostServicePinnedTagsTest extends UnitTestCase
{
    use MockeryPHPUnitIntegration;

    private PostRepositoryInterface&MockInterface $repository;

    private PostService $service;

    /**
     * 建立測試用文章模型.
     */
    private function makePost(int $id, bool $pinned = false): Post
    {
        return Post::fromArray([
            'id'         => $id,
            'uuid'       => 'uuid-' . $id,
            'title'      => '置頂測試文章',
            'content'    => '置頂測試內容',
            'user_id'    => 1,
            'is_pinned'  => $pinned,
            'status'     => 'published',
            'created_at' => '2026-01-01 00:00:00',
            'updated_at' => '2026-01-01 00:00:00',
        ]);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = Mockery::mock(PostRepositoryInterface::class);
        $this->service = new PostService($this->repository);
    }

    public function test_getPinnedPosts回傳置頂清單(): void
    {
        $posts = [$this->makePost(1, true), $this->makePost(2, true)];

        $this->repository
            ->shouldReceive('getPinnedPosts')
            ->once()
            ->with(5)
            ->andReturn($posts);

        $this->assertSame($posts, $this->service->getPinnedPosts());
    }

    public function test_setPinned委派至儲存庫(): void
    {
        $this->repository
            ->shouldReceive('setPinned')
            ->once()
            ->with(9, true)
            ->andReturn(true);

        $this->assertTrue($this->service->setPinned(9, true));
    }

    public function test_getPostTags回傳標籤陣列(): void
    {
        $tags = [
            ['id' => 1, 'name' => '公告'],
            ['id' => 2, 'name' => '維護'],
        ];

        $this->repository
            ->shouldReceive('getPostTags')
            ->once()
            ->with(4)
            ->andReturn($tags);

        $this->assertSame($tags, $this->service->getPostTags(4));
    }

    public function test_pinPost設定置頂後回傳最新狀態(): void
    {
        $pinned = $this->makePost(6, true);

        $this->repository->shouldReceive('setPinned')->once()->with(6, true)->andReturn(true);
        $this->repository->shouldReceive('find')->once()->with(6)->andReturn($pinned);

        $result = $this->service->pinPost(6);

        $this->assertTrue($result->isPinned());
    }

    public function test_unpinPost取消置頂後回傳最新狀態(): void
    {
        $unpinned = $this->makePost(7, false);

        $this->repository->shouldReceive('setPinned')->once()->with(7, false)->andReturn(true);
        $this->repository->shouldReceive('find')->once()->with(7)->andReturn($unpinned);

        $result = $this->service->unpinPost(7);

        $this->assertFalse($result->isPinned());
    }
}
