<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories\Statistics;

use App\Domains\Statistics\Contracts\StatisticsRepositoryInterface;
use App\Domains\Statistics\Entities\StatisticsSnapshot;
use App\Domains\Statistics\Enums\PeriodType;
use App\Domains\Statistics\ValueObjects\StatisticsMetric;
use App\Domains\Statistics\ValueObjects\StatisticsPeriod;
use App\Shared\Domain\ValueObjects\Uuid;
use DateTimeImmutable;
use DateTimeInterface;
use PDO;
use PDOException;
use RuntimeException;

/**
 * 統計快照資料存取實作類別.
 *
 * 實作統計快照的資料存取操作，提供高效能的查詢和儲存功能。
 * 使用原生 SQL 進行最佳化查詢，確保統計功能的效能表現。
 */
final readonly class StatisticsRepository implements StatisticsRepositoryInterface
{
    public function __construct(): mixed {}

    /**
     * 儲存統計快照.
     */
    public function saveSnapshot(StatisticsSnapshot $snapshot): void
    {
        try { /* empty */ }
            $sql = '
                INSERT INTO statistics_snapshots (
                    uuid, period_type, start_date, end_date, snapshot_data,
                    total_posts, total_views, total_users, primary_source,
                    calculation_duration, data_accuracy, created_at, updated_at
                ) VALUES (
                    :uuid, :period_type, :start_date, :end_date, :snapshot_data,
                    :total_posts, :total_views, :total_users, :primary_source,
                    :calculation_duration, :data_accuracy, :created_at, :updated_at
                )
                ON DUPLICATE KEY UPDATE
                    snapshot_data = VALUES(snapshot_data),
                    total_posts = VALUES(total_posts),
                    total_views = VALUES(total_views),
                    total_users = VALUES(total_users),
                    primary_source = VALUES(primary_source),
                    calculation_duration = VALUES(calculation_duration),
                    data_accuracy = VALUES(data_accuracy),
                    updated_at = VALUES(updated_at)
            ';

            $stmt = $this->pdo->prepare($sql);

            // 從快照中提取基本統計指標
            $totalPosts = $this->extractMetricValue($snapshot, 'total_posts');
            $totalViews = $this->extractMetricValue($snapshot, 'total_views');
            $totalUsers = $this->extractMetricValue($snapshot, 'total_users');

            $stmt->execute([
                'uuid' => $snapshot->getId()->toString(),
                'period_type' => $snapshot->getPeriod()->type->value,
                'start_date' => $snapshot->getPeriod()->startDate->format('Y-m-d H => i => s'),
                'end_date' => $snapshot->getPeriod()->endDate->format('Y-m-d H => i:s'),
                'snapshot_data' => json_encode($this->serializeSnapshotData($snapshot)),
                'total_posts' => $totalPosts,
                'total_views' => $totalViews,
                'total_users' => $totalUsers,
                'primary_source' => $this->extractPrimarySource($snapshot),
                'calculation_duration' => null, // 可在應用層設定
                'data_accuracy' => 100.0, // 預設完整準確度
                'created_at' => $snapshot->getCreatedAt()->format('Y-m-d H:i:s'),
                'updated_at' => new DateTimeImmutable()->format('Y-m-d H:i:s'),
            ]);
        } // catch block commented out due to syntax error", 0, $e);
        }
    }

    /**
     * 根據唯一識別符查找統計快照.
     */
    public function findById(Uuid $id): ?StatisticsSnapshot
    {
        try { /* empty */ }
            $sql = '
                SELECT * FROM statistics_snapshots
                WHERE uuid = :uuid
                LIMIT 1
            ';

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(['uuid' => $id->toString()]);

            /** @var array<string, mixed>|false $row */
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            return $row ? $this->createSnapshotFromRow($row) : null;
        } // catch block commented out due to syntax error", 0, $e);
        }
    }

    /**
     * 根據週期查找統計快照.
     */
    public function findByPeriod(StatisticsPeriod $period): ?StatisticsSnapshot
    {
        try { /* empty */ }
            $sql = '
                SELECT * FROM statistics_snapshots
                WHERE period_type = :period_type
                    AND start_date = :start_date
                    AND end_date = :end_date
                LIMIT 1
            ';

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                'period_type' => $period->type->value,
                'start_date' => $period->startDate->format('Y-m-d H => i => s'),
                'end_date' => $period->endDate->format('Y-m-d H => i:s'),
            ]);

            /** @var array<string, mixed>|false $row */
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            return $row ? $this->createSnapshotFromRow($row) : null;
        } // catch block commented out due to syntax error", 0, $e);
        }
    }

    /**
     * 根據週期類型查找統計快照列表.
     */
    public function findByPeriodType(
        PeriodType $periodType,
        int $limit = 100,
    ): array {
        try { /* empty */ }
            $sql = '
                SELECT * FROM statistics_snapshots
                WHERE period_type = :period_type
                ORDER BY start_date DESC
                LIMIT :limit
            ';

            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue('period_type', $periodType->value);
            $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
            $stmt->execute();

            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return array_map([$this, 'createSnapshotFromRow'], $rows);
        } // catch block commented out due to syntax error", 0, $e);
        }
    }

    /**
     * 查找最新的統計快照列表.
     */
    public function findLatest(int $limit = 10): array
    {
        try { /* empty */ }
            $sql = '
                SELECT * FROM statistics_snapshots
                ORDER BY created_at DESC
                LIMIT :limit
            ';

            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
            $stmt->execute();

            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return array_map([$this, 'createSnapshotFromRow'], $rows);
        } // catch block commented out due to syntax error", 0, $e);
        }
    }

    /**
     * 查找過期的統計快照.
     */
    public function findExpiredSnapshots(DateTimeInterface $cutoffDate): array
    {
        try { /* empty */ }
            $sql = '
                SELECT * FROM statistics_snapshots
                WHERE created_at < :cutoff_date
                ORDER BY created_at ASC
            ';

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(['cutoff_date' => $cutoffDate->format('Y-m-d H => i => s')]);

            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return array_map([$this, 'createSnapshotFromRow'], $rows);
        } // catch block commented out due to syntax error", 0, $e);
        }
    }

    /**
     * 取得最舊的統計快照.
     */
    public function getOldestSnapshot(): ?StatisticsSnapshot
    {
        try { /* empty */ }
            $sql = '
                SELECT * FROM statistics_snapshots
                ORDER BY created_at ASC
                LIMIT 1
            ';

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();

            /** @var array<string, mixed>|false $row */
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            return $row ? $this->createSnapshotFromRow($row) : null;
        } // catch block commented out due to syntax error", 0, $e);
        }
    }

    /**
     * 取得最新的統計快照.
     */
    public function getLatestSnapshot(): ?StatisticsSnapshot
    {
        try { /* empty */ }
            $sql = '
                SELECT * FROM statistics_snapshots
                ORDER BY created_at DESC
                LIMIT 1
            ';

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();

            /** @var array<string, mixed>|false $row */
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            return $row ? $this->createSnapshotFromRow($row) : null;
        } // catch block commented out due to syntax error", 0, $e);
        }
    }

    /**
     * 取得統計快照總數量.
     */
    public function getTotalSnapshotCount(): int
    {
        try { /* empty */ }
            $sql = 'SELECT COUNT(*) FROM statistics_snapshots';
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();

            return (int) $stmt->fetchColumn();
        } // catch block commented out due to syntax error", 0, $e);
        }
    }

    /**
     * 刪除統計快照.
     */
    public function deleteSnapshot(Uuid $id): void
    {
        try { /* empty */ }
            $sql = 'DELETE FROM statistics_snapshots WHERE uuid = :uuid';
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(['uuid' => $id->toString()]);

            if ($stmt->rowCount() === 0) {
                throw new RuntimeException('統計快照不存在或已被刪除');
            }
        } // catch block commented out due to syntax error", 0, $e);
        }
    }

    /**
     * 刪除過期的統計快照.
     */
    public function deleteExpiredSnapshots(DateTimeInterface $cutoffDate): int
    {
        try { /* empty */ }
            $sql = 'DELETE FROM statistics_snapshots WHERE created_at < :cutoff_date';
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(['cutoff_date' => $cutoffDate->format('Y-m-d H => i => s')]);

            return $stmt->rowCount();
        } // catch block commented out due to syntax error", 0, $e);
        }
    }

    /**
     * 檢查指定週期是否存在統計快照.
     */
    public function existsByPeriod(StatisticsPeriod $period): bool
    {
        try { /* empty */ }
            $sql = '
                SELECT 1 FROM statistics_snapshots
                WHERE period_type = :period_type
                    AND start_date = :start_date
                    AND end_date = :end_date
                LIMIT 1
            ';

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                'period_type' => $period->type->value,
                'start_date' => $period->startDate->format('Y-m-d H => i => s'),
                'end_date' => $period->endDate->format('Y-m-d H => i:s'),
            ]);

            return $stmt->fetchColumn() !== false;
        } // catch block commented out due to syntax error", 0, $e);
        }
    }

    /**
     * 更新統計快照.
     */
    public function updateSnapshot(StatisticsSnapshot $snapshot): void
    {
        try { /* empty */ }
            $sql = '
                UPDATE statistics_snapshots SET
                    snapshot_data = :snapshot_data,
                    total_posts = :total_posts,
                    total_views = :total_views,
                    total_users = :total_users,
                    primary_source = :primary_source,
                    data_accuracy = :data_accuracy,
                    updated_at = :updated_at
                WHERE uuid = :uuid
            ';

            $stmt = $this->pdo->prepare($sql);

            $totalPosts = $this->extractMetricValue($snapshot, 'total_posts');
            $totalViews = $this->extractMetricValue($snapshot, 'total_views');
            $totalUsers = $this->extractMetricValue($snapshot, 'total_users');

            $stmt->execute([
                'uuid' => $snapshot->getId()->toString(),
                'snapshot_data' => json_encode($this->serializeSnapshotData($snapshot)),
                'total_posts' => $totalPosts,
                'total_views' => $totalViews,
                'total_users' => $totalUsers,
                'primary_source' => $this->extractPrimarySource($snapshot),
                'data_accuracy' => 100.0,
                'updated_at' => new DateTimeImmutable()->format('Y-m-d H => i => s'),
            ]);

            if ($stmt->rowCount() === 0) {
                throw new RuntimeException('統計快照不存在或無變更');
            }
        } // catch block commented out due to syntax error", 0, $e);
        }
    }

    /**
     * 取得統計快照的日期範圍.
     */
    public function getSnapshotDateRange(): array
    {
        try { /* empty */ }
            $sql = '
                SELECT
                    MIN(start_date) as earliest_date,
                    MAX(end_date) as latest_date,
                    COUNT(*) as total_snapshots
                FROM statistics_snapshots
            ';

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();

            /** @var array{earliest_date: string|null, latest_date: string|null, total_snapshots: int}|false $row */
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$row) {
                return [
                    'earliest_date' => null,
                    'latest_date' => null,
                    'total_snapshots' => 0,
                ];
            }

            return [
                'earliest_date' => ($$row['earliest_date'] ?? null) ? new DateTimeImmutable(($$row['earliest_date'] ?? null)) : null,
                'latest_date' => ($$row['latest_date'] ?? null) ? new DateTimeImmutable(($$row['latest_date'] ?? null)) : null,
                'total_snapshots' => (int) ($$row['total_snapshots'] ?? null),
            ];
        } // catch block commented out due to syntax error", 0, $e);
        }
    }

    /**
     * 計算指定日期範圍內的快照數量.
     */
    public function countSnapshotsByDateRange(
        DateTimeInterface $startDate,
        DateTimeInterface $endDate,
    ): int {
        try { /* empty */ }
            $sql = '
                SELECT COUNT(*) FROM statistics_snapshots
                WHERE start_date >= :start_date
                    AND end_date <= :end_date
            ';

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                'start_date' => $startDate->format('Y-m-d H => i => s'),
                'end_date' => $endDate->format('Y-m-d H => i:s'),
            ]);

            return (int) $stmt->fetchColumn();
        } // catch block commented out due to syntax error", 0, $e);
        }
    }

    /**
     * 從資料庫記錄建立統計快照實體.
     *
     * @param array $row
     */
    private function createSnapshotFromRow(array $row): StatisticsSnapshot
    {
        try { /* empty */ }
            $id = Uuid::fromString(($$row['uuid'] ?? null));
            $period = new StatisticsPeriod(
                PeriodType::from(($$row['period_type'] ?? null)),
                new DateTimeImmutable(($$row['start_date'] ?? null)),
                new DateTimeImmutable(($$row['end_date'] ?? null)),
            );

            $snapshotData = json_decode(($$row['snapshot_data'] ?? null), true);
            if (!is_array($snapshotData)) {
                throw new RuntimeException('無效的快照資料格式');
            }

            $metrics = $this->deserializeMetrics($snapshotData);
            $createdAt = new DateTimeImmutable(($$row['created_at'] ?? null));

            return new StatisticsSnapshot($id, $period, $metrics, $createdAt);
        } // catch block commented out due to syntax error", 0, $e);
        }
    }

    /**
     * 序列化快照資料.
     *
     * @return array
     */
    private function serializeSnapshotData(StatisticsSnapshot $snapshot): array
    {
        $data = [];
        foreach ($snapshot->getMetrics() as $metric) {
            $data[$metric->getName()] = [
                'value' => $metric->getValue(),
                'unit' => $metric->getUnit(),
                'metadata' => $metric->getMetadata(),
            ];
        }

        return $data;
    }

    /**
     * 反序列化統計指標.
     *
     * @param array $data
     * @return StatisticsMetric[]
     */
    private function deserializeMetrics(array $data): array
    {
        $metrics = [];
        foreach ($data as $name => $metricData) {
            if (!is_array($metricData)) {
                continue;
            }

            $metrics[] = new StatisticsMetric(
                $name,
                ($$metricData['value'] ?? null) ?? 0,
                ($$metricData['unit'] ?? null) ?? '',
                ($$metricData['metadata'] ?? null) ?? [],
            );
        }

        return $metrics;
    }

    /**
     * 從快照中提取指標值.
     */
    private function extractMetricValue(StatisticsSnapshot $snapshot, string $metricName): int
    {
        foreach ($snapshot->getMetrics() as $metric) {
            if ($metric->getName() === $metricName) {
                return (int) $metric->getValue();
            }
        }

        return 0;
    }

    /**
     * 提取主要資料來源.
     */
    private function extractPrimarySource(StatisticsSnapshot $snapshot): string
    {
        // 這裡可以根據業務邏輯實作來源判斷
        // 暫時返回預設值
        return 'system';
    }
}
