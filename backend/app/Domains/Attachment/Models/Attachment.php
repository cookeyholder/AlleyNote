<?php

declare(strict_types=1);

namespace App\Domains\Attachment\Models;

class Attachment
{
    private ?int $id = null;

    private ?string $uuid = null;

    private int $postId;

    private string $filename;

    private string $originalName;

    private string $mimeType;

    private int $fileSize;

    private string $storagePath;

    private ?string $createdAt = null;

    private ?string $updatedAt = null;

    private ?string $deletedAt = null;

    public function __construct(array $attributes = [])
    {
        // 驗證並設定 id
        $this->id = isset($attributes['id']) && (is_int($attributes['id']) || is_numeric($attributes['id'])) ? (is_int($attributes['id']) ? $attributes['id'] : (int) $attributes['id']) : null;
        
        // 驗證並設定 uuid
        $this->uuid = isset($attributes['uuid']) && is_string($attributes['uuid']) ? $attributes['uuid'] : null;
        
        // 驗證並設定必要欄位
        if (!isset($attributes['post_id']) || (!is_int($attributes['post_id']) && !is_numeric($attributes['post_id']))) {
            throw new \InvalidArgumentException('post_id is required and must be numeric');
        }
        $this->postId = is_int($attributes['post_id']) ? $attributes['post_id'] : (int) $attributes['post_id'];
        
        if (!isset($attributes['filename']) || !is_string($attributes['filename'])) {
            throw new \InvalidArgumentException('filename is required and must be string');
        }
        $this->filename = $attributes['filename'];
        
        if (!isset($attributes['original_name']) || !is_string($attributes['original_name'])) {
            throw new \InvalidArgumentException('original_name is required and must be string');
        }
        $this->originalName = $attributes['original_name'];
        
        if (!isset($attributes['mime_type']) || !is_string($attributes['mime_type'])) {
            throw new \InvalidArgumentException('mime_type is required and must be string');
        }
        $this->mimeType = $attributes['mime_type'];
        
        if (!isset($attributes['file_size']) || (!is_int($attributes['file_size']) && !is_numeric($attributes['file_size']))) {
            throw new \InvalidArgumentException('file_size is required and must be numeric');
        }
        $this->fileSize = is_int($attributes['file_size']) ? $attributes['file_size'] : (int) $attributes['file_size'];
        
        if (!isset($attributes['storage_path']) || !is_string($attributes['storage_path'])) {
            throw new \InvalidArgumentException('storage_path is required and must be string');
        }
        $this->storagePath = $attributes['storage_path'];
        
        // 驗證並設定可選欄位
        $this->createdAt = isset($attributes['created_at']) && is_string($attributes['created_at']) ? $attributes['created_at'] : null;
        $this->updatedAt = isset($attributes['updated_at']) && is_string($attributes['updated_at']) ? $attributes['updated_at'] : null;
        $this->deletedAt = isset($attributes['deleted_at']) && is_string($attributes['deleted_at']) ? $attributes['deleted_at'] : null;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUuid(): ?string
    {
        return $this->uuid;
    }

    public function getPostId(): int
    {
        return $this->postId;
    }

    public function getFilename(): string
    {
        return $this->filename;
    }

    public function getOriginalName(): string
    {
        return $this->originalName;
    }

    public function getMimeType(): string
    {
        return $this->mimeType;
    }

    public function getFileSize(): int
    {
        return $this->fileSize;
    }

    public function getStoragePath(): string
    {
        return $this->storagePath;
    }

    public function getCreatedAt(): ?string
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?string
    {
        return $this->updatedAt;
    }

    public function getDeletedAt(): ?string
    {
        return $this->deletedAt;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'post_id' => $this->postId,
            'filename' => $this->filename,
            'original_name' => $this->originalName,
            'mime_type' => $this->mimeType,
            'file_size' => $this->fileSize,
            'storage_path' => $this->storagePath,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
            'deleted_at' => $this->deletedAt,
        ];
    }
}
