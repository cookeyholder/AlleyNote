<?php

declare(strict_types=1);

namespace Tests\Integration\Security;

use App\Domains\Post\Repositories\PostRepository;
use App\Domains\Security\Contracts\LoggingSecurityServiceInterface;
use App\Shared\Contracts\CacheServiceInterface;
use Mockery;
use Tests\Support\DatabaseTestCase;

class SqlInjectionTest extends DatabaseTestCase
{
    private PostRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        // 使用 insertTestPost 方法插入測試資料
        $this->insertTestPost([
            'uuid' => 'test-uuid',
            'title' => 'Safe Title',
            'content' => 'Safe Content',
        ]);

        $cache = Mockery::mock(CacheServiceInterface::class);
        $cache->shouldReceive('get')->andReturn(null);
        $cache->shouldReceive('set')->andReturn(true);

        $logger = Mockery::mock(LoggingSecurityServiceInterface::class);
        $logger->shouldReceive('logSecurityEvent')->andReturn(true);

        $this->repository = new PostRepository($this->db, $cache, $logger);
    }

    public function test_sql_injection_on_search_is_prevented(): void
    {
        // SQL Injection payload attempting to return all rows
        $maliciousPayload = "NonExistent' OR '1'='1";

        $results = $this->repository->search($maliciousPayload);

        // 因為使用了 Prepared Statements，它會將 Payload 視為純字串，因此搜尋不到任何結果
        $this->assertEmpty($results, '檢測到 SQL 注入漏洞：惡意 Payload 被當作 SQL 語法執行了。');
    }
}

