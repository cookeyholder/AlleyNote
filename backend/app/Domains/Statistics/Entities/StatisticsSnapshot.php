<?php

declare(strict_types=1);

namespace App\Domains\Statistics\Entities;

use App\Domains\Statistics\ValueObjects\StatisticsPeriod;
use App\Shared\Contracts\OutputSanitizerInterface;
use DateTime;
use DateTimeInterface;
use Exception;
use InvalidArgumentException;
use JsonException;
use JsonSerializable;

/**
 * StatisticsSnapshot 統計快照實體.
 *
 * 代表某個時間週期內的統計資料快照，是統計領域的核心聚合根。
 * 負責管理統計資料的生命週期、驗證資料完整性，以及提供查詢介面。
 */
class StatisticsSnapshot implements JsonSerializable
{
    private int $id;

    private string $uuid;

    private string $snapshotType;

    private StatisticsPeriod $period;

    private array $statisticsData;

    private array $metadata;

    private ?DateTimeInterface $expiresAt;

    private DateTimeInterface $createdAt;

    private DateTimeInterface $updatedAt;

    /**
     * 支援的快照類型常數.
     */
    public const TYPE_OVERVIEW = 'overview';

    public const TYPE_POSTS = 'posts';

    public const TYPE_SOURCES = 'sources';

    public const TYPE_USERS = 'users';

    public const TYPE_POPULAR = 'popular';

    /**
     * 所有支援的快照類型.
     */
    private const SUPPORTED_TYPES = [
        self::TYPE_OVERVIEW,
        self::TYPE_POSTS,
        self::TYPE_SOURCES,
        self::TYPE_USERS,
        self::TYPE_POPULAR,
    ];

    /**
     * @param array<string, mixed> $data 建構資料陣列
     * @throws InvalidArgumentException 當資料格式不正確時
     */
    public function __construct(array $data)
    {
        $this->validateConstructorData($data);

        /** @phpstan-ignore-next-line cast.int */
        $this->id = (int) ($data['id'] ?? 0);
        /** @phpstan-ignore-next-line cast.string */
        $this->uuid = (string) ($data['uuid'] ?? $this->generateUuid());
        /** @phpstan-ignore-next-line cast.string */
        $this->snapshotType = (string) ($data['snapshot_type'] ?? '');
        $this->period = $this->buildPeriodFromData($data);
        /** @phpstan-ignore-next-line cast.string */
        $this->statisticsData = $this->parseJsonData((string) ($data['statistics_data'] ?? '{}'));
        /** @phpstan-ignore-next-line cast.string */
        $this->metadata = $this->parseJsonData((string) ($data['metadata'] ?? '{}'));
        $this->expiresAt = $this->parseDateTime($data['expires_at'] ?? null);
        $this->createdAt = $this->parseDateTime($data['created_at'] ?? null) ?? new DateTime();
        $this->updatedAt = $this->parseDateTime($data['updated_at'] ?? null) ?? new DateTime();
    }

    // Getters
    public function getId(): int
    {
        return $this->id;
    }

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function getSnapshotType(): string
    {
        return $this->snapshotType;
    }

    public function getPeriod(): StatisticsPeriod
    {
        return $this->period;
    }

    /**
     * @return array<string, mixed>
     */
    public function getStatisticsData(): array
    {
        /** @var array<string, mixed> */
        return $this->statisticsData;
    }

    /**
     * @return array<string, mixed>
     */
    public function getMetadata(): array
    {
        /** @var array<string, mixed> */
        return $this->metadata;
    }

    public function getExpiresAt(): ?DateTimeInterface
    {
        return $this->expiresAt;
    }

