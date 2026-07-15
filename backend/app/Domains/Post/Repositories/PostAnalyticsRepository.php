<?php

declare(strict_types=1);

namespace App\Domains\Post\Repositories;

use App\Domains\Post\Models\Post;
use PDO;

class PostAnalyticsRepository extends PostBaseRepository
{
    public function findByCreationSource(string $creationSource, int $limit = 10, int $offset = 0): array
    {
        $cacheKey = sprintf('posts:source:%s:limit:%d:offset:%d', $creationSource, $limit, $offset);

        return $this->cache->remember($cacheKey, function () use ($creationSource, $limit, $offset) {
            $sql = $this->buildSelectQuery('p.creation_source = :creation_source')
                . ' ORDER BY p.created_at DESC LIMIT :limit OFFSET :offset';
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':creation_source', $creationSource, PDO::PARAM_STR);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();

            return array_map(
                fn($row) => Post::fromArray($this->preparePostData($row)),
                $stmt->fetchAll(PDO::FETCH_ASSOC),
            );
        }, self::CACHE_TTL);
    }

    public function getSourceDistribution(): array
    {
        $cacheKey = 'posts:source_distribution';

        return $this->cache->remember($cacheKey, function () {
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
    }

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

        return $this->cache->remember($cacheKey, function () use ($creationSource, $creationSourceDetail, $limit, $offset) {
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
                    'creation_source'        => $creationSource,
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

            return array_map(
                fn($row) => Post::fromArray($this->preparePostData($row)),
                $stmt->fetchAll(PDO::FETCH_ASSOC),
            );
        }, self::CACHE_TTL);
    }

    public function countByCreationSource(string $creationSource): int
    {
        $cacheKey = sprintf('posts:count:source:%s', $creationSource);

        return $this->cache->remember($cacheKey, function () use ($creationSource) {
            $sql = 'SELECT COUNT(*) FROM posts WHERE creation_source = :creation_source AND deleted_at IS NULL';
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':creation_source', $creationSource, PDO::PARAM_STR);
            $stmt->execute();

            return (int) $stmt->fetchColumn();
        }, self::CACHE_TTL);
    }

    public function paginateByCreationSource(
        string $creationSource,
        int $page = 1,
        int $perPage = 10,
    ): array {
        $cacheKey = sprintf('posts:paginate:source:%s:page:%d:per:%d', $creationSource, $page, $perPage);

        return $this->cache->remember($cacheKey, function () use ($creationSource, $page, $perPage) {
            $offset = ($page - 1) * $perPage;
            $total = $this->countByCreationSource($creationSource);

            $sql = $this->buildSelectQuery('p.creation_source = :creation_source')
                . ' ORDER BY p.is_pinned DESC, p.created_at DESC LIMIT :limit OFFSET :offset';
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':creation_source', $creationSource, PDO::PARAM_STR);
            $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            $items = array_map(
                fn($row) => Post::fromArray($this->preparePostData($row)),
                $stmt->fetchAll(PDO::FETCH_ASSOC),
            );

            return [
                'items'    => $items,
                'total'    => $total,
                'page'     => $page,
                'perPage'  => $perPage,
                'lastPage' => ceil($total / $perPage),
            ];
        }, self::CACHE_TTL);
    }
}
