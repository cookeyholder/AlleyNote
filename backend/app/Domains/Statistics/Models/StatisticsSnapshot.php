<?php

declare(strict_types=1);

namespace App\Domains\Statistics\Models;

use JsonSerializable;

/**
 * 統計快照模型.
 *
 * 用於表示系統中的統計數據快照，支援不同類型和週期的統計資料存儲
 */
class StatisticsSnapshot implements JsonSerializable
{
    private int $id;

    private string $uuid;

    private string $snapshotType;

    private string $periodType;

    private string $periodStart;

    private string $periodEnd;

    /** @var array<string, mixed> */
    private array $statisticsData;

    private int $totalViews;

    private int $totalUniqueViewers;

    private string $createdAt;

    private ?string $updatedAt;

    /**
     * @param array<string, mixed> $data
     */
    public function __construct(array $data)
    {
        $this->id = isset($data['id']) ? (int) $data['id'] : 0;
        $this->uuid = isset($data['uuid']) ? (string) $data['uuid'] : '';
        $this->snapshotType = isset($data['snapshot_type']) ? (string) $data['snapshot_type'] : '';
        $this->periodType = isset($data['period_type']) ? (string) $data['period_type'] : '';
        $this->periodStart = isset($data['period_start']) ? (string) $data['period_start'] : '';
        $this->periodEnd = isset($data['period_end']) ? (string) $data['period_end'] : '';

        // 處理 JSON 資料
        if (isset($data['statistics_data'])) {
            if (is_string($data['statistics_data'])) {
                $decoded = json_decode($data['statistics_data'], true);
                $this->statisticsData = is_array($decoded) ? $decoded : [];
            } elseif (is_array($data['statistics_data'])) {
                $this->statisticsData = $data['statistics_data'];
            } else {
                $this->statisticsData = [];
            }
        } else {
            $this->statisticsData = [];
        }

        $this->totalViews = isset($data['total_views']) ? (int) $data['total_views'] : 0;
        $this->totalUniqueViewers = isset($data['total_unique_viewers']) ? (int) $data['total_unique_viewers'] : 0;
        $this->createdAt = isset($data['created_at']) ? (string) $data['created_at'] : '';
        $this->updatedAt = (isset($data['updated_at']) && $data['updated_at'] !== null)
            ? (string) $data['updated_at']
            : null;
    }

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

    public function getPeriodType(): string
    {
        return $this->periodType;
    }

    public function getPeriodStart(): string
    {
        return $this->periodStart;
    }

    public function getPeriodEnd(): string
    {
        return $this->periodEnd;
    }

    /**
     * @return array<string, mixed>
     */
    public function getStatisticsData(): array
    {
        return $this->statisticsData;
    }

    public function getTotalViews(): int
    {
        return $this->totalViews;
    }

    public function getTotalUniqueViewers(): int
    {
        return $this->totalUniqueViewers;
    }

    public function getCreatedAt(): string
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?string
    {
        return $this->updatedAt;
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
            'period_type' => $this->periodType,
            'period_start' => $this->periodStart,
            'period_end' => $this->periodEnd,
            'statistics_data' => $this->statisticsData,
            'total_views' => $this->totalViews,
            'total_unique_viewers' => $this->totalUniqueViewers,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
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
}
