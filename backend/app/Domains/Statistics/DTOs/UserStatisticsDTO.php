<?php

declare(strict_types=1);

namespace App\Domains\Statistics\DTOs;

use DateTimeImmutable;
use InvalidArgumentException;
use JsonSerializable;

/**
 * 使用者統計 D    public function get    public function getHighEngage    public function getEngagementScore(): float.
    {
        $engagementScore = $this->engagementStats['engagement_score'] ?? 0.0;
        return is_numeric($engagementScore) ? (float) $engagementScore : 0.0;
    }(): int
    {
        $highEngagement =    private function getActivityConcentration(): string
    {
        $totalActivity = array_sum($this->activityTimeDistribution);
        if ($totalActivity === 0 || empty($this->activityTimeDistribution)) {
            return 'inactive';
        }

        $peakCount = max($this->activityTimeDistribution);
        $concentration = ($peakCount / $totalActivity) * 100;

        return match (true) {
            $concentration >= 50 => 'concentrated',
            $concentration >= 30 => 'moderate',
            default => 'distributed',
        };
    }ntStats['high_engagement'] ?? 0;
        return is_numeric($highEngagement) ? (int) $highEngagement : 0;
    }u    public function getMediumEngagement(): int
    {
        $mediumEngagement = $this->engagementStats['medium_engagement'] ?? 0;
        return is_numeric($mediumEngagement) ? (int) $mediumEngagement : 0;
    }r    public function getLowEngagement(): int
    {
        $lowEngagement = $this->engagementStats['low_engagement'] ?? 0;
        return is_numeric($lowEngagement) ? (int) $lowEngagement : 0;
    } int
    {
        $uniqueUsers = $this->loginActivity['unique_users'] ?? 0;
        return is_numeric($uniqueUsers) ? (int) $uniqueUsers : 0;
    } *
 *     public function getPeakLoginHour(): int
    {
        $peakHour = $this->loginActivity['peak_hour'] ?? 0;
        return is_numeric($peakHour) ? (int) $peakHour : 0;
    }者相關統計資料的傳輸物件，包含活躍度分析、登入統計、參與度等。
 * 專門用於使用者統計 API 的回應格式與內部資料傳遞。
 */
