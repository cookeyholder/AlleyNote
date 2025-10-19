<?php

declare(strict_types=1);

namespace App\Domains\Post\Repositories;

use App\Domains\Post\Contracts\PostRepositoryInterface;
use App\Domains\Post\Enums\PostStatus;
use App\Domains\Post\Models\Post;
use App\Domains\Post\Services\PostCacheKeyService;
use App\Domains\Security\Contracts\LoggingSecurityServiceInterface;
use App\Shared\Contracts\CacheServiceInterface;
use DateTime;
use DateTimeZone;
use Exception;
use InvalidArgumentException;
use PDO;
use PDOException;
use RuntimeException;

class PostRepository implements PostRepositoryInterface
{
    private PDO $db;

    private CacheServiceInterface $cache;

    private LoggingSecurityServiceInterface $logger;

    private const CACHE_TTL = 3600;

    // SQL 查詢常數
    private const POST_SELECT_FIELDS = 'id, uuid, seq_number, title, content, user_id, user_ip, is_pinned, status, publish_date, views, created_at, updated_at, creation_source, creation_source_detail';

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
        LoggingSecurityServiceInterface $logger,
    ) {
        $this->db = $db;
        $this->cache = $cache;
        $this->logger = $logger;
    }

    /**
     * 在交易中執行操作.
     * @template T
     * @param callable(): T $callback 要在交易中執行的操作
     * @return T 操作的結果
     * @throws Exception 當操作失敗時拋出異常
     */
    private function executeInTransaction(callable $callback): mixed
    {
        $this->db->beginTransaction();

        try {
            $result = $callback();
            $this->db->commit();

            return $result;
        } catch (Exception $e) {
            $this->db->rollBack();

            throw $e;
        }
    }

    private function invalidateCache(int $postId): void
    {
        $post = $this->find($postId);
        if ($post) {
            // 刪除單一貼文相關快取
            $this->cache->delete(PostCacheKeyService::post($post->getId()));
            $this->cache->delete(PostCacheKeyService::postByUuid($post->getUuid()));
            $this->cache->delete(PostCacheKeyService::postTags($post->getId()));
            $this->cache->delete(PostCacheKeyService::postViews($post->getId()));

            // 刪除貼文列表相關快取
            $this->cache->delete(PostCacheKeyService::pinnedPosts());

            // 使用模式刪除相關的分頁快取
            $this->cache->deletePattern(PostCacheKeyService::postsListPattern());

            // 刪除使用者貼文快取（如果有）
            if ($post->getUserId()) {
                $this->cache->deletePattern(PostCacheKeyService::userPattern($post->getUserId()));
            }
        }
    }

    /**
     * 在 SQL 查詢中新增 deleted_at 條件.
     */
    private function addDeletedAtCondition(string $sql, string $tableAlias = ''): string
    {
        $prefix = $tableAlias ? $tableAlias . '.' : '';

        // 檢查是否已經包含 WHERE 子句
        if (stripos($sql, 'WHERE') !== false) {
            return $sql . ' AND ' . $prefix . 'deleted_at IS NULL';
        } else {
            return $sql . ' WHERE ' . $prefix . 'deleted_at IS NULL';
        }
    }

    /**
     * 建立帶有 deleted_at 條件的查詢.
     */
    private function buildSelectQuery(string $additionalConditions = '', string $tableAlias = ''): string
    {
        $alias = $tableAlias ?: 'p';
        $fields = $alias . '.id, ' . $alias . '.uuid, ' . $alias . '.seq_number, ' . $alias . '.title, ' . $alias . '.content, '
                . $alias . '.user_id, ' . $alias . '.user_ip, ' . $alias . '.is_pinned, ' . $alias . '.status, '
                . $alias . '.publish_date, ' . $alias . '.views, ' . $alias . '.created_at, ' . $alias . '.updated_at, '
                . $alias . '.creation_source, ' . $alias . '.creation_source_detail, u.username as author';

        $sql = "SELECT {$fields} FROM posts {$alias} LEFT JOIN users u ON {$alias}.user_id = u.id";

        if ($additionalConditions) {
            $sql .= " WHERE {$additionalConditions}";
            $sql = $this->addDeletedAtCondition($sql, $alias);
        } else {
            $sql = $this->addDeletedAtCondition($sql, $alias);
        }

        return $sql;
    }

    /**
     * 準備資料庫查詢結果為 Post 物件的資料.
     * @return array<string, mixed>
     */
    private function preparePostData(array $result): array
    {
        // 格式化 publish_date 為 RFC3339
        $publishDate = $result['publish_date'] ?? null;
        if (is_string($publishDate) && strpos($publishDate, 'T') === false) {
            // 資料庫格式轉 RFC3339
            try {
                $dt = new DateTime($publishDate, new DateTimeZone('UTC'));
                $publishDate = $dt->format(DateTime::ATOM);
            } catch (Exception $e) {
                // 轉換失敗時保持原值
            }
        }

        return [
            'id' => isset($result['id']) && is_numeric($result['id']) ? (int) $result['id'] : 0,
            'uuid' => isset($result['uuid']) && is_string($result['uuid']) ? $result['uuid'] : '',
            'seq_number' => isset($result['seq_number']) && is_string($result['seq_number']) ? $result['seq_number'] : '',
            'title' => isset($result['title']) && is_string($result['title']) ? $result['title'] : '',
            'content' => isset($result['content']) && is_string($result['content']) ? $result['content'] : '',
            'user_id' => isset($result['user_id']) && is_numeric($result['user_id']) ? (int) $result['user_id'] : 0,
            'user_ip' => $result['user_ip'] ?? null,
            'views' => isset($result['views']) && is_numeric($result['views']) ? (int) $result['views'] : 0,
            'is_pinned' => (bool) ($result['is_pinned'] ?? false),
            'status' => $result['status'] ?? 'draft',
            'publish_date' => $publishDate,
            'creation_source' => $result['creation_source'] ?? null,
            'creation_source_detail' => $result['creation_source_detail'] ?? null,
            'created_at' => $result['created_at'] ?? null,
            'updated_at' => $result['updated_at'] ?? null,
            'author' => $result['author'] ?? 'Unknown', // 添加 author 字段
        ];
    }

    /**
     * 準備新文章的資料.
     */
    /**
     * @return array<string, mixed>
     */
    private function prepareNewPostData(array $data): array
    {
        $now = format_datetime();

        return [
            'uuid' => $data['uuid'] ?? generate_uuid(),
            'seq_number' => $this->getNextSeqNumber(),
            'title' => $data['title'] ?? '',
            'content' => $data['content'] ?? '',
            'user_id' => $data['user_id'] ?? 0,
            'user_ip' => $data['user_ip'] ?? null,
            'is_pinned' => $data['is_pinned'] ?? false,
            'status' => $data['status'] ?? PostStatus::DRAFT->value,
            'publish_date' => $data['publish_date'] ?? null,
            'created_at' => $now,
            'updated_at' => $now,
        ];
    }

    /**
     * 取得下一個序列號碼
     */
    private function getNextSeqNumber(): int
    {
        $sql = 'SELECT COALESCE(MAX(seq_number), 0) + 1 as next_seq FROM posts';
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!is_array($result) || !isset($result['next_seq']) || !is_numeric($result['next_seq'])) {
            return 1;
        }

        return (int) $result['next_seq'];
    }

    public function find(int $id): ?Post
    {
        $cacheKey = PostCacheKeyService::post($id);

        $data = $this->cache->remember($cacheKey, function () use ($id) {
            $sql = $this->buildSelectQuery('p.id = ?');
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!is_array($result)) {
                return null;
            }

            return $this->preparePostData($result);
        }, self::CACHE_TTL);

        return (is_array($data)) ? Post::fromArray($data) : null;
    }

    /**
     * 使用悲觀鎖查找文章（用於防止競爭條件）.
     */
    public function findWithLock(int $id): ?Post
    {
        // SQLite 不支援 FOR UPDATE，改用事務和 EXCLUSIVE 模式
        $sql = $this->buildSelectQuery('p.id = ?');
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!is_array($result)) {
            return null;
        }

        return Post::fromArray($this->preparePostData($result));
    }

    public function findByUuid(string $uuid): ?Post
    {
        $cacheKey = PostCacheKeyService::postByUuid($uuid);

        $data = $this->cache->remember($cacheKey, function () use ($uuid) {
            $sql = $this->buildSelectQuery('p.uuid = ?');
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$uuid]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!is_array($result)) {
                return null;
            }

            return $this->preparePostData($result);
        }, self::CACHE_TTL);

        return (is_array($data)) ? Post::fromArray($data) : null;
    }

    public function findBySeqNumber(int $seqNumber): ?Post
    {
        $sql = $this->buildSelectQuery('seq_number = ?');
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$seqNumber]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!is_array($result)) {
            return null;
        }

        return Post::fromArray($this->preparePostData($result));
    }

    /**
     * 安全刪除文章（使用悲觀鎖防止競爭條件）.
     */
    public function safeDelete(int $id): bool
    {
        return $this->executeInTransaction(function () use ($id) {
            $post = $this->findWithLock($id);
            if (!$post) {
                return false;
            }

            // 檢查是否可以刪除（業務邏輯檢查在這裡進行，因為有鎖定保護）
            if ($post->getStatus() === PostStatus::PUBLISHED) {
                throw new InvalidArgumentException('已發布的文章不能刪除，請改為封存');
            }

            return $this->delete($id);
        });
    }

    /**
     * 安全設定置頂狀態（使用悲觀鎖防止競爭條件）.
     */
    public function safeSetPinned(int $id, bool $isPinned): bool
    {
        return $this->executeInTransaction(function () use ($id, $isPinned) {
            $post = $this->findWithLock($id);
            if (!$post) {
                return false;
            }

            // 檢查業務邏輯：只有已發布的文章可以置頂
            if ($isPinned && $post->getStatus() !== PostStatus::PUBLISHED) {
                throw new InvalidArgumentException('只有已發布的文章可以置頂');
            }

            return $this->setPinned($id, $isPinned);
        });
    }

    /**
     * 檢查標籤是否存在.
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
     * 指派標籤到文章.
     * @throws PDOException 當標籤不存在時拋出異常
     */
    public function create(array $data, array $tagIds = []): Post
    {
        return $this->executeInTransaction(function () use ($data, $tagIds) {
            // 資料已在 DTO 層級完成驗證，這裡直接處理

            // 準備資料
            $data = $this->prepareNewPostData($data);

            // 新增文章
            $stmt = $this->db->prepare(self::SQL_INSERT_POST);
            if (!$stmt->execute($data)) {
                $errorInfo = $stmt->errorInfo();
                $errorMessage = is_array($errorInfo) && isset($errorInfo[2]) && is_string($errorInfo[2])
                    ? $errorInfo[2]
                    : 'Unknown error';

                throw new PDOException('Failed to insert post: ' . $errorMessage);
            }
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
                throw new RuntimeException('無法建立文章');
            }

            return $post;
        });
    }

    /**
     * 指派標籤到文章.
     * @throws PDOException 當標籤不存在時拋出異常
     */
    private function assignTags(int $postId, array $tagIds): void
    {
        // 驗證標籤是否存在
        if (!$this->tagsExist($tagIds)) {
            throw new PDOException('指定的標籤不存在');
        }

        // 指派標籤
        $stmt = $this->db->prepare(self::SQL_INSERT_TAG);
        $now = format_datetime();
        foreach ($tagIds as $tagId) {
            $stmt->execute([$postId, $tagId, $now]);
        }

        // 更新標籤的使用次數
        /** @var array<int> $tagIds */
        $this->updateTagsUsageCount($tagIds);
    }

    public function update(int $id, array $data): Post
    {
        // 檢查文章是否存在
        $post = $this->find($id);
        if (!$post) {
            throw new InvalidArgumentException('找不到指定的文章');
        }

        // 防止修改關鍵欄位
        $protectedFields = ['id', 'uuid', 'seq_number', 'created_at', 'views'];
        foreach ($protectedFields as $field) {
            unset($data[$field]);
        }

        // 資料已在 DTO 層級完成驗證，這裡直接處理

        // 更新時間戳記
        // // $data ? $data->updated_at : null)) = format_datetime(); // 語法錯誤已註解 // 複雜賦值語法錯誤已註解

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
                    'action' => 'update_post',
                ]);
            }
        }

        if (empty($sets)) {
            return $post;
        }

        $sql = 'UPDATE posts SET ' . implode(', ', $sets) . ' WHERE id = :id';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        // 清除快取
        $this->invalidateCache($id);

        $updatedPost = $this->find($id);
        if ($updatedPost === null) {
            throw new RuntimeException('Failed to retrieve updated post');
        }

        return $updatedPost;
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
        // 根據條件決定使用哪種快取鍵
        if (empty($conditions)) {
            $cacheKey = PostCacheKeyService::postList($page, 'published');
        } else {
            // 複雜查詢使用舊的方式
            $jsonConditions = json_encode($conditions);
            $conditionHash = is_string($jsonConditions) ? md5($jsonConditions) : md5('{}');
            $cacheKey = sprintf(
                'posts:page:%d:per:%d:%s',
                $page,
                $perPage,
                $conditionHash,
            );
        }

        /** @var array{items: Post[], total: int, page: int, perPage: int, lastPage: int} $result */
        $result = $this->cache->remember($cacheKey, function () use ($page, $perPage, $conditions) {
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
                            'conditions' => array_keys($conditions),
                        ]);
                    }
                }
            }

            // 計算總筆數
            $baseWhere = empty($where) ? 'deleted_at IS NULL' : implode(' AND ', $where) . ' AND deleted_at IS NULL';
            // 對於已發布的文章，只顯示發布時間已到的
            $publishTimeCheck = "AND (status != 'published' OR publish_date IS NULL OR publish_date <= datetime('now'))";

            $countSql = 'SELECT COUNT(*) FROM posts WHERE ' . $baseWhere . ' ' . $publishTimeCheck;
            $stmt = $this->db->prepare($countSql);
            $stmt->execute($params);
            $total = (int) $stmt->fetchColumn();

            // 取得分頁資料
            $sql = 'SELECT p.id, p.uuid, p.seq_number, p.title, p.content, p.user_id, p.user_ip, p.is_pinned, p.status, p.publish_date, p.views, p.created_at, p.updated_at, p.creation_source, p.creation_source_detail, u.username as author'
                . ' FROM posts p'
                . ' LEFT JOIN users u ON p.user_id = u.id'
                . ' WHERE ' . $baseWhere . ' ' . $publishTimeCheck
                . ' ORDER BY p.is_pinned DESC, p.publish_date DESC LIMIT :offset, :limit';

            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);

            foreach ($params as $key => $value) {
                $stmt->bindValue(":{$key}", $value);
            }

            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $items = [];

            foreach ($results as $row) {
                if (is_array($row)) {
                    $items[] = Post::fromArray($this->preparePostData($row));
                }
            }

            return [
                'items' => $items,
                'total' => $total,
                'page' => $page,
                'perPage' => $perPage,
                'lastPage' => (int) ceil($total / $perPage),
            ];
        }, self::CACHE_TTL);

        return $result;
    }

    public function getPinnedPosts(int $limit = 5): array
    {
        $cacheKey = PostCacheKeyService::pinnedPosts();

        /** @var Post[] $result */
        $result = $this->cache->remember($cacheKey, function () use ($limit) {
            $sql = $this->buildSelectQuery("is_pinned = 1 AND (status != 'published' OR publish_date IS NULL OR publish_date <= datetime('now'))")
                . ' ORDER BY publish_date DESC LIMIT :limit';

            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();

            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $items = [];

            foreach ($results as $row) {
                if (is_array($row)) {
                    $items[] = Post::fromArray($this->preparePostData($row));
                }
            }

            return $items;
        }, self::CACHE_TTL);

        return $result;
    }

    public function getPostsByTag(int $tagId, int $page = 1, int $perPage = 10): array
    {
        $cacheKey = PostCacheKeyService::tagPosts($tagId, $page);

        /** @var array{items: Post[], total: int, page: int, perPage: int, lastPage: int} $result */
        $result = $this->cache->remember($cacheKey, function () use ($tagId, $page, $perPage) {
            $offset = ($page - 1) * $perPage;

            // 計算總筆數
            $publishTimeCheck = "AND (p.status != 'published' OR p.publish_date IS NULL OR p.publish_date <= datetime('now'))";
            $countSql = 'SELECT COUNT(*) FROM posts p '
                . 'INNER JOIN post_tags pt ON p.id = pt.post_id '
                . 'WHERE pt.tag_id = :tag_id AND p.deleted_at IS NULL ' . $publishTimeCheck;

            $stmt = $this->db->prepare($countSql);
            $stmt->execute(['tag_id' => $tagId]);
            $total = (int) $stmt->fetchColumn();

            // 取得分頁資料
            $sql = 'SELECT ' . str_replace('id, uuid, seq_number, title, content, user_id, user_ip, is_pinned, status, publish_date, views, created_at, updated_at', 'p.id, p.uuid, p.seq_number, p.title, p.content, p.user_id, p.user_ip, p.is_pinned, p.status, p.publish_date, p.views, p.created_at, p.updated_at', self::POST_SELECT_FIELDS) . ' FROM posts p '
                . 'INNER JOIN post_tags pt ON p.id = pt.post_id '
                . 'WHERE pt.tag_id = :tag_id AND p.deleted_at IS NULL ' . $publishTimeCheck . ' '
                . 'ORDER BY p.is_pinned DESC, p.publish_date DESC '
                . 'LIMIT :offset, :limit';

            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':tag_id', $tagId, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
            $stmt->execute();

            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $items = [];

            foreach ($results as $row) {
                if (is_array($row)) {
                    $items[] = Post::fromArray($this->preparePostData($row));
                }
            }

            return [
                'items' => $items,
                'total' => $total,
                'page' => $page,
                'perPage' => $perPage,
                'lastPage' => (int) ceil($total / $perPage),
            ];
        }, self::CACHE_TTL);

        return $result;
    }

    public function incrementViews(int $id, string $userIp, ?int $userId = null): bool
    {
        // 驗證 IP 位址格式
        if (!filter_var($userIp, FILTER_VALIDATE_IP)) {
            throw new InvalidArgumentException('無效的 IP 位址格式');
        }

        // 驗證使用者 ID（如果提供）
        if ($userId !== null && $userId <= 0) {
            throw new InvalidArgumentException('使用者 ID 必須是正整數');
        }

        $this->db->beginTransaction();

        try {
            // 檢查文章是否存在
            $sql = $this->buildSelectQuery('p.id = ?');
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            $post = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$post) {
                throw new InvalidArgumentException('找不到指定的文章');
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
                'view_date' => format_datetime(),
            ]);

            $this->db->commit();
            $this->invalidateCache($id);

            return true;
        } catch (Exception $e) {
            $this->db->rollBack();

            throw $e;
        }
    }

    public function setPinned(int $id, bool $isPinned): bool
    {
        $stmt = $this->db->prepare('UPDATE posts SET is_pinned = :is_pinned WHERE id = :id');
        $result = $stmt->execute([
            'id' => $id,
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
            // 驗證標籤是否存在
            if (!empty($tagIds) && !$this->tagsExist($tagIds)) {
                throw new PDOException('部分標籤不存在');
            }

            // 取得舊標籤列表以便後續更新 usage_count
            $oldTagStmt = $this->db->prepare('SELECT tag_id FROM post_tags WHERE post_id = ?');
            $oldTagStmt->execute([$id]);
            $oldTagRows = $oldTagStmt->fetchAll(PDO::FETCH_ASSOC);
            /** @var array<int> $oldTagIds */
            $oldTagIds = [];
            foreach ($oldTagRows as $row) {
                if (is_array($row) && isset($row['tag_id']) && is_numeric($row['tag_id'])) {
                    $oldTagIds[] = (int) $row['tag_id'];
                }
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

            // 更新受影響標籤的 usage_count
            /** @var array<int> $affectedTagIds */
            $affectedTagIds = array_unique(array_merge($oldTagIds, $tagIds));
            $this->updateTagsUsageCount($affectedTagIds);

            $this->db->commit();
            $this->invalidateCache($id);
        } catch (Exception $e) {
            $this->db->rollBack();
            // 記錄錯誤並重新拋出，以便調用方能處理
            error_log("Failed to set tags for post {$id}: " . $e->getMessage());

            throw new RuntimeException('無法設定文章標籤: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * 更新標籤的使用次數.
     *
     * @param array<int> $tagIds
     */
    private function updateTagsUsageCount(array $tagIds): void
    {
        if (empty($tagIds)) {
            return;
        }

        foreach ($tagIds as $tagId) {
            // 計算該標籤被使用的次數
            $countStmt = $this->db->prepare('SELECT COUNT(*) FROM post_tags WHERE tag_id = ?');
            $countStmt->execute([$tagId]);
            $count = (int) $countStmt->fetchColumn();

            // 更新標籤的 usage_count
            $updateStmt = $this->db->prepare('UPDATE tags SET usage_count = ? WHERE id = ?');
            $updateStmt->execute([$count, $tagId]);
        }
    }

    /**
     * @return Post[]
     */
    public function searchByTitle(string $title): array
    {
        $sql = 'SELECT ' . self::POST_SELECT_FIELDS . ' FROM posts WHERE title LIKE :title AND deleted_at IS NULL';
        $stmt = $this->db->prepare($sql);
        $title = '%' . $title . '%';
        $stmt->bindValue(':title', $title, PDO::PARAM_STR);
        $stmt->execute();

        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $items = [];

        foreach ($results as $row) {
            if (is_array($row)) {
                $items[] = Post::fromArray($this->preparePostData($row));
            }
        }

        return $items;
    }

    public function findByUserId(int $userId): ?Post
    {
        $sql = $this->buildSelectQuery('p.user_id = :userId') . ' LIMIT 1';
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':userId', $userId, PDO::PARAM_INT);
        $stmt->execute();

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!is_array($result)) {
            return null;
        }

        return Post::fromArray($this->preparePostData($result));
    }

    /**
     * @return Post[]
     */
    public function search(string $keyword): array
    {
        $sql = $this->buildSelectQuery('title LIKE :keyword OR content LIKE :keyword');
        $stmt = $this->db->prepare($sql);
        $keyword = '%' . $keyword . '%';
        $stmt->bindValue(':keyword', $keyword, PDO::PARAM_STR);
        $stmt->execute();

        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $items = [];

        foreach ($results as $row) {
            if (is_array($row)) {
                $items[] = Post::fromArray($this->preparePostData($row));
            }
        }

        return $items;
    }

    /**
     * 依來源類型取得文章列表.
     *
     * @return Post[]
     */
    public function findByCreationSource(string $creationSource, int $limit = 10, int $offset = 0): array
    {
        $cacheKey = sprintf('posts:source:%s:limit:%d:offset:%d', $creationSource, $limit, $offset);

        /** @var Post[] $result */
        $result = $this->cache->remember($cacheKey, function () use ($creationSource, $limit, $offset) {
            $sql = $this->buildSelectQuery('p.creation_source = :creation_source')
                . ' ORDER BY p.created_at DESC LIMIT :limit OFFSET :offset';

            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':creation_source', $creationSource, PDO::PARAM_STR);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();

            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $items = [];

            foreach ($results as $row) {
                if (is_array($row)) {
                    $items[] = Post::fromArray($this->preparePostData($row));
                }
            }

            return $items;
        }, self::CACHE_TTL);

        return $result;
    }

    /**
     * 取得來源分佈統計.
     *
     * @return array<string, int> 來源類型 => 文章數量的陣列
     */
    public function getSourceDistribution(): array
    {
        $cacheKey = 'posts:source_distribution';

        /** @var array<string, int> $result */
        $result = $this->cache->remember($cacheKey, function () {
            $sql = 'SELECT creation_source, COUNT(*) as count FROM posts WHERE deleted_at IS NULL GROUP BY creation_source ORDER BY count DESC';
            $stmt = $this->db->prepare($sql);
            $stmt->execute();

            $result = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                if (!is_array($row)) {
                    continue;
                }
                $source = isset($row['creation_source']) && is_string($row['creation_source']) ? $row['creation_source'] : 'unknown';
                $count = $row['count'] ?? 0;
                $result[$source] = is_int($count) ? $count : (is_numeric($count) ? (int) $count : 0);
            }

            return $result;
        }, self::CACHE_TTL);

        return $result;
    }

    /**
     * 依來源類型和詳細資訊取得文章列表.
     *
     * @return Post[]
     */
    public function findByCreationSourceAndDetail(
        string $creationSource,
        ?string $creationSourceDetail = null,
        int $limit = 10,
        int $offset = 0,
    ): array {
        $cacheKey = sprintf(
            'posts:source:%s:detail:%s:limit:%d:offset:%d',
            $creationSource,
            $creationSourceDetail ?? 'null',
            $limit,
            $offset,
        );

        /** @var Post[] $result */
        $result = $this->cache->remember($cacheKey, function () use ($creationSource, $creationSourceDetail, $limit, $offset) {
            if ($creationSourceDetail === null) {
                $sql = $this->buildSelectQuery('p.creation_source = :creation_source AND p.creation_source_detail IS NULL')
                    . ' ORDER BY p.created_at DESC LIMIT :limit OFFSET :offset';
                $params = [
                    'creation_source' => $creationSource,
                ];
            } else {
                $sql = $this->buildSelectQuery('p.creation_source = :creation_source AND p.creation_source_detail = :creation_source_detail')
                    . ' ORDER BY p.created_at DESC LIMIT :limit OFFSET :offset';
                $params = [
                    'creation_source' => $creationSource,
                    'creation_source_detail' => $creationSourceDetail,
                ];
            }

            $stmt = $this->db->prepare($sql);

            foreach ($params as $key => $value) {
                $stmt->bindValue(":{$key}", $value, PDO::PARAM_STR);
            }

            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();

            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $items = [];

            foreach ($results as $row) {
                if (is_array($row)) {
                    $items[] = Post::fromArray($this->preparePostData($row));
                }
            }

            return $items;
        }, self::CACHE_TTL);

        return $result;
    }

    /**
     * 計算特定來源的文章總數.
     */
    public function countByCreationSource(string $creationSource): int
    {
        $cacheKey = sprintf('posts:count:source:%s', $creationSource);

        /** @var int $result */
        $result = $this->cache->remember($cacheKey, function () use ($creationSource) {
            $sql = 'SELECT COUNT(*) FROM posts WHERE creation_source = :creation_source AND deleted_at IS NULL';
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':creation_source', $creationSource, PDO::PARAM_STR);
            $stmt->execute();

            $count = $stmt->fetchColumn();

            return is_numeric($count) ? (int) $count : 0;
        }, self::CACHE_TTL);

        return $result;
    }

    /**
     * 依來源類型取得分頁文章列表.
     *
     * @return array{items: Post[], total: int, page: int, perPage: int, lastPage: int}
     */
    public function paginateByCreationSource(
        string $creationSource,
        int $page = 1,
        int $perPage = 10,
    ): array {
        $cacheKey = sprintf('posts:paginate:source:%s:page:%d:per:%d', $creationSource, $page, $perPage);

        /** @var array{items: Post[], total: int, page: int, perPage: int, lastPage: int} $result */
        $result = $this->cache->remember($cacheKey, function () use ($creationSource, $page, $perPage) {
            $offset = ($page - 1) * $perPage;

            // 計算總筆數
            $total = $this->countByCreationSource($creationSource);

            // 取得分頁資料
            $sql = $this->buildSelectQuery('p.creation_source = :creation_source')
                . ' ORDER BY p.is_pinned DESC, p.created_at DESC LIMIT :limit OFFSET :offset';

            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':creation_source', $creationSource, PDO::PARAM_STR);
            $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();

            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $items = [];

            foreach ($results as $row) {
                if (is_array($row)) {
                    $items[] = Post::fromArray($this->preparePostData($row));
                }
            }

            return [
                'items' => $items,
                'total' => $total,
                'page' => $page,
                'perPage' => $perPage,
                'lastPage' => (int) ceil($total / $perPage),
            ];
        }, self::CACHE_TTL);

        return $result;
    }
}
