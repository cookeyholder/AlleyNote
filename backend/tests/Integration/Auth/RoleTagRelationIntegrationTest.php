<?php

declare(strict_types=1);

namespace Tests\Integration\Auth;

use App\Domains\Auth\Repositories\RoleRepository;
use App\Domains\Post\Repositories\TagRepository;
use PHPUnit\Framework\Attributes\Group;
use Tests\Support\IntegrationTestCase;

#[Group('integration')]
#[Group('auth')]
#[Group('tag')]
final class RoleTagRelationIntegrationTest extends IntegrationTestCase
{
    private RoleRepository $roleRepository;

    private TagRepository $tagRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->db->exec('CREATE TABLE IF NOT EXISTS roles (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL UNIQUE,
            display_name TEXT NOT NULL,
            description TEXT,
            created_at TEXT,
            updated_at TEXT
        )');

        $this->db->exec('CREATE TABLE IF NOT EXISTS permissions (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL UNIQUE,
            display_name TEXT NOT NULL
        )');

        $this->db->exec('CREATE TABLE IF NOT EXISTS role_permissions (
            role_id INTEGER NOT NULL,
            permission_id INTEGER NOT NULL,
            created_at TEXT,
            PRIMARY KEY (role_id, permission_id)
        )');

        $this->db->exec('CREATE TABLE IF NOT EXISTS tags (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL UNIQUE,
            slug TEXT UNIQUE,
            description TEXT,
            color TEXT,
            usage_count INTEGER DEFAULT 0,
            created_at TEXT,
            updated_at TEXT
        )');

        $this->db->exec('CREATE TABLE IF NOT EXISTS post_tags (
            post_id INTEGER NOT NULL,
            tag_id INTEGER NOT NULL,
            PRIMARY KEY (post_id, tag_id)
        )');

        $this->db->exec('CREATE TABLE IF NOT EXISTS posts (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title TEXT NOT NULL
        )');

        $this->roleRepository = new RoleRepository($this->db);
        $this->tagRepository = new TagRepository($this->db);
    }

    public function testRolePermissionsCanBeReplacedWithoutResidualLinks(): void
    {
        $this->db->exec("INSERT INTO roles (name, display_name, description, created_at, updated_at) VALUES ('editor','Editor','desc',datetime('now'),datetime('now'))");
        $this->db->exec("INSERT INTO permissions (name, display_name) VALUES ('post.read','Read')");
        $this->db->exec("INSERT INTO permissions (name, display_name) VALUES ('post.write','Write')");
        $this->db->exec("INSERT INTO permissions (name, display_name) VALUES ('post.delete','Delete')");

        $this->assertTrue($this->roleRepository->setRolePermissions(1, [1, 2]));
        $this->assertSame([1, 2], $this->roleRepository->getRolePermissionIds(1));

        $this->assertTrue($this->roleRepository->setRolePermissions(1, [3]));
        $this->assertSame([3], $this->roleRepository->getRolePermissionIds(1));
    }

    public function testDetachTagFromAllPostsOnlyAffectsTargetTag(): void
    {
        $now = date('Y-m-d H:i:s');
        $this->db->exec("INSERT INTO posts (uuid, seq_number, title, content, user_id, publish_date, created_at, updated_at) VALUES
            ('post-uuid-1', 1, 'P1', 'content-1', 1, '{$now}', '{$now}', '{$now}'),
            ('post-uuid-2', 2, 'P2', 'content-2', 1, '{$now}', '{$now}', '{$now}')");
        $this->db->exec("INSERT INTO tags (name, slug, created_at, updated_at) VALUES ('T1', 't1', datetime('now'), datetime('now'))");
        $this->db->exec("INSERT INTO tags (name, slug, created_at, updated_at) VALUES ('T2', 't2', datetime('now'), datetime('now'))");
        $this->db->exec('INSERT INTO post_tags (post_id, tag_id) VALUES (1, 1), (2, 1), (1, 2)');

        $this->tagRepository->detachFromAllPosts(1);

        $remainingTag1 = (int) $this->db->query('SELECT COUNT(*) FROM post_tags WHERE tag_id = 1')->fetchColumn();
        $remainingTag2 = (int) $this->db->query('SELECT COUNT(*) FROM post_tags WHERE tag_id = 2')->fetchColumn();

        $this->assertSame(0, $remainingTag1);
        $this->assertSame(1, $remainingTag2);
    }
}
