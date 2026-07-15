<?php

declare(strict_types=1);

namespace App\Domains\Statistics\ValueObjects;

use JsonSerializable;

/**
 * 建議值物件.
 */
readonly class Recommendation implements JsonSerializable
{
    /**
     * @param string $title 建議標題
     * @param string $description 建議描述
     * @param string $priority 優先級（high/medium/low）
     * @param string $category 分類
     */
    public function __construct(
        private string $title,
        private string $description,
        private string $priority = 'medium',
        private string $category = 'general',
    ) {}

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getPriority(): string
    {
        return $this->priority;
    }

    public function getCategory(): string
    {
        return $this->category;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'title'       => $this->title,
            'description' => $this->description,
            'priority'    => $this->priority,
            'category'    => $this->category,
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
