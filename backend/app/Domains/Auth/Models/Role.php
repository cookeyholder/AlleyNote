<?php

declare(strict_types=1);

namespace App\Domains\Auth\Models;

class Role
{
    private int $id;

    private string $name;

    private string $displayName;

    private ?string $description;

    private string $createdAt;

    private string $updatedAt;

    public function __construct(
        int $id,
        string $name,
        string $displayName = '',
        ?string $description = null,
        string $createdAt = '',
        string $updatedAt = '',
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->displayName = $displayName ?: $name;
        $this->description = $description;
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

    public function getDisplayName(): string
    {
        return $this->displayName;
    }

    public function getDescription(): ?string
    {
        return $this->description;
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
            'display_name' => $this->displayName,
            'description' => $this->description,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            (int) $data['id'],
            $data['name'],
            $data['display_name'] ?? '',
            $data['description'] ?? null,
            $data['created_at'] ?? '',
            $data['updated_at'] ?? '',
        );
    }
}
