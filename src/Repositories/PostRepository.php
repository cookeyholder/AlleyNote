<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;
use App\Models\Post;
use App\Repositories\Contracts\PostRepositoryInterface;

class PostRepository implements PostRepositoryInterface
{
    public function __construct(
        private readonly PDO $db
    ) {}

    public function find(int $id): ?Post
    {
        $stmt = $this->db->prepare('SELECT * FROM posts WHERE id = ?');
        $stmt->execute([$id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result ? Post::fromArray($result) : null;
    }

    public function findByUuid(string $uuid): ?Post
    {
        $stmt = $this->db->prepare('SELECT * FROM posts WHERE uuid = ?');
        $stmt->execute([$uuid]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result ? Post::fromArray($result) : null;
    }

    public function findBySeqNumber(int $seqNumber): ?Post
    {
        $stmt = $this->db->prepare('SELECT * FROM posts WHERE seq_number = ?');
        $stmt->execute([$seqNumber]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result ? Post::fromArray($result) : null;
    }

    public function create(array $data): Post
    {
        // 驗證必要欄位
        if (empty($data['title']) || empty($data['content']) || empty($data['user_id'])) {
            throw new \InvalidArgumentException('標題、內容和使用者 ID 為必填欄位');
        }

        // 產生 UUID
        $data['uuid'] = $data['uuid'] ?? generate_uuid();

        // 取得下一個流水號
        $stmt = $this->db->query('SELECT COALESCE(MAX(seq_number), 0) + 1 FROM posts');
        $data['seq_number'] = $stmt->fetchColumn();

        // 設定時間戳記
        $now = format_datetime();
        $data['created_at'] = $now;
        $data['updated_at'] = $now;

        // 設定發布時間
        $data['publish_date'] = $data['publish_date'] ?? $now;

        // 準備 SQL 及參數
        $sql = "INSERT INTO posts (
            uuid, seq_number, title, content, user_id, user_ip,
            views, is_pinned, status, publish_date, created_at, updated_at
        ) VALUES (
            :uuid, :seq_number, :title, :content, :user_id, :user_ip,
            :views, :is_pinned, :status, :publish_date, :created_at, :updated_at
        )";

        // 執行新增
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'uuid' => $data['uuid'],
            'seq_number' => $data['seq_number'],
            'title' => $data['title'],
            'content' => $data['content'],
            'user_id' => $data['user_id'],
            'user_ip' => $data['user_ip'] ?? '127.0.0.1',
            'views' => $data['views'] ?? 0,
            'is_pinned' => $data['is_pinned'] ?? false,
            'status' => $data['status'] ?? 1,
            'publish_date' => $data['publish_date'],
            'created_at' => $data['created_at'],
            'updated_at' => $data['updated_at']
        ]);

        $data['id'] = (int) $this->db->lastInsertId();
        return Post::fromArray($data);
    }

    public function update(int $id, array $data): Post
    {
        // 檢查文章是否存在
        if (!$this->find($id)) {
            throw new \InvalidArgumentException('找不到指定的文章');
        }

        // 防止修改關鍵欄位
        $protectedFields = ['id', 'uuid', 'seq_number', 'created_at', 'views'];
        foreach ($protectedFields as $field) {
            unset($data[$field]);
        }

        // 驗證必要欄位格式
        if (isset($data['title']) && empty($data['title'])) {
            throw new \InvalidArgumentException('標題不能為空');
        }

        if (isset($data['content']) && empty($data['content'])) {
            throw new \InvalidArgumentException('內容不能為空');
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
            return $this->find($id);
        }

        $sql = "UPDATE posts SET " . implode(', ', $sets) . " WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $this->find($id);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM posts WHERE id = ?');
        return $stmt->execute([$id]);
    }

    public function paginate(int $page = 1, int $perPage = 10, array $conditions = []): array
    {
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
    }

    public function getPinnedPosts(int $limit = 5): array
    {
        $sql = 'SELECT * FROM posts WHERE is_pinned = 1 ' .
            'ORDER BY publish_date DESC LIMIT :limit';

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return array_map(
            fn($row) => Post::fromArray($row),
            $stmt->fetchAll(PDO::FETCH_ASSOC)
        );
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
        $this->db->beginTransaction();

        try {
            // 更新文章觀看次數
            $stmt = $this->db->prepare('UPDATE posts SET views = views + 1 WHERE id = ?');
            $stmt->execute([$id]);

            // 記錄觀看記錄
            $sql = 'INSERT INTO post_views (uuid, post_id, user_id, user_ip, view_date) ' .
                'VALUES (:uuid, :post_id, :user_id, :user_ip, :view_date)';

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                'uuid' => generate_uuid(),
                'post_id' => $id,
                'user_id' => $userId,
                'user_ip' => $userIp,
                'view_date' => format_datetime()
            ]);

            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }

    public function setPinned(int $id, bool $isPinned): bool
    {
        $stmt = $this->db->prepare('UPDATE posts SET is_pinned = :is_pinned WHERE id = :id');
        return $stmt->execute([
            'id' => $id,
            'is_pinned' => $isPinned
        ]);
    }

    public function setTags(int $id, array $tagIds): bool
    {
        $this->db->beginTransaction();

        try {
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
            return true;
        } catch (\Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }
}
