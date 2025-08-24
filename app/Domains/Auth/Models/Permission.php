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
        string $updatedAt = ''
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

    public function toArray(): array
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
            (int) $data['id'],
            $data['name'],
            $data['resource'],
            $data['action'],
            $data['description'] ?? null,
            $data['created_at'] ?? '',
            $data['updated_at'] ?? ''
        );
    }
}
