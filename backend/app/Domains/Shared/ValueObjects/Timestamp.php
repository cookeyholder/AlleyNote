<?php

declare(strict_types=1);

namespace App\Domains\Shared\ValueObjects;

use DateTimeImmutable;
use DateTimeZone;
use Exception;
use InvalidArgumentException;
use JsonSerializable;
use Stringable;

/**
 * Timestamp 值物件.
 *
 * 表示不可變的時間戳記，提供時間相關的領域邏輯
 */
final readonly class Timestamp implements JsonSerializable, Stringable
{
    private DateTimeImmutable $dateTime;

    public function __construct(DateTimeImmutable|int|string $timestamp, ?DateTimeZone $timezone = null)
    {
        if ($timestamp instanceof DateTimeImmutable) {
            $this->dateTime = $timestamp;
        } elseif (is_int($timestamp)) {
            $this->dateTime = new DateTimeImmutable('@' . $timestamp)
                ->setTimezone($timezone ?? new DateTimeZone('UTC'));
        } elseif (is_string($timestamp)) {
            try {
                $this->dateTime = new DateTimeImmutable($timestamp, $timezone);
            } catch (Exception $e) {
                throw new InvalidArgumentException("無效的時間戳記格式: {$timestamp}");
            }
        } else {
            throw new InvalidArgumentException('Timestamp 必須是 DateTimeImmutable、整數或字串');
        }
    }

    /**
     * 建立當前時間的 Timestamp.
     */
    public static function now(?DateTimeZone $timezone = null): self
    {
        return new self(new DateTimeImmutable('now', $timezone));
    }

    /**
     * 從 Unix 時間戳記建立.
     */
    public static function fromUnixTimestamp(int $timestamp): self
    {
        return new self($timestamp);
    }

    /**
     * 從字串建立.
     */
    public static function fromString(string $timestamp, ?DateTimeZone $timezone = null): self
    {
        return new self($timestamp, $timezone);
    }

    /**
     * 取得 Unix 時間戳記.
     */
    public function toUnixTimestamp(): int
    {
        return $this->dateTime->getTimestamp();
    }

    /**
     * 格式化為字串.
     */
    public function format(string $format = 'Y-m-d H:i:s'): string
    {
        return $this->dateTime->format($format);
    }

    /**
     * 取得 ISO 8601 格式.
     */
    public function toIso8601(): string
    {
        return $this->dateTime->format(DateTimeImmutable::ATOM);
    }

    /**
     * 轉換為 RFC 3339 格式.
     */
    public function toRfc3339(): string
    {
        return $this->dateTime->format(DateTimeImmutable::RFC3339);
    }

    /**
     * 增加時間.
     */
    public function addSeconds(int $seconds): self
    {
        return new self($this->dateTime->modify("+{$seconds} seconds"));
    }

    /**
     * 增加分鐘.
     */
    public function addMinutes(int $minutes): self
    {
        return new self($this->dateTime->modify("+{$minutes} minutes"));
    }

    /**
     * 增加小時.
     */
    public function addHours(int $hours): self
    {
        return new self($this->dateTime->modify("+{$hours} hours"));
    }

    /**
     * 增加天數.
     */
    public function addDays(int $days): self
    {
        return new self($this->dateTime->modify("+{$days} days"));
    }

    /**
     * 檢查是否在指定時間之前.
     */
    public function isBefore(Timestamp $other): bool
    {
        return $this->dateTime < $other->dateTime;
    }

    /**
     * 檢查是否在指定時間之後.
     */
    public function isAfter(Timestamp $other): bool
    {
        return $this->dateTime > $other->dateTime;
    }

    /**
     * 計算與另一個時間戳記的差距（秒）.
     */
    public function diffInSeconds(Timestamp $other): int
    {
        return abs($this->toUnixTimestamp() - $other->toUnixTimestamp());
    }

    /**
     * 檢查是否在過去.
     */
    public function isPast(): bool
    {
        return $this->isBefore(self::now());
    }

    /**
     * 檢查是否在未來.
     */
    public function isFuture(): bool
    {
        return $this->isAfter(self::now());
    }

    /**
     * 檢查兩個時間戳記是否相等.
     */
    public function equals(Timestamp $other): bool
    {
        return $this->toUnixTimestamp() === $other->toUnixTimestamp();
    }

    /**
     * 取得日期部分（年-月-日）.
     */
    public function toDateString(): string
    {
        return $this->dateTime->format('Y-m-d');
    }

    /**
     * 取得時間部分（時:分:秒）.
     */
    public function toTimeString(): string
    {
        return $this->dateTime->format('H:i:s');
    }

    /**
     * 取得底層的 DateTimeImmutable 物件.
     */
    public function toDateTime(): DateTimeImmutable
    {
        return $this->dateTime;
    }

    /**
     * 轉換為字串.
     */
    public function toString(): string
    {
        return $this->format();
    }

    public function __toString(): string
    {
        return $this->toString();
    }

    /**
     * JSON 序列化.
     */
    public function jsonSerialize(): string
    {
        return $this->toIso8601();
    }

    /**
     * 轉換為陣列.
     */
    public function toArray(): array
    {
        return [
            'timestamp' => $this->toUnixTimestamp(),
            'iso8601' => $this->toIso8601(),
            'formatted' => $this->format(),
            'date' => $this->toDateString(),
            'time' => $this->toTimeString(),
        ];
    }
}
