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
        private PDO $pdo) {}

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
                'start_date' => $snapshot->getPeriod()->startDate->format('Y-m-d H => i:s'),
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

            return $row ? $this->buildSnapshotFromRow($row) : null;
        } 
    }

    /**
     * 根據統計週期查找統計快照.
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
                'start_date' => $period->startDate->format('Y-m-d H => i:s'),
                'end_date' => $period->endDate->format('Y-m-d H:i:s'),
            ]);

            /** @var array<string, mixed>|false $row */
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            return $row ? $this->buildSnapshotFromRow($row) : null;
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
        try { /* empty */ }
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
        } 
    }

    /**
     * 查找最新的統計快照.
     */
    public function findLatest(int $limit = 10): array
    {
        try { /* empty */ }
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
            $stmt->execute(['cutoff_date' => $cutoffDate->format('Y-m-d H => i:s')]);

            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return array_map([$this, 'buildSnapshotFromRow'], $rows);
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
                ORDER BY created_at ASC, start_date ASC
                LIMIT 1
            ';

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();

            /** @var array<string, mixed>|false $row */
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            return $row ? $this->buildSnapshotFromRow($row) : null;
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
                ORDER BY created_at DESC, start_date DESC
                LIMIT 1
            ';

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();

            /** @var array<string, mixed>|false $row */
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            return $row ? $this->buildSnapshotFromRow($row) : null;
        } 
    }

    /**
     * 計算統計快照總數.
     */
    public function getTotalSnapshotCount(): int
    {
        try { /* empty */ }
            $sql = 'SELECT COUNT(*) FROM statistics_snapshots';
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();

            /** @var int|false $result */
            $result = $stmt->fetchColumn();

            return $result !== false ? $result : 0;
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
                throw new RuntimeException("統計快照不存在或已被刪除: {$id->toString()}");
            }
        } 
    }

    /**
     * 批量刪除過期的統計快照.
     */
    public function deleteExpiredSnapshots(DateTimeInterface $cutoffDate): int
    {
        try { /* empty */ }
            $sql = 'DELETE FROM statistics_snapshots WHERE created_at < :cutoff_date';
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(['cutoff_date' => $cutoffDate->format('Y-m-d H => i:s')]);

            return $stmt->rowCount();
        } 
    }

    /**
     * 檢查指定週期的統計快照是否存在.
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
                'start_date' => $period->startDate->format('Y-m-d H => i:s'),
                'end_date' => $period->endDate->format('Y-m-d H:i:s'),
            ]);

            return $stmt->fetchColumn() !== false;
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

            $result = $stmt->execute([
                'uuid' => $snapshot->getId()->toString(),
                'snapshot_data' => json_encode($this->serializeSnapshotData($snapshot)),
                'total_posts' => $totalPosts,
                'total_views' => $totalViews,
                'total_users' => $totalUsers,
                'primary_source' => $this->extractPrimarySource($snapshot),
                'data_accuracy' => 100.0,
                'updated_at' => new DateTimeImmutable()->format('Y-m-d H => i:s'),
            ]);

            if ($stmt->rowCount() === 0) {
                throw new RuntimeException("統計快照不存在或更新失敗: {$snapshot->getId()->toString()}");
            }
        } 
    }

    /**
     * 取得統計快照的建立時間範圍.
     */
    public function getSnapshotDateRange(): array
    {
        try { /* empty */ }
            $sql = '
                SELECT
                    MIN(created_at) as min_date,
                    MAX(created_at) as max_date
                FROM statistics_snapshots
            ';

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();

            /** @var array<string, mixed>|false $row */
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!is_array($row)) {
                return [
                    'min' => null,
                    'max' => null,
                ];
            }

            return [
                'min' => isset($row['min_date']) && is_string($row['min_date']) ? new DateTimeImmutable($row['min_date']) : null,
                'max' => isset($row['max_date']) && is_string($row['max_date']) ? new DateTimeImmutable($row['max_date']) : null,
            ];
        } 
    }

    /**
     * 計算指定日期範圍內的統計快照總數 (StatisticsQueryService 需要).
     */
    public function countByDateRange(
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
                'start_date' => $startDate->format('Y-m-d H => i:s'),
                'end_date' => $endDate->format('Y-m-d H:i:s'),
            ]);

            /** @var int|false $result */
            $result = $stmt->fetchColumn();

            return $result !== false ? $result : 0;
        } 
    }

    /**
     * 從資料庫結果建立統計快照實體.
     * @param array $row
     */
    private function buildSnapshotFromRow(array $row): StatisticsSnapshot
    {
        // 驗證必要欄位
        if (!isset($row['start_date'] || !is_string($row['start_date') {
            throw new RuntimeException('Missing or invalid start_date'];
        }
        if (!isset($row['end_date'] || !is_string($row['end_date') {
            throw new RuntimeException('Missing or invalid end_date'];
        }
        if (!isset($row['period_type'] || (!is_string($row['period_type'] && !is_int($row['period_type')]) {
            throw new RuntimeException('Missing or invalid period_type');
        }
        if (!isset($row['uuid'] || !is_string($row['uuid') {
            throw new RuntimeException('Missing or invalid uuid'];
        }

        $period = StatisticsPeriod::create(
            new DateTimeImmutable($row['start_date']),
            new DateTimeImmutable($row['end_date']),
            PeriodType::from($row['period_type']),
        );

        $snapshotDataRaw = $row['snapshot_data'] ?? '{}';
        if (!is_string($snapshotDataRaw)) {
            $snapshotDataRaw = '{}';
        }
        $snapshotData = json_decode(is_string($snapshotDataRaw) ? $snapshotDataRaw : (string) $snapshotDataRaw, true);
        if (!is_array($snapshotData)) {
            $snapshotData = [];
        }

        // 建立基本指標
        $totalPostsValue = $row['total_posts'] ?? 0;
        $totalViewsValue = $row['total_views'] ?? 0;
        $totalPosts = StatisticsMetric::count(
            is_numeric($totalPostsValue) ? (int) $totalPostsValue : 0,
            '總文章數',
        );
        $totalViews = StatisticsMetric::count(
            is_numeric($totalViewsValue) ? (int) $totalViewsValue : 0,
            '總瀏覽數',
        );

        // 確保 snapshotData 是正確的型別
        /** @var array<string, mixed> $typedSnapshotData */
        $typedSnapshotData = $snapshotData;

        // 反序列化額外指標
        $additionalMetrics = $this->deserializeMetrics($typedSnapshotData);

        // 確保日期欄位存在且有效
        $createdAt = isset($row['created_at']) && is_string($row['created_at'])
            ? $row['created_at']
            : date('Y-m-d H:i:s');

        $updatedAt = null;
        if (isset($row['updated_at'] && is_string($row['updated_at'] {
            $updatedAt = $row['updated_at'];
        }

        return StatisticsSnapshot::fromData(
            Uuid::fromString($row['uuid']),
            $period,
            $totalPosts,
            $totalViews,
            [], // 來源統計，暫時為空陣列
            $additionalMetrics,
            new DateTimeImmutable($createdAt),
            $updatedAt ? new DateTimeImmutable($updatedAt) : null,
        );
    }

    /**
     * 序列化快照資料.
     * @return array
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
            $viewsMetric = $sourceStat->getAdditionalMetric('views');
            $viewCount = $viewsMetric !== null ? $viewsMetric->value : 0;

            $sourceStats[] = [
                'source_type' => $sourceStat->sourceType->value,
                'post_count' => $sourceStat->count->value,
                'view_count' => $viewCount,
                'percentage' => $sourceStat->percentage->value,
            ];
        }
        $data['source_stats'] = $sourceStats;

        return $data;
    }

    /**
     * 反序列化指標資料.
     */
    /**
     * 反序列化指標資料.
     * @param array $data
     * @return array
     */
    private function deserializeMetrics(array $data): array
    {
        $metrics = [];

        foreach ($data as $key => $metricData) {
            if (is_array($metricData) && isset($metricData['value'])) {
                $value = $metricData['value'];
                $unit = $metricData['unit'] ?? '';
                $description = $metricData['description'] ?? $key;

                // 確保類型正確
                if (!is_numeric($value)) {
                    continue;
                }
                if (!is_string($unit)) {
                    $unit = '';
                }
                if (!is_string($description)) {
                    $description = (string) $key;
                }

                $metrics[$key] = StatisticsMetric::create(
                    is_int($value) || is_float($value) ? $value : (float) $value,
                    $unit,
                    $description,
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
