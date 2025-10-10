<?php

declare(strict_types=1);

namespace App\Domains\Post\DTOs;

/**
 * 更新標籤 DTO.
 */
readonly class UpdateTagDTO
{
    public function __construct(
        public int $id,
        public ?string $name = null,
        public ?string $slug = null,
        public ?string $description = null,
        public ?string $color = null,
    ) {}

    /**
     * 從陣列建立 DTO.
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $id = isset($data['id']) && is_numeric($data['id']) ? (int) $data['id'] : 0;
        $name = isset($data['name']) && is_string($data['name']) ? $data['name'] : null;
        $slug = isset($data['slug']) && is_string($data['slug']) ? $data['slug'] : null;
        $description = isset($data['description']) && is_string($data['description']) ? $data['description'] : null;
        $color = isset($data['color']) && is_string($data['color']) ? $data['color'] : null;

        return new self(
            id: $id,
            name: $name,
            slug: $slug,
            description: $description,
            color: $color,
        );
    }

    /**
     * 轉換為陣列.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = ['id' => $this->id];

        if ($this->name !== null) {
            $data['name'] = $this->name;
        }
        if ($this->slug !== null) {
            $data['slug'] = $this->slug;
        }
        if ($this->description !== null) {
            $data['description'] = $this->description;
        }
        if ($this->color !== null) {
            $data['color'] = $this->color;
        }

        return $data;
    }
}
