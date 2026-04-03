<?php

declare(strict_types=1);

namespace App\Domains\Post\Repositories;
use PDO;
use PDOStatement;
final class AdminPostReadRepository
{
    public function __construct(
        private readonly PDO $pdo,
    ) {}
    /**
     * @return array{items: array<int, array<string, mixed>>, total: int}
     */
    public function paginate(int $page, int $perPage, string $search = '', string $status = '', bool $includeFuture = false): array
    {
        $hasDeletedAt = $this->hasTableColumn('posts', 'deleted_at');
        $postUserColumn = $this->resolvePostsUserColumn();
        $postPublishColumn = $this->resolvePostsPublishColumn();
        $hasCreatedAt = $this->hasTableColumn('posts', 'created_at');
        $hasUpdatedAt = $this->hasTableColumn('posts', 'updated_at');
        $where = [];
        if ($hasDeletedAt) {
            $where[] = 'p.deleted_at IS NULL';
        }
        $params = [];
        if ($search !== '') {
            $where[] = '(p.title LIKE :search OR p.content LIKE :search)';
            $params[':search'] = "%{$search}%";
        }
        if ($status !== '') {
            $where[] = 'p.status = :status';
            $params[':status'] = $status;
        }
        if (!$includeFuture && $postPublishColumn !== null) {
            $where[] = "(p.{$postPublishColumn} IS NULL OR p.{$postPublishColumn} <= datetime('now'))";
        }
        $whereClause = empty($where) ? '1=1' : implode(' AND ', $where);
        $countSql = "SELECT COUNT(*) as total FROM posts p WHERE {$whereClause}";
        $countStmt = $this->pdo->prepare($countSql);
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();
        $offset = ($page - 1) * $perPage;
        $userIdSelect = $postUserColumn !== null ? "p.{$postUserColumn} as user_id" : 'NULL as user_id';
        $userJoin = $postUserColumn !== null ? "LEFT JOIN users u ON p.{$postUserColumn} = u.id" : 'LEFT JOIN users u ON 1 = 0';
        $publishDateSelect = $postPublishColumn !== null ? "p.{$postPublishColumn} as publish_date" : 'NULL as publish_date';
        $createdAtSelect = $hasCreatedAt ? 'p.created_at' : 'NULL as created_at';
        $updatedAtSelect = $hasUpdatedAt ? 'p.updated_at' : 'NULL as updated_at';
        if ($postPublishColumn !== null && $hasCreatedAt) {
            $orderBy = "COALESCE(p.{$postPublishColumn}, p.created_at) DESC";
        } elseif ($postPublishColumn !== null) {
            $orderBy = "p.{$postPublishColumn} DESC";
        } elseif ($hasCreatedAt) {
            $orderBy = 'p.created_at DESC';
        } else {
            $orderBy = 'p.id DESC';
        }
        $sql = "SELECT p.id, p.title, p.content, p.status, {$userIdSelect}, {$createdAtSelect}, {$updatedAtSelect}, {$publishDateSelect},
                       u.username as author
                FROM posts p
                {$userJoin}
                WHERE {$whereClause}
                ORDER BY {$orderBy}
                LIMIT :limit OFFSET :offset";
        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (!is_array($posts)) {
            $posts = [];
        }
        $items = array_values(array_map(static function ($post): array {
            if (!is_array($post)) {
                return [];
            }
            /** @var array<string, mixed> $post */
            $post['author'] ??= 'Unknown';
            return $post;
        }, $posts));
        return [
            'items' => $items,
            'total' => $total,
        ];
    }
    /**
     * @return array<string, mixed>|null
     */
    public function findById(int $id, bool $includeFuture = false): ?array
    {
        $hasDeletedAt = $this->hasTableColumn('posts', 'deleted_at');
        $postUserColumn = $this->resolvePostsUserColumn();
        $postPublishColumn = $this->resolvePostsPublishColumn();
        $conditions = ['p.id = :id'];
        if ($hasDeletedAt) {
            $conditions[] = 'p.deleted_at IS NULL';
        }
        if (!$includeFuture) {
            $conditions[] = "p.status = 'published'";
            if ($postPublishColumn !== null) {
                $conditions[] = "(p.{$postPublishColumn} IS NULL OR p.{$postPublishColumn} <= datetime('now'))";
            }
        }
        $userJoin = $postUserColumn !== null ? "LEFT JOIN users u ON p.{$postUserColumn} = u.id" : 'LEFT JOIN users u ON 1 = 0';
        $sql = 'SELECT p.*, u.username as author
                FROM posts p
                ' . $userJoin . '
                WHERE ' . implode(' AND ', $conditions);
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        $post = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!is_array($post) || $post === []) {
            return null;
        }
        $post['author'] ??= 'Unknown';
        $post['tags'] = $this->fetchTagsForPost($id);
        /** @var array<string, mixed> */
        return $post;
    }
    /**
     * @return array<int, array<string, mixed>>
     */
    private function fetchTagsForPost(int $postId): array
    {
        $tagsSql = 'SELECT t.id, t.name
                   FROM tags t
                   INNER JOIN post_tags pt ON t.id = pt.tag_id
                   WHERE pt.post_id = :post_id
                   ORDER BY t.name';
        $tagsStmt = $this->pdo->prepare($tagsSql);
        $tagsStmt->execute([':post_id' => $postId]);
        $tags = $tagsStmt->fetchAll(PDO::FETCH_ASSOC);
        /** @var array<int, array<string, mixed>> */
        return is_array($tags) ? $tags : [];
    }
    private function hasTableColumn(string $table, string $column): bool
    {
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $column)) {
            return false;
        }
        return in_array($column, $this->loadTableColumnNames($table), true);
    }
    /**
     * @return array<int, string>
     */
    private function loadTableColumnNames(string $table): array
    {
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $table)) {
            return [];
        }
        $stmt = $this->pdo->query("PRAGMA table_info({$table})");
        if (!$stmt instanceof PDOStatement) {
            return [];
        }
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $columnNames = [];
        foreach ($columns as $column) {
            if (is_array($column) && isset($column['name']) && is_string($column['name'])) {
                $columnNames[] = $column['name'];
            }
        }
        return $columnNames;
    }
    private function resolvePostsUserColumn(): ?string
    {
        if ($this->hasTableColumn('posts', 'user_id')) {
            return 'user_id';
        }
        if ($this->hasTableColumn('posts', 'author_id')) {
            return 'author_id';
        }
        return null;
    }
    private function resolvePostsPublishColumn(): ?string
    {
        if ($this->hasTableColumn('posts', 'publish_date')) {
            return 'publish_date';
        }
        if ($this->hasTableColumn('posts', 'published_at')) {
            return 'published_at';
        }
        return null;
    }
}
