<?php

declare(strict_types=1);

namespace App\Domains\Attachment\Repositories;

use App\Domains\Attachment\Models\Attachment;
use App\Shared\Contracts\CacheServiceInterface;
use PDO;

class AttachmentRepository
{
    public function __construct(
        private PDO $db,
        private CacheServiceInterface $cache,
    ) {}

    public function create(array $data): Attachment
    {
        $uuid = generate_uuid();
        $sql = '
            INSERT INTO attachments (
                uuid, post_id, filename, original_name,
                mime_type, file_size, storage_path,
                created_at, updated_at
            ) VALUES (
                :uuid, :post_id, :filename, :original_name,
                :mime_type, :file_size, :storage_path,
                CURRENT_TIMESTAMP, CURRENT_TIMESTAMP
            )
        ';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'uuid'          => $uuid,
            'post_id'       => $data['post_id'],
            'filename'      => $data['filename'],
            'original_name' => $data['original_name'],
            'mime_type'     => $data['mime_type'],
            'file_size'     => $data['file_size'],
            'storage_path'  => $data['storage_path'],
        ]);
        $data['id'] = (int) $this->db->lastInsertId();
        $data['uuid'] = $uuid;

        return new Attachment($data);
    }

    public function find(int $id): ?Attachment
    {
        // 快取原始資料列（陣列）而非模型物件，避免快取序列化破壞型別
        /** @var array<string, mixed>|null $data */
        $data = $this->cache->remember("attachment:{$id}", function () use ($id) {
            $sql = '
                SELECT *
                FROM attachments
                WHERE id = :id
            ';
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['id' => $id]);
            /** @var array<string, mixed>|false $row */
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            return is_array($row) ? $row : null;
        });

        return is_array($data) ? new Attachment($data) : null;
    }

    public function findByUuid(string $uuid): ?Attachment
    {
        // 快取原始資料列（陣列）而非模型物件，避免快取序列化破壞型別
        /** @var array<string, mixed>|null $data */
        $data = $this->cache->remember("attachment:uuid:{$uuid}", function () use ($uuid) {
            $sql = '
                SELECT *
                FROM attachments
                WHERE uuid = :uuid
            ';
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['uuid' => $uuid]);
            /** @var array<string, mixed>|false $row */
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            return is_array($row) ? $row : null;
        });

        return is_array($data) ? new Attachment($data) : null;
    }

    /**
     * @return array<int, Attachment>
     */
    public function getByPostId(int $postId): array
    {
        // 快取原始資料列（陣列）而非模型物件，避免快取序列化破壞型別
        /** @var list<array<string, mixed>> $rows */
        $rows = $this->cache->remember("attachments:post:{$postId}", function () use ($postId) {
            $sql = '
                SELECT *
                FROM attachments
                WHERE post_id = :post_id
                AND deleted_at IS NULL
                ORDER BY created_at DESC
            ';
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['post_id' => $postId]);
            /** @var list<array<string, mixed>> $rows */
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return $rows;
        });

        $attachments = [];
        foreach ($rows as $row) {
            if (is_array($row)) {
                $attachments[] = new Attachment($row);
            }
        }

        return $attachments;
    }

    /**
     * 計算指定文章的附件數量.
     */
    public function countByPostId(int $postId): int
    {
        $sql = '
            SELECT COUNT(*) as count
            FROM attachments
            WHERE post_id = :post_id
            AND deleted_at IS NULL
        ';
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['post_id' => $postId]);
        /** @var array<string, mixed>|false $result */
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if (is_array($result) && isset($result['count']) && is_numeric($result['count'])) {
            return (int) $result['count'];
        }

        return 0;
    }

    public function delete(int $id): bool
    {
        $sql = '
            UPDATE attachments
            SET deleted_at = CURRENT_TIMESTAMP,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = :id
            AND deleted_at IS NULL
        ';
        $stmt = $this->db->prepare($sql);
        $success = $stmt->execute(['id' => $id]);
        if ($success) {
            $this->cache->delete("attachment:{$id}");
            // 清除相關的快取
            $attachment = $this->find($id);
            if ($attachment) {
                $this->cache->delete("attachment:uuid:{$attachment->getUuid()}");
                $this->cache->delete("attachments:post:{$attachment->getPostId()}");
            }
        }

        return $success;
    }
}
