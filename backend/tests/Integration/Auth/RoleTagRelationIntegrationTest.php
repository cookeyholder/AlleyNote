<?php

declare(strict_types=1);

namespace Tests\Integration\Auth;

use App\Domains\Auth\Repositories\RoleRepository;
use App\Domains\Post\Repositories\TagRepository;
use PDOStatement;
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

        $this->roleRepository = new RoleRepository($this->db);
        $this->tagRepository = new TagRepository($this->db);
    }

    public function testRolePermissionsCanBeReplacedWithoutResidualLinks(): void
    {
        $this->db->exec("INSERT INTO roles (name, display_name, description, created_at, updated_at) VALUES ('editor','Editor','desc',datetime('now'),datetime('now'))");
        $stmt = $this->db->prepare('INSERT INTO permissions (name, display_name, resource, action) VALUES (?, ?, ?, ?)');
        $stmt->execute(['post.read', 'Read', 'post', 'read']);
        $stmt->execute(['post.write', 'Write', 'post', 'write']);
        $stmt->execute(['post.delete', 'Delete', 'post', 'delete']);

        $this->assertTrue($this->roleRepository->setRolePermissions(1, [1, 2]));
        $this->assertSame([1, 2], $this->roleRepository->getRolePermissionIds(1));

        $this->assertTrue($this->roleRepository->setRolePermissions(1, [3]));
        $this->assertSame([3], $this->roleRepository->getRolePermissionIds(1));
    }

    public function testDetachTagFromAllPostsOnlyAffectsTargetTag(): void
    {
        $now = date('Y-m-d H:i:s');
        $stmt = $this->db->prepare('INSERT INTO posts (uuid, seq_number, title, content, user_id, publish_date, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute(['post-uuid-1', 1, 'P1', 'content-1', 1, $now, $now, $now]);
        $stmt->execute(['post-uuid-2', 2, 'P2', 'content-2', 1, $now, $now, $now]);
        $tagStmt = $this->db->prepare('INSERT INTO tags (name, slug, created_at, updated_at) VALUES (?, ?, datetime(\'now\'), datetime(\'now\'))');
        $tagStmt->execute(['T1', 't1']);
        $tagStmt->execute(['T2', 't2']);
        $this->db->exec('INSERT INTO post_tags (post_id, tag_id) VALUES (1, 1), (2, 1), (1, 2)');

        $this->tagRepository->detachFromAllPosts(1);

        $tag1CountStmt = $this->db->query('SELECT COUNT(*) FROM post_tags WHERE tag_id = 1');
        $tag2CountStmt = $this->db->query('SELECT COUNT(*) FROM post_tags WHERE tag_id = 2');

        self::assertInstanceOf(PDOStatement::class, $tag1CountStmt);
        self::assertInstanceOf(PDOStatement::class, $tag2CountStmt);

        $remainingTag1 = (int) $tag1CountStmt->fetchColumn();
        $remainingTag2 = (int) $tag2CountStmt->fetchColumn();

        $this->assertSame(0, $remainingTag1);
        $this->assertSame(1, $remainingTag2);
    }
}
