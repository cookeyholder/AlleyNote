<?php

declare(strict_types=1);

namespace App\Application\Statistics\DTOs;

use App\Shared\Contracts\ValidatorInterface;
use App\Shared\DTOs\BaseDTO;
use DateTimeImmutable;

/**
 * 貼文統計資料傳輸物件.
 *
 * 封裝貼文相關的統計資訊
 */
class PostStatisticsDTO extends BaseDTO
{
    public function __construct(
        ValidatorInterface $validator,
        public readonly string $postId,
        public readonly string $title,
        public readonly string $sourceName,
        public readonly int $viewCount,
        public readonly int $favoriteCount,
        public readonly int $shareCount,
        public readonly float $engagementScore,
        public readonly DateTimeImmutable $publishedAt,
        public readonly DateTimeImmutable $createdAt,
        public readonly array $metadata = [],
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
            postId: self::safeString($validatedData['post_id']),
            title: self::safeString($validatedData['title']),
            sourceName: self::safeString($validatedData['source_name']),
            viewCount: self::safeInt($validatedData['view_count']),
            favoriteCount: self::safeInt($validatedData['favorite_count']),
            shareCount: self::safeInt($validatedData['share_count']),
            engagementScore: self::safeFloat($validatedData['engagement_score']),
            publishedAt: new DateTimeImmutable(self::safeString($validatedData['published_at'])),
            createdAt: new DateTimeImmutable(self::safeString($validatedData['created_at'])),
            metadata: self::safeArray($validatedData['metadata'] ?? []),
        );
    }

    /**
     * 將 DTO 轉換為陣列.
     */
    public function toArray(): array
    {
        return [
            'post_id' => $this->postId,
            'title' => $this->title,
            'source_name' => $this->sourceName,
            'view_count' => $this->viewCount,
            'favorite_count' => $this->favoriteCount,
            'share_count' => $this->shareCount,
            'engagement_score' => $this->engagementScore,
            'published_at' => $this->publishedAt->format('Y-m-d H:i:s'),
            'created_at' => $this->createdAt->format('Y-m-d H:i:s'),
            'metadata' => $this->metadata,
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
            'post_id' => 'required|string|max:36',
            'title' => 'required|string|max:255',
            'source_name' => 'required|string|max:100',
            'view_count' => 'required|integer|min:0',
            'favorite_count' => 'required|integer|min:0',
            'share_count' => 'required|integer|min:0',
            'engagement_score' => 'required|numeric|min:0|max:100',
            'published_at' => 'required|string',
            'created_at' => 'required|string',
            'metadata' => 'sometimes|array',
        ];
    }

    /**
     * 計算總互動數.
     */
    public function getTotalEngagement(): int
    {
        return $this->viewCount + $this->favoriteCount + $this->shareCount;
    }

    /**
     * 計算互動率.
     */
    public function getEngagementRate(): float
    {
        if ($this->viewCount === 0) {
            return 0.0;
        }

        $totalInteractions = $this->favoriteCount + $this->shareCount;

        return round(($totalInteractions / $this->viewCount) * 100, 2);
    }

    /**
     * 檢查是否為熱門貼文.
     */
    public function isPopular(): bool
    {
        return $this->engagementScore >= 80.0 || $this->getTotalEngagement() >= 1000;
    }

    /**
     * 檢查是否為近期貼文.
     */
    public function isRecent(): bool
    {
        $daysSincePublished = new DateTimeImmutable()->diff($this->publishedAt)->days;

        return $daysSincePublished <= 7;
    }

    /**
     * 取得貼文年齡（天數）.
     */
    public function getAgeInDays(): int
    {
        $diff = new DateTimeImmutable()->diff($this->publishedAt);

        return $diff->days !== false ? $diff->days : 0;
    }

    /**
     * 取得標題摘要.
     */
    public function getTitleSummary(int $maxLength = 50): string
    {
        if (mb_strlen($this->title) <= $maxLength) {
            return $this->title;
        }

        return mb_substr($this->title, 0, $maxLength - 3) . '...';
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
