<?php

declare(strict_types=1);

namespace Tests\Integration\Repositories;

use App\Domains\Post\Enums\PostStatus;
use App\Domains\Post\Models\Post;
use App\Domains\Post\Repositories\PostRepository;
use App\Domains\Security\Contracts\LoggingSecurityServiceInterface;
use App\Infrastructure\Services\CacheService;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PDO;
use Tests\Support\IntegrationTestCase;
use RuntimeException;

class PostRepositoryTest extends IntegrationTestCase
{
    use MockeryPHPUnitIntegration;

    private PostRepository $repository;

    private CacheService $cacheService;

    private LoggingSecurityServiceInterface $logger;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock 依賴項目
        $this->cacheService = Mockery::mock(CacheService::class);
        $this->logger = Mockery::mock(LoggingSecurityServiceInterface::class);

        // 設定快取預設行為
        $this->cacheService->shouldReceive('remember')
            ->andReturnUsing(function ($key, $callback, $ttl = null) {
                return $callback();
            });

        $this->logger->shouldReceive('logFailedLogin')
            ->andReturn(true)
            ->byDefault();

        $this->logger->shouldReceive('logSuspiciousActivity')
            ->andReturn(true)
            ->byDefault();

        $this->logger->shouldReceive('logSecurityEvent')
            ->zeroOrMoreTimes()
            ->andReturn(true);
            
        $this->cacheService->shouldReceive('delete')->andReturn(true);
        $this->cacheService->shouldReceive('deletePattern')->andReturn(true);

        // 使用 IntegrationTestCase 提供的 $this->db
        $this->repository = new PostRepository($this->db, $this->cacheService, $this->logger);
    }

    private function createTestPostData(array $data = []): array
    {
        $defaultData = [
            'uuid' => 'test-uuid-' . uniqid(),
            'seq_number' => rand(1000, 9999),
            'title' => '測試文章標題',
            'content' => '測試文章內容',
            'user_id' => 1,
            'user_ip' => '127.0.0.1',
            'is_pinned' => 0,
            'status' => PostStatus::DRAFT->value,
            'publish_date' => date('Y-m-d H:i:s'),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        return array_merge($defaultData, $data);
    }

    public function testCreatePost(): void
    {
        $data = $this->createTestPostData([
            'title' => '新建文章',
            'content' => '新建文章內容',
        ]);

        $post = $this->repository->create($data);

        $this->assertInstanceOf(Post::class, $post);
        $this->assertIsInt($post->getId());
        $this->assertEquals('新建文章', $post->getTitle());
    }

    public function testFindPostById(): void
    {
        $data = $this->createTestPostData();
        $post = $this->repository->create($data);
        
        $foundPost = $this->repository->find($post->getId());

        $this->assertInstanceOf(Post::class, $foundPost);
        $this->assertEquals($post->getId(), $foundPost->getId());
    }
}