    public function getCreatedAt(): DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): DateTimeInterface
    {
        return $this->updatedAt;
    }

    // 領域方法

    /**
     * 檢查快照是否已過期
     */
    public function isExpired(): bool
    {
        if ($this->expiresAt === null) {
            return false;
        }

        return new DateTime() > $this->expiresAt;
    }

    /**
     * 檢查快照是否為指定類型.
     */
    public function isType(string $type): bool
    {
        return $this->snapshotType === $type;
    }

    /**
     * 取得特定統計指標的值
     *
     * @param string $key 指標鍵名
     * @param mixed $default 預設值
     */
    public function getStatistic(string $key, mixed $default = null): mixed
    {
        return $this->statisticsData[$key] ?? $default;
    }

    /**
     * 檢查是否包含特定統計指標.
     */
    public function hasStatistic(string $key): bool
    {
        return array_key_exists($key, $this->statisticsData);
    }

    /**
     * 取得統計資料總數.
     */
    public function getTotalCount(): int
    {
        $totalCount = $this->getStatistic('total_count', 0);

        /** @phpstan-ignore-next-line cast.int */
        return is_int($totalCount) ? $totalCount : (int) $totalCount;
    }

    /**
     * 取得與上一週期比較的成長率.
     */
    public function getGrowthRate(): ?float
    {
        $trends = $this->getStatistic('trends', []);
        if (!is_array($trends) || !array_key_exists('growth_rate', $trends)) {
            return null;
        }

        $growthRate = $trends['growth_rate'];
        if ($growthRate === null) {
            return null;
        }

        /** @phpstan-ignore-next-line cast.double */
        return is_float($growthRate) ? $growthRate : (float) $growthRate;
    }

    /**
     * 檢查資料完整性.
     */
    public function validateDataIntegrity(): bool
    {
        // 基本欄位檢查
        if (empty($this->statisticsData)) {
            return false;
        }

        // 根據類型進行特定檢查
        return match ($this->snapshotType) {
            self::TYPE_OVERVIEW => $this->validateOverviewData(),
            self::TYPE_POSTS => $this->validatePostsData(),
            self::TYPE_SOURCES => $this->validateSourcesData(),
            self::TYPE_USERS => $this->validateUsersData(),
            self::TYPE_POPULAR => $this->validatePopularData(),
            default => true,
        };
    }

    /**
     * 更新統計資料.
     *
     * @param array<string, mixed> $newData
     */
    public function updateStatistics(array $newData): void
    {
        $this->statisticsData = array_merge($this->statisticsData, $newData);
        $this->updatedAt = new DateTime();
    }

    /**
     * 更新元資料.
     *
     * @param array<string, mixed> $newMetadata
     */
    public function updateMetadata(array $newMetadata): void
    {
        $this->metadata = array_merge($this->metadata, $newMetadata);
        $this->updatedAt = new DateTime();
    }

    /**
     * 設定快照過期時間.
     */
    public function setExpiresAt(?DateTimeInterface $expiresAt): void
    {
        if ($expiresAt !== null && $expiresAt <= $this->createdAt) {
            throw new InvalidArgumentException('過期時間必須晚於建立時間');
        }

        $this->expiresAt = $expiresAt;
        $this->updatedAt = new DateTime();
    }

    /**
     * 建立新的統計快照.
     *
     * @param string $snapshotType 快照類型
     * @param StatisticsPeriod $period 統計週期
     * @param array<string, mixed> $statisticsData 統計資料
     * @param array<string, mixed> $metadata 元資料
     * @param DateTimeInterface|null $expiresAt 過期時間
     */
    public static function create(
        string $snapshotType,
        StatisticsPeriod $period,
        array $statisticsData,
        array $metadata = [],
        ?DateTimeInterface $expiresAt = null,
    ): self {
        return new self([
            'uuid' => self::generateUuid(),
            'snapshot_type' => $snapshotType,
            'period_type' => $period->type->value,
            'period_start' => $period->startTime->format('Y-m-d H:i:s'),
            'period_end' => $period->endTime->format('Y-m-d H:i:s'),
            'statistics_data' => json_encode($statisticsData, JSON_THROW_ON_ERROR),
            'metadata' => json_encode($metadata, JSON_THROW_ON_ERROR),
            'expires_at' => $expiresAt?->format('Y-m-d H:i:s'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'snapshot_type' => $this->snapshotType,
            'period_type' => $this->period->type->value,
            'period_start' => $this->period->startTime->format('Y-m-d H:i:s'),
            'period_end' => $this->period->endTime->format('Y-m-d H:i:s'),
            'statistics_data' => json_encode($this->statisticsData, JSON_THROW_ON_ERROR),
            'metadata' => json_encode($this->metadata, JSON_THROW_ON_ERROR),
            'expires_at' => $this->expiresAt?->format('Y-m-d H:i:s'),
            'created_at' => $this->createdAt->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * 取得清理過的資料陣列，適用於前端顯示.
     *
     * @param OutputSanitizerInterface $sanitizer 清理服務
     * @return array<string, mixed>
     */
    public function toSafeArray(OutputSanitizerInterface $sanitizer): array
    {
        $data = $this->toArray();

        // 統計資料通常不需要 HTML 清理，但保留介面一致性
        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): mixed
    {
        return $this->toArray();
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self($data);
    }

    // 私有方法

    /**
     * @param array<string, mixed> $data
     * @throws InvalidArgumentException
     */
    private function validateConstructorData(array $data): void
    {
        if (empty($data['snapshot_type']) || !in_array($data['snapshot_type'], self::SUPPORTED_TYPES, true)) {
            throw new InvalidArgumentException(sprintf(
                '無效的快照類型。支援的類型：%s',
                implode(', ', self::SUPPORTED_TYPES),
            ));
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    private function buildPeriodFromData(array $data): StatisticsPeriod
    {
        /** @phpstan-ignore-next-line cast.string */
        $periodType = (string) ($data['period_type'] ?? '');
        /** @phpstan-ignore-next-line cast.string */
        $periodStart = (string) ($data['period_start'] ?? '');
        /** @phpstan-ignore-next-line cast.string */
        $periodEnd = (string) ($data['period_end'] ?? '');

        if (empty($periodType) || empty($periodStart) || empty($periodEnd)) {
            throw new InvalidArgumentException('統計週期資料不完整');
        }

        return StatisticsPeriod::fromArray([
            'type' => $periodType,
            'start_time' => $periodStart,
            'end_time' => $periodEnd,
        ]);
    }

    /**
     * 解析 JSON 資料.
     *
     * @return array<string, mixed>
     */
    private function parseJsonData(string $json): array
    {
        if (empty($json) || $json === '{}') {
            return [];
        }

        try {
            $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

            /** @var array<string, mixed> $data */
            return is_array($data) ? $data : [];
        } catch (JsonException $e) {
            throw new InvalidArgumentException("無效的 JSON 資料: {$e->getMessage()}");
        }
    }

    private function parseDateTime(mixed $dateTime): ?DateTimeInterface
    {
        if ($dateTime === null || $dateTime === '') {
            return null;
        }

        /** @phpstan-ignore-next-line cast.string */
        $dateTimeString = is_string($dateTime) ? $dateTime : (string) $dateTime;

        try {
            return new DateTime($dateTimeString);
        } catch (Exception $e) {
            throw new InvalidArgumentException("無效的日期時間格式: {$dateTimeString}");
        }
    }

    private static function generateUuid(): string
    {
        // 使用專案現有的 UUID 生成函式
        return function_exists('generate_uuid')
            ? generate_uuid()
            : sprintf(
                '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                mt_rand(0, 0xffff),
                mt_rand(0, 0xffff),
                mt_rand(0, 0xffff),
                mt_rand(0, 0x0fff) | 0x4000,
                mt_rand(0, 0x3fff) | 0x8000,
                mt_rand(0, 0xffff),
                mt_rand(0, 0xffff),
                mt_rand(0, 0xffff),
            );
    }

    private function validateOverviewData(): bool
    {
        $requiredKeys = ['total_posts'];

        return $this->hasRequiredKeys($requiredKeys);
    }

    private function validatePostsData(): bool
    {
        $requiredKeys = ['by_status'];

        return $this->hasRequiredKeys($requiredKeys);
    }

    private function validateSourcesData(): bool
    {
        $requiredKeys = ['by_source'];

        return $this->hasRequiredKeys($requiredKeys);
    }

    private function validateUsersData(): bool
    {
        $requiredKeys = ['active_users'];

        return $this->hasRequiredKeys($requiredKeys);
    }

    private function validatePopularData(): bool
    {
        $requiredKeys = ['top_posts'];

        return $this->hasRequiredKeys($requiredKeys);
    }

    /**
     * @param string[] $requiredKeys
     */
    private function hasRequiredKeys(array $requiredKeys): bool
    {
        foreach ($requiredKeys as $key) {
            if (!$this->hasStatistic($key)) {
                return false;
            }
        }

        return true;
    }
}
