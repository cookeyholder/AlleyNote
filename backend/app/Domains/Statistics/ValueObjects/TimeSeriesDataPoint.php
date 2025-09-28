<?php

declare(strict_types=1);

namespace App\Domains\Statistics\ValueObjects;

use DateTimeInterface;
use JsonSerializable;

/**
 * 時間序列資料點值物件.
 */
readonly class TimeSeriesDataPoint implements JsonSerializable
{
    public function __construct(
        public string $timestamp,
        public float $value,
        public ?string $label = null,
    ) {}

    /**
     * 建立日期資料點.
     */
    public static function forDate(
        DateTimeInterface $date,
        float $value,
        ?string $label = null,
    ): self {
        return new self(
            timestamp: $date->format('Y-m-d'),
            value: $value,
            label: $label,
        );
    }

    /**
     * 建立月份資料點.
     */
    public static function forMonth(
        DateTimeInterface $date,
        float $value,
        ?string $label = null,
    ): self {
        return new self(
            timestamp: $date->format('Y-m'),
            value: $value,
            label: $label,
        );
    }

    /**
     * 建立小時資料點.
     */
    public static function forHour(
        DateTimeInterface $dateTime,
        float $value,
        ?string $label = null,
    ): self {
        return new self(
            timestamp: $dateTime->format('Y-m-d H:i'),
            value: $value,
            label: $label,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        $result = [
            'x' => $this->timestamp,
            'y' => $this->value,
        ];

        if ($this->label !== null) {
            $result['label'] = $this->label;
        }

        return $result;
    }
}
