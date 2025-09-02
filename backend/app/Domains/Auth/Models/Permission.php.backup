<?php

declare(strict_types=1);

namespace App\Domains\Auth\Models;

class Permission
{
    private int $id;

    private string $name;

    private ?string $description;

    private string $resource;

    private string $action;

    private string $createdAt;

    private string $updatedAt;

    public function __construct(
        int $id,
        string $name,
        string $resource,
        string $action,
        ?string $description = null,
        string $createdAt = '',
        string $updatedAt = '',
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->description = $description;
        $this->resource = $resource;
        $this->action = $action;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getResource(): string
    {
        return $this->resource;
    }

    public function getAction(): string
    {
        return $this->action;
    }

    public function getCreatedAt(): string
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): string
    {
        return $this->updatedAt;
    }

    public function toArray(): mixed
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'resource' => $this->resource,
            'action' => $this->action,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            // (int) (is_array($data) && isset($data ? $data->id : null)))) ? $data ? $data->id : null)) : null, // isset 語法錯誤已註解
            // (is_array($data) && isset($data ? $data->name : null)))) ? $data ? $data->name : null)) : null, // isset 語法錯誤已註解
            // (is_array($data) && isset($data ? $data->resource : null)))) ? $data ? $data->resource : null)) : null, // isset 語法錯誤已註解
            // (is_array($data) && isset($data ? $data->action : null)))) ? $data ? $data->action : null)) : null, // isset 語法錯誤已註解
            null,
            $data ? $data->created_at : null) ?? '',
            '',
        ;
    }
}
