<?php

declare(strict_types=1);

namespace App\Domains\Post\Repositories;

use App\Domains\Post\Contracts\TagRepositoryInterface;
use App\Domains\Post\Models\Tag;
use DateTimeImmutable;
use PDO;
use RuntimeException;

/**
 * 標籤資料存取實現.
 */
class TagRepository implements TagRepositoryInterface
{
    public function __construct(
        private readonly PDO $db,
    ) {}

    /**
     * @param array<string, mixed> $filters
     * @return array{items: array<int, Tag>, total: int}
     */
    public function list(int $page = 1, int $perPage = 20, array $filters = []): array
    {
        $where = [];
        $params = [];

        // 搜尋過濾
        if (!empty($filters['search']) && is_string($filters['search'])) {
            $where[] = '(name LIKE :search OR slug LIKE :search OR description LIKE :search)';
            $params[':search'] = '%' . $filters['search'] . '%';
        }

        $whereClause = empty($where) ? '' : 'WHERE ' . implode(' AND ', $where);

        // 計算總數
        $totalStmt = $this->db->prepare("SELECT COUNT(*) FROM tags {$whereClause}");
        $totalStmt->execute($params);
        $total = (int) $totalStmt->fetchColumn();

        // 查詢標籤
        $offset = ($page - 1) * $perPage;
        $sql = "SELECT * FROM tags {$whereClause} ORDER BY usage_count DESC, name ASC LIMIT :limit OFFSET :offset";
        $stmt = $this->db->prepare($sql);

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!is_array($rows)) {
            $rows = [];
        }

        /** @var array<int, Tag> $tags */
        $tags = [];
        foreach ($rows as $row) {
            if (is_array($row)) {
                /** @var array<string, mixed> $row */
                $tags[] = $this->mapRowToTag($row);
            }
        }

        return [
            'items' => $tags,
            'total' => $total,
        ];
    }

    public function findById(int $id): ?Tag
    {
        $stmt = $this->db->prepare('SELECT * FROM tags WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!is_array($row) || empty($row)) {
            return null;
        }

        /** @var array<string, mixed> $row */
        return $this->mapRowToTag($row);
    }

    public function findByName(string $name): ?Tag
    {
        $stmt = $this->db->prepare('SELECT * FROM tags WHERE name = :name');
        $stmt->execute([':name' => $name]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!is_array($row) || empty($row)) {
            return null;
        }

        /** @var array<string, mixed> $row */
        return $this->mapRowToTag($row);
    }

    public function findBySlug(string $slug): ?Tag
    {
        $stmt = $this->db->prepare('SELECT * FROM tags WHERE slug = :slug');
        $stmt->execute([':slug' => $slug]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!is_array($row) || empty($row)) {
            return null;
        }

        /** @var array<string, mixed> $row */
        return $this->mapRowToTag($row);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): Tag
    {
        $now = new DateTimeImmutable()->format('Y-m-d H:i:s');

        $stmt = $this->db->prepare('
            INSERT INTO tags (name, slug, description, color, usage_count, created_at, updated_at)
            VALUES (:name, :slug, :description, :color, :usage_count, :created_at, :updated_at)
        ');

        $stmt->execute([
            ':name' => $data['name'],
            ':slug' => $data['slug'] ?? null,
            ':description' => $data['description'] ?? null,
            ':color' => $data['color'] ?? null,
            ':usage_count' => $data['usage_count'] ?? 0,
            ':created_at' => $now,
            ':updated_at' => $now,
        ]);

        $id = (int) $this->db->lastInsertId();
        $tag = $this->findById($id);

        if (!$tag) {
            throw new RuntimeException('建立標籤後無法取得標籤資料');
        }

        return $tag;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update(int $id, array $data): Tag
    {
        $updates = [];
        $params = [':id' => $id];

        $allowedFields = ['name', 'slug', 'description', 'color', 'usage_count'];

        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $data)) {
                $updates[] = "{$field} = :{$field}";
                $params[":{$field}"] = $data[$field];
            }
        }

        if (empty($updates)) {
            $tag = $this->findById($id);
            if (!$tag) {
                throw new RuntimeException('標籤不存在');
            }

            return $tag;
        }

        $now = new DateTimeImmutable()->format('Y-m-d H:i:s');
        $updates[] = 'updated_at = :updated_at';
        $params[':updated_at'] = $now;

        $sql = 'UPDATE tags SET ' . implode(', ', $updates) . ' WHERE id = :id';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        $tag = $this->findById($id);
        if (!$tag) {
            throw new RuntimeException('更新標籤後無法取得標籤資料');
        }

        return $tag;
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM tags WHERE id = :id');

        return $stmt->execute([':id' => $id]);
    }

    public function detachFromAllPosts(int $tagId): void
    {
        $stmt = $this->db->prepare('DELETE FROM post_tags WHERE tag_id = :tag_id');
        $stmt->execute([':tag_id' => $tagId]);
    }

    /**
     * @param array<string, mixed> $row
     */
    private function mapRowToTag(array $row): Tag
    {
        if (!isset($row['id'], $row['name'], $row['created_at'])) {
            throw new RuntimeException('標籤資料不完整');
        }

        $slug = isset($row['slug']) && is_string($row['slug']) ? $row['slug'] : null;
        $description = isset($row['description']) && is_string($row['description']) ? $row['description'] : null;
        $color = isset($row['color']) && is_string($row['color']) ? $row['color'] : null;
        $updatedAt = isset($row['updated_at']) && is_string($row['updated_at']) ? $row['updated_at'] : null;

        // @phpstan-ignore-next-line
        $id = is_int($row['id']) ? $row['id'] : (int) $row['id'];
        // @phpstan-ignore-next-line
        $name = is_string($row['name']) ? $row['name'] : (string) $row['name'];
        // @phpstan-ignore-next-line
        $usageCount = isset($row['usage_count']) ? (is_int($row['usage_count']) ? $row['usage_count'] : (int) $row['usage_count']) : 0;
        // @phpstan-ignore-next-line
        $createdAtStr = is_string($row['created_at']) ? $row['created_at'] : (string) $row['created_at'];

        return new Tag(
            id: $id,
            name: $name,
            slug: $slug,
            description: $description,
            color: $color,
            usageCount: $usageCount,
            createdAt: new DateTimeImmutable($createdAtStr),
            updatedAt: $updatedAt ? new DateTimeImmutable($updatedAt) : null,
        );
    }
}
