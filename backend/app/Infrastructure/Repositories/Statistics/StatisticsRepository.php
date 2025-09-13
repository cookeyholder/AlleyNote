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
    public function __construct(
        private readonly PDO $pdo,
    ) {}

    /**
     * 儲存統計快照.
     */
    public function saveSnapshot(StatisticsSnapshot $snapshot): void
    {
        try {
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
                'start_date' => $snapshot->getPeriod()->startDate->format('Y-m-d H:i:s'),
                'end_date' => $snapshot->getPeriod()->endDate->format('Y-m-d H:i:s'),
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
        } catch (PDOException $e) {
            throw new RuntimeException('無法儲存統計快照: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * 根據唯一識別符查找統計快照.
     */
    public function findById(Uuid $id): ?StatisticsSnapshot
    {
        try {
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
        } catch (PDOException $e) {
            throw new RuntimeException('無法查詢統計快照: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * 根據週期查找統計快照.
     */
    public function findByPeriod(StatisticsPeriod $period): ?StatisticsSnapshot
    {
        try {
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
                'start_date' => $period->startDate->format('Y-m-d H:i:s'),
                'end_date' => $period->endDate->format('Y-m-d H:i:s'),
            ]);

            /** @var array<string, mixed>|false $row */
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            return $row ? $this->createSnapshotFromRow($row) : null;
        } catch (PDOException $e) {
            throw new RuntimeException('無法查詢週期統計快照: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * 根據週期類型查找統計快照列表.
     */
    public function findByPeriodType(
        PeriodType $periodType,
        int $limit = 100,
    ): array {
        try {
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
        } catch (PDOException $e) {
            throw new RuntimeException('無法查詢週期類型統計快照: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * 查找最新的統計快照列表.
     */
    public function findLatest(int $limit = 10): array
    {
        try {
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
        } catch (PDOException $e) {
            throw new RuntimeException('無法查詢最新統計快照: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * 查找過期的統計快照.
     */
    public function findExpiredSnapshots(DateTimeInterface $cutoffDate): array
    {
        try {
            $sql = '
                SELECT * FROM statistics_snapshots
                WHERE created_at < :cutoff_date
                ORDER BY created_at ASC
            ';

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(['cutoff_date' => $cutoffDate->format('Y-m-d H:i:s')]);

            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return array_map([$this, 'createSnapshotFromRow'], $rows);
        } catch (PDOException $e) {
            throw new RuntimeException('無法查詢過期統計快照: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * 刪除統計快照.
     */
    public function deleteSnapshot(Uuid $id): bool
    {
        try {
            $sql = 'DELETE FROM statistics_snapshots WHERE uuid = :uuid';
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute(['uuid' => $id->toString()]);

            return $result && $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            throw new RuntimeException('無法刪除統計快照: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * 從資料庫行建立統計快照物件.
     */
    private function createSnapshotFromRow(array $row): StatisticsSnapshot
    {
        $snapshotData = json_decode($row['snapshot_data'], true) ?: [];

        // 從 JSON 資料重建必要的值物件
        $period = StatisticsPeriod::create(
            PeriodType::from($row['period_type']),
            new DateTimeImmutable($row['start_date']),
            new DateTimeImmutable($row['end_date']),
        );

        $totalPosts = StatisticsMetric::count((int) $row['total_posts'], '總文章數');
        $totalViews = StatisticsMetric::count((int) $row['total_views'], '總瀏覽數');

        return StatisticsSnapshot::fromData(
            Uuid::fromString($row['uuid']),
            $period,
            $totalPosts,
            $totalViews,
            $snapshotData['source_stats'] ?? [],
            $snapshotData['additional_metrics'] ?? [],
            new DateTimeImmutable($row['created_at']),
            isset($row['updated_at']) ? new DateTimeImmutable($row['updated_at']) : null,
        );
    }

    /**
     * 序列化快照資料為 JSON.
     */
    private function serializeSnapshotData(StatisticsSnapshot $snapshot): array
    {
        return [
            'source_stats' => array_map(fn($stat) => $stat->toArray(), $snapshot->getSourceStats()),
            'additional_metrics' => array_map(fn($metric) => $metric->toArray(), $snapshot->getAdditionalMetrics()),
            'summary' => $snapshot->getSummary(),
        ];
    }

    /**
     * 從快照中提取指標值.
     */
    private function extractMetricValue(StatisticsSnapshot $snapshot, string $metricKey): int
    {
        return match ($metricKey) {
            'total_posts' => $snapshot->getTotalPosts()->value,
            'total_views' => $snapshot->getTotalViews()->value,
            'total_users' => $snapshot->getAdditionalMetric('total_users')?->value ?? 0,
            default => 0,
        };
    }

    /**
     * 從快照中提取主要來源.
     */
    private function extractPrimarySource(StatisticsSnapshot $snapshot): ?string
    {
        return $snapshot->getPrimarySourceType()?->value;
    }
}
