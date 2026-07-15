<?php

declare(strict_types=1);

namespace App\Domains\Post\Repositories;

use App\Domains\Post\Enums\PostStatus;
use App\Domains\Post\Models\Post;
use App\Domains\Post\Services\PostCacheKeyService;
use App\Domains\Security\Contracts\LoggingSecurityServiceInterface;
use App\Shared\Contracts\CacheServiceInterface;
use InvalidArgumentException;
use PDO;
use PDOException;
use RuntimeException;
use Throwable;

class PostCrudRepository extends PostBaseRepository
{
    use HasTransactionSupport;

    private const SQL_INSERT_POST = 'INSERT INTO posts (uuid, seq_number, title, content, user_id, user_ip, is_pinned, status, publish_date, created_at, updated_at) VALUES (:uuid, :seq_number, :title, :content, :user_id, :user_ip, :is_pinned, :status, :publish_date, :created_at, :updated_at)';

    private const SQL_INSERT_TAG = 'INSERT INTO post_tags (post_id, tag_id, created_at) VALUES (?, ?, ?)';

    private const ALLOWED_UPDATE_FIELDS = [
        'title',
        'content',
        'user_ip',
        'is_pinned',
        'status',
        'publish_date',
        'updated_at',
    ];

    private const ALLOWED_CONDITION_FIELDS = [
        'id',
        'uuid',
        'seq_number',
        'title',
        'user_id',
        'is_pinned',
        'status',
        'publish_date',
        'creation_source',
        'creation_source_detail',
        'created_at',
        'updated_at',
    ];

    public function __construct(
        PDO $db,
        CacheServiceInterface $cache,
        private readonly LoggingSecurityServiceInterface $logger,
    ) {
        parent::__construct($db, $cache);
    }

    public function find(int $id): ?Post
    {
        $cacheKey = PostCacheKeyService::post($id);
        /** @var array<string, mixed>|null $data */
        $data = $this->cache->remember($cacheKey, function () use ($id) {
            $sql = $this->buildSelectQuery('p.id = ?');
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            /** @var array<string, mixed>|false $result */
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$result) {
                return null;
            }

            return $this->preparePostData($result);
        }, self::CACHE_TTL);

