<?php

declare(strict_types=1);

namespace App\Domains\Post\Repositories;

use App\Shared\Contracts\CacheServiceInterface;
use DateTime;
use DateTimeZone;
use PDO;
use RuntimeException;
use Throwable;

abstract class PostBaseRepository
{
    protected const CACHE_TTL = 3600;

    protected const POST_SELECT_FIELDS = 'id, uuid, seq_number, title, content, user_id, user_ip, is_pinned, status, publish_date, views, created_at, updated_at, creation_source, creation_source_detail';

    public function __construct(
        protected readonly PDO $db,
        protected readonly CacheServiceInterface $cache,
    ) {}

    protected function addDeletedAtCondition(string $sql, string $tableAlias = ''): string
    {
        $prefix = $tableAlias ? $tableAlias . '.' : '';
        if (stripos($sql, 'WHERE') !== false) {
            return $sql . ' AND ' . $prefix . 'deleted_at IS NULL';
        }

        return $sql . ' WHERE ' . $prefix . 'deleted_at IS NULL';
    }

    protected function buildSelectQuery(string $additionalConditions = '', string $tableAlias = ''): string
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
     * @param array<string, mixed> $result
     */
    protected function preparePostData(array $result): array
    {
        $publishDate = $result['publish_date'] ?? null;
        if (is_string($publishDate) && strpos($publishDate, 'T') === false) {
            try {
                $dt = new DateTime($publishDate, new DateTimeZone('UTC'));
                $publishDate = $dt->format(DateTime::ATOM);
            } catch (Throwable $e) {
            }
        }

        $id = $result['id'] ?? 0;
        $seqNumber = $result['seq_number'] ?? '';
        $userId = $result['user_id'] ?? 0;
        $views = $result['views'] ?? 0;

        return [
            'id'                     => is_numeric($id) ? (int) $id : 0,
            'uuid'                   => $result['uuid'] ?? '',
            'seq_number'             => is_scalar($seqNumber) ? (string) $seqNumber : '',
            'title'                  => $result['title'] ?? '',
            'content'                => $result['content'] ?? '',
            'user_id'                => is_numeric($userId) ? (int) $userId : 0,
            'user_ip'                => $result['user_ip'] ?? null,
            'views'                  => is_numeric($views) ? (int) $views : 0,
            'is_pinned'              => (bool) ($result['is_pinned'] ?? false),
            'status'                 => $result['status'] ?? 'draft',
            'publish_date'           => $publishDate,
            'creation_source'        => $result['creation_source'] ?? null,
            'creation_source_detail' => $result['creation_source_detail'] ?? null,
            'created_at'             => $result['created_at'] ?? null,
            'updated_at'             => $result['updated_at'] ?? null,
            'author'                 => $result['author'] ?? 'Unknown',
        ];
    }

    protected function getNextSeqNumber(): int
    {
        $inTransaction = $this->db->inTransaction();
        if (!$inTransaction) {
            $this->db->exec('BEGIN IMMEDIATE');
        }

        try {
            $sql = 'SELECT COALESCE(MAX(seq_number), 0) + 1 as next_seq FROM posts';
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            /** @var array<string, mixed>|false $result */
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $nextSeq = $result !== false && is_numeric($result['next_seq']) ? (int) $result['next_seq'] : 1;
            if (!$inTransaction) {
                $this->db->exec('COMMIT');
            }

            return $nextSeq;
        } catch (Throwable $e) {
            if (!$inTransaction) {
                $this->db->exec('ROLLBACK');
            }

            throw new RuntimeException('取得序列號失敗: ' . $e->getMessage(), 0, $e);
        }
    }
}
