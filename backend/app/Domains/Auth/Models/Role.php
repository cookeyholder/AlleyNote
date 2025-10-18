<?php

declare(strict_types=1);

namespace App\Domains\Auth\Models;

use InvalidArgumentException;

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
        $id = is_int($data['id']) ? $data['id'] : (is_numeric($data['id']) ? (int) $data['id'] : throw new InvalidArgumentException('id must be numeric'));

        if (!is_string($data['name'])) {
            throw new InvalidArgumentException('name must be string');
        }

        $displayName = isset($data['display_name']) && is_string($data['display_name']) ? $data['display_name'] : '';
        $description = isset($data['description']) && is_string($data['description']) ? $data['description'] : null;
        $createdAt = isset($data['created_at']) && is_string($data['created_at']) ? $data['created_at'] : '';
        $updatedAt = isset($data['updated_at']) && is_string($data['updated_at']) ? $data['updated_at'] : '';

        return new self(
            $id,
            $data['name'],
            $displayName,
            $description,
            $createdAt,
            $updatedAt,
        );
    }
}
