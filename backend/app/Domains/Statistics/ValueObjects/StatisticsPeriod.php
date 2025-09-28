<?php

declare(strict_types=1);

namespace App\Domains\Statistics\ValueObjects;

use DateTime;
use DateTimeImmutable;
use InvalidArgumentException;
use JsonSerializable;

/**
 * 統計週期值物件.
 *
 * 表示統計資料的時間範圍，包含週期類型、開始時間、結束時間和時區。
 * 此值物件是 immutable 的，一旦建立就不能修改。
 *
 * @psalm-immutable
 */
final readonly class StatisticsPeriod implements JsonSerializable
{
    /**
     * @param PeriodType $type 週期類型
     * @param DateTimeImmutable $startTime 週期開始時間
     * @param DateTimeImmutable $endTime 週期結束時間
     * @param string $timezone 時區
     */
    public function __construct(
        public PeriodType $type,
        public DateTimeImmutable $startTime,
        public DateTimeImmutable $endTime,
        public string $timezone = 'UTC',
    ) {
        $this->validate();
    }

    /**
     * 從陣列建立統計週期物件.
     *
     * @param array{type: string, start_time: string, end_time: string, timezone?: string} $data
     * @throws InvalidArgumentException
     */
    public static function fromArray(array $data): self
    {
        if (!isset($data['type'], $data['start_time'], $data['end_time'])) {
            throw new InvalidArgumentException('Missing required fields: type, start_time, end_time');
        }

        $type = PeriodType::from($data['type']);
        $startTime = new DateTimeImmutable($data['start_time']);
        $endTime = new DateTimeImmutable($data['end_time']);
        $timezone = $data['timezone'] ?? 'UTC';

        return new self($type, $startTime, $endTime, $timezone);
    }

    /**
     * 建立日統計週期.
     */
    public static function createDaily(DateTimeImmutable $date, string $timezone = 'UTC'): self
    {
        $startTime = $date->setTime(0, 0, 0);
        $endTime = $date->setTime(23, 59, 59);

        return new self(PeriodType::DAILY, $startTime, $endTime, $timezone);
    }

    /**
     * 建立週統計週期（週一到週日）.
     */
    public static function createWeekly(DateTimeImmutable $date, string $timezone = 'UTC'): self
    {
        // 找到這週的週一
        $dayOfWeek = (int) $date->format('N'); // 1 = 週一, 7 = 週日
        $startTime = $date->modify('-' . ($dayOfWeek - 1) . ' days')->setTime(0, 0, 0);
        $endTime = $startTime->modify('+6 days')->setTime(23, 59, 59);

        return new self(PeriodType::WEEKLY, $startTime, $endTime, $timezone);
    }

    /**
     * 建立月統計週期.
     */
    public static function createMonthly(DateTimeImmutable $date, string $timezone = 'UTC'): self
    {
        $startTime = $date->modify('first day of this month')->setTime(0, 0, 0);
        $endTime = $date->modify('last day of this month')->setTime(23, 59, 59);

        return new self(PeriodType::MONTHLY, $startTime, $endTime, $timezone);
    }

    /**
     * 建立年統計週期.
     */
    public static function createYearly(DateTimeImmutable $date, string $timezone = 'UTC'): self
    {
        $year = (int) $date->format('Y');
        $startTime = (new DateTimeImmutable("{$year}-01-01 00:00:00"));
        $endTime = (new DateTimeImmutable("{$year}-12-31 23:59:59"));

        return new self(PeriodType::YEARLY, $startTime, $endTime, $timezone);
    }

    /**
     * 檢查是否包含指定日期.
     */
    public function contains(DateTimeImmutable $date): bool
    {
        return $date >= $this->startTime && $date <= $this->endTime;
    }

    /**
     * 取得週期持續時間（秒）.
     */
    public function getDurationInSeconds(): int
    {
        return $this->endTime->getTimestamp() - $this->startTime->getTimestamp();
    }

    /**
     * 取得週期持續時間（天）.
     */
    public function getDurationInDays(): int
    {
        return (int) ceil($this->getDurationInSeconds() / 86400);
    }

    /**
     * 格式化為可讀字串.
     */
    public function format(string $format = 'Y-m-d'): string
    {
        return match ($this->type) {
            PeriodType::DAILY => $this->startTime->format($format),
            PeriodType::WEEKLY => $this->startTime->format($format) . ' ~ ' . $this->endTime->format($format),
            PeriodType::MONTHLY => $this->startTime->format('Y-m'),
            PeriodType::YEARLY => $this->startTime->format('Y'),
        };
    }

    /**
     * 轉換為陣列.
     *
     * @return array{type: string, start_time: string, end_time: string, timezone: string}
     */
    public function toArray(): array
    {
        return [
            'type' => $this->type->value,
            'start_time' => $this->startTime->format(DateTime::ATOM),
            'end_time' => $this->endTime->format(DateTime::ATOM),
            'timezone' => $this->timezone,
        ];
    }

    /**
     * JSON 序列化.
     *
     * @return array{type: string, start_time: string, end_time: string, timezone: string}
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * 檢查兩個統計週期是否相等.
     */
    public function equals(StatisticsPeriod $other): bool
    {
        return $this->type === $other->type
            && $this->startTime->getTimestamp() === $other->startTime->getTimestamp()
            && $this->endTime->getTimestamp() === $other->endTime->getTimestamp()
            && $this->timezone === $other->timezone;
    }

    /**
     * 轉換為字串表示.
     */
    public function __toString(): string
    {
        return sprintf(
            '%s: %s',
            $this->type->value,
            $this->format(),
        );
    }

    /**
     * 驗證統計週期的有效性.
     *
     * @throws InvalidArgumentException
     */
    private function validate(): void
    {
        // 檢查開始時間必須早於結束時間
        if ($this->startTime >= $this->endTime) {
            throw new InvalidArgumentException('Start time must be before end time');
        }

        // 檢查時區格式是否有效
        if (!$this->isValidTimezone($this->timezone)) {
            throw new InvalidArgumentException("Invalid timezone: {$this->timezone}");
        }

        // 檢查週期長度是否符合類型定義
        $this->validatePeriodLength();
    }

    /**
     * 驗證週期長度是否符合類型.
     *
     * @throws InvalidArgumentException
     */
    private function validatePeriodLength(): void
    {
        $durationInDays = $this->getDurationInDays();

        match ($this->type) {
            PeriodType::DAILY => $durationInDays === 1 ?: throw new InvalidArgumentException('Daily period must be exactly 1 day'),
            PeriodType::WEEKLY => $durationInDays === 7 ?: throw new InvalidArgumentException('Weekly period must be exactly 7 days'),
            PeriodType::MONTHLY => ($durationInDays >= 28 && $durationInDays <= 31) ?: throw new InvalidArgumentException('Monthly period must be 28-31 days'),
            PeriodType::YEARLY => ($durationInDays >= 365 && $durationInDays <= 366) ?: throw new InvalidArgumentException('Yearly period must be 365-366 days'),
        };
    }

    /**
     * 檢查時區字串是否有效.
     */
    private function isValidTimezone(string $timezone): bool
    {
        return in_array($timezone, timezone_identifiers_list(), true);
    }
}
