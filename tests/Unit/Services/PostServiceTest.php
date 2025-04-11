<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use Mockery;
use Mockery\MockInterface;
use App\Models\Post;
use App\Services\PostService;
use App\Services\Validators\PostValidator;
use App\Services\Enums\PostStatus;
use App\Repositories\Contracts\PostRepositoryInterface;
use App\Exceptions\ValidationException;
use App\Exceptions\NotFoundException;
use App\Exceptions\StateTransitionException;
use Tests\Factory\PostFactory;
use Mockery\Adapter\Phpunit\MockeryTestCase;

class PostServiceTest extends MockeryTestCase
{
    private PostRepositoryInterface|MockInterface $repository;
    private PostValidator $validator;
    private PostService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = Mockery::mock(PostRepositoryInterface::class);
        $this->validator = new PostValidator();
        $this->service = new PostService($this->repository, $this->validator);
    }

    public function testCreatePostWithValidData(): void
    {
        $data = PostFactory::make([
            'title' => '測試文章',
            'content' => '這是測試內容',
            'status' => PostStatus::DRAFT->value
        ]);

        $this->repository->shouldReceive('create')
            ->once()
            ->with(Mockery::on(function ($arg) use ($data) {
                return $arg['title'] === $data['title']
                    && $arg['content'] === $data['content']
                    && $arg['status'] === $data['status']
                    && isset($arg['created_at']);
            }))
            ->andReturn(new Post($data));

        $post = $this->service->createPost($data);

        $this->assertInstanceOf(Post::class, $post);
        $this->assertEquals('測試文章', $post->getTitle());
    }

    public function testCreatePostWithInvalidData(): void
    {
        $data = ['title' => '', 'content' => ''];

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

    public function testCreatePostWithEmptyContent(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('文章內容不可為空');

        $this->service->createPost([
            'title' => '測試標題',
            'content' => ''
        ]);
    }

    public function testCreatePostWithFutureDate(): void
    {
        $futureDate = date('Y-m-d H:i:s', strtotime('+1 year'));

        $data = [
            'title' => '測試標題',
            'content' => '測試內容',
            'publish_date' => $futureDate,
            'status' => PostStatus::DRAFT->value,
            'is_pinned' => false
        ];

        $this->repository->shouldReceive('create')
            ->once()
            ->with(Mockery::on(function ($arg) use ($data) {
                return $arg['title'] === $data['title']
                    && $arg['content'] === $data['content']
                    && $arg['publish_date'] === $data['publish_date']
                    && $arg['status'] === $data['status']
                    && isset($arg['created_at']);
            }))
            ->andReturn(new Post($data));

        $result = $this->service->createPost($data);

        $this->assertInstanceOf(Post::class, $result);
        $this->assertEquals($futureDate, $result->getPublishDate());
    }

    public function testCreatePostWithInvalidStatus(): void
    {
        $data = [
            'title' => '測試標題',
            'content' => '測試內容',
            'status' => 'invalid_status'
        ];

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('無效的文章狀態');

        $this->service->createPost($data);
    }

    public function testUpdatePostWithValidData(): void
    {
        $id = 1;
        $initialData = PostFactory::make(['status' => PostStatus::DRAFT->value]);
        $updateData = [
            'title' => '更新的標題',
            'content' => '更新的內容'
        ];

        $post = new Post($initialData);

        $this->repository->shouldReceive('find')
            ->once()
            ->with($id)
            ->andReturn($post);

        $this->repository->shouldReceive('update')
            ->once()
            ->with(Mockery::on(function ($actualId) use ($id) {
                return $actualId === $id;
            }), Mockery::on(function ($data) use ($updateData) {
                return $data['title'] === $updateData['title']
                    && $data['content'] === $updateData['content']
                    && isset($data['updated_at']);
            }))
            ->andReturn(new Post(array_merge($initialData, $updateData)));

        $updated = $this->service->updatePost($id, $updateData);

        $this->assertEquals('更新的標題', $updated->getTitle());
        $this->assertEquals('更新的內容', $updated->getContent());
    }

    public function testUpdatePostWithInvalidStatusTransition(): void
    {
        $id = 1;
        $post = new Post(PostFactory::make(['status' => PostStatus::PUBLISHED->value]));

        $this->repository->shouldReceive('find')
            ->once()
            ->with($id)
            ->andReturn($post);

        $this->expectException(StateTransitionException::class);
        $this->expectExceptionMessage('無法將文章從「已發布」狀態變更為「草稿」');

        $this->service->updatePost($id, [
            'title' => '更新的標題',
            'content' => '更新的內容',
            'status' => PostStatus::DRAFT->value
        ]);
    }

    public function testUpdateNonExistentPost(): void
    {
        $this->repository->shouldReceive('find')
            ->once()
            ->with(999)
            ->andReturnNull();

        $this->expectException(NotFoundException::class);
        $this->service->updatePost(999, ['title' => '測試']);
    }

    public function testUpdatePostWithInvalidUserIp(): void
    {
        $id = 1;
        $post = new Post(PostFactory::make());

        $this->repository->shouldReceive('find')
            ->once()
            ->with($id)
            ->andReturn($post);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('無效的 IP 位址格式');

        $this->service->updatePost($id, [
            'title' => '測試標題',
            'content' => '測試內容',
            'user_ip' => '999.999.999.999'
        ]);
    }

    public function testUpdatePostWithAllValidationRules(): void
    {
        $id = 1;
        $post = new Post(PostFactory::make(['status' => PostStatus::PUBLISHED->value]));
        $validData = [
            'title' => '有效的標題',
            'content' => '有效的內容',
            'publish_date' => '2025-12-31',
            'status' => PostStatus::PUBLISHED->value,
            'user_ip' => '192.168.1.1'
        ];

        $this->repository->shouldReceive('find')
            ->once()
            ->with($id)
            ->andReturn($post);

        $expectedData = array_merge($validData, ['updated_at' => Mockery::any()]);

        $this->repository->shouldReceive('update')
            ->once()
            ->with($id, Mockery::on(function ($data) use ($expectedData) {
                return $data['title'] === $expectedData['title']
                    && $data['content'] === $expectedData['content']
                    && $data['publish_date'] === $expectedData['publish_date']
                    && $data['status'] === $expectedData['status']
                    && $data['user_ip'] === $expectedData['user_ip']
                    && isset($data['updated_at']);
            }))
            ->andReturn(new Post(array_merge($post->toArray(), $validData)));

        $result = $this->service->updatePost($id, $validData);

        $this->assertInstanceOf(Post::class, $result);
        $this->assertEquals('有效的標題', $result->getTitle());
        $this->assertEquals('有效的內容', $result->getContent());
        $this->assertEquals('2025-12-31', $result->getPublishDate());
        $this->assertEquals(PostStatus::PUBLISHED->value, $result->getStatus());
    }

    public function testDeletePublishedPost(): void
    {
        $id = 1;
        $post = new Post(PostFactory::make(['status' => PostStatus::PUBLISHED->value]));

        $this->repository->shouldReceive('find')
            ->once()
            ->with($id)
            ->andReturn($post);

        $this->expectException(StateTransitionException::class);
        $this->expectExceptionMessage('已發布的文章不能刪除，請改為封存');

        $this->service->deletePost($id);
    }

    public function testSetPinnedStatus(): void
    {
        $id = 1;
        $post = new Post(PostFactory::make(['status' => PostStatus::PUBLISHED->value]));

        $this->repository->shouldReceive('find')
            ->once()
            ->with($id)
            ->andReturn($post);

        $this->repository->shouldReceive('setPinned')
            ->once()
            ->with($id, true)
            ->andReturn(true);

        $result = $this->service->setPinned($id, true);

        $this->assertTrue($result);
    }

    public function testSetPinnedForNonPublishedPost(): void
    {
        $id = 1;
        $post = new Post(PostFactory::make(['status' => PostStatus::DRAFT->value]));

        $this->repository->shouldReceive('find')
            ->once()
            ->with($id)
            ->andReturn($post);

        $this->expectException(StateTransitionException::class);
        $this->expectExceptionMessage('只有已發布的文章可以置頂');

        $this->service->setPinned($id, true);
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

    public function testRecordViewForNonPublishedPost(): void
    {
        $id = 1;
        $post = new Post(PostFactory::make(['status' => PostStatus::DRAFT->value]));

        $this->repository->shouldReceive('find')
            ->once()
            ->with($id)
            ->andReturn($post);

        $result = $this->service->recordView($id, '127.0.0.1');
        $this->assertFalse($result);
    }

    public function testRecordViewWithValidData(): void
    {
        $id = 1;
        $ip = '127.0.0.1';
        $userId = 1;
        $post = new Post(PostFactory::make([
            'status' => 'published'
        ]));

        $this->repository->shouldReceive('find')
            ->once()
            ->with($id)
            ->andReturn($post);

        $this->repository->shouldReceive('incrementViews')
            ->once()
            ->with($id, $ip, $userId)
            ->andReturn(true);

        $result = $this->service->recordView($id, $ip, $userId);

        $this->assertTrue($result);
    }
}
