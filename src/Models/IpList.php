<?php

declare(strict_types=1);

namespace App\Models;

use JsonSerializable;

class IpList implements JsonSerializable
{
    private int $id;
    private string $uuid;
    private string $ipAddress;
    private int $type;
    private ?int $unitId;
    private ?string $description;
    private string $createdAt;
    private string $updatedAt;

    public function __construct(array $attributes)
    {
        $this->id = $attributes['id'] ?? 0;
        $this->uuid = $attributes['uuid'] ?? '';
        $this->ipAddress = $attributes['ip_address'] ?? '';
        $this->type = $attributes['type'] ?? 0;
        $this->unitId = $attributes['unit_id'] ?? null;
        $this->description = $attributes['description'] ?? null;
        $this->createdAt = $attributes['created_at'] ?? date('Y-m-d H:i:s');
        $this->updatedAt = $attributes['updated_at'] ?? date('Y-m-d H:i:s');
    }

    public static function fromArray(array $data): self
    {
        return new self($data);
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function getIpAddress(): string
    {
        return $this->ipAddress;
    }

    public function getType(): int
    {
        return $this->type;
    }

    public function getUnitId(): ?int
    {
        return $this->unitId;
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

    public function isWhitelist(): bool
    {
        return $this->type === 1;
    }

    public function isBlacklist(): bool
    {
        return $this->type === 0;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'ip_address' => $this->ipAddress,
            'type' => $this->type,
            'unit_id' => $this->unitId,
            'description' => $this->description,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
