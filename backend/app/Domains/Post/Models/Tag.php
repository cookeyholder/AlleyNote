<?php

declare(strict_types=1);

namespace App\Domains\Post\Models;

use DateTimeImmutable;
use JsonSerializable;

/**
 * 標籤 Model.
 */
class Tag implements JsonSerializable
{
    private int $id;

    private string $name;

    private ?string $slug;

    private ?string $description;

    private ?string $color;

    private int $usageCount;

    private DateTimeImmutable $createdAt;

    private ?DateTimeImmutable $updatedAt;

    public function __construct(
        int $id,
        string $name,
        ?string $slug = null,
        ?string $description = null,
        ?string $color = null,
        int $usageCount = 0,
        ?DateTimeImmutable $createdAt = null,
        ?DateTimeImmutable $updatedAt = null,
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->slug = $slug;
        $this->description = $description;
        $this->color = $color;
        $this->usageCount = $usageCount;
        $this->createdAt = $createdAt ?? new DateTimeImmutable();
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

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getColor(): ?string
    {
        return $this->color;
    }

    public function getUsageCount(): int
    {
        return $this->usageCount;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?DateTimeImmutable
    {
        return $this->updatedAt;
    }

    /**
     * 轉換為陣列.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'color' => $this->color,
            'usage_count' => $this->usageCount,
            'post_count' => $this->usageCount, // 前端使用的別名
            'created_at' => $this->createdAt->format('c'),
            'updated_at' => $this->updatedAt?->format('c'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
