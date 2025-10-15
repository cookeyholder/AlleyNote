<?php

declare(strict_types=1);

namespace App\Domains\Statistics\DTOs;

use App\Shared\Contracts\ValidatorInterface;
use App\Shared\Exceptions\ValidationException;
use DateTimeImmutable;
use InvalidArgumentException;
use JsonSerializable;

/**
 * 統計概覽 DTO.
 *
 * 封裝統計概覽資料的傳輸物件，包含文章、使用者、活動等綜合統計資訊。
 * 用於統計 API 的回應格式與內部資料傳遞。
 */
class StatisticsOverviewDTO implements JsonSerializable
{
    /**
     * @param array<string, mixed> $postActivity 文章活動統計
     * @param array<string, mixed> $userActivity 使用者活動統計
     * @param array<string, mixed> $engagementMetrics 互動指標
     * @param array<string, mixed> $periodSummary 週期摘要
     * @param DateTimeImmutable|null $generatedAt 生成時間
     * @param array<string, mixed> $metadata 額外元資料
     */
    public function __construct(
        private readonly int $totalPosts,
        private readonly int $activeUsers,
        private readonly int $newUsers,
        private readonly int $totalViews = 0,
        private readonly array $postActivity = [],
        private readonly array $userActivity = [],
        private readonly array $engagementMetrics = [],
        private readonly array $periodSummary = [],
        private readonly ?DateTimeImmutable $generatedAt = null,
        private readonly array $metadata = [],
    ) {
        $this->validateData();
    }

    /**
     * 從陣列建立 DTO.
     *
     * @param array<string, mixed> $data 原始資料陣列
     * @throws InvalidArgumentException 當資料格式不正確時
     */
    public static function fromArray(array $data): self
    {
        $generatedAt = null;
        if (isset($data['generated_at']) && is_string($data['generated_at'])) {
            $generatedAt = new DateTimeImmutable($data['generated_at']);
        }

        return new self(
            totalPosts: isset($data['total_posts']) && is_numeric($data['total_posts']) ? (int) $data['total_posts'] : 0,
            activeUsers: isset($data['active_users']) && is_numeric($data['active_users']) ? (int) $data['active_users'] : 0,
            newUsers: isset($data['new_users']) && is_numeric($data['new_users']) ? (int) $data['new_users'] : 0,
            totalViews: isset($data['total_views']) && is_numeric($data['total_views']) ? (int) $data['total_views'] : 0,
            postActivity: self::ensureStringMixedArray($data['post_activity'] ?? []),
            userActivity: self::ensureStringMixedArray($data['user_activity'] ?? []),
            engagementMetrics: self::ensureStringMixedArray($data['engagement_metrics'] ?? []),
            periodSummary: self::ensureStringMixedArray($data['period_summary'] ?? []),
            generatedAt: $generatedAt,
            metadata: self::ensureStringMixedArray($data['metadata'] ?? []),
        );
    }

    /**
     * 確保回傳 array<string, mixed> 型別.
     *
     * @param mixed $data
     * @return array<string, mixed>
     */
    private static function ensureStringMixedArray($data): array
    {
        if (!is_array($data)) {
            return [];
        }

        $result = [];
        foreach ($data as $key => $value) {
            if (is_string($key)) {
                $result[$key] = $value;
            }
        }

        return $result;
    }

    /**
     * 建立帶驗證的 DTO.
     *
     * @param ValidatorInterface $validator 驗證器
     * @param array<string, mixed> $data 要驗證的資料
     * @throws ValidationException 當驗證失敗時
     */
    public static function createWithValidation(ValidatorInterface $validator, array $data): self
    {
        $rules = [
            'total_posts' => 'required|integer|min:0',
            'active_users' => 'required|integer|min:0',
            'new_users' => 'required|integer|min:0',
            'post_activity' => 'required|array',
            'user_activity' => 'required|array',
            'engagement_metrics' => 'required|array',
            'period_summary' => 'required|array',
            'generated_at' => 'sometimes|string|date',
            'metadata' => 'sometimes|array',
        ];

        $validator->validate($data, $rules);

        return self::fromArray($data);
    }

    // Getters
    public function getTotalPosts(): int
    {
        return $this->totalPosts;
    }

    public function getActiveUsers(): int
    {
        return $this->activeUsers;
    }

    public function getNewUsers(): int
    {
        return $this->newUsers;
    }

    /**
     * @return array<string, mixed>
     */
    public function getPostActivity(): array
    {
        return $this->postActivity;
    }

    /**
     * @return array<string, mixed>
     */
    public function getUserActivity(): array
    {
        return $this->userActivity;
    }

    /**
     * @return array<string, mixed>
     */
    public function getEngagementMetrics(): array
    {
        return $this->engagementMetrics;
    }

