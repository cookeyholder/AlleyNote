<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Domains\Post\Contracts\PostRepositoryInterface;
use App\Domains\Post\DTOs\CreatePostDTO;
use App\Domains\Post\DTOs\UpdatePostDTO;
use App\Domains\Post\Enums\PostStatus;
use App\Domains\Post\Models\Post;
use App\Domains\Post\Services\PostService;
use App\Shared\Contracts\ValidatorInterface;
use App\Shared\Exceptions\NotFoundException;
use App\Shared\Exceptions\StateTransitionException;
use App\Shared\Exceptions\ValidationException;
use DateTimeImmutable;
use Exception;
use InvalidArgumentException;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use RuntimeException;

class PostServiceTest extends MockeryTestCase
{
    private PostRepositoryInterface|MockInterface $repository;

    private App\Shared\Contracts\ValidatorInterface|MockInterface $validator;

    private PostService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = Mockery::mock(PostRepositoryInterface::class);
        $this->validator = Mockery::mock(ValidatorInterface::class);

        // 設定 Validator Mock 的通用預期
        $this->validator->shouldReceive('addRule')
            ->zeroOrMoreTimes()
            ->andReturnSelf();
        $this->validator->shouldReceive('addMessage')
            ->zeroOrMoreTimes()
            ->andReturnSelf();

