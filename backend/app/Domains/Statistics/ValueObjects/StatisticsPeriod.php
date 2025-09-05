<?php

declare(strict_types=1);

namespace App\Domains\Statistics\ValueObjects;

use App\Domains\Statistics\Enums\PeriodType;
use App\Domains\Statistics\Exceptions\InvalidStatisticsPeriodException;
use DateTimeImmutable;
use DateTimeInterface;

/**
 * 統計週期值物件
 * 表示統計資料的時間範圍，包含開始時間、結束時間和週期類型.
 */
readonly class StatisticsPeriod
{
    /**
     * @param DateTimeImmutable $startDate 開始日期
     * @param DateTimeImmutable $endDate 結束日期
     * @param PeriodType $type 週期類型
     */
    private function __construct(
        public DateTimeImmutable $startDate,
        public DateTimeImmutable $endDate,
        public PeriodType $type,
    ) {}

    /**
     * 建立統計週期.
     */
    public static function create(
        DateTimeInterface $startDate,
        DateTimeInterface $endDate,
        PeriodType $type,
    ): self {
        $start = DateTimeImmutable::createFromInterface($startDate);
        $end = DateTimeImmutable::createFromInterface($endDate);

        if ($start > $end) {
            throw new InvalidStatisticsPeriodException(
                '開始日期不能晚於結束日期',
            );
        }

        $period = new self($start, $end, $type);
        $period->validatePeriodLength();

        return $period;
    }

    /**
     * 建立當日週期.
     */
    public static function today(): self
    {
        $now = new DateTimeImmutable();
        $startOfDay = $now->setTime(0, 0, 0);
        $endOfDay = $now->setTime(23, 59, 59);

        return new self($startOfDay, $endOfDay, PeriodType::DAILY);
    }

    /**
     * 建立昨日週期.
     */
    public static function yesterday(): self
    {
        $yesterday = new DateTimeImmutable('-1 day');
        $startOfDay = $yesterday->setTime(0, 0, 0);
        $endOfDay = $yesterday->setTime(23, 59, 59);

        return new self($startOfDay, $endOfDay, PeriodType::DAILY);
    }

    /**
     * 建立本週週期.
     */
    public static function thisWeek(): self
    {
        $now = new DateTimeImmutable();
        $startOfWeek = $now->modify('monday this week')->setTime(0, 0, 0);
        $endOfWeek = $now->modify('sunday this week')->setTime(23, 59, 59);

        return new self($startOfWeek, $endOfWeek, PeriodType::WEEKLY);
    }

    /**
     * 建立上週週期.
     */
    public static function lastWeek(): self
    {
        $now = new DateTimeImmutable();
        $startOfLastWeek = $now->modify('monday last week')->setTime(0, 0, 0);
        $endOfLastWeek = $now->modify('sunday last week')->setTime(23, 59, 59);

        return new self($startOfLastWeek, $endOfLastWeek, PeriodType::WEEKLY);
    }

    /**
     * 建立本月週期.
     */
    public static function thisMonth(): self
    {
        $now = new DateTimeImmutable();
        $startOfMonth = $now->modify('first day of this month')->setTime(0, 0, 0);
        $endOfMonth = $now->modify('last day of this month')->setTime(23, 59, 59);

        return new self($startOfMonth, $endOfMonth, PeriodType::MONTHLY);
    }

    /**
     * 建立上月週期.
     */
    public static function lastMonth(): self
    {
        $now = new DateTimeImmutable();
        $startOfLastMonth = $now->modify('first day of last month')->setTime(0, 0, 0);
        $endOfLastMonth = $now->modify('last day of last month')->setTime(23, 59, 59);

        return new self($startOfLastMonth, $endOfLastMonth, PeriodType::MONTHLY);
    }

    /**
     * 建立今年週期.
     */
    public static function thisYear(): self
    {
        $now = new DateTimeImmutable();
        $startOfYear = $now->setDate((int) $now->format('Y'), 1, 1)->setTime(0, 0, 0);
        $endOfYear = $now->setDate((int) $now->format('Y'), 12, 31)->setTime(23, 59, 59);

        return new self($startOfYear, $endOfYear, PeriodType::YEARLY);
    }

    /**
     * 建立去年週期.
     */
    public static function lastYear(): self
    {
        $now = new DateTimeImmutable();
        $lastYear = (int) $now->format('Y') - 1;
        $startOfLastYear = $now->setDate($lastYear, 1, 1)->setTime(0, 0, 0);
        $endOfLastYear = $now->setDate($lastYear, 12, 31)->setTime(23, 59, 59);

        return new self($startOfLastYear, $endOfLastYear, PeriodType::YEARLY);
    }

    /**
     * 建立自訂範圍週期.
     */
    public static function customRange(
        DateTimeInterface $startDate,
        DateTimeInterface $endDate,
    ): self {
        $start = DateTimeImmutable::createFromInterface($startDate);
        $end = DateTimeImmutable::createFromInterface($endDate);
        $daysDiff = $start->diff($end)->days;

        $type = match (true) {
            $daysDiff === 1 => PeriodType::DAILY,
            $daysDiff <= 7 => PeriodType::WEEKLY,
            $daysDiff <= 31 => PeriodType::MONTHLY,
            default => PeriodType::YEARLY,
        };

        return self::create($start, $end, $type);
    }

    /**
     * 取得週期長度（天數）.
     */
    public function getDaysCount(): int
    {
        return (int) $this->startDate->diff($this->endDate)->days + 1;
    }

    /**
     * 取得週期長度（小時數）.
     */
    public function getHoursCount(): int
    {
        return $this->getDaysCount() * 24;
    }

    /**
     * 判斷指定日期是否在週期範圍內.
     */
    public function contains(DateTimeInterface $date): bool
    {
        $checkDate = DateTimeImmutable::createFromInterface($date);

        return $checkDate >= $this->startDate && $checkDate <= $this->endDate;
    }

    /**
     * 判斷是否為當前週期.
     */
    public function isCurrent(): bool
    {
        $now = new DateTimeImmutable();

        return $this->contains($now);
    }

    /**
     * 判斷是否為過去週期.
     */
    public function isPast(): bool
    {
        $now = new DateTimeImmutable();

        return $this->endDate < $now;
    }

    /**
     * 判斷是否為未來週期.
     */
    public function isFuture(): bool
    {
        $now = new DateTimeImmutable();

        return $this->startDate > $now;
    }

    /**
     * 取得下一個週期.
     */
    public function getNext(): self
    {
        $nextStart = match ($this->type) {
            PeriodType::DAILY => $this->startDate->modify('+1 day'),
            PeriodType::WEEKLY => $this->startDate->modify('+1 week'),
            PeriodType::MONTHLY => $this->startDate->modify('+1 month'),
            PeriodType::YEARLY => $this->startDate->modify('+1 year'),
        };

        $nextEnd = match ($this->type) {
            PeriodType::DAILY => $this->endDate->modify('+1 day'),
            PeriodType::WEEKLY => $this->endDate->modify('+1 week'),
            PeriodType::MONTHLY => $this->endDate->modify('+1 month'),
            PeriodType::YEARLY => $this->endDate->modify('+1 year'),
        };

        return new self($nextStart, $nextEnd, $this->type);
    }

    /**
     * 取得上一個週期.
     */
    public function getPrevious(): self
    {
        $prevStart = match ($this->type) {
            PeriodType::DAILY => $this->startDate->modify('-1 day'),
            PeriodType::WEEKLY => $this->startDate->modify('-1 week'),
            PeriodType::MONTHLY => $this->startDate->modify('-1 month'),
            PeriodType::YEARLY => $this->startDate->modify('-1 year'),
        };

        $prevEnd = match ($this->type) {
            PeriodType::DAILY => $this->endDate->modify('-1 day'),
            PeriodType::WEEKLY => $this->endDate->modify('-1 week'),
            PeriodType::MONTHLY => $this->endDate->modify('-1 month'),
            PeriodType::YEARLY => $this->endDate->modify('-1 year'),
        };

        return new self($prevStart, $prevEnd, $this->type);
    }

    /**
     * 取得週期格式化字串.
     */
    public function format(string $format = 'Y-m-d'): string
    {
        return sprintf(
            '%s to %s',
            $this->startDate->format($format),
            $this->endDate->format($format),
        );
    }

    /**
     * 取得週期顯示名稱.
     */
    public function getDisplayName(): string
    {
        return match ($this->type) {
            PeriodType::DAILY => $this->startDate->format('Y-m-d'),
            PeriodType::WEEKLY => sprintf(
                '%s 週',
                $this->startDate->format('Y-m-d'),
            ),
            PeriodType::MONTHLY => $this->startDate->format('Y 年 m 月'),
            PeriodType::YEARLY => $this->startDate->format('Y 年'),
        };
    }

    /**
     * 比較兩個週期是否相等.
     */
    public function equals(StatisticsPeriod $other): bool
    {
        return $this->startDate->format('Y-m-d H:i:s') === $other->startDate->format('Y-m-d H:i:s')
            && $this->endDate->format('Y-m-d H:i:s') === $other->endDate->format('Y-m-d H:i:s')
            && $this->type === $other->type;
    }

    /**
     * 轉換為陣列.
     *
     * @return array{
     *     start_date: string,
     *     end_date: string,
     *     type: string,
     *     display_name: string,
     *     days_count: int
     * }
     */
    public function toArray(): array
    {
        return [
            'start_date' => $this->startDate->format('Y-m-d H:i:s'),
            'end_date' => $this->endDate->format('Y-m-d H:i:s'),
            'type' => $this->type->value,
            'display_name' => $this->getDisplayName(),
            'days_count' => $this->getDaysCount(),
        ];
    }

    /**
     * 轉換為字串.
     */
    public function __toString(): string
    {
        return sprintf(
            '[%s] %s (%s)',
            $this->type->getDisplayName(),
            $this->format(),
            $this->getDaysCount() . ' 天',
        );
    }

    /**
     * 驗證週期長度是否合理.
     */
    private function validatePeriodLength(): void
    {
        $maxDays = match ($this->type) {
            PeriodType::DAILY => 1,
            PeriodType::WEEKLY => 7,
            PeriodType::MONTHLY => 31,
            PeriodType::YEARLY => 366, // 考慮閏年
        };

        if ($this->getDaysCount() > $maxDays) {
            throw new InvalidStatisticsPeriodException(
                sprintf(
                    '週期長度 %d 天超過 %s 類型的最大允許值 %d 天',
                    $this->getDaysCount(),
                    $this->type->getDisplayName(),
                    $maxDays,
                ),
            );
        }
    }
}
