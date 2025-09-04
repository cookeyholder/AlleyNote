<?php

declare(strict_types=1);

namespace App\Application\Statistics\DTOs;

use App\Shared\Contracts\ValidatorInterface;
use App\Shared\DTOs\BaseDTO;

/**
 * 來源分佈統計資料傳輸物件.
 *
 * 封裝內容來源的分佈統計資訊
 */
class SourceDistributionDTO extends BaseDTO
{
    public function __construct(
        ValidatorInterface $validator,
        public readonly string $sourceId,
        public readonly string $sourceName,
        public readonly string $sourceType,
        public readonly int $postCount,
        public readonly int $totalViews,
        public readonly int $totalFavorites,
        public readonly float $averageEngagement,
        public readonly float $marketSharePercentage,
        public readonly bool $isActive,
        public readonly array $categoryDistribution = [],
        public readonly array $timeSeriesData = [],
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
            sourceId: self::safeString($validatedData['source_id']),
            sourceName: self::safeString($validatedData['source_name']),
            sourceType: self::safeString($validatedData['source_type']),
            postCount: self::safeInt($validatedData['post_count']),
            totalViews: self::safeInt($validatedData['total_views']),
            totalFavorites: self::safeInt($validatedData['total_favorites']),
            averageEngagement: self::safeFloat($validatedData['average_engagement']),
            marketSharePercentage: self::safeFloat($validatedData['market_share_percentage']),
            isActive: self::safeBool($validatedData['is_active']),
            categoryDistribution: self::safeArray($validatedData['category_distribution'] ?? []),
            timeSeriesData: self::safeArray($validatedData['time_series_data'] ?? []),
        );
    }

    /**
     * 將 DTO 轉換為陣列.
     */
    public function toArray(): array
    {
        return [
            'source_id' => $this->sourceId,
            'source_name' => $this->sourceName,
            'source_type' => $this->sourceType,
            'post_count' => $this->postCount,
            'total_views' => $this->totalViews,
            'total_favorites' => $this->totalFavorites,
            'average_engagement' => $this->averageEngagement,
            'market_share_percentage' => $this->marketSharePercentage,
            'is_active' => $this->isActive,
            'category_distribution' => $this->categoryDistribution,
            'time_series_data' => $this->timeSeriesData,
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
            'source_id' => 'required|string|max:36',
            'source_name' => 'required|string|max:100',
            'source_type' => 'required|string|in:rss,api,manual,scraper',
            'post_count' => 'required|integer|min:0',
            'total_views' => 'required|integer|min:0',
            'total_favorites' => 'required|integer|min:0',
            'average_engagement' => 'required|numeric|min:0|max:100',
            'market_share_percentage' => 'required|numeric|min:0|max:100',
            'is_active' => 'required|boolean',
            'category_distribution' => 'sometimes|array',
            'time_series_data' => 'sometimes|array',
        ];
    }

    /**
     * 計算平均每篇貼文的瀏覽數.
     */
    public function getAverageViewsPerPost(): float
    {
        if ($this->postCount === 0) {
            return 0.0;
        }

        return round($this->totalViews / $this->postCount, 2);
    }

    /**
     * 計算平均每篇貼文的收藏數.
     */
    public function getAverageFavoritesPerPost(): float
    {
        if ($this->postCount === 0) {
            return 0.0;
        }

        return round($this->totalFavorites / $this->postCount, 2);
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
     * 檢查是否為主要來源.
     */
    public function isMajorSource(): bool
    {
        return $this->marketSharePercentage >= 10.0;
    }

    /**
     * 檢查是否為高品質來源.
     */
    public function isHighQualitySource(): bool
    {
        return $this->averageEngagement >= 70.0 && $this->getFavoriteRate() >= 5.0;
    }

    /**
     * 取得最受歡迎的類別.
     */
    public function getTopCategory(): ?string
    {
        if (empty($this->categoryDistribution)) {
            return null;
        }

        /** @var array<string, int> $distribution */
        $distribution = $this->categoryDistribution;
        arsort($distribution);

        $topCategory = array_key_first($distribution);

        return is_string($topCategory) ? $topCategory : null;
    }

    /**
     * 取得類別多樣性分數.
     */
    public function getCategoryDiversityScore(): float
    {
        $categoryCount = count($this->categoryDistribution);

        if ($categoryCount <= 1) {
            return 0.0;
        }

        // 計算熵值作為多樣性指標
        $total = array_sum($this->categoryDistribution);
        if ($total === 0) {
            return 0.0;
        }

        $entropy = 0.0;
        foreach ($this->categoryDistribution as $count) {
            if (is_numeric($count) && $count > 0) {
                $probability = $count / $total;
                $entropy -= $probability * log($probability, 2);
            }
        }

        // 正規化到 0-100 範圍
        $maxEntropy = log($categoryCount, 2);

        return $maxEntropy > 0 ? round(($entropy / $maxEntropy) * 100, 2) : 0.0;
    }

    /**
     * 取得來源狀態描述.
     */
    public function getStatusDescription(): string
    {
        if (!$this->isActive) {
            return '未啟用';
        }

        if ($this->isMajorSource()) {
            return '主要來源';
        }

        if ($this->isHighQualitySource()) {
            return '高品質來源';
        }

        return '一般來源';
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
     * 安全轉換為布林值.
     */
    private static function safeBool(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }
        if (is_numeric($value)) {
            return $value > 0;
        }

        return in_array($value, [1, '1', 'true', 'on', 'yes'], true);
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
