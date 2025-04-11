<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;
use App\Models\Post;
use App\Services\CacheService;
use App\Repositories\Contracts\PostRepositoryInterface;

class PostRepository implements PostRepositoryInterface
{
    private PDO $db;
    private CacheService $cache;
    private const CACHE_TTL = 3600;

    // SQL 查詢常數
    private const SQL_SELECT_BASE = 'SELECT * FROM posts WHERE';
    private const SQL_INSERT_POST = 'INSERT INTO posts (uuid, seq_number, title, content, user_id, user_ip, is_pinned, status, publish_date, created_at, updated_at) VALUES (:uuid, :seq_number, :title, :content, :user_id, :user_ip, :is_pinned, :status, :publish_date, :created_at, :updated_at)';
    private const SQL_INSERT_TAG = 'INSERT INTO post_tags (post_id, tag_id, created_at) VALUES (?, ?, ?)';

    public function __construct(PDO $db, CacheService $cache)
    {
        $this->db = $db;
        $this->cache = $cache;
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

    public function find(int $id): ?Post
    {
        $cacheKey = $this->getCacheKey('id', $id);

        return $this->cache->remember($cacheKey, function () use ($id) {
            $stmt = $this->db->prepare('SELECT * FROM posts WHERE id = ?');
            $stmt->execute([$id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return $result ? Post::fromArray($result) : null;
        }, self::CACHE_TTL);
    }

    public function findByUuid(string $uuid): ?Post
    {
        $cacheKey = $this->getCacheKey('uuid', $uuid);

        return $this->cache->remember($cacheKey, function () use ($uuid) {
            $stmt = $this->db->prepare('SELECT * FROM posts WHERE uuid = ?');
            $stmt->execute([$uuid]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return $result ? Post::fromArray($result) : null;
        }, self::CACHE_TTL);
    }

    public function findBySeqNumber(int $seqNumber): ?Post
    {
        $stmt = $this->db->prepare('SELECT * FROM posts WHERE seq_number = ?');
        $stmt->execute([$seqNumber]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result ? Post::fromArray($result) : null;
    }

    private function validateRequiredFields(array $data): void
    {
        $errors = [];

        // 檢查必要欄位
        if (empty($data['title'])) {
            $errors[] = '標題不能為空';
        }
        if (empty($data['content'])) {
            $errors[] = '內容不能為空';
        }
        if (empty($data['user_id'])) {
            $errors[] = '使用者 ID 不能為空';
        }

        // 檢查欄位長度
        if (isset($data['title']) && mb_strlen($data['title']) > 100) {
            $errors[] = '標題長度不能超過 100 個字';
        }
        if (isset($data['content']) && mb_strlen($data['content']) > 10000) {
            $errors[] = '內容長度不能超過 10000 個字';
        }

        // 檢查資料型別
        if (isset($data['user_id']) && !is_numeric($data['user_id'])) {
            $errors[] = '使用者 ID 必須是數字';
        }
        if (isset($data['is_pinned']) && !is_bool($data['is_pinned'])) {
            $errors[] = '置頂標記必須是布林值';
        }

        // 檢查狀態值
        if (isset($data['status']) && !in_array($data['status'], ['draft', 'published', 'archived'], true)) {
            $errors[] = '狀態值必須是 draft、published 或 archived';
        }

        // 檢查日期格式
        if (isset($data['publish_date'])) {
            $date = \DateTime::createFromFormat('Y-m-d H:i:s', $data['publish_date']);
            if (!$date || $date->format('Y-m-d H:i:s') !== $data['publish_date']) {
                $errors[] = '發布日期格式必須是 Y-m-d H:i:s';
            }
        }

        // 檢查 IP 格式
        if (isset($data['user_ip']) && !filter_var($data['user_ip'], FILTER_VALIDATE_IP)) {
            $errors[] = 'IP 位址格式無效';
        }

        // 如果有任何錯誤，拋出異常
        if (!empty($errors)) {
            throw new \InvalidArgumentException(implode(', ', $errors));
        }
    }

    private function validateTagAssignment(array $tagIds): void
    {
        if (empty($tagIds)) {
            return;
        }

        // 檢查標籤 ID 是否都是正整數
        foreach ($tagIds as $tagId) {
            if (!is_int($tagId) || $tagId <= 0) {
                throw new \InvalidArgumentException('標籤 ID 必須是正整數');
            }
        }

        // 檢查標籤是否存在
        $placeholders = str_repeat('?,', count($tagIds) - 1) . '?';
        $sql = "SELECT COUNT(*) FROM tags WHERE id IN ({$placeholders})";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($tagIds);

        $count = (int) $stmt->fetchColumn();
        if ($count !== count($tagIds)) {
            throw new \InvalidArgumentException('某些標籤不存在');
        }
    }

    public function create(array $data, array $tagIds = []): Post
    {
        return $this->executeInTransaction(function () use ($data, $tagIds) {
            // 驗證輸入資料
            $this->validateRequiredFields($data);
            $this->validateTagAssignment($tagIds);

            // 準備資料
            $data = $this->preparePostData($data);

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
     * 準備文章資料
     */
    private function preparePostData(array $data): array
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

    /**
     * 指派標籤到文章
     * @throws \PDOException 當標籤不存在時拋出異常
     */
    private function assignTags(int $postId, array $tagIds): void
    {
        // 先驗證所有標籤是否存在
        $placeholders = str_repeat('?,', count($tagIds) - 1) . '?';
        $sql = "SELECT COUNT(*) FROM tags WHERE id IN ({$placeholders})";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($tagIds);

        $count = (int) $stmt->fetchColumn();
        if ($count !== count($tagIds)) {
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

        // 只驗證有提供的欄位
        $validationData = array_intersect_key($data, [
            'title' => true,
            'content' => true,
            'user_id' => true,
            'user_ip' => true,
            'is_pinned' => true,
            'status' => true,
            'publish_date' => true
        ]);

        if (!empty($validationData)) {
            $this->validateRequiredFields($validationData);
        }

        // 更新時間戳記
        $data['updated_at'] = format_datetime();

        // 準備更新欄位
        $sets = [];
        $params = ['id' => $id];

        foreach ($data as $key => $value) {
            $sets[] = "{$key} = :{$key}";
            $params[$key] = $value;
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

            // 建立查詢條件
            $where = [];
            $params = [];

            if (!empty($conditions)) {
                foreach ($conditions as $key => $value) {
                    $where[] = "{$key} = :{$key}";
                    $params[$key] = $value;
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
                fn($row) => Post::fromArray($row),
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
                fn($row) => Post::fromArray($row),
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
            fn($row) => Post::fromArray($row),
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

            // 檢查是否已經在 24 小時內觀看過
            $stmt = $this->db->prepare('
                SELECT COUNT(*) FROM post_views 
                WHERE post_id = ? AND user_ip = ? 
                AND view_date > datetime("now", "-1 day")
            ');
            $stmt->execute([$id, $userIp]);

            if ((int) $stmt->fetchColumn() > 0) {
                $this->db->rollBack();
                return false;
            }

            // 更新文章觀看次數
            $stmt = $this->db->prepare('UPDATE posts SET view_count = view_count + 1 WHERE id = ?');
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
                'view_date' => date('Y-m-d H:i:s')
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
            if (!empty($tagIds)) {
                $placeholders = str_repeat('?,', count($tagIds) - 1) . '?';
                $sql = "SELECT COUNT(*) FROM tags WHERE id IN ({$placeholders})";
                $stmt = $this->db->prepare($sql);
                $stmt->execute($tagIds);

                if ((int) $stmt->fetchColumn() !== count($tagIds)) {
                    throw new \PDOException('部分標籤不存在');
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

            $this->db->commit();
            $this->invalidateCache($id);
            return true;
        } catch (\Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }
}
