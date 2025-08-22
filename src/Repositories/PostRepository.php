<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;
use App\Models\Post;
use App\Services\CacheService;
use App\Services\Security\Contracts\LoggingSecurityServiceInterface;
use App\Repositories\Contracts\PostRepositoryInterface;

class PostRepository implements PostRepositoryInterface
{
    private PDO $db;
    private CacheService $cache;
    private LoggingSecurityServiceInterface $logger;
    private const CACHE_TTL = 3600;

    // SQL 查詢常數
    private const SQL_SELECT_BASE = 'SELECT * FROM posts WHERE';
    private const SQL_INSERT_POST = 'INSERT INTO posts (uuid, seq_number, title, content, user_id, user_ip, is_pinned, status, publish_date, created_at, updated_at) VALUES (:uuid, :seq_number, :title, :content, :user_id, :user_ip, :is_pinned, :status, :publish_date, :created_at, :updated_at)';
    private const SQL_INSERT_TAG = 'INSERT INTO post_tags (post_id, tag_id, created_at) VALUES (?, ?, ?)';

    // 允許的欄位白名單
    private const ALLOWED_UPDATE_FIELDS = [
        'title',
        'content',
        'user_ip',
        'is_pinned',
        'status',
        'publish_date',
        'updated_at'
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
        'created_at',
        'updated_at'
    ];

    public function __construct(
        PDO $db,
        CacheService $cache,
        LoggingSecurityServiceInterface $logger
    ) {
        $this->db = $db;
        $this->cache = $cache;
        $this->logger = $logger;
    }

