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
use App\Shared\Exceptions\ValidationException;
use Mockery;
use Mockery\MockInterface;
use Tests\Support\UnitTestCase;

class PostServiceTest extends UnitTestCase
{
    private PostRepositoryInterface|MockInterface $repository;

    private ValidatorInterface|MockInterface $validator;

    private PostService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = Mockery::mock(PostRepositoryInterface::class);
        $this->validator = Mockery::mock(ValidatorInterface::class);

        // 設定 Validator Mock 的通用預期
        $this->validator->shouldReceive('addRule')->zeroOrMoreTimes()->andReturnSelf();
        $this->validator->shouldReceive('addMessage')->zeroOrMoreTimes()->andReturnSelf();

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
            ->andReturn($dtoData);

        $dto = new CreatePostDTO($this->validator, $dtoData);

        $expectedPost = new Post(array_merge($dtoData, ['id' => 1]));

        $this->repository->shouldReceive('create')
            ->once()
            ->with($dto->toArray())
            ->andReturn($expectedPost);

        $result = $this->service->createPost($dto);

        $this->assertInstanceOf(Post::class, $result);
        $this->assertEquals('測試文章', $result->getTitle());
    }

    public function testUpdatePostWithValidDTO(): void
    {
        $id = 1;
        $updateData = ['title' => '更新標題'];

        $this->validator->shouldReceive('validateOrFail')
            ->once()
            ->andReturn($updateData);

        $dto = new UpdatePostDTO($this->validator, $updateData);

        $expectedPost = new Post(['id' => $id, 'title' => '更新標題']);

        $this->repository->shouldReceive('update')
            ->once()
            ->with($id, $dto->toArray())
            ->andReturn($expectedPost);

        $result = $this->service->updatePost($id, $dto);

        $this->assertInstanceOf(Post::class, $result);
        $this->assertEquals('更新標題', $result->getTitle());
    }

    public function testDeletePost(): void
    {
        $id = 1;
        $this->repository->shouldReceive('delete')
            ->once()
            ->with($id)
            ->andReturn(true);

        $result = $this->service->deletePost($id);

        $this->assertTrue($result);
    }

    public function testListPosts(): void
    {
        $page = 1;
        $perPage = 10;
        $filters = ['status' => 'published'];

        $this->repository->shouldReceive('paginate')
            ->once()
            ->with($page, $perPage, $filters)
            ->andReturn(['items' => [], 'total' => 0]);

        $result = $this->service->listPosts($page, $perPage, $filters);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('items', $result);
    }

    public function testRecordViewWithValidIP(): void
    {
        $id = 1;
        $ip = '127.0.0.1';
        $post = new Post(['id' => $id, 'status' => PostStatus::PUBLISHED->value]);

        $this->repository->shouldReceive('find')->once()->with($id)->andReturn($post);
        $this->repository->shouldReceive('incrementViews')->once()->with($id, $ip, null)->andReturn(true);

        $result = $this->service->recordView($id, $ip);
        $this->assertTrue($result);
    }

    public function testRecordViewWithInvalidIP(): void
    {
        $id = 1;
        $ip = 'invalid-ip';
        $post = new Post(['id' => $id, 'status' => PostStatus::PUBLISHED->value]);

        $this->repository->shouldReceive('find')->once()->with($id)->andReturn($post);

        $this->expectException(ValidationException::class);
        $this->service->recordView($id, $ip);
    }
}
