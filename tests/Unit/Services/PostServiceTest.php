<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\Post;
use App\Services\PostService;
use App\Repositories\Contracts\PostRepositoryInterface;
use App\Exceptions\ValidationException;
use App\Exceptions\NotFoundException;
use Mockery;
use Tests\Factory\PostFactory;
use Mockery\Adapter\Phpunit\MockeryTestCase;

class PostServiceTest extends MockeryTestCase
{
    private PostRepositoryInterface $repository;
    private PostService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = Mockery::mock(PostRepositoryInterface::class);
        $this->service = new PostService($this->repository);
    }

    public function testCreatePostWithValidData(): void
    {
        // 準備測試資料
        $data = PostFactory::make([
            'title' => '測試文章',
            'content' => '這是測試內容'
        ]);

        // 模擬 Repository
        $this->repository->shouldReceive('create')
            ->once()
            ->with($data)
            ->andReturn(new Post($data));

        // 執行測試
        $post = $this->service->createPost($data);

        // 驗證結果
        $this->assertInstanceOf(Post::class, $post);
        $this->assertEquals('測試文章', $post->getTitle());
    }

    public function testCreatePostWithInvalidData(): void
    {
        // 準備測試資料
        $data = ['title' => '', 'content' => ''];

        // 執行測試並驗證異常
        $this->expectException(ValidationException::class);
        $this->service->createPost($data);
    }

    public function testCreatePostWithInvalidTitle(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('文章標題不可為空');

        $this->service->createPost(['content' => '測試內容']);
    }

    public function testCreatePostWithTooLongTitle(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('文章標題不可超過 255 字元');

        $this->service->createPost([
            'title' => str_repeat('a', 256),
            'content' => '測試內容'
        ]);
    }

    public function testCreatePostWithInvalidDate(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('無效的發布日期格式');

        $this->service->createPost([
            'title' => '測試標題',
            'content' => '測試內容',
            'publish_date' => 'invalid-date'
        ]);
    }

    public function testUpdatePostWithValidData(): void
    {
        // 準備測試資料
        $id = 1;
        $data = ['title' => '更新的標題', 'content' => '更新的內容'];
        $post = new Post(PostFactory::make());

        // 模擬 Repository
        $this->repository->shouldReceive('find')
            ->once()
            ->with($id)
            ->andReturn($post);

        $this->repository->shouldReceive('update')
            ->once()
            ->with($id, $data)
            ->andReturn(new Post(array_merge($post->toArray(), $data)));

        // 執行測試
        $updated = $this->service->updatePost($id, $data);

        // 驗證結果
        $this->assertEquals('更新的標題', $updated->getTitle());
        $this->assertEquals('更新的內容', $updated->getContent());
    }

    public function testUpdateNonExistentPost(): void
    {
        // 模擬 Repository
        $this->repository->shouldReceive('find')
            ->once()
            ->with(999)
            ->andReturnNull();

        // 執行測試並驗證異常
        $this->expectException(NotFoundException::class);
        $this->service->updatePost(999, ['title' => '測試']);
    }

    public function testSetPinnedStatus(): void
    {
        // 準備測試資料
        $id = 1;
        $post = new Post(PostFactory::make());

        // 模擬 Repository
        $this->repository->shouldReceive('find')
            ->once()
            ->with($id)
            ->andReturn($post);

        $this->repository->shouldReceive('setPinned')
            ->once()
            ->with($id, true)
            ->andReturn(true);

        // 執行測試
        $result = $this->service->setPinned($id, true);

        // 驗證結果
        $this->assertTrue($result);
    }

    public function testSetPinnedWithInvalidId(): void
    {
        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('找不到指定的文章');

        $this->repository->shouldReceive('find')
            ->once()
            ->with(999)
            ->andReturnNull();

        $this->service->setPinned(999, true);
    }

    public function testSetTagsWithInvalidId(): void
    {
        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('找不到指定的文章');

        $this->repository->shouldReceive('find')
            ->once()
            ->with(999)
            ->andReturnNull();

        $this->service->setTags(999, [1, 2, 3]);
    }

    public function testRecordViewWithInvalidIp(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('無效的 IP 位址');

        $post = new Post(PostFactory::make());

        $this->repository->shouldReceive('find')
            ->once()
            ->with(1)
            ->andReturn($post);

        $this->service->recordView(1, 'invalid-ip');
    }

    public function testRecordViewWithValidData(): void
    {
        // 準備測試資料
        $id = 1;
        $ip = '127.0.0.1';
        $userId = 1;
        $post = new Post(PostFactory::make());

        // 模擬 Repository
        $this->repository->shouldReceive('find')
            ->once()
            ->with($id)
            ->andReturn($post);

        $this->repository->shouldReceive('incrementViews')
            ->once()
            ->with($id, $ip, $userId)
            ->andReturn(true);

        // 執行測試
        $result = $this->service->recordView($id, $ip, $userId);

        // 驗證結果
        $this->assertTrue($result);
    }
}
