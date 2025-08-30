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
        $this->id = $attributes['id'] ?? null;
        $this->uuid = $attributes['uuid'] ?? null;
        $this->postId = $attributes['post_id'];
        $this->filename = $attributes['filename'];
        $this->originalName = $attributes['original_name'];
        $this->mimeType = $attributes['mime_type'];
        $this->fileSize = $attributes['file_size'];
        $this->storagePath = $attributes['storage_path'];
        $this->createdAt = $attributes['created_at'] ?? null;
        $this->updatedAt = $attributes['updated_at'] ?? null;
        $this->deletedAt = $attributes['deleted_at'] ?? null;
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