    /**
     * @return array<string, mixed>
     */
    public function getPeriodSummary(): array
    {
        return $this->periodSummary;
    }

    public function getGeneratedAt(): ?DateTimeImmutable
    {
        return $this->generatedAt;
    }

    /**
     * @return array<string, mixed>
     */
    public function getMetadata(): array
    {
        return $this->metadata;
    }

    // 計算方法
    public function getGrowthRate(): float
    {
        if ($this->activeUsers === 0) {
            return $this->newUsers > 0 ? 100.0 : 0.0;
        }

        return round(($this->newUsers / $this->activeUsers) * 100, 2);
    }

    public function getPostsPerUser(): float
    {
        if ($this->activeUsers === 0) {
            return 0.0;
        }

        return round($this->totalPosts / $this->activeUsers, 2);
    }

    public function getActivityLevel(): string
    {
        $activityScore = $this->calculateActivityScore();

        return match (true) {
            $activityScore >= 80 => 'high',
            $activityScore >= 50 => 'medium',
            $activityScore >= 20 => 'low',
            default => 'inactive',
        };
    }

    /**
     * 轉換為陣列.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = [
            'total_posts' => $this->totalPosts,
            'active_users' => $this->activeUsers,
            'new_users' => $this->newUsers,
            'total_views' => $this->totalViews,
            'post_activity' => $this->postActivity,
            'user_activity' => $this->userActivity,
            'engagement_metrics' => $this->engagementMetrics,
            'period_summary' => $this->periodSummary,
            'calculated_metrics' => [
                'growth_rate' => $this->getGrowthRate(),
                'posts_per_user' => $this->getPostsPerUser(),
                'activity_level' => $this->getActivityLevel(),
            ],
        ];

        if ($this->generatedAt !== null) {
            $data['generated_at'] = $this->generatedAt->format('Y-m-d\TH:i:s\Z');
        }

        if (!empty($this->metadata)) {
            $data['metadata'] = $this->metadata;
        }

        return $data;
    }

    /**
     * JSON 序列化.
     *
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * 檢查是否有有效資料.
     */
    public function hasData(): bool
    {
        return $this->totalPosts > 0 || $this->activeUsers > 0 || $this->newUsers > 0;
    }

    /**
     * 取得摘要資訊.
     *
     * @return array<string, mixed>
     */
    public function getSummary(): array
    {
        return [
            'total_posts' => $this->totalPosts,
            'active_users' => $this->activeUsers,
            'new_users' => $this->newUsers,
            'growth_rate' => $this->getGrowthRate(),
            'activity_level' => $this->getActivityLevel(),
        ];
    }

    /**
     * 驗證資料完整性.
     *
     * @throws InvalidArgumentException 當資料無效時
     */
    private function validateData(): void
    {
        if ($this->totalPosts < 0) {
            throw new InvalidArgumentException('文章總數不能為負數');
        }

        if ($this->activeUsers < 0) {
            throw new InvalidArgumentException('活躍使用者數不能為負數');
        }

        if ($this->newUsers < 0) {
            throw new InvalidArgumentException('新使用者數不能為負數');
        }

        // 驗證必要的陣列鍵
        $this->validateArrayStructure('post_activity', $this->postActivity, [
            'total_posts', 'published_posts', 'draft_posts',
        ]);

        $this->validateArrayStructure('user_activity', $this->userActivity, [
            'total_users', 'active_users', 'new_users',
        ]);

        $this->validateArrayStructure('engagement_metrics', $this->engagementMetrics, [
            'posts_per_active_user', 'user_growth_rate',
        ]);

        $this->validateArrayStructure('period_summary', $this->periodSummary, [
            'type', 'duration_days',
        ]);
    }

    /**
     * 驗證陣列結構.
     *
     * @param string $name 陣列名稱
     * @param array<string, mixed> $data 要驗證的陣列
     * @param array<string> $requiredKeys 必要的鍵
     * @throws InvalidArgumentException 當結構無效時
     */
    private function validateArrayStructure(string $name, array $data, array $requiredKeys): void
    {
        foreach ($requiredKeys as $key) {
            if (!array_key_exists($key, $data)) {
                throw new InvalidArgumentException("{$name} 缺少必要的鍵: {$key}");
            }
        }
    }

    /**
     * 計算活動分數.
     */
    private function calculateActivityScore(): float
    {
        $postScore = min(($this->totalPosts / 100) * 40, 40); // 最多40分
        $userScore = min(($this->activeUsers / 50) * 30, 30); // 最多30分
        $growthScore = min($this->getGrowthRate() / 10 * 30, 30); // 最多30分

        return round($postScore + $userScore + $growthScore, 2);
    }
}
