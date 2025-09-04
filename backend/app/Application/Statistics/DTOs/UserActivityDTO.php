<?php

declare(strict_types=1);

namespace App\Application\Statistics\DTOs;

use App\Shared\Contracts\ValidatorInterface;
use App\Shared\DTOs\BaseDTO;
use DateTimeImmutable;

/**
 * 使用者活動統計資料傳輸物件.
 *
 * 封裝使用者活動相關的統計資訊
 */
class UserActivityDTO extends BaseDTO
{
    public function __construct(
        ValidatorInterface $validator,
        public readonly string $userId,
        public readonly string $username,
        public readonly int $totalViews,
        public readonly int $totalFavorites,
        public readonly int $totalShares,
        public readonly int $sessionsCount,
        public readonly float $averageSessionDuration,
        public readonly DateTimeImmutable $lastActiveAt,
        public readonly DateTimeImmutable $registeredAt,
        public readonly array $activityByHour = [],
        public readonly array $favoriteCategories = [],
        public readonly array $deviceTypes = [],
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
            userId: self::safeString($validatedData['user_id']),
            username: self::safeString($validatedData['username']),
            totalViews: self::safeInt($validatedData['total_views']),
            totalFavorites: self::safeInt($validatedData['total_favorites']),
            totalShares: self::safeInt($validatedData['total_shares']),
            sessionsCount: self::safeInt($validatedData['sessions_count']),
            averageSessionDuration: self::safeFloat($validatedData['average_session_duration']),
            lastActiveAt: new DateTimeImmutable(self::safeString($validatedData['last_active_at'])),
            registeredAt: new DateTimeImmutable(self::safeString($validatedData['registered_at'])),
            activityByHour: self::safeArray($validatedData['activity_by_hour'] ?? []),
            favoriteCategories: self::safeArray($validatedData['favorite_categories'] ?? []),
            deviceTypes: self::safeArray($validatedData['device_types'] ?? []),
        );
    }

    /**
     * 將 DTO 轉換為陣列.
     */
    public function toArray(): array
    {
        return [
            'user_id' => $this->userId,
            'username' => $this->username,
            'total_views' => $this->totalViews,
            'total_favorites' => $this->totalFavorites,
            'total_shares' => $this->totalShares,
            'sessions_count' => $this->sessionsCount,
            'average_session_duration' => $this->averageSessionDuration,
            'last_active_at' => $this->lastActiveAt->format('Y-m-d H:i:s'),
            'registered_at' => $this->registeredAt->format('Y-m-d H:i:s'),
            'activity_by_hour' => $this->activityByHour,
            'favorite_categories' => $this->favoriteCategories,
            'device_types' => $this->deviceTypes,
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
            'user_id' => 'required|string|max:36',
            'username' => 'required|string|max:50',
            'total_views' => 'required|integer|min:0',
            'total_favorites' => 'required|integer|min:0',
            'total_shares' => 'required|integer|min:0',
            'sessions_count' => 'required|integer|min:0',
            'average_session_duration' => 'required|numeric|min:0',
            'last_active_at' => 'required|string',
            'registered_at' => 'required|string',
            'activity_by_hour' => 'sometimes|array',
            'favorite_categories' => 'sometimes|array',
            'device_types' => 'sometimes|array',
        ];
    }

    /**
     * 計算總互動數.
     */
    public function getTotalInteractions(): int
    {
        return $this->totalViews + $this->totalFavorites + $this->totalShares;
    }

    /**
     * 計算平均每次會話的互動數.
     */
    public function getAverageInteractionsPerSession(): float
    {
        if ($this->sessionsCount === 0) {
            return 0.0;
        }

        return round($this->getTotalInteractions() / $this->sessionsCount, 2);
    }

    /**
     * 計算收藏率.
     */
    public function getFavoriteRate(): float
    {
        if ($this->totalViews === 0) {
            return 0.0;
        }

        return round(($this->totalFavorites / $this->totalViews) * 100, 2);
    }

    /**
     * 計算分享率.
     */
    public function getShareRate(): float
    {
        if ($this->totalViews === 0) {
            return 0.0;
        }

        return round(($this->totalShares / $this->totalViews) * 100, 2);
    }

    /**
     * 取得使用者年齡（天數）.
     */
    public function getAccountAgeInDays(): int
    {
        $diff = new DateTimeImmutable()->diff($this->registeredAt);

        return $diff->days !== false ? $diff->days : 0;
    }

    /**
     * 取得最後活動天數.
     */
    public function getDaysSinceLastActive(): int
    {
        $diff = new DateTimeImmutable()->diff($this->lastActiveAt);

        return $diff->days !== false ? $diff->days : 0;
    }

    /**
     * 檢查是否為活躍使用者.
     */
    public function isActiveUser(): bool
    {
        return $this->getDaysSinceLastActive() <= 7;
    }

    /**
     * 檢查是否為超級使用者.
     */
    public function isPowerUser(): bool
    {
        return $this->getTotalInteractions() >= 1000
            && $this->getFavoriteRate() >= 10.0
            && $this->getShareRate() >= 5.0;
    }

    /**
     * 檢查是否為新使用者.
     */
    public function isNewUser(): bool
    {
        return $this->getAccountAgeInDays() <= 30;
    }

    /**
     * 取得最活躍的時段.
     */
    public function getMostActiveHour(): ?int
    {
        if (empty($this->activityByHour)) {
            return null;
        }

        /** @var array<string|int, int> $activity */
        $activity = $this->activityByHour;
        arsort($activity);

        $mostActiveHour = array_key_first($activity);

        return is_numeric($mostActiveHour) ? (int) $mostActiveHour : null;
    }

    /**
     * 取得最喜愛的類別.
     */
    public function getTopFavoriteCategory(): ?string
    {
        if (empty($this->favoriteCategories)) {
            return null;
        }

        /** @var array<string, int> $categories */
        $categories = $this->favoriteCategories;
        arsort($categories);

        $topCategory = array_key_first($categories);

        return is_string($topCategory) ? $topCategory : null;
    }

    /**
     * 取得主要使用裝置類型.
     */
    public function getPrimaryDeviceType(): ?string
    {
        if (empty($this->deviceTypes)) {
            return null;
        }

        /** @var array<string, int> $devices */
        $devices = $this->deviceTypes;
        arsort($devices);

        $primaryDevice = array_key_first($devices);

        return is_string($primaryDevice) ? $primaryDevice : null;
    }

    /**
     * 計算使用者活躍度分數.
     */
    public function getActivityScore(): float
    {
        $recencyScore = max(0, 100 - $this->getDaysSinceLastActive() * 5);
        $interactionScore = min(100, $this->getTotalInteractions() / 10);
        $engagementScore = ($this->getFavoriteRate() + $this->getShareRate()) / 2;
        $sessionScore = min(100, $this->sessionsCount);

        return round(($recencyScore + $interactionScore + $engagementScore + $sessionScore) / 4, 2);
    }

    /**
     * 取得使用者等級描述.
     */
    public function getUserLevelDescription(): string
    {
        if ($this->isPowerUser()) {
            return '超級使用者';
        }

        if ($this->isActiveUser() && $this->getTotalInteractions() >= 100) {
            return '活躍使用者';
        }

        if ($this->isNewUser()) {
            return '新手使用者';
        }

        if (!$this->isActiveUser()) {
            return '非活躍使用者';
        }

        return '一般使用者';
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
