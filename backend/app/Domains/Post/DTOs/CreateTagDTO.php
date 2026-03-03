<?php

declare(strict_types=1);

namespace App\Domains\Post\DTOs;

/**
 * 建立標籤 DTO.
 */
readonly class CreateTagDTO
{
    public function __construct(
        public string $name,
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
        $name = isset($data['name']) && is_string($data['name']) ? $data['name'] : '';
        $slug = isset($data['slug']) && is_string($data['slug']) ? $data['slug'] : null;
        $description = isset($data['description']) && is_string($data['description']) ? $data['description'] : null;
        $color = isset($data['color']) && is_string($data['color']) ? $data['color'] : null;

        return new self(
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
        return [
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'color' => $this->color,
        ];
    }
}
