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
        private PDO $pdo,
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
            throw new RuntimeException(
                "儲存統計快照失敗: {$e->getMessage()}",
                (int) $e->getCode(),
                $e,
            );
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

            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            return $row ? $this->buildSnapshotFromRow($row) : null;
        } catch (PDOException $e) {
            throw new RuntimeException(
                "查詢統計快照失敗: {$e->getMessage()}",
                (int) $e->getCode(),
                $e,
            );
        }
    }

    /**
     * 根據統計週期查找統計快照.
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

            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            return $row ? $this->buildSnapshotFromRow($row) : null;
        } catch (PDOException $e) {
            throw new RuntimeException(
                "根據週期查詢統計快照失敗: {$e->getMessage()}",
                (int) $e->getCode(),
                $e,
            );
        }
    }

    /**
     * 查找指定時間範圍內的所有統計快照.
     */
    public function findByDateRange(
        DateTimeInterface $startDate,
        DateTimeInterface $endDate,
        int $limit = 100,
    ): array {
        try {
            $sql = '
                SELECT * FROM statistics_snapshots
                WHERE start_date >= :start_date
                    AND end_date <= :end_date
                ORDER BY start_date DESC, created_at DESC
                LIMIT :limit
            ';

            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue('start_date', $startDate->format('Y-m-d H:i:s'));
            $stmt->bindValue('end_date', $endDate->format('Y-m-d H:i:s'));
            $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
            $stmt->execute();

            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return array_map([$this, 'buildSnapshotFromRow'], $rows);
        } catch (PDOException $e) {
            throw new RuntimeException(
                "根據日期範圍查詢統計快照失敗: {$e->getMessage()}",
                (int) $e->getCode(),
                $e,
            );
        }
    }

    /**
     * 查找最新的統計快照.
     */
    public function findLatest(int $limit = 10): array
    {
        try {
            $sql = '
                SELECT * FROM statistics_snapshots
                ORDER BY created_at DESC, start_date DESC
                LIMIT :limit
            ';

            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
            $stmt->execute();

            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return array_map([$this, 'buildSnapshotFromRow'], $rows);
        } catch (PDOException $e) {
            throw new RuntimeException(
                "查詢最新統計快照失敗: {$e->getMessage()}",
                (int) $e->getCode(),
                $e,
            );
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

            return array_map([$this, 'buildSnapshotFromRow'], $rows);
        } catch (PDOException $e) {
            throw new RuntimeException(
                "查詢過期統計快照失敗: {$e->getMessage()}",
                (int) $e->getCode(),
                $e,
            );
        }
    }

    /**
     * 取得最舊的統計快照.
     */
    public function getOldestSnapshot(): ?StatisticsSnapshot
    {
        try {
            $sql = '
                SELECT * FROM statistics_snapshots
                ORDER BY created_at ASC, start_date ASC
                LIMIT 1
            ';

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();

            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            return $row ? $this->buildSnapshotFromRow($row) : null;
        } catch (PDOException $e) {
            throw new RuntimeException(
                "取得最舊統計快照失敗: {$e->getMessage()}",
                (int) $e->getCode(),
                $e,
            );
        }
    }

    /**
     * 取得最新的統計快照.
     */
    public function getLatestSnapshot(): ?StatisticsSnapshot
    {
        try {
            $sql = '
                SELECT * FROM statistics_snapshots
                ORDER BY created_at DESC, start_date DESC
                LIMIT 1
            ';

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();

            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            return $row ? $this->buildSnapshotFromRow($row) : null;
        } catch (PDOException $e) {
            throw new RuntimeException(
                "取得最新統計快照失敗: {$e->getMessage()}",
                (int) $e->getCode(),
                $e,
            );
        }
    }

    /**
     * 計算統計快照總數.
     */
    public function getTotalSnapshotCount(): int
    {
        try {
            $sql = 'SELECT COUNT(*) FROM statistics_snapshots';
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();

            return (int) $stmt->fetchColumn();
        } catch (PDOException $e) {
            throw new RuntimeException(
                "計算統計快照總數失敗: {$e->getMessage()}",
                (int) $e->getCode(),
                $e,
            );
        }
    }

    /**
     * 刪除統計快照.
     */
    public function deleteSnapshot(Uuid $id): void
    {
        try {
            $sql = 'DELETE FROM statistics_snapshots WHERE uuid = :uuid';
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(['uuid' => $id->toString()]);

            if ($stmt->rowCount() === 0) {
                throw new RuntimeException("統計快照不存在或已被刪除: {$id->toString()}");
            }
        } catch (PDOException $e) {
            throw new RuntimeException(
                "刪除統計快照失敗: {$e->getMessage()}",
                (int) $e->getCode(),
                $e,
            );
        }
    }

    /**
     * 批量刪除過期的統計快照.
     */
    public function deleteExpiredSnapshots(DateTimeInterface $cutoffDate): int
    {
        try {
            $sql = 'DELETE FROM statistics_snapshots WHERE created_at < :cutoff_date';
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(['cutoff_date' => $cutoffDate->format('Y-m-d H:i:s')]);

            return $stmt->rowCount();
        } catch (PDOException $e) {
            throw new RuntimeException(
                "批量刪除過期統計快照失敗: {$e->getMessage()}",
                (int) $e->getCode(),
                $e,
            );
        }
    }

    /**
     * 檢查指定週期的統計快照是否存在.
     */
    public function existsByPeriod(StatisticsPeriod $period): bool
    {
        try {
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
                'start_date' => $period->startDate->format('Y-m-d H:i:s'),
                'end_date' => $period->endDate->format('Y-m-d H:i:s'),
            ]);

            return $stmt->fetchColumn() !== false;
        } catch (PDOException $e) {
            throw new RuntimeException(
                "檢查統計快照是否存在失敗: {$e->getMessage()}",
                (int) $e->getCode(),
                $e,
            );
        }
    }

    /**
     * 更新統計快照.
     */
    public function updateSnapshot(StatisticsSnapshot $snapshot): void
    {
        try {
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

            $result = $stmt->execute([
                'uuid' => $snapshot->getId()->toString(),
                'snapshot_data' => json_encode($this->serializeSnapshotData($snapshot)),
                'total_posts' => $totalPosts,
                'total_views' => $totalViews,
                'total_users' => $totalUsers,
                'primary_source' => $this->extractPrimarySource($snapshot),
                'data_accuracy' => 100.0,
                'updated_at' => new DateTimeImmutable()->format('Y-m-d H:i:s'),
            ]);

            if ($stmt->rowCount() === 0) {
                throw new RuntimeException("統計快照不存在或更新失敗: {$snapshot->getId()->toString()}");
            }
        } catch (PDOException $e) {
            throw new RuntimeException(
                "更新統計快照失敗: {$e->getMessage()}",
                (int) $e->getCode(),
                $e,
            );
        }
    }

    /**
     * 取得統計快照的建立時間範圍.
     */
    public function getSnapshotDateRange(): array
    {
        try {
            $sql = '
                SELECT
                    MIN(created_at) as min_date,
                    MAX(created_at) as max_date
                FROM statistics_snapshots
            ';

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();

            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            return [
                'min' => $row['min_date'] ? new DateTimeImmutable($row['min_date']) : null,
                'max' => $row['max_date'] ? new DateTimeImmutable($row['max_date']) : null,
            ];
        } catch (PDOException $e) {
            throw new RuntimeException(
                "取得統計快照時間範圍失敗: {$e->getMessage()}",
                (int) $e->getCode(),
                $e,
            );
        }
    }

    /**
     * 計算指定日期範圍內的統計快照總數 (StatisticsQueryService 需要).
     */
    public function countByDateRange(
        DateTimeInterface $startDate,
        DateTimeInterface $endDate,
    ): int {
        try {
            $sql = '
                SELECT COUNT(*) FROM statistics_snapshots
                WHERE start_date >= :start_date
                    AND end_date <= :end_date
            ';

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                'start_date' => $startDate->format('Y-m-d H:i:s'),
                'end_date' => $endDate->format('Y-m-d H:i:s'),
            ]);

            return (int) $stmt->fetchColumn();
        } catch (PDOException $e) {
            throw new RuntimeException(
                "計算日期範圍內統計快照總數失敗: {$e->getMessage()}",
                (int) $e->getCode(),
                $e,
            );
        }
    }

    /**
     * 從資料庫結果建立統計快照實體.
     */
    private function buildSnapshotFromRow(array $row): StatisticsSnapshot
    {
        $period = StatisticsPeriod::create(
            new DateTimeImmutable($row['start_date']),
            new DateTimeImmutable($row['end_date']),
            PeriodType::from($row['period_type']),
        );

        $snapshotData = json_decode($row['snapshot_data'], true) ?? [];

        // 建立基本指標
        $totalPosts = StatisticsMetric::count((int) $row['total_posts'], '總文章數');
        $totalViews = StatisticsMetric::count((int) $row['total_views'], '總瀏覽數');

        // 反序列化額外指標
        $additionalMetrics = $this->deserializeMetrics($snapshotData);

        return StatisticsSnapshot::fromData(
            Uuid::fromString($row['uuid']),
            $period,
            $totalPosts,
            $totalViews,
            [], // 來源統計，暫時為空陣列
            $additionalMetrics,
            new DateTimeImmutable($row['created_at']),
            $row['updated_at'] ? new DateTimeImmutable($row['updated_at']) : null,
        );
    }

    /**
     * 序列化快照資料.
     */
    private function serializeSnapshotData(StatisticsSnapshot $snapshot): array
    {
        $data = [];

        // 儲存基本指標
        $data['total_posts'] = [
            'value' => $snapshot->getTotalPosts()->value,
            'unit' => $snapshot->getTotalPosts()->unit,
            'description' => $snapshot->getTotalPosts()->description,
        ];

        $data['total_views'] = [
            'value' => $snapshot->getTotalViews()->value,
            'unit' => $snapshot->getTotalViews()->unit,
            'description' => $snapshot->getTotalViews()->description,
        ];

        // 儲存額外指標
        foreach ($snapshot->getAdditionalMetrics() as $key => $metric) {
            $data[$key] = [
                'value' => $metric->value,
                'unit' => $metric->unit,
                'description' => $metric->description,
            ];
        }

        // 儲存來源統計
        $sourceStats = [];
        foreach ($snapshot->getSourceStats() as $sourceStat) {
            $sourceStats[] = [
                'source_type' => $sourceStat->sourceType->value,
                'post_count' => $sourceStat->count->value,
                'view_count' => $sourceStat->getAdditionalMetric('views')?->value ?? 0,
                'percentage' => $sourceStat->percentage->value,
            ];
        }
        $data['source_stats'] = $sourceStats;

        return $data;
    }

    /**
     * 反序列化指標資料.
     */
    private function deserializeMetrics(array $data): array
    {
        $metrics = [];

        foreach ($data as $key => $metricData) {
            if (is_array($metricData) && isset($metricData['value'])) {
                $metrics[$key] = StatisticsMetric::create(
                    $metricData['value'],
                    $metricData['unit'] ?? '',
                    $metricData['description'] ?? $key,
                );
            }
        }

        return $metrics;
    }

    /**
     * 從快照中提取指標值
     */
    private function extractMetricValue(StatisticsSnapshot $snapshot, string $key): int
    {
        switch ($key) {
            case 'total_posts':
                return (int) $snapshot->getTotalPosts()->value;
            case 'total_views':
                return (int) $snapshot->getTotalViews()->value;
            case 'total_users':
                $metric = $snapshot->getAdditionalMetric('total_users');

                return $metric ? (int) $metric->value : 0;
            default:
                $metric = $snapshot->getAdditionalMetric($key);

                return $metric ? (int) $metric->value : 0;
        }
    }

    /**
     * 從快照中提取主要來源.
     */
    private function extractPrimarySource(StatisticsSnapshot $snapshot): ?string
    {
        // 從額外指標中查找主要來源
        $primarySource = $snapshot->getAdditionalMetric('primary_source');
        if ($primarySource) {
            return (string) $primarySource->value;
        }

        // 如果沒有主要來源指標，從來源統計中找出比例最高的
        $sourceStats = $snapshot->getSourceStats();
        if (!empty($sourceStats)) {
            $maxPercentage = 0;
            $primarySourceType = null;

            foreach ($sourceStats as $sourceStat) {
                if ($sourceStat->percentage->value > $maxPercentage) {
                    $maxPercentage = $sourceStat->percentage->value;
                    $primarySourceType = $sourceStat->sourceType->value;
                }
            }

            return $primarySourceType;
        }

        return 'web'; // 預設值
    }
}
