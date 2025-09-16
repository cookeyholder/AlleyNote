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
     * @return array<int, StatisticsSnapshot> 統計快照陣列
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

            /** @var array<int, StatisticsSnapshot> */
            return array_values(array_map([$this, 'createSnapshotFromRow'], $rows));
        } catch (PDOException $e) {
            throw new RuntimeException('無法查詢最新統計快照: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * 查找最新的統計快照列表.
     * @return array<int, StatisticsSnapshot> 最新的統計快照陣列
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

            /** @var array<int, StatisticsSnapshot> */
            return array_values(array_map([$this, 'createSnapshotFromRow'], $rows));
        } catch (PDOException $e) {
            throw new RuntimeException('無法查詢最新統計快照: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * 查找過期的統計快照.
     * @return array<int, StatisticsSnapshot> 過期的統計快照陣列
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

            /** @var array<int, StatisticsSnapshot> */
            return array_values(array_map([$this, 'createSnapshotFromRow'], $rows));
        } catch (PDOException $e) {
            throw new RuntimeException('無法查詢過期統計快照: ' . $e->getMessage(), 0, $e);
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
        } catch (PDOException $e) {
            throw new RuntimeException('無法刪除統計快照: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * 從資料庫行建立統計快照物件.
     * @param array<string, mixed> $row 資料庫行資料
     */
    private function createSnapshotFromRow(array $row): StatisticsSnapshot
    {
        // 安全地解析 JSON 資料
        $snapshotDataJson = $row['snapshot_data'] ?? '{}';
        if (!is_string($snapshotDataJson)) {
            $snapshotDataJson = '{}';
        }
        $snapshotData = json_decode($snapshotDataJson, true);
        if (!is_array($snapshotData)) {
            $snapshotData = [];
        }

        // 安全地建立週期物件
        $periodTypeValue = $row['period_type'] ?? '';
        if (!is_string($periodTypeValue) && !is_int($periodTypeValue)) {
            throw new RuntimeException('無效的週期類型值');
        }

        $startDateString = $row['start_date'] ?? 'now';
        $endDateString = $row['end_date'] ?? 'now';
        if (!is_string($startDateString) || !is_string($endDateString)) {
            throw new RuntimeException('無效的日期格式');
        }

        $period = StatisticsPeriod::create(
            new DateTimeImmutable($startDateString),
            new DateTimeImmutable($endDateString),
            PeriodType::from($periodTypeValue),
        );

        // 安全地建立統計指標
        $totalPostsValue = isset($row['total_posts']) ? (int) $row['total_posts'] : 0;
        $totalViewsValue = isset($row['total_views']) ? (int) $row['total_views'] : 0;

        $totalPosts = StatisticsMetric::count($totalPostsValue, '總文章數');
        $totalViews = StatisticsMetric::count($totalViewsValue, '總瀏覽數');

        // 安全地取得 UUID
        $uuidString = $row['uuid'] ?? '';
        if (!is_string($uuidString)) {
            throw new RuntimeException('無效的 UUID 值');
        }

        // 安全地解析日期
        $createdAtString = $row['created_at'] ?? 'now';
        $updatedAtString = $row['updated_at'] ?? null;
        if (!is_string($createdAtString)) {
            $createdAtString = 'now';
        }

        $createdAt = new DateTimeImmutable($createdAtString);
        $updatedAt = null;
        if (is_string($updatedAtString) && !empty($updatedAtString)) {
            $updatedAt = new DateTimeImmutable($updatedAtString);
        }

        return StatisticsSnapshot::fromData(
            Uuid::fromString($uuidString),
            $period,
            $totalPosts,
            $totalViews,
            [], // 暫時使用空陣列，因為反序列化複雜物件需要特別處理
            [], // 暫時使用空陣列，因為反序列化複雜物件需要特別處理
            $createdAt,
            $updatedAt,
        );
    }

    /**
     * 序列化快照資料為 JSON.
     * @return array<string, mixed> 序列化後的資料
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
            'total_posts' => (int) $snapshot->getTotalPosts()->value,
            'total_views' => (int) $snapshot->getTotalViews()->value,
            'total_users' => (int) ($snapshot->getAdditionalMetric('total_users')->value ?? 0),
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

    /**
     * 查找指定時間範圍內的所有統計快照.
     * @return array<int, StatisticsSnapshot> 統計快照陣列
     */
    public function findByDateRange(
        DateTimeInterface $startDate,
        DateTimeInterface $endDate,
        int $limit = 100,
    ): array {
        try {
            $sql = '
                SELECT * FROM statistics_snapshots
                WHERE created_at BETWEEN :start_date AND :end_date
                ORDER BY created_at DESC
                LIMIT :limit
            ';

            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue('start_date', $startDate->format('Y-m-d H:i:s'));
            $stmt->bindValue('end_date', $endDate->format('Y-m-d H:i:s'));
            $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
            $stmt->execute();

            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            /** @var array<int, StatisticsSnapshot> */
            return array_values(array_map([$this, 'createSnapshotFromRow'], $rows));
        } catch (PDOException $e) {
            throw new RuntimeException('無法查詢時間範圍統計快照: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * 計算指定時間範圍內的統計快照數量.
     */
    public function countByDateRange(
        DateTimeInterface $startDate,
        DateTimeInterface $endDate,
    ): int {
        try {
            $sql = '
                SELECT COUNT(*) as count FROM statistics_snapshots
                WHERE created_at BETWEEN :start_date AND :end_date
            ';

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                'start_date' => $startDate->format('Y-m-d H:i:s'),
                'end_date' => $endDate->format('Y-m-d H:i:s'),
            ]);

            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return (int) ($result['count'] ?? 0);
        } catch (PDOException $e) {
            throw new RuntimeException('無法計算時間範圍統計快照數量: ' . $e->getMessage(), 0, $e);
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
                ORDER BY created_at ASC
                LIMIT 1
            ';

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();

            /** @var array<string, mixed>|false $row */
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            return $row ? $this->createSnapshotFromRow($row) : null;
        } catch (PDOException $e) {
            throw new RuntimeException('無法查詢最舊統計快照: ' . $e->getMessage(), 0, $e);
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
                ORDER BY created_at DESC
                LIMIT 1
            ';

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();

            /** @var array<string, mixed>|false $row */
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            return $row ? $this->createSnapshotFromRow($row) : null;
        } catch (PDOException $e) {
            throw new RuntimeException('無法查詢最新統計快照: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * 計算統計快照總數.
     */
    public function getTotalSnapshotCount(): int
    {
        try {
            $sql = 'SELECT COUNT(*) as count FROM statistics_snapshots';

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();

            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return (int) ($result['count'] ?? 0);
        } catch (PDOException $e) {
            throw new RuntimeException('無法計算統計快照總數量: ' . $e->getMessage(), 0, $e);
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
            throw new RuntimeException('無法刪除過期統計快照: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * 檢查指定週期的統計快照是否存在.
     */
    public function existsByPeriod(StatisticsPeriod $period): bool
    {
        try {
            $sql = '
                SELECT COUNT(*) as count FROM statistics_snapshots
                WHERE period_type = :period_type
                    AND start_date = :start_date
                    AND end_date = :end_date
            ';

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                'period_type' => $period->type->value,
                'start_date' => $period->startDate->format('Y-m-d H:i:s'),
                'end_date' => $period->endDate->format('Y-m-d H:i:s'),
            ]);

            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return (int) ($result['count'] ?? 0) > 0;
        } catch (PDOException $e) {
            throw new RuntimeException('無法檢查統計快照是否存在: ' . $e->getMessage(), 0, $e);
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
                    period_type = :period_type,
                    start_date = :start_date,
                    end_date = :end_date,
                    snapshot_data = :snapshot_data,
                    total_posts = :total_posts,
                    total_views = :total_views,
                    total_users = :total_users,
                    primary_source = :primary_source,
                    calculation_duration = :calculation_duration,
                    data_accuracy = :data_accuracy,
                    updated_at = :updated_at
                WHERE uuid = :uuid
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
                'updated_at' => new DateTimeImmutable()->format('Y-m-d H:i:s'),
            ]);
        } catch (PDOException $e) {
            throw new RuntimeException('無法更新統計快照: ' . $e->getMessage(), 0, $e);
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

            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!is_array($result)) {
                return ['min' => null, 'max' => null];
            }

            $minDate = null;
            $maxDate = null;

            if (is_string($result['min_date']) && !empty($result['min_date'])) {
                $minDate = new DateTimeImmutable($result['min_date']);
            }

            if (is_string($result['max_date']) && !empty($result['max_date'])) {
                $maxDate = new DateTimeImmutable($result['max_date']);
            }

            return [
                'min' => $minDate,
                'max' => $maxDate,
            ];
        } catch (PDOException $e) {
            throw new RuntimeException('無法查詢統計快照時間範圍: ' . $e->getMessage(), 0, $e);
        }
    }
}
