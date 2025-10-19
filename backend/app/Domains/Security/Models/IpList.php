<?php

declare(strict_types=1);

namespace App\Domains\Security\Models;

use App\Shared\Contracts\OutputSanitizerInterface;
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
        $idValue = $attributes['id'] ?? 0;
        $this->id = is_numeric($idValue) ? (int) $idValue : 0;

        $uuidValue = $attributes['uuid'] ?? '';
        $this->uuid = is_string($uuidValue) ? $uuidValue : '';

        $ipAddressValue = $attributes['ip_address'] ?? '';
        $this->ipAddress = is_string($ipAddressValue) ? $ipAddressValue : '';

        $typeValue = $attributes['type'] ?? 0;
        $this->type = is_numeric($typeValue) ? (int) $typeValue : 0;

        $unitIdValue = $attributes['unit_id'] ?? null;
        $this->unitId = (isset($unitIdValue) && is_numeric($unitIdValue)) ? (int) $unitIdValue : null;

        $descriptionValue = $attributes['description'] ?? null;
        $this->description = (isset($descriptionValue) && is_string($descriptionValue)) ? $descriptionValue : null;

        $createdAtValue = $attributes['created_at'] ?? date('Y-m-d H:i:s');
        $this->createdAt = is_string($createdAtValue) ? $createdAtValue : date('Y-m-d H:i:s');

        $updatedAtValue = $attributes['updated_at'] ?? date('Y-m-d H:i:s');
        $this->updatedAt = is_string($updatedAtValue) ? $updatedAtValue : date('Y-m-d H:i:s');
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
     *
     * @param OutputSanitizerInterface $sanitizer 清理服務
     */
    public function toSafeArray(OutputSanitizerInterface $sanitizer): array
    {
        $data = $this->toArray();

        // 清理可能包含 HTML 的欄位
        if ($data['description'] !== null && is_string($data['description'])) {
            $data['description'] = $sanitizer->sanitizeHtml($data['description']);
        }

        return $data;
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
