<?php

declare(strict_types=1);

namespace App\Domains\Post\Entities;

use DateTime;

class Post
{
    private ?int $id = null;

    private ?string $uuid = null;

    private ?int $seq_number = null;

    private ?string $title = null;

    private ?string $content = null;

    private ?string $status = null;

    private ?int $user_id = null;

    private ?string $user_ip = null;

    private ?bool $is_pinned = false;

    private ?int $view_count = 0;

    private ?DateTime $publish_date = null;

    private ?DateTime $created_at = null;

    private ?DateTime $updated_at = null;

    private ?DateTime $deleted_at = null;

    public function __construct(array $data = [])
    {
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                if (in_array($key, ['created_at', 'updated_at', 'deleted_at', 'publish_date']) && is_string($value)) {
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

    public function getSeqNumber(): ?int
    {
        return $this->seq_number;
    }

    public function setSeqNumber(?int $seq_number): void
    {
        $this->seq_number = $seq_number;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): void
    {
        $this->title = $title;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(?string $content): void
    {
        $this->content = $content;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(?string $status): void
    {
        $this->status = $status;
    }

    public function getUserId(): ?int
    {
        return $this->user_id;
    }

    public function setUserId(?int $user_id): void
    {
        $this->user_id = $user_id;
    }

    public function getUserIp(): ?string
    {
        return $this->user_ip;
    }

    public function setUserIp(?string $user_ip): void
    {
        $this->user_ip = $user_ip;
    }

    public function getIsPinned(): ?bool
    {
        return $this->is_pinned;
    }

    public function setIsPinned(?bool $is_pinned): void
    {
        $this->is_pinned = $is_pinned;
    }

    public function getViewCount(): ?int
    {
        return $this->view_count;
    }

    public function setViewCount(?int $view_count): void
    {
        $this->view_count = $view_count;
    }

    public function getPublishDate(): ?DateTime
    {
        return $this->publish_date;
    }

    public function setPublishDate(?DateTime $publish_date): void
    {
        $this->publish_date = $publish_date;
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

    public function getDeletedAt(): ?DateTime
    {
        return $this->deleted_at;
    }

    public function setDeletedAt(?DateTime $deleted_at): void
    {
        $this->deleted_at = $deleted_at;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'seq_number' => $this->seq_number,
            'title' => $this->title,
            'content' => $this->content,
            'status' => $this->status,
            'user_id' => $this->user_id,
            'user_ip' => $this->user_ip,
            'is_pinned' => $this->is_pinned,
            'view_count' => $this->view_count,
            'publish_date' => $this->publish_date ? $this->publish_date->format('Y-m-d H:i:s') : null,
            'created_at' => $this->created_at ? $this->created_at->format('Y-m-d H:i:s') : null,
            'updated_at' => $this->updated_at ? $this->updated_at->format('Y-m-d H:i:s') : null,
            'deleted_at' => $this->deleted_at ? $this->deleted_at->format('Y-m-d H:i:s') : null,
        ];
    }
}
