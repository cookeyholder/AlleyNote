<?php

declare(strict_types=1);

namespace Tests\Integration\Security;

use Tests\Support\IntegrationTestCase;
use App\Domains\Post\Repositories\PostRepository;
use App\Shared\Contracts\CacheServiceInterface;
use App\Domains\Security\Contracts\LoggingSecurityServiceInterface;
use PDO;
use Mockery;

class SqlInjectionTest extends IntegrationTestCase
{
    private PostRepository $repository;
    private PDO $pdo;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $this->pdo->exec("CREATE TABLE posts (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            uuid VARCHAR(36) NOT NULL,
            seq_number INTEGER NOT NULL,
            title VARCHAR(255) NOT NULL,
            content TEXT NOT NULL,
            user_id INTEGER NOT NULL,
            user_ip VARCHAR(45) NOT NULL,
            is_pinned BOOLEAN DEFAULT 0,
            status VARCHAR(20) DEFAULT 'published',
            publish_date DATETIME NULL,
            views INTEGER DEFAULT 0,
            creation_source VARCHAR(50) DEFAULT 'web',
            creation_source_detail VARCHAR(255) NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            deleted_at DATETIME NULL
        )");

        $this->pdo->exec("INSERT INTO posts (uuid, seq_number, title, content, user_id, user_ip, is_pinned, status, created_at, updated_at) 
                        VALUES ('test-uuid', 1, 'Safe Title', 'Safe Content', 1, '127.0.0.1', 0, 'published', datetime('now'), datetime('now'))");

        $cache = Mockery::mock(CacheServiceInterface::class);
        $cache->shouldReceive('get')->andReturn(null);
        $cache->shouldReceive('set')->andReturn(true);

        $logger = Mockery::mock(LoggingSecurityServiceInterface::class);
        $logger->shouldReceive('logSecurityEvent')->andReturn(true);

        $this->repository = new PostRepository($this->pdo, $cache, $logger);
    }

    public function test_sql_injection_on_search_is_prevented(): void
    {
        // SQL Injection payload attempting to return all rows
        $maliciousPayload = "NonExistent' OR '1'='1";
        
        $results = $this->repository->search($maliciousPayload);
        
        $this->assertEmpty($results, 'SQL Injection vulnerability detected: Malicious payload was executed as SQL.');
    }
}