class UserStatisticsDTO implements JsonSerializable
{
    /**
     * @param array<string, int> $byActivityType 按活動類型分組的統計
     * @param array<string, mixed> $loginActivity 登入活動統計
     * @param array<int, array<string, mixed>> $mostActive 最活躍使用者清單
     * @param array<string, mixed> $engagementStats 參與度統計
     * @param array<string, int> $registrationSources 註冊來源分析
     * @param array<int, array<string, mixed>> $geographicalDistribution 地理分布
     * @param array<string, int> $byRole 按角色分組的統計
     * @param array<string, int> $activityTimeDistribution 活動時間分布
     * @param DateTimeImmutable|null $generatedAt 生成時間
     * @param array<string, mixed> $metadata 額外元資料
     */
    public function __construct(
        private readonly int $activeUsers,
        private readonly array $byActivityType,
        private readonly array $loginActivity,
        private readonly array $mostActive,
        private readonly array $engagementStats,
        private readonly array $registrationSources,
        private readonly array $geographicalDistribution,
        private readonly array $byRole,
        private readonly array $activityTimeDistribution,
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
            activeUsers: isset($data['active_users']) && is_numeric($data['active_users']) ? (int) $data['active_users'] : 0,
            byActivityType: self::ensureStringIntArray($data['by_activity_type'] ?? []),
            loginActivity: self::ensureStringMixedArray($data['login_activity'] ?? []),
            mostActive: self::ensureIntArrayStringMixedArray($data['most_active'] ?? []),
            engagementStats: self::ensureStringMixedArray($data['engagement_stats'] ?? []),
            registrationSources: self::ensureStringIntArray($data['registration_sources'] ?? []),
            geographicalDistribution: self::ensureIntArrayStringMixedArray($data['geographical_distribution'] ?? []),
            byRole: self::ensureStringIntArray($data['by_role'] ?? []),
            activityTimeDistribution: self::ensureStringIntArray($data['activity_time_distribution'] ?? []),
            generatedAt: $generatedAt,
            metadata: self::ensureStringMixedArray($data['metadata'] ?? []),
        );
    }

    // Getters
    public function getActiveUsers(): int
    {
        return $this->activeUsers;
    }

    /**
     * @return array<string, int>
     */
    public function getByActivityType(): array
    {
        return $this->byActivityType;
    }

    /**
     * @return array<string, mixed>
     */
    public function getLoginActivity(): array
    {
        return $this->loginActivity;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getMostActive(): array
    {
        return $this->mostActive;
    }

    /**
     * @return array<string, mixed>
     */
    public function getEngagementStats(): array
    {
        return $this->engagementStats;
    }

    /**
     * @return array<string, int>
     */
    public function getRegistrationSources(): array
    {
        return $this->registrationSources;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getGeographicalDistribution(): array
    {
        return $this->geographicalDistribution;
    }

    /**
     * @return array<string, int>
     */
    public function getByRole(): array
    {
        return $this->byRole;
    }

    /**
     * @return array<string, int>
     */
    public function getActivityTimeDistribution(): array
    {
        return $this->activityTimeDistribution;
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
    public function getTotalLogins(): int
    {
        $totalLogins = $this->loginActivity['total_logins'] ?? 0;

        return is_numeric($totalLogins) ? (int) $totalLogins : 0;
    }

    public function getUniqueLoggedInUsers(): int
    {
        $uniqueUsers = $this->loginActivity['unique_users'] ?? 0;

        return is_numeric($uniqueUsers) ? (int) $uniqueUsers : 0;
    }

    public function getAverageLoginsPerUser(): float
    {
        $avgLogins = $this->loginActivity['avg_logins_per_user'] ?? 0.0;

        return is_numeric($avgLogins) ? (float) $avgLogins : 0.0;
    }

    public function getPeakHour(): int
    {
        $peakHour = $this->loginActivity['peak_hour'] ?? 0;

        return is_numeric($peakHour) ? (int) $peakHour : 0;
    }

    public function getHighEngagementUsers(): int
    {
        $highEngagement = $this->engagementStats['high_engagement'] ?? 0;

        return is_numeric($highEngagement) ? (int) $highEngagement : 0;
    }

    public function getMediumEngagementUsers(): int
    {
        $mediumEngagement = $this->engagementStats['medium_engagement'] ?? 0;

        return is_numeric($mediumEngagement) ? (int) $mediumEngagement : 0;
    }

    public function getLowEngagementUsers(): int
    {
        $lowEngagement = $this->engagementStats['low_engagement'] ?? 0;

        return is_numeric($lowEngagement) ? (int) $lowEngagement : 0;
    }

    public function getInactiveUsers(): int
    {
        $inactiveUsers = $this->engagementStats['inactive'] ?? 0;

        return is_numeric($inactiveUsers) ? (int) $inactiveUsers : 0;
    }

    public function getAverageEngagementScore(): float
    {
        $avgScore = $this->engagementStats['avg_engagement_score'] ?? 0.0;

        return is_numeric($avgScore) ? (float) $avgScore : 0.0;
    }

    public function getEngagementRate(): float
    {
        if ($this->activeUsers === 0) {
            return 0.0;
        }

        $engagedUsers = $this->getHighEngagementUsers() + $this->getMediumEngagementUsers();

        return round(($engagedUsers / $this->activeUsers) * 100, 2);
    }

    public function getMostActiveUser(): ?array
    {
        return $this->mostActive[0] ?? null;
    }

    public function getTopRegistrationSource(): ?string
    {
        if (empty($this->registrationSources)) {
            return null;
        }

        $maxCount = max($this->registrationSources);
        $topSources = array_keys($this->registrationSources, $maxCount);

        return $topSources[0] ?? null;
    }

    public function getTopLocation(): ?string
    {
        if (empty($this->geographicalDistribution)) {
            return null;
        }

        $topLocation = $this->geographicalDistribution[0] ?? null;
        if (!is_array($topLocation) || !isset($topLocation['location'])) {
            return null;
        }

        return is_string($topLocation['location']) ? $topLocation['location'] : null;
    }

    /**
     * 取得使用者參與度分析.
     *
     * @return array<string, mixed>
     */
    public function getEngagementAnalysis(): array
    {
        $totalUsers = $this->getHighEngagementUsers()
                     + $this->getMediumEngagementUsers()
                     + $this->getLowEngagementUsers()
                     + $this->getInactiveUsers();

        return [
            'total_users' => $totalUsers,
            'engagement_rate' => $this->getEngagementRate(),
            'average_engagement_score' => $this->getAverageEngagementScore(),
            'engagement_distribution' => [
                'high' => ['count' => $this->getHighEngagementUsers(), 'percentage' => $totalUsers > 0 ? round(($this->getHighEngagementUsers() / $totalUsers) * 100, 1) : 0],
                'medium' => ['count' => $this->getMediumEngagementUsers(), 'percentage' => $totalUsers > 0 ? round(($this->getMediumEngagementUsers() / $totalUsers) * 100, 1) : 0],
                'low' => ['count' => $this->getLowEngagementUsers(), 'percentage' => $totalUsers > 0 ? round(($this->getLowEngagementUsers() / $totalUsers) * 100, 1) : 0],
                'inactive' => ['count' => $this->getInactiveUsers(), 'percentage' => $totalUsers > 0 ? round(($this->getInactiveUsers() / $totalUsers) * 100, 1) : 0],
            ],
        ];
    }

    /**
     * 取得活動時間洞察.
     *
     * @return array<string, mixed>
     */
    public function getActivityInsights(): array
    {
        $peakHour = $this->getPeakActiveHour();

        return [
            'peak_login_hour' => $this->getPeakHour(),
            'peak_activity_hour' => $peakHour,
            'activity_pattern' => $this->getActivityPattern(),
            'weekend_vs_weekday' => $this->getWeekendVsWeekdayActivity(),
        ];
    }

    /**
     * 轉換為陣列.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = [
            'active_users' => $this->activeUsers,
            'by_activity_type' => $this->byActivityType,
            'login_activity' => $this->loginActivity,
            'most_active' => $this->mostActive,
            'engagement_stats' => $this->engagementStats,
            'registration_sources' => $this->registrationSources,
            'geographical_distribution' => $this->geographicalDistribution,
            'by_role' => $this->byRole,
            'activity_time_distribution' => $this->activityTimeDistribution,
            'calculated_metrics' => [
                'total_logins' => $this->getTotalLogins(),
                'unique_logged_in_users' => $this->getUniqueLoggedInUsers(),
                'average_logins_per_user' => $this->getAverageLoginsPerUser(),
                'engagement_rate' => $this->getEngagementRate(),
                'most_active_user' => $this->getMostActiveUser(),
                'top_registration_source' => $this->getTopRegistrationSource(),
                'top_location' => $this->getTopLocation(),
            ],
            'engagement_analysis' => $this->getEngagementAnalysis(),
            'activity_insights' => $this->getActivityInsights(),
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
        return $this->activeUsers > 0 || !empty($this->byActivityType) || !empty($this->loginActivity);
    }

    /**
     * 取得摘要資訊.
     *
     * @return array<string, mixed>
     */
    public function getSummary(): array
    {
        return [
            'active_users' => $this->activeUsers,
            'total_logins' => $this->getTotalLogins(),
            'engagement_rate' => $this->getEngagementRate(),
            'average_engagement_score' => $this->getAverageEngagementScore(),
            'top_location' => $this->getTopLocation(),
        ];
    }

    /**
     * 驗證資料完整性.
     *
     * @throws InvalidArgumentException 當資料無效時
     */
    private function validateData(): void
    {
        if ($this->activeUsers < 0) {
            throw new InvalidArgumentException('活躍使用者數不能為負數');
        }

        // 驗證活動類型統計
        foreach ($this->byActivityType as $type => $count) {
            if (!is_string($type) || !is_int($count) || $count < 0) {
                throw new InvalidArgumentException('活動類型統計資料格式不正確');
            }
        }

        // 驗證登入活動統計
        if (!empty($this->loginActivity)) {
            $requiredKeys = ['total_logins', 'unique_users', 'avg_logins_per_user'];
            foreach ($requiredKeys as $key) {
                if (!array_key_exists($key, $this->loginActivity)) {
                    throw new InvalidArgumentException("登入活動統計缺少必要的鍵: {$key}");
                }
            }
        }

        // 驗證最活躍使用者
        foreach ($this->mostActive as $user) {
            if (!is_array($user) || !isset($user['user_id'], $user['username'], $user['metric_value'])) {
                throw new InvalidArgumentException('最活躍使用者資料結構不正確');
            }
        }

        // 驗證參與度統計
        if (!empty($this->engagementStats)) {
            $requiredKeys = ['high_engagement', 'medium_engagement', 'low_engagement', 'inactive'];
            foreach ($requiredKeys as $key) {
                if (!array_key_exists($key, $this->engagementStats)) {
                    throw new InvalidArgumentException("參與度統計缺少必要的鍵: {$key}");
                }
            }
        }

        // 驗證註冊來源
        foreach ($this->registrationSources as $source => $count) {
            if (!is_string($source) || !is_int($count) || $count < 0) {
                throw new InvalidArgumentException('註冊來源統計資料格式不正確');
            }
        }

        // 驗證地理分布
        foreach ($this->geographicalDistribution as $location) {
            if (!is_array($location) || !isset($location['location'], $location['users_count'])) {
                throw new InvalidArgumentException('地理分布資料結構不正確');
            }
        }

        // 驗證角色分布
        foreach ($this->byRole as $role => $count) {
            if (!is_string($role) || !is_int($count) || $count < 0) {
                throw new InvalidArgumentException('角色統計資料格式不正確');
            }
        }
    }

    /**
     * 取得最活躍的時間.
     */
    private function getPeakActiveHour(): ?string
    {
        if (empty($this->activityTimeDistribution)) {
            return null;
        }

        /** @var int $maxCount */
        $maxCount = max($this->activityTimeDistribution);
        $peakHours = array_keys($this->activityTimeDistribution, $maxCount);

        return $peakHours[0] ?? null;
    }

    /**
     * 取得活動模式.
     */
    private function getActivityPattern(): string
    {
        $totalActivity = array_sum($this->activityTimeDistribution);
        if ($totalActivity === 0 || empty($this->activityTimeDistribution)) {
            return 'inactive';
        }

        $peakCount = max($this->activityTimeDistribution);
        $concentration = ($peakCount / $totalActivity) * 100;

        return match (true) {
            $concentration >= 50 => 'concentrated',
            $concentration >= 30 => 'moderate',
            default => 'distributed',
        };
    }

    /**
     * 取得週末與工作日活動比較.
     *
     * @return array<string, mixed>
     */
    private function getWeekendVsWeekdayActivity(): array
    {
        // 這是一個簡化的實現，實際上需要根據具體的時間分布資料來計算
        // 這裡假設有週末和工作日的資料
        return [
            'weekend_percentage' => 30,
            'weekday_percentage' => 70,
            'weekend_activity_score' => 0.3,
            'weekday_activity_score' => 0.7,
        ];
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
     * 確保回傳 array<string, int> 型別.
     *
     * @param mixed $data
     * @return array<string, int>
     */
    private static function ensureStringIntArray($data): array
    {
        if (!is_array($data)) {
            return [];
        }

        $result = [];
        foreach ($data as $key => $value) {
            if (is_string($key) && is_numeric($value)) {
                $result[$key] = (int) $value;
            }
        }

        return $result;
    }

    /**
     * 確保回傳 array<int, array<string, mixed>> 型別.
     *
     * @param mixed $data
     * @return array<int, array<string, mixed>>
     */
    private static function ensureIntArrayStringMixedArray($data): array
    {
        if (!is_array($data)) {
            return [];
        }

        $result = [];
        foreach ($data as $item) {
            if (is_array($item)) {
                $filteredItem = [];
                foreach ($item as $key => $value) {
                    if (is_string($key)) {
                        $filteredItem[$key] = $value;
                    }
                }
                $result[] = $filteredItem;
            }
        }

        return $result;
    }
}