        return $data !== null ? Post::fromArray($data) : null;
    }

    public function findWithLock(int $id): ?Post
    {
        $sql = $this->buildSelectQuery('p.id = ?');
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        /** @var array<string, mixed>|false $result */
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$result) {
            return null;
        }

        return Post::fromArray($this->preparePostData($result));
    }

    public function findByUuid(string $uuid): ?Post
    {
        $cacheKey = PostCacheKeyService::postByUuid($uuid);
        /** @var array<string, mixed>|null $data */
        $data = $this->cache->remember($cacheKey, function () use ($uuid) {
            $sql = $this->buildSelectQuery('p.uuid = ?');
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$uuid]);
            /** @var array<string, mixed>|false $result */
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$result) {
                return null;
            }

            return $this->preparePostData($result);
        }, self::CACHE_TTL);

        return $data !== null ? Post::fromArray($data) : null;
    }

    public function findBySeqNumber(int $seqNumber): ?Post
    {
        $sql = $this->buildSelectQuery('seq_number = ?');
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$seqNumber]);
        /** @var array<string, mixed>|false $result */
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$result) {
            return null;
        }

        return Post::fromArray($this->preparePostData($result));
    }

    public function safeDelete(int $id): bool
    {
        /** @var bool */
        return $this->executeInTransaction(function () use ($id) {
            $post = $this->findWithLock($id);
            if (!$post) {
                return false;
            }

            if ($post->getStatus() === PostStatus::PUBLISHED) {
                throw new InvalidArgumentException('已發布的文章不能刪除，請改為封存');
            }

            return $this->delete($id);
        });
    }

    public function safeSetPinned(int $id, bool $isPinned): bool
    {
        /** @var bool */
        return $this->executeInTransaction(function () use ($id, $isPinned) {
            $post = $this->findWithLock($id);
            if (!$post) {
                return false;
            }

            if ($isPinned && $post->getStatus() !== PostStatus::PUBLISHED) {
                throw new InvalidArgumentException('只有已發布的文章可以置頂');
            }

            return $this->setPinned($id, $isPinned);
        });
    }

    public function create(array $data, array $tagIds = []): Post
    {
        /** @var Post */
        return $this->executeInTransaction(function () use ($data, $tagIds) {
            $data = $this->prepareNewPostData($data);
            $stmt = $this->db->prepare(self::SQL_INSERT_POST);
            if (!$stmt->execute($data)) {
                $errorInfo = $stmt->errorInfo();

                $errorMsg = $errorInfo[2] ?? 'unknown';

                throw new PDOException('Failed to insert post: ' . (is_scalar($errorMsg) ? (string) $errorMsg : 'unknown'));
            }
            $postId = (int) $this->db->lastInsertId();

            if (!empty($tagIds)) {
                $this->assignTags($postId, $tagIds);
            }

            $this->cache->delete('posts:latest');
            $this->cache->delete('posts:pinned');

            $post = $this->find($postId);
            if (!$post) {
                throw new RuntimeException('無法建立文章');
            }

            return $post;
        });
    }

    public function update(int $id, array $data): Post
    {
        $post = $this->find($id);
        if (!$post) {
            throw new InvalidArgumentException('找不到指定的文章');
        }

        $protectedFields = ['id', 'uuid', 'seq_number', 'created_at', 'views'];
        foreach ($protectedFields as $field) {
            unset($data[$field]);
        }

        $data['updated_at'] = format_datetime();

        $sets = [];
        $params = ['id' => $id];
        foreach ($data as $key => $value) {
            if (in_array($key, self::ALLOWED_UPDATE_FIELDS, true)) {
                $sets[] = "{$key} = :{$key}";
                $params[$key] = $value;
            } else {
                $this->logger->logSecurityEvent('Attempt to update disallowed field', [
                    'field'   => $key,
                    'post_id' => $id,
                    'action'  => 'update_post',
                ]);
            }
        }

        if (empty($sets)) {
            return $post;
        }

        $sql = 'UPDATE posts SET ' . implode(', ', $sets) . ' WHERE id = :id';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        $this->invalidateCache($id);

        $updated = $this->find($id);
        if (!$updated) {
            throw new RuntimeException('更新後找不到文章');
        }

        return $updated;
    }

    public function delete(int $id): bool
    {
        $this->invalidateCache($id);
        $stmt = $this->db->prepare('DELETE FROM posts WHERE id = ?');

        return $stmt->execute([$id]);
    }

    /**
     * @return array{items: list<Post>, total: int, page: int, perPage: int, lastPage: int}
     */
    public function paginate(int $page = 1, int $perPage = 10, array $conditions = []): array
    {
        $offset = ($page - 1) * $perPage;

        $where = [];
        $params = [];
        if (!empty($conditions)) {
            foreach ($conditions as $key => $value) {
                if (in_array($key, self::ALLOWED_CONDITION_FIELDS, true)) {
                    $where[] = "p.{$key} = :{$key}";
                    $params[$key] = $value;
                } else {
                    $this->logger->logSecurityEvent('Attempt to query with disallowed field', [
                        'field'      => $key,
                        'action'     => 'get_paginated',
                        'conditions' => array_keys($conditions),
                    ]);
                }
            }
        }

        $baseWhere = empty($where) ? 'p.deleted_at IS NULL' : implode(' AND ', $where) . ' AND p.deleted_at IS NULL';
        $publishTimeCheck = "AND (p.status != 'published' OR p.publish_date IS NULL OR p.publish_date <= datetime('now'))";
        $countSql = 'SELECT COUNT(*) FROM posts p WHERE ' . $baseWhere . ' ' . $publishTimeCheck;
        $stmt = $this->db->prepare($countSql);
        $stmt->execute($params);
        $total = (int) $stmt->fetchColumn();

        $sql = 'SELECT p.id, p.uuid, p.seq_number, p.title, p.content, p.user_id, p.user_ip, p.is_pinned, p.status, p.publish_date, p.views, p.created_at, p.updated_at, p.creation_source, p.creation_source_detail, u.username as author'
            . ' FROM posts p'
            . ' LEFT JOIN users u ON p.user_id = u.id'
            . ' WHERE ' . $baseWhere . ' ' . $publishTimeCheck
            . ' ORDER BY p.is_pinned DESC, p.publish_date DESC, p.created_at DESC, p.id DESC LIMIT :offset, :limit';
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        foreach ($params as $key => $value) {
            $stmt->bindValue(":{$key}", $value);
        }
        $stmt->execute();
        /** @var list<array<string, mixed>> $rows */
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $items = array_map(
            fn(array $row): Post => Post::fromArray($this->preparePostData($row)),
            $rows,
        );

        return [
            'items'    => $items,
            'total'    => $total,
            'page'     => $page,
            'perPage'  => $perPage,
            'lastPage' => (int) ceil($total / $perPage),
        ];
    }

    /**
     * @return Post[]
     */
    public function getPinnedPosts(int $limit = 5): array
    {
        $cacheKey = PostCacheKeyService::pinnedPosts();

        /** @var list<Post> $result */
        $result = $this->cache->remember($cacheKey, function () use ($limit) {
            $sql = $this->buildSelectQuery("is_pinned = 1 AND (status != 'published' OR publish_date IS NULL OR publish_date <= datetime('now'))")
                . ' ORDER BY publish_date DESC LIMIT :limit';
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();

            /** @var list<array<string, mixed>> $rows */
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return array_map(
                fn(array $row): Post => Post::fromArray($this->preparePostData($row)),
                $rows,
            );
        }, self::CACHE_TTL);

        return $result;
    }

    /**
     * @return array{items: list<Post>, total: int, page: int, perPage: int, lastPage: int}
     */
    public function getPostsByTag(int $tagId, int $page = 1, int $perPage = 10): array
    {
        $cacheKey = PostCacheKeyService::tagPosts($tagId, $page);

        /** @var array{items: list<Post>, total: int, page: int, perPage: int, lastPage: int} $result */
        $result = $this->cache->remember($cacheKey, function () use ($tagId, $page, $perPage) {
            $offset = ($page - 1) * $perPage;
            $publishTimeCheck = "AND (p.status != 'published' OR p.publish_date IS NULL OR p.publish_date <= datetime('now'))";
            $countSql = 'SELECT COUNT(*) FROM posts p '
                . 'INNER JOIN post_tags pt ON p.id = pt.post_id '
                . 'WHERE pt.tag_id = :tag_id AND p.deleted_at IS NULL ' . $publishTimeCheck;
            $stmt = $this->db->prepare($countSql);
            $stmt->execute(['tag_id' => $tagId]);
            $total = (int) $stmt->fetchColumn();

            $sql = 'SELECT p.id, p.uuid, p.seq_number, p.title, p.content, p.user_id, p.user_ip, p.is_pinned, p.status, p.publish_date, p.views, p.created_at, p.updated_at, p.creation_source, p.creation_source_detail, u.username as author'
                . ' FROM posts p '
                . 'INNER JOIN post_tags pt ON p.id = pt.post_id '
                . 'LEFT JOIN users u ON p.user_id = u.id '
                . 'WHERE pt.tag_id = :tag_id AND p.deleted_at IS NULL ' . $publishTimeCheck . ' '
                . 'ORDER BY p.is_pinned DESC, p.publish_date DESC, p.created_at DESC, p.id DESC '
                . 'LIMIT :offset, :limit';
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':tag_id', $tagId, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
            $stmt->execute();
            /** @var list<array<string, mixed>> $rows */
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $items = array_map(
                fn(array $row): Post => Post::fromArray($this->preparePostData($row)),
                $rows,
            );

            return [
                'items'    => $items,
                'total'    => $total,
                'page'     => $page,
                'perPage'  => $perPage,
                'lastPage' => (int) ceil($total / $perPage),
            ];
        }, self::CACHE_TTL);

        return $result;
    }

    public function incrementViews(int $id, string $userIp, ?int $userId = null): bool
    {
        if ($userId !== null && $userId <= 0) {
            throw new InvalidArgumentException('使用者 ID 必須是正整數');
        }

        $this->db->beginTransaction();

        try {
            $sql = $this->buildSelectQuery('p.id = ?');
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            $post = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$post) {
                throw new InvalidArgumentException('找不到指定的文章');
            }

            $stmt = $this->db->prepare('UPDATE posts SET views = views + 1 WHERE id = ?');
            $stmt->execute([$id]);

            $stmt = $this->db->prepare('
                INSERT INTO post_views (uuid, post_id, user_id, user_ip, view_date)
                VALUES (:uuid, :post_id, :user_id, :user_ip, :view_date)
            ');
            $stmt->execute([
                'uuid'      => generate_uuid(),
                'post_id'   => $id,
                'user_id'   => $userId,
                'user_ip'   => $userIp,
                'view_date' => format_datetime(),
            ]);

            $this->db->commit();

            $this->invalidateCache($id);

            return true;
        } catch (Throwable $e) {
            $this->db->rollBack();

            throw $e;
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getPostTags(int $id): array
    {
        $sql = 'SELECT t.id, t.name
               FROM tags t
               INNER JOIN post_tags pt ON t.id = pt.tag_id
               WHERE pt.post_id = :post_id
               ORDER BY t.name';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':post_id' => $id]);
        /** @var list<array<string, mixed>> $tags */
        $tags = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $tags;
    }

    public function setPinned(int $id, bool $isPinned): bool
    {
        $stmt = $this->db->prepare('UPDATE posts SET is_pinned = :is_pinned WHERE id = :id');
        $result = $stmt->execute([
            'id'        => $id,
            'is_pinned' => $isPinned,
        ]);
        if ($result) {
            $this->invalidateCache($id);
        }

        return $result;
    }

    public function setTags(int $id, array $tagIds): void
    {
        $this->db->beginTransaction();

        try {
            $normalizedTagIds = array_values(array_filter(
                array_map(
                    static fn(mixed $tagId): ?int => is_numeric($tagId) ? (int) $tagId : null,
                    $tagIds,
                ),
                static fn(?int $tagId): bool => $tagId !== null,
            ));

            if (!empty($normalizedTagIds) && !$this->tagsExist($normalizedTagIds)) {
                throw new PDOException('部分標籤不存在');
            }

            $oldTagStmt = $this->db->prepare('SELECT tag_id FROM post_tags WHERE post_id = ?');
            $oldTagStmt->execute([$id]);
            /** @var list<array<string, mixed>> $oldTagRows */
            $oldTagRows = $oldTagStmt->fetchAll(PDO::FETCH_ASSOC);
            $oldTagIds = [];
            foreach ($oldTagRows as $row) {
                if (is_array($row) && isset($row['tag_id'])) {
                    $tagId = $row['tag_id'];
                    $oldTagIds[] = is_numeric($tagId) ? (int) $tagId : 0;
                }
            }

            $stmt = $this->db->prepare('DELETE FROM post_tags WHERE post_id = ?');
            $stmt->execute([$id]);

            if (!empty($normalizedTagIds)) {
                $sql = 'INSERT INTO post_tags (post_id, tag_id, created_at) VALUES (?, ?, ?)';
                $stmt = $this->db->prepare($sql);
                $now = format_datetime();
                foreach ($normalizedTagIds as $tagId) {
                    $stmt->execute([$id, $tagId, $now]);
                }
            }

            $affectedTagIds = array_values(array_unique(array_merge($oldTagIds, $normalizedTagIds)));
            $this->updateTagsUsageCount($affectedTagIds);

            $this->db->commit();

            $this->invalidateCache($id);
        } catch (Throwable $e) {
            $this->db->rollBack();

            app_log('error', 'Failed to set tags for post', [
                'post_id'   => $id,
                'exception' => $e->getMessage(),
            ]);

            throw new RuntimeException('無法設定文章標籤: ' . $e->getMessage(), 0, $e);
        }
    }

    private function prepareNewPostData(array $data): array
    {
        $now = format_datetime();

        return [
            'uuid'         => $data['uuid'] ?? generate_uuid(),
            'seq_number'   => $this->getNextSeqNumber(),
            'title'        => $data['title'] ?? '',
            'content'      => $data['content'] ?? '',
            'user_id'      => $data['user_id'] ?? 0,
            'user_ip'      => $data['user_ip'] ?? null,
            'is_pinned'    => $data['is_pinned'] ?? false,
            'status'       => $data['status'] ?? PostStatus::DRAFT->value,
            'publish_date' => $data['publish_date'] ?? null,
            'created_at'   => $now,
            'updated_at'   => $now,
        ];
    }

    private function invalidateCache(int $postId): void
    {
        $post = $this->find($postId);
        if ($post) {
            $this->cache->delete(PostCacheKeyService::post($post->getId()));
            $this->cache->delete(PostCacheKeyService::postByUuid($post->getUuid()));
            $this->cache->delete(PostCacheKeyService::postTags($post->getId()));
            $this->cache->delete(PostCacheKeyService::postViews($post->getId()));
            $this->cache->delete(PostCacheKeyService::pinnedPosts());
            $this->cache->deletePattern(PostCacheKeyService::postsListPattern());

            if ($post->getUserId()) {
                $this->cache->deletePattern(PostCacheKeyService::userPattern($post->getUserId()));
            }
        }
    }

    private function tagsExist(array $tagIds): bool
    {
        if (empty($tagIds)) {
            return true;
        }

        $placeholders = str_repeat('?,', count($tagIds) - 1) . '?';
        $sql = "SELECT COUNT(*) FROM tags WHERE id IN ({$placeholders})";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($tagIds);
        $count = (int) $stmt->fetchColumn();

        return $count === count($tagIds);
    }

    private function assignTags(int $postId, array $tagIds): void
    {
        if (!$this->tagsExist($tagIds)) {
            throw new PDOException('指定的標籤不存在');
        }

        $stmt = $this->db->prepare(self::SQL_INSERT_TAG);
        $now = format_datetime();
        foreach ($tagIds as $tagId) {
            $stmt->execute([$postId, $tagId, $now]);
        }

        $this->updateTagsUsageCount($tagIds);
    }

    private function updateTagsUsageCount(array $tagIds): void
    {
        if (empty($tagIds)) {
            return;
        }

        try {
            $uniqueTagIds = array_values(array_unique(array_map(
                static fn(mixed $id): int => is_numeric($id) ? (int) $id : 0,
                $tagIds,
            )));
            $placeholders = implode(',', array_fill(0, count($uniqueTagIds), '?'));
            $sql = "
                UPDATE tags
                SET usage_count = COALESCE((
                    SELECT COUNT(*)
                    FROM post_tags
                    WHERE post_tags.tag_id = tags.id
                ), 0)
                WHERE id IN ({$placeholders})
            ";
            $stmt = $this->db->prepare($sql);
            $stmt->execute($uniqueTagIds);
        } catch (PDOException $e) {
            app_log('error', 'Failed to update tags usage count', ['exception' => $e->getMessage()]);
        }
    }
}
