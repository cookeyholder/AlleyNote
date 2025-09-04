<?php

declare(strict_types=1);

namespace App\Application\Statistics\DTOs;

use App\Shared\Contracts\ValidatorInterface;
use App\Shared\DTOs\BaseDTO;
use DateTimeImmutable;

/**
 * 統計總覽資料傳輸物件.
 *
 * 封裝整體統計資訊，包含總體數據和時段比較
 */
class StatisticsOverviewDTO extends BaseDTO
{
    public function __construct(
        ValidatorInterface $validator,
        public readonly int $totalPosts,
        public readonly int $totalUsers,
        public readonly int $totalSources,
        public readonly int $todayPosts,
        public readonly int $weeklyPosts,
        public readonly int $monthlyPosts,
        public readonly float $averagePostsPerDay,
        public readonly float $userEngagementRate,
        public readonly DateTimeImmutable $lastUpdated,
        public readonly array $trendData = [],
    ) {
        parent::__construct($validator);
    }

    /**
     * 從陣列建立 DTO 實例.
     *
     * @param ValidatorInterface $validator 驗證器
     * @param array<string, mixed> $data 輸入資料
     */
    public static function fromArray(ValidatorInterface $validator, array $data): self
    {
        /** @var array<string, mixed> $validatedData */
        $validatedData = $validator->validateOrFail($data, self::getStaticValidationRules());

        return new self(
            validator: $validator,
            totalPosts: self::safeInt($validatedData['total_posts']),
            totalUsers: self::safeInt($validatedData['total_users']),
            totalSources: self::safeInt($validatedData['total_sources']),
            todayPosts: self::safeInt($validatedData['today_posts']),
            weeklyPosts: self::safeInt($validatedData['weekly_posts']),
            monthlyPosts: self::safeInt($validatedData['monthly_posts']),
            averagePostsPerDay: self::safeFloat($validatedData['average_posts_per_day']),
            userEngagementRate: self::safeFloat($validatedData['user_engagement_rate']),
            lastUpdated: new DateTimeImmutable(self::safeString($validatedData['last_updated'])),
            trendData: self::safeArray($validatedData['trend_data'] ?? []),
        );
    }

    /**
     * 將 DTO 轉換為陣列.
     */
    public function toArray(): array
    {
        return [
            'total_posts' => $this->totalPosts,
            'total_users' => $this->totalUsers,
            'total_sources' => $this->totalSources,
            'today_posts' => $this->todayPosts,
            'weekly_posts' => $this->weeklyPosts,
            'monthly_posts' => $this->monthlyPosts,
            'average_posts_per_day' => $this->averagePostsPerDay,
            'user_engagement_rate' => $this->userEngagementRate,
            'last_updated' => $this->lastUpdated->format('Y-m-d H:i:s'),
            'trend_data' => $this->trendData,
        ];
    }

    /**
     * 取得驗證規則.
     */
    protected function getValidationRules(): array
    {
        return self::getStaticValidationRules();
    }

    /**
     * 取得靜態驗證規則.
     *
     * @return array<string, mixed>
     */
    private static function getStaticValidationRules(): array
    {
        return [
            'total_posts' => 'required|integer|min:0',
            'total_users' => 'required|integer|min:0',
            'total_sources' => 'required|integer|min:0',
            'today_posts' => 'required|integer|min:0',
            'weekly_posts' => 'required|integer|min:0',
            'monthly_posts' => 'required|integer|min:0',
            'average_posts_per_day' => 'required|numeric|min:0',
            'user_engagement_rate' => 'required|numeric|min:0|max:100',
            'last_updated' => 'required|string',
            'trend_data' => 'sometimes|array',
        ];
    }

    /**
     * 計算成長率.
     *
     * @param int $current 當前值
     * @param int $previous 之前值
     * @return float 成長率百分比
     */
    public function calculateGrowthRate(int $current, int $previous): float
    {
        if ($previous === 0) {
            return $current > 0 ? 100.0 : 0.0;
        }

        return round((($current - $previous) / $previous) * 100, 2);
    }

    /**
     * 取得今日與昨日貼文成長率.
     */
    public function getTodayGrowthRate(): float
    {
        $yesterdayPosts = self::safeInt($this->trendData['yesterday_posts'] ?? 0);

        return $this->calculateGrowthRate($this->todayPosts, $yesterdayPosts);
    }

    /**
     * 取得本週與上週貼文成長率.
     */
    public function getWeeklyGrowthRate(): float
    {
        $lastWeekPosts = self::safeInt($this->trendData['last_week_posts'] ?? 0);

        return $this->calculateGrowthRate($this->weeklyPosts, $lastWeekPosts);
    }

    /**
     * 檢查是否為高活躍度狀態.
     */
    public function isHighActivity(): bool
    {
        return $this->userEngagementRate >= 70.0 && $this->averagePostsPerDay >= 10.0;
    }

    /**
     * 安全轉換為整數.
     */
    private static function safeInt(mixed $value): int
    {
        if (is_int($value)) {
            return $value;
        }
        if (is_numeric($value)) {
            return (int) $value;
        }

        return 0;
    }

    /**
     * 安全轉換為浮點數.
     */
    private static function safeFloat(mixed $value): float
    {
        if (is_float($value)) {
            return $value;
        }
        if (is_numeric($value)) {
            return (float) $value;
        }

        return 0.0;
    }

    /**
     * 安全轉換為字串.
     */
    private static function safeString(mixed $value): string
    {
        if (is_string($value)) {
            return $value;
        }
        if (is_null($value)) {
            return '';
        }
        if (is_scalar($value)) {
            return (string) $value;
        }

        return '';
    }

    /**
     * 安全轉換為陣列.
     */
    private static function safeArray(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }

        return [];
    }
}
