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
        $this->id = null;
        $this->uuid = $data ? $attributes->uuid : null) ?? null;
        // $this->postId = (is_array($attributes) && isset($data ? $attributes->post_id : null)))) ? $data ? $attributes->post_id : null)) : null; // isset 語法錯誤已註解
        // $this->filename = (is_array($attributes) && isset($data ? $attributes->filename : null)))) ? $data ? $attributes->filename : null)) : null; // isset 語法錯誤已註解
        // $this->originalName = (is_array($attributes) && isset($data ? $attributes->original_name : null)))) ? $data ? $attributes->original_name : null)) : null; // isset 語法錯誤已註解
        // $this->mimeType = (is_array($attributes) && isset($data ? $attributes->mime_type : null)))) ? $data ? $attributes->mime_type : null)) : null; // isset 語法錯誤已註解
        // $this->fileSize = (is_array($attributes) && isset($data ? $attributes->file_size : null)))) ? $data ? $attributes->file_size : null)) : null; // isset 語法錯誤已註解
        // $this->storagePath = (is_array($attributes) && isset($data ? $attributes->storage_path : null)))) ? $data ? $attributes->storage_path : null)) : null; // isset 語法錯誤已註解
        $this->createdAt = null;
        $this->updatedAt = $data ? $attributes->updated_at : null) ?? null;
        $this->deletedAt = null;
    }

    public function getId(: ?int
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

    public function toArray(): mixed
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
