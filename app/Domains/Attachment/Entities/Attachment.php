<?php

declare(strict_types=1);

namespace App\Domains\Attachment\Entities;

use DateTime;

class Attachment
{
    private ?int $id = null;

    private ?string $uuid = null;

    private ?int $post_id = null;

    private ?string $filename = null;

    private ?string $original_name = null;

    private ?int $file_size = null;

    private ?string $mime_type = null;

    private ?string $storage_path = null;

    private ?DateTime $created_at = null;

    private ?DateTime $updated_at = null;

    public function __construct(array $data = [])
    {
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                if (in_array($key, ['created_at', 'updated_at']) && is_string($value)) {
                    $this->{$key} = new DateTime($value);
                } else {
                    $this->{$key} = $value;
                }
            }
        }
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    public function getUuid(): ?string
    {
        return $this->uuid;
    }

    public function setUuid(?string $uuid): void
    {
        $this->uuid = $uuid;
    }

    public function getPostId(): ?int
    {
        return $this->post_id;
    }

    public function setPostId(?int $post_id): void
    {
        $this->post_id = $post_id;
    }

    public function getFilename(): ?string
    {
        return $this->filename;
    }

    public function setFilename(?string $filename): void
    {
        $this->filename = $filename;
    }

    public function getOriginalName(): ?string
    {
        return $this->original_name;
    }

    public function setOriginalName(?string $original_name): void
    {
        $this->original_name = $original_name;
    }

    public function getFileSize(): ?int
    {
        return $this->file_size;
    }

    public function setFileSize(?int $file_size): void
    {
        $this->file_size = $file_size;
    }

    public function getMimeType(): ?string
    {
        return $this->mime_type;
    }

    public function setMimeType(?string $mime_type): void
    {
        $this->mime_type = $mime_type;
    }

    public function getStoragePath(): ?string
    {
        return $this->storage_path;
    }

    public function setStoragePath(?string $storage_path): void
    {
        $this->storage_path = $storage_path;
    }

    public function getCreatedAt(): ?DateTime
    {
        return $this->created_at;
    }

    public function setCreatedAt(?DateTime $created_at): void
    {
        $this->created_at = $created_at;
    }

    public function getUpdatedAt(): ?DateTime
    {
        return $this->updated_at;
    }

    public function setUpdatedAt(?DateTime $updated_at): void
    {
        $this->updated_at = $updated_at;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'post_id' => $this->post_id,
            'filename' => $this->filename,
            'original_name' => $this->original_name,
            'file_size' => $this->file_size,
            'mime_type' => $this->mime_type,
            'storage_path' => $this->storage_path,
            'created_at' => $this->created_at ? $this->created_at->format('Y-m-d H:i:s') : null,
            'updated_at' => $this->updated_at ? $this->updated_at->format('Y-m-d H:i:s') : null,
        ];
    }
}
