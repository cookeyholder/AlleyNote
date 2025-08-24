<?php

declare(strict_types=1);

namespace App\Domains\Security\Models;

use App\Infrastructure\Services\OutputSanitizer;
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
        $this->id = isset($attributes['id']) ? (int) $attributes['id'] : 0;
        $this->uuid = $attributes['uuid'] ?? '';
        $this->ipAddress = $attributes['ip_address'] ?? '';
        $this->type = isset($attributes['type']) ? (int) $attributes['type'] : 0;
        $this->unitId = isset($attributes['unit_id']) ? (int) $attributes['unit_id'] : null;
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
            'updated_at' => $this->updatedAt,
        ];
    }

    /**
     * 取得清理過的資料陣列，適用於前端顯示.
     */
    public function toSafeArray(): array
    {
        $data = $this->toArray();

        // 清理可能包含 HTML 的欄位
        if ($data['description'] !== null) {
            $data['description'] = OutputSanitizer::sanitizeHtml($data['description']);
        }

        return $data;
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