        $this->service = new PostService($this->repository);
    }

    public function testCreatePostWithValidDTO(): void
    {
        $dtoData = [
            'title' => '測試文章',
            'content' => '這是測試內容',
            'user_id' => 1,
            'user_ip' => '192.168.1.1',
            'status' => PostStatus::DRAFT->value,
        ];

        $this->validator->shouldReceive('validateOrFail')
            ->once()
            ->with(Mockery::any(), Mockery::any())
            ->andReturn($dtoData);

        $dto = new CreatePostDTO($this->validator, $dtoData);

        $expectedPost = new Post([
            'id' => 1,
            'uuid' => 'test-uuid',
            'title' => '測試文章',
            'content' => '這是測試內容',
            'user_id' => 1,
            'user_ip' => '192.168.1.1',
            'status' => PostStatus::DRAFT->value,
            'created_at' => new DateTimeImmutable()->format(DateTimeImmutable::RFC3339),
            'updated_at' => null,
        ]);

        $this->repository->shouldReceive('create')
            ->once()
            ->with(Mockery::on(function ($data) {
                return $data['title'] === '測試文章'
                    && $data['content'] === '這是測試內容'
                    && $data['user_id'] === 1
                    && $data['user_ip'] === '192.168.1.1'
                    && $data['status'] === PostStatus::DRAFT->value
                    && isset($data['created_at']);
            }))
            ->andReturn($expectedPost);

        $result = $this->service->createPost($dto);

        $this->assertInstanceOf(Post::class, $result);
        $this->assertEquals('測試文章', $result->getTitle());
        $this->assertEquals('這是測試內容', $result->getContent());
    }

    public function testUpdatePostWithValidDTO(): void
    {
        $id = 1;
        $initialPost = new Post([
            'id' => $id,
            'uuid' => 'test-uuid',
            'title' => '原始標題',
            'content' => '原始內容',
            'user_id' => 1,
            'user_ip' => '192.168.1.1',
            'status' => PostStatus::DRAFT->value,
            'created_at' => new DateTimeImmutable()->format(DateTimeImmutable::RFC3339),
            'updated_at' => null,
        ]);

        $updateData = [
            'title' => '更新的標題',
            'content' => '更新的內容',
        ];

        $this->validator->shouldReceive('validateOrFail')
            ->once()
            ->with(Mockery::any(), Mockery::any())
            ->andReturn($updateData);

        $dto = new UpdatePostDTO($this->validator, $updateData);

        $updatedPost = new Post([
            'id' => $id,
            'uuid' => 'test-uuid',
            'title' => '更新的標題',
            'content' => '更新的內容',
            'user_id' => 1,
            'user_ip' => '192.168.1.1',
            'status' => PostStatus::DRAFT->value,
            'created_at' => $initialPost->getCreatedAt(),
            'updated_at' => new DateTimeImmutable()->format(DateTimeImmutable::RFC3339),
        ]);

        $this->repository->shouldReceive('find')
            ->once()
            ->with($id)
            ->andReturn($initialPost);

        $this->repository->shouldReceive('update')
            ->once()
            ->with($id, Mockery::on(function ($data) {
                return $data['title'] === '更新的標題'
                    && $data['content'] === '更新的內容'
                    && isset($data['updated_at']);
            }))
            ->andReturn($updatedPost);

        $result = $this->service->updatePost($id, $dto);

        $this->assertInstanceOf(Post::class, $result);
        $this->assertEquals('更新的標題', $result->getTitle());
        $this->assertEquals('更新的內容', $result->getContent());
    }

    public function testUpdatePostWithInvalidStatusTransition(): void
    {
        $id = 1;
        $publishedPost = new Post([
            'id' => $id,
            'uuid' => 'test-uuid',
            'title' => '已發布文章',
            'content' => '已發布內容',
            'user_id' => 1,
            'user_ip' => '192.168.1.1',
            'status' => PostStatus::PUBLISHED->value,
            'created_at' => new DateTimeImmutable()->format(DateTimeImmutable::RFC3339),
            'updated_at' => null,
        ]);

        $updateData = [
            'status' => PostStatus::DRAFT->value,
        ];

        $this->validator->shouldReceive('validateOrFail')
            ->once()
            ->with(Mockery::any(), Mockery::any())
            ->andReturn($updateData);

        $dto = new UpdatePostDTO($this->validator, $updateData);

        $this->repository->shouldReceive('find')
            ->once()
            ->with($id)
            ->andReturn($publishedPost);

        $this->expectException(StateTransitionException::class);

        $this->service->updatePost($id, $dto);
    }

    public function testUpdateNonExistentPost(): void
    {
        $id = 999;
        $updateData = ['title' => '測試標題'];
        $this->validator->shouldReceive('validateOrFail')
            ->once()
            ->with(Mockery::any(), Mockery::any())
            ->andReturn($updateData);

        $dto = new UpdatePostDTO($this->validator, $updateData);

        $this->repository->shouldReceive('find')
            ->once()
            ->with($id)
            ->andReturnNull();

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('找不到指定的文章');

        $this->service->updatePost($id, $dto);
    }

    public function testUpdatePostWithNoChanges(): void
    {
        $id = 1;
        $post = new Post([
            'id' => $id,
            'uuid' => 'test-uuid',
            'title' => '測試標題',
            'content' => '測試內容',
            'user_id' => 1,
            'user_ip' => '192.168.1.1',
            'status' => PostStatus::DRAFT->value,
            'created_at' => new DateTimeImmutable()->format(DateTimeImmutable::RFC3339),
            'updated_at' => null,
        ]);

        // 建立沒有變更的 DTO（這會需要檢查 UpdatePostDTO 的實作）
        // 空陣列不會觸發驗證，因為 UpdatePostDTO 會直接返回
        $dto = new UpdatePostDTO($this->validator, []);

        $this->repository->shouldReceive('find')
            ->once()
            ->with($id)
            ->andReturn($post);

        // 如果沒有變更，應該直接返回原本的 post
        $result = $this->service->updatePost($id, $dto);

        $this->assertSame($post, $result);
    }

    public function testDeletePost(): void
    {
        $id = 1;

        $this->repository->shouldReceive('safeDelete')
            ->once()
            ->with($id)
            ->andReturn(true);

        $result = $this->service->deletePost($id);

        $this->assertTrue($result);
    }

    public function testDeletePostWithRepositoryException(): void
    {
        $id = 1;

        $this->repository->shouldReceive('safeDelete')
            ->once()
            ->with($id)
            ->andThrow(new InvalidArgumentException('測試錯誤'));

        $this->expectException(StateTransitionException::class);
        $this->expectExceptionMessage('測試錯誤');

        $this->service->deletePost($id);
    }

    public function testDeletePostWithGeneralException(): void
    {
        $id = 1;

        $this->repository->shouldReceive('safeDelete')
            ->once()
            ->with($id)
            ->andThrow(new Exception('一般錯誤'));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('刪除文章時發生錯誤');

        $this->service->deletePost($id);
    }

    public function testFindById(): void
    {
        $id = 1;
        $post = new Post([
            'id' => $id,
            'uuid' => 'test-uuid',
            'title' => '測試文章',
            'content' => '測試內容',
            'user_id' => 1,
            'user_ip' => '192.168.1.1',
            'status' => PostStatus::PUBLISHED->value,
            'created_at' => new DateTimeImmutable()->format(DateTimeImmutable::RFC3339),
            'updated_at' => null,
        ]);

        $this->repository->shouldReceive('find')
            ->once()
            ->with($id)
            ->andReturn($post);

        $result = $this->service->findById($id);

        $this->assertInstanceOf(Post::class, $result);
        $this->assertEquals($id, $result->getId());
        $this->assertEquals('測試文章', $result->getTitle());
    }

    public function testFindByIdNotFound(): void
    {
        $id = 999;

        $this->repository->shouldReceive('find')
            ->once()
            ->with($id)
            ->andReturnNull();

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('找不到指定的文章');

        $this->service->findById($id);
    }

    public function testListPosts(): void
    {
        $page = 1;
        $perPage = 10;
        $filters = ['status' => 'published'];

        $expectedResult = [
            'items' => [
                new Post([
                    'id' => 1,
                    'title' => '文章1',
                    'content' => '內容1',
                    'user_id' => 1,
                    'user_ip' => '192.168.1.1',
                    'status' => PostStatus::PUBLISHED->value,
                ]),
                new Post([
                    'id' => 2,
                    'title' => '文章2',
                    'content' => '內容2',
                    'user_id' => 1,
                    'user_ip' => '192.168.1.1',
                    'status' => PostStatus::PUBLISHED->value,
                ]),
            ],
            'total' => 2,
            'page' => $page,
            'perPage' => $perPage,
        ];

        $this->repository->shouldReceive('paginate')
            ->once()
            ->with($page, $perPage, $filters)
            ->andReturn($expectedResult);

        $result = $this->service->listPosts($page, $perPage, $filters);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('items', $result);
        $this->assertArrayHasKey('total', $result);
        $this->assertEquals(2, $result['total']);
        $this->assertCount(2, $result['items']);
    }

    public function testGetPinnedPosts(): void
    {
        $limit = 5;

        $expectedPosts = [
            new Post([
                'id' => 1,
                'title' => '置頂文章1',
                'content' => '置頂內容1',
                'user_id' => 1,
                'user_ip' => '192.168.1.1',
                'status' => PostStatus::PUBLISHED->value,
                'is_pinned' => true,
            ]),
        ];

        $this->repository->shouldReceive('getPinnedPosts')
            ->once()
            ->with($limit)
            ->andReturn($expectedPosts);

        $result = $this->service->getPinnedPosts($limit);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertInstanceOf(Post::class, $result[0]);
        $this->assertEquals('置頂文章1', $result[0]->getTitle());
    }

    public function testSetPinned(): void
    {
        $id = 1;
        $isPinned = true;

        $this->repository->shouldReceive('safeSetPinned')
            ->once()
            ->with($id, $isPinned)
            ->andReturn(true);

        $result = $this->service->setPinned($id, $isPinned);

        $this->assertTrue($result);
    }

    public function testSetPinnedWithRepositoryException(): void
    {
        $id = 1;
        $isPinned = true;

        $this->repository->shouldReceive('safeSetPinned')
            ->once()
            ->with($id, $isPinned)
            ->andThrow(new InvalidArgumentException('置頂操作失敗'));

        $this->expectException(StateTransitionException::class);
        $this->expectExceptionMessage('置頂操作失敗');

        $this->service->setPinned($id, $isPinned);
    }

    public function testSetPinnedWithGeneralException(): void
    {
        $id = 1;
        $isPinned = true;

        $this->repository->shouldReceive('safeSetPinned')
            ->once()
            ->with($id, $isPinned)
            ->andThrow(new Exception('一般錯誤'));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('設定置頂狀態時發生錯誤');

        $this->service->setPinned($id, $isPinned);
    }

    public function testSetTags(): void
    {
        $id = 1;
        $tagIds = [1, 2, 3];
        $post = new Post([
            'id' => $id,
            'title' => '測試文章',
            'content' => '測試內容',
            'user_id' => 1,
            'user_ip' => '192.168.1.1',
            'status' => PostStatus::PUBLISHED->value,
        ]);

        $this->repository->shouldReceive('find')
            ->once()
            ->with($id)
            ->andReturn($post);

        $this->repository->shouldReceive('setTags')
            ->once()
            ->with($id, $tagIds)
            ->andReturn(true);

        $result = $this->service->setTags($id, $tagIds);

        $this->assertTrue($result);
    }

    public function testSetTagsWithNonExistentPost(): void
    {
        $id = 999;
        $tagIds = [1, 2, 3];

        $this->repository->shouldReceive('find')
            ->once()
            ->with($id)
            ->andReturnNull();

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('找不到指定的文章');

        $this->service->setTags($id, $tagIds);
    }

    public function testRecordView(): void
    {
        $id = 1;
        $userIp = '192.168.1.1';
        $userId = 1;

        $post = new Post([
            'id' => $id,
            'title' => '測試文章',
            'content' => '測試內容',
            'user_id' => 1,
            'user_ip' => '192.168.1.1',
            'status' => PostStatus::PUBLISHED->value,
        ]);

        $this->repository->shouldReceive('find')
            ->once()
            ->with($id)
            ->andReturn($post);

        $this->repository->shouldReceive('incrementViews')
            ->once()
            ->with($id, $userIp, $userId)
            ->andReturn(true);

        $result = $this->service->recordView($id, $userIp, $userId);

        $this->assertTrue($result);
    }

    public function testRecordViewWithInvalidIp(): void
    {
        $id = 1;
        $invalidIp = 'invalid-ip';

        $post = new Post([
            'id' => $id,
            'title' => '測試文章',
            'content' => '測試內容',
            'user_id' => 1,
            'user_ip' => '192.168.1.1',
            'status' => PostStatus::PUBLISHED->value,
        ]);

        $this->repository->shouldReceive('find')
            ->once()
            ->with($id)
            ->andReturn($post);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('無效的 IP 位址');

        $this->service->recordView($id, $invalidIp);
    }

    public function testRecordViewForNonPublishedPost(): void
    {
        $id = 1;
        $userIp = '192.168.1.1';

        $post = new Post([
            'id' => $id,
            'title' => '草稿文章',
            'content' => '草稿內容',
            'user_id' => 1,
            'user_ip' => '192.168.1.1',
            'status' => PostStatus::DRAFT->value,
        ]);

        $this->repository->shouldReceive('find')
            ->once()
            ->with($id)
            ->andReturn($post);

        $result = $this->service->recordView($id, $userIp);

        $this->assertFalse($result);
    }
}
