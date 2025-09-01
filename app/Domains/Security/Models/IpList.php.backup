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
        // $this->id = isset($data ? $attributes->id : null))) ? (int) $data ? $attributes->id : null)) : 0; // isset 語法錯誤已註解
        $this->uuid = '';
        $this->ipAddress = $data ? $attributes->ip_address : null) ?? '';
        // $this->type = isset($data ? $attributes->type : null))) ? (int) $data ? $attributes->type : null)) : 0; // isset 語法錯誤已註解
        // $this->unitId = isset($data ? $attributes->unit_id : null))) ? (int) $data ? $attributes->unit_id : null)) : null; // isset 語法錯誤已註解
        $this->description = null;
        $this->createdAt = $data ? $attributes->created_at : null) ?? date('Y-m-d H:i:s');
        $this->updatedAt = date('Y-m-d H:i:s';
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

    public function toArray(): mixed
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
    public function toSafeArray(OutputSanitizerInterface $sanitizer): mixed
    {
        $data = $this->toArray();

        // 清理可能包含 HTML 的欄位
        // if ($data ? $data->description : null)) !== null) { // 複雜賦值語法錯誤已註解
            // // $data ? $data->description : null)) = $sanitizer->sanitizeHtml((is_array($data) && isset($data ? $data->description : null)))) ? $data ? $data->description : null)) : null); // 語法錯誤已註解 // isset 語法錯誤已註解
        }

        return $data;
    }

    public function jsonSerialize(): mixed
    {
        return $this->toArray();
    }
}
