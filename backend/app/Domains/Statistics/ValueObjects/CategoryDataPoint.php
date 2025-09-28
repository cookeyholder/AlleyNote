<?php

declare(strict_types=1);

namespace App\Domains\Statistics\ValueObjects;

use JsonSerializable;

/**
 * 分類資料點值物件.
 */
readonly class CategoryDataPoint implements JsonSerializable
{
    public function __construct(
        public string $category,
        public float $value,
        public string $color = '#3B82F6',
        public ?string $label = null,
    ) {}

    /**
     * 建立百分比資料點.
     */
    public static function withPercentage(
        string $category,
        float $value,
        float $total,
        string $color = '#3B82F6',
        ?string $label = null,
    ): self {
        $percentage = $total > 0 ? ($value / $total) * 100 : 0;

        return new self(
            category: $category,
            value: $percentage,
            color: $color,
            label: $label ?? sprintf('%s (%.1f%%)', $category, $percentage),
        );
    }

    /**
     * 建立計數資料點.
     */
    public static function withCount(
        string $category,
        int $count,
        string $color = '#3B82F6',
        ?string $label = null,
    ): self {
        return new self(
            category: $category,
            value: (float) $count,
            color: $color,
            label: $label ?? sprintf('%s (%d)', $category, $count),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        $result = [
            'category' => $this->category,
            'value' => $this->value,
            'color' => $this->color,
        ];

        if ($this->label !== null) {
            $result['label'] = $this->label;
        }

        return $result;
    }
}
