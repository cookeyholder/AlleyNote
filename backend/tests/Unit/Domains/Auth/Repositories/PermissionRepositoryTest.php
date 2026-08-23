<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Auth\Repositories;

use App\Domains\Auth\Repositories\PermissionRepository;
use PDO;
use Tests\Support\UnitTestCase;

/**
 * 權限儲存庫單元測試.
 */
final class PermissionRepositoryTest extends UnitTestCase
{
    private PDO $db;

    private PermissionRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->db = new PDO('sqlite::memory:');
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $this->db->exec('
            CREATE TABLE permissions (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL UNIQUE,
                resource TEXT NOT NULL,
                action TEXT NOT NULL,
                description TEXT,
                created_at TEXT,
                updated_at TEXT
            );
        ');

        $this->repository = new PermissionRepository($this->db);
    }

    /**
     * 測試查詢權限（findAll, findById, findByName, findByIds, findByResource, findAllGroupedByResource）.
     */
    public function testPermissionQueries(): void
    {
        $this->assertSame([], $this->repository->findAll());
        $this->assertSame([], $this->repository->findByIds([]));

        $this->db->exec("
            INSERT INTO permissions (id, name, resource, action, description) VALUES
            (1, 'post.view', 'post', 'view', '查看文章'),
            (2, 'post.create', 'post', 'create', '新增文章'),
            (3, 'user.view', 'user', 'view', '查看使用者');
        ");

        // findAll
        $all = $this->repository->findAll();
        $this->assertCount(3, $all);

        // findById
        $p1 = $this->repository->findById(1);
        $this->assertNotNull($p1);
        $this->assertSame('post.view', $p1->getName());
        $this->assertSame('post', $p1->getResource());
        $this->assertSame('view', $p1->getAction());
        $this->assertSame('查看文章', $p1->getDescription());
        $this->assertNull($this->repository->findById(999));

        // findByName
        $p2 = $this->repository->findByName('post.create');
        $this->assertNotNull($p2);
        $this->assertSame(2, $p2->getId());
        $this->assertNull($this->repository->findByName('nonexistent'));

        // findByIds
        $selected = $this->repository->findByIds([1, 3]);
        $this->assertCount(2, $selected);
        $this->assertSame('post.view', $selected[0]->getName());
        $this->assertSame('user.view', $selected[1]->getName());

        // findByResource
        $postPerms = $this->repository->findByResource('post');
        $this->assertCount(2, $postPerms);

        // findAllGroupedByResource
        $grouped = $this->repository->findAllGroupedByResource();
        $this->assertArrayHasKey('post', $grouped);
        $this->assertArrayHasKey('user', $grouped);
        $this->assertCount(2, $grouped['post']);
        $this->assertCount(1, $grouped['user']);
    }
}