    /**
     * 在交易中執行操作
     * @template T
     * @param callable(): T $callback 要在交易中執行的操作
     * @return T 操作的結果
     * @throws \Exception 當操作失敗時拋出異常
     */
    private function executeInTransaction(callable $callback): mixed
    {
        $this->db->beginTransaction();
        try {
            $result = $callback();
            $this->db->commit();
            return $result;
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    private function getCacheKey(string $type, mixed $identifier): string
    {
        return "post:{$type}:{$identifier}";
    }

    private function invalidateCache(int $postId): void
    {
        $post = $this->find($postId);
        if ($post) {
            $this->cache->delete($this->getCacheKey('id', $post->getId()));
            $this->cache->delete($this->getCacheKey('uuid', $post->getUuid()));
            $this->cache->delete($this->getCacheKey('seq', $post->getSeqNumber()));
            $this->cache->delete('posts:pinned');
            $this->cache->delete('posts:latest');
        }
    }

    /**
     * 準備資料庫查詢結果為 Post 物件的資料
     */
    private function preparePostData(array $result): array
    {
        return [
            'id' => (int)$result['id'],
            'uuid' => $result['uuid'],
            'seq_number' => (int)$result['seq_number'],
            'title' => $result['title'],
            'content' => $result['content'],
            'user_id' => (int)$result['user_id'],
            'user_ip' => $result['user_ip'],
            'views' => (int)$result['views'],
            'is_pinned' => (bool)$result['is_pinned'],
            'status' => $result['status'],
            'publish_date' => $result['publish_date'],
            'created_at' => $result['created_at'],
            'updated_at' => $result['updated_at']
        ];
    }

    /**
     * 準備新文章的資料
     */
    private function prepareNewPostData(array $data): array
    {
        $now = format_datetime();
        return [
            'uuid' => $data['uuid'] ?? generate_uuid(),
            'seq_number' => $data['seq_number'] ?? null,
            'title' => $data['title'],
            'content' => $data['content'],
            'user_id' => $data['user_id'],
            'user_ip' => $data['user_ip'] ?? null,
            'is_pinned' => $data['is_pinned'] ?? false,
            'status' => $data['status'] ?? 'draft',
            'publish_date' => $data['publish_date'] ?? $now,
            'created_at' => $now,
            'updated_at' => $now
        ];
    }

    public function find(int $id): ?Post
    {
        $cacheKey = $this->getCacheKey('id', $id);

        $data = $this->cache->remember($cacheKey, function () use ($id) {
            $stmt = $this->db->prepare('SELECT * FROM posts WHERE id = ?');
            $stmt->execute([$id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$result) {
                return null;
            }
            return $this->preparePostData($result);
        }, self::CACHE_TTL);

        return $data ? Post::fromArray($data) : null;
    }

    /**
     * 使用悲觀鎖查找文章（用於防止競爭條件）
     */
    public function findWithLock(int $id): ?Post
    {
        $stmt = $this->db->prepare('SELECT * FROM posts WHERE id = ? FOR UPDATE');
        $stmt->execute([$id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$result) {
            return null;
        }

        return Post::fromArray($this->preparePostData($result));
    }

    public function findByUuid(string $uuid): ?Post
    {
        $cacheKey = $this->getCacheKey('uuid', $uuid);

        $data = $this->cache->remember($cacheKey, function () use ($uuid) {
            $stmt = $this->db->prepare('SELECT * FROM posts WHERE uuid = ?');
            $stmt->execute([$uuid]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$result) {
                return null;
            }
            return $this->preparePostData($result);
        }, self::CACHE_TTL);

        return $data ? Post::fromArray($data) : null;
    }

    public function findBySeqNumber(int $seqNumber): ?Post
    {
        $stmt = $this->db->prepare('SELECT * FROM posts WHERE seq_number = ?');
        $stmt->execute([$seqNumber]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$result) {
            return null;
        }

        return Post::fromArray($this->preparePostData($result));
    }

    /**
     * 安全刪除文章（使用悲觀鎖防止競爭條件）
     */
    public function safeDelete(int $id): bool
    {
        return $this->executeInTransaction(function () use ($id) {
            $post = $this->findWithLock($id);
            if (!$post) {
                return false;
            }

            // 檢查是否可以刪除（業務邏輯檢查在這裡進行，因為有鎖定保護）
            if ($post->getStatus() === 'published') {
                throw new \InvalidArgumentException('已發布的文章不能刪除，請改為封存');
            }

            return $this->delete($id);
        });
    }

    /**
     * 安全設定置頂狀態（使用悲觀鎖防止競爭條件）
     */
    public function safeSetPinned(int $id, bool $isPinned): bool
    {
        return $this->executeInTransaction(function () use ($id, $isPinned) {
            $post = $this->findWithLock($id);
            if (!$post) {
                return false;
            }

            // 檢查業務邏輯：只有已發布的文章可以置頂
            if ($isPinned && $post->getStatus() !== 'published') {
                throw new \InvalidArgumentException('只有已發布的文章可以置頂');
            }

            return $this->setPinned($id, $isPinned);
        });
    }

    /**
     * 檢查標籤是否存在
     */
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

    /**
     * 指派標籤到文章
     * @throws \PDOException 當標籤不存在時拋出異常
     */

    public function create(array $data, array $tagIds = []): Post
    {
        return $this->executeInTransaction(function () use ($data, $tagIds) {
            // 資料已在 DTO 層級完成驗證，這裡直接處理

            // 準備資料
            $data = $this->prepareNewPostData($data);

            // 新增文章
            $stmt = $this->db->prepare(self::SQL_INSERT_POST);
            $stmt->execute($data);
            $postId = (int) $this->db->lastInsertId();

            // 指派標籤（如果有的話）
            if (!empty($tagIds)) {
                $this->assignTags($postId, $tagIds);
            }

            // 清除相關快取
            $this->cache->delete('posts:latest');
            $this->cache->delete('posts:pinned');

            // 回傳建立的物件
            $post = $this->find($postId);
            if (!$post) {
                throw new \RuntimeException('無法建立文章');
            }
            return $post;
        });
    }

    /**
     * 指派標籤到文章
     * @throws \PDOException 當標籤不存在時拋出異常
     */
    private function assignTags(int $postId, array $tagIds): void
    {
        // 驗證標籤是否存在
        if (!$this->tagsExist($tagIds)) {
            throw new \PDOException('指定的標籤不存在');
        }

        // 指派標籤
        $stmt = $this->db->prepare(self::SQL_INSERT_TAG);
        $now = format_datetime();
        foreach ($tagIds as $tagId) {
            $stmt->execute([$postId, $tagId, $now]);
        }
    }

    public function update(int $id, array $data): Post
    {
        // 檢查文章是否存在
        $post = $this->find($id);
        if (!$post) {
            throw new \InvalidArgumentException('找不到指定的文章');
        }

        // 防止修改關鍵欄位
        $protectedFields = ['id', 'uuid', 'seq_number', 'created_at', 'views'];
        foreach ($protectedFields as $field) {
            unset($data[$field]);
        }

        // 資料已在 DTO 層級完成驗證，這裡直接處理

        // 更新時間戳記
        $data['updated_at'] = format_datetime();

        // 準備更新欄位 - 只允許安全的欄位
        $sets = [];
        $params = ['id' => $id];

        foreach ($data as $key => $value) {
            // 檢查欄位是否在允許的白名單中
            if (in_array($key, self::ALLOWED_UPDATE_FIELDS, true)) {
                $sets[] = "{$key} = :{$key}";
                $params[$key] = $value;
            } else {
                // 記錄嘗試更新不允許欄位的行為
                $this->logger->logSecurityEvent('Attempt to update disallowed field', [
                    'field' => $key,
                    'post_id' => $id,
                    'action' => 'update_post'
                ]);
            }
        }

        if (empty($sets)) {
            return $post;
        }

        $sql = "UPDATE posts SET " . implode(', ', $sets) . " WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        // 清除快取
        $this->invalidateCache($id);

        return $this->find($id);
    }

    public function delete(int $id): bool
    {
        // 清除快取
        $this->invalidateCache($id);

        $stmt = $this->db->prepare('DELETE FROM posts WHERE id = ?');
        return $stmt->execute([$id]);
    }

    public function paginate(int $page = 1, int $perPage = 10, array $conditions = []): array
    {
        $cacheKey = sprintf(
            'posts:page:%d:per:%d:%s',
            $page,
            $perPage,
            md5(json_encode($conditions))
        );

        return $this->cache->remember($cacheKey, function () use ($page, $perPage, $conditions) {
            $offset = ($page - 1) * $perPage;

            // 建立查詢條件 - 只允許安全的欄位
            $where = [];
            $params = [];

            if (!empty($conditions)) {
                foreach ($conditions as $key => $value) {
                    // 檢查欄位是否在允許的白名單中
                    if (in_array($key, self::ALLOWED_CONDITION_FIELDS, true)) {
                        $where[] = "{$key} = :{$key}";
                        $params[$key] = $value;
                    } else {
                        // 記錄嘗試查詢不允許欄位的行為
                        $this->logger->logSecurityEvent('Attempt to query with disallowed field', [
                            'field' => $key,
                            'action' => 'get_paginated',
                            'conditions' => array_keys($conditions)
                        ]);
                    }
                }
            }

            // 計算總筆數
            $countSql = 'SELECT COUNT(*) FROM posts' .
                (empty($where) ? '' : ' WHERE ' . implode(' AND ', $where));
            $stmt = $this->db->prepare($countSql);
            $stmt->execute($params);
            $total = (int) $stmt->fetchColumn();

            // 取得分頁資料
            $sql = 'SELECT * FROM posts' .
                (empty($where) ? '' : ' WHERE ' . implode(' AND ', $where)) .
                ' ORDER BY is_pinned DESC, publish_date DESC LIMIT :offset, :limit';

            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);

            foreach ($params as $key => $value) {
                $stmt->bindValue(":{$key}", $value);
            }

            $stmt->execute();
            $items = array_map(
                fn($row) => Post::fromArray($this->preparePostData($row)),
                $stmt->fetchAll(PDO::FETCH_ASSOC)
            );

            return [
                'items' => $items,
                'total' => $total,
                'page' => $page,
                'per_page' => $perPage,
                'last_page' => ceil($total / $perPage)
            ];
        }, self::CACHE_TTL);
    }

    public function getPinnedPosts(int $limit = 5): array
    {
        $cacheKey = "posts:pinned:limit:{$limit}";

        return $this->cache->remember($cacheKey, function () use ($limit) {
            $sql = 'SELECT * FROM posts WHERE is_pinned = 1 ' .
                'ORDER BY publish_date DESC LIMIT :limit';

            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();

            return array_map(
                fn($row) => Post::fromArray($this->preparePostData($row)),
                $stmt->fetchAll(PDO::FETCH_ASSOC)
            );
        }, self::CACHE_TTL);
    }

    public function getPostsByTag(int $tagId, int $page = 1, int $perPage = 10): array
    {
        $offset = ($page - 1) * $perPage;

        // 計算總筆數
        $countSql = 'SELECT COUNT(*) FROM posts p ' .
            'INNER JOIN post_tags pt ON p.id = pt.post_id ' .
            'WHERE pt.tag_id = :tag_id';

        $stmt = $this->db->prepare($countSql);
        $stmt->execute(['tag_id' => $tagId]);
        $total = (int) $stmt->fetchColumn();

        // 取得分頁資料
        $sql = 'SELECT p.* FROM posts p ' .
            'INNER JOIN post_tags pt ON p.id = pt.post_id ' .
            'WHERE pt.tag_id = :tag_id ' .
            'ORDER BY p.is_pinned DESC, p.publish_date DESC ' .
            'LIMIT :offset, :limit';

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':tag_id', $tagId, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->execute();

        $items = array_map(
            fn($row) => Post::fromArray($this->preparePostData($row)),
            $stmt->fetchAll(PDO::FETCH_ASSOC)
        );

        return [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'last_page' => ceil($total / $perPage)
        ];
    }

    public function incrementViews(int $id, string $userIp, ?int $userId = null): bool
    {
        // 驗證 IP 位址格式
        if (!filter_var($userIp, FILTER_VALIDATE_IP)) {
            throw new \InvalidArgumentException('無效的 IP 位址格式');
        }

        // 驗證使用者 ID（如果提供）
        if ($userId !== null && (!is_int($userId) || $userId <= 0)) {
            throw new \InvalidArgumentException('使用者 ID 必須是正整數');
        }

        $this->db->beginTransaction();

        try {
            // 檢查文章是否存在
            $stmt = $this->db->prepare('SELECT * FROM posts WHERE id = ?');
            $stmt->execute([$id]);
            $post = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$post) {
                throw new \InvalidArgumentException('找不到指定的文章');
            }

            // 更新文章觀看次數
            $stmt = $this->db->prepare('UPDATE posts SET views = views + 1 WHERE id = ?');
            $stmt->execute([$id]);

            // 記錄觀看記錄
            $stmt = $this->db->prepare('
                INSERT INTO post_views (uuid, post_id, user_id, user_ip, view_date) 
                VALUES (:uuid, :post_id, :user_id, :user_ip, :view_date)
            ');

            $stmt->execute([
                'uuid' => generate_uuid(),
                'post_id' => $id,
                'user_id' => $userId,
                'user_ip' => $userIp,
                'view_date' => format_datetime()
            ]);

            $this->db->commit();
            $this->invalidateCache($id);
            return true;
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function setPinned(int $id, bool $isPinned): bool
    {
        $stmt = $this->db->prepare('UPDATE posts SET is_pinned = :is_pinned WHERE id = :id');
        $result = $stmt->execute([
            'id' => $id,
            'is_pinned' => $isPinned
        ]);

        if ($result) {
            $this->invalidateCache($id);
        }

        return $result;
    }

    public function setTags(int $id, array $tagIds): bool
    {
        $this->db->beginTransaction();

        try {
            // 驗證標籤是否存在
            if (!empty($tagIds) && !$this->tagsExist($tagIds)) {
                throw new \PDOException('部分標籤不存在');
            }

            // 移除現有標籤
            $stmt = $this->db->prepare('DELETE FROM post_tags WHERE post_id = ?');
            $stmt->execute([$id]);

            // 新增標籤
            if (!empty($tagIds)) {
                $sql = 'INSERT INTO post_tags (post_id, tag_id, created_at) VALUES (?, ?, ?)';
                $stmt = $this->db->prepare($sql);
                $now = format_datetime();

                foreach ($tagIds as $tagId) {
                    $stmt->execute([$id, $tagId, $now]);
                }
            }

            $this->db->commit();
            $this->invalidateCache($id);
            return true;
        } catch (\Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }

    public function searchByTitle(string $title): array
    {
        $stmt = $this->db->prepare('SELECT * FROM posts WHERE title LIKE :title AND deleted_at IS NULL');
        $title = '%' . $title . '%';
        $stmt->bindValue(':title', $title, PDO::PARAM_STR);
        $stmt->execute();

        return array_map(
            fn($row) => Post::fromArray($this->preparePostData($row)),
            $stmt->fetchAll(PDO::FETCH_ASSOC)
        );
    }

    public function findByUserId(int $userId): ?Post
    {
        $stmt = $this->db->prepare('SELECT * FROM posts WHERE user_id = :userId LIMIT 1');
        $stmt->bindValue(':userId', $userId, PDO::PARAM_INT);
        $stmt->execute();

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$result) {
            return null;
        }

        return Post::fromArray($this->preparePostData($result));
    }

    public function search(string $keyword): array
    {
        $stmt = $this->db->prepare('SELECT * FROM posts WHERE title LIKE :keyword OR content LIKE :keyword');
        $keyword = '%' . $keyword . '%';
        $stmt->bindValue(':keyword', $keyword, PDO::PARAM_STR);
        $stmt->execute();

        return array_map(
            fn($row) => Post::fromArray($this->preparePostData($row)),
            $stmt->fetchAll(PDO::FETCH_ASSOC)
        );
    }
}
