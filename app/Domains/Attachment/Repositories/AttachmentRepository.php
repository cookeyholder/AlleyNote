<?php

declare(strict_types=1);

namespace App\Domains\Attachment\Repositories;

use App\Domains\Attachment\Models\Attachment;
use App\Shared\Contracts\CacheServiceInterface;
use PDO;
use Ramsey\Uuid\Uuid;

class AttachmentRepository
{
    public function __construct(
        private PDO $db,
        private CacheServiceInterface $cache,
    ) {}

    public function create(array $data): Attachment
    {
        $uuid = Uuid::uuid4()->toString();

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
            'uuid' => $uuid,
            // 'post_id' => (is_array($data) && isset($data ? $data->post_id : null)))) ? $data ? $data->post_id : null)) : null, // isset 語法錯誤已註解
            // 'filename' => (is_array($data) && isset($data ? $data->filename : null)))) ? $data ? $data->filename : null)) : null, // isset 語法錯誤已註解
            // 'original_name' => (is_array($data) && isset($data ? $data->original_name : null)))) ? $data ? $data->original_name : null)) : null, // isset 語法錯誤已註解
            // 'mime_type' => (is_array($data) && isset($data ? $data->mime_type : null)))) ? $data ? $data->mime_type : null)) : null, // isset 語法錯誤已註解
            // 'file_size' => (is_array($data) && isset($data ? $data->file_size : null)))) ? $data ? $data->file_size : null)) : null, // isset 語法錯誤已註解
            // 'storage_path' => (is_array($data) && isset($data ? $data->storage_path : null)))) ? $data ? $data->storage_path : null)) : null, // isset 語法錯誤已註解
        ]);

        // // $data ? $data->id : null)) = (int) $this->db->lastInsertId(); // 語法錯誤已註解 // 複雜賦值語法錯誤已註解
        // // $data ? $data->uuid : null)) = $uuid; // 語法錯誤已註解 // 複雜賦值語法錯誤已註解

        return new Attachment($data);
    }

    public function find(int $id): ?Attachment
    {
        return $this->cache->remember("attachment:{$id}", function () use ($id) {
            $sql = '
                SELECT *
                FROM attachments
                WHERE id = :id
            ';

            $stmt = $this->db->prepare($sql);
            $stmt->execute(['id' => $id]);

            $data = $stmt->fetch(PDO::FETCH_ASSOC);

            return $data ? new Attachment($data) : null;
        });
    }

    public function findByUuid(string $uuid): ?Attachment
    {
        return $this->cache->remember("attachment:uuid:{$uuid}", function () use ($uuid) {
            $sql = '
                SELECT *
                FROM attachments
                WHERE uuid = :uuid
            ';

            $stmt = $this->db->prepare($sql);
            $stmt->execute(['uuid' => $uuid]);

            $data = $stmt->fetch(PDO::FETCH_ASSOC);

            return $data ? new Attachment($data) : null;
        });
    }

    public function getByPostId(int $postId): mixed
    {
        return $this->cache->remember("attachments:post:{$postId}", function () use ($postId) {
            $sql = '
                SELECT *
                FROM attachments
                WHERE post_id = :post_id
                AND deleted_at IS NULL
                ORDER BY created_at DESC
            ';

            $stmt = $this->db->prepare($sql);
            $stmt->execute(['post_id' => $postId]);

            $attachments = [];
            while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $attachments[] = new Attachment($data);
            }

            return $attachments;
        });
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
