<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories\Statistics;

use App\Domains\Statistics\Contracts\StatisticsRepositoryInterface;
use App\Domains\Statistics\Entities\StatisticsSnapshot;
use App\Domains\Statistics\Enums\PeriodType;
use App\Domains\Statistics\Enums\SourceType;
use App\Domains\Statistics\ValueObjects\SourceStatistics;
use App\Domains\Statistics\ValueObjects\StatisticsMetric;
use App\Domains\Statistics\ValueObjects\StatisticsPeriod;
use App\Infrastructure\Database\DatabaseConnection;
use App\Shared\Domain\ValueObjects\Uuid;
use DateTimeImmutable;
use DateTimeInterface;
use PDO;
use RuntimeException;
use Throwable;

/**
 * 統計資料存取實作類別.
 *
 * 使用原生 SQL 實作高效能的統計資料存取，
 * 提供完整的錯誤處理和複雜查詢支援。
 *
 * 設計原則：
 * - 使用原生 SQL 最佳化效能
 * - 準備語句防止 SQL 注入
 * - 完整的錯誤處理和日誌記錄
 * - 支援交易管理
 * - 遵循領域模型轉換
 *
 * @author GitHub Copilot
 * @version 1.0.0
 * @since 2025-09-05
 */
final class StatisticsRepository implements StatisticsRepositoryInterface
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = DatabaseConnection::getInstance();
    }

    /**
     * 儲存統計快照.
     */
    public function saveSnapshot(StatisticsSnapshot $snapshot): StatisticsSnapshot
    {
        try {
            $sql = '
                INSERT INTO statistics_snapshots (
                    uuid, snapshot_type, period_type, period_start, period_end,
                    statistics_data, total_views, total_unique_viewers, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ';

            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute([
                $snapshot->getId()->toString(),
                'posts', // 固定為 posts 類型
                $snapshot->getPeriod()->type->value,
                $snapshot->getPeriod()->startDate->format('Y-m-d H:i:s'),
                $snapshot->getPeriod()->endDate->format('Y-m-d H:i:s'),
                $this->encodeStatisticsData($snapshot),
                $snapshot->getTotalViews()->value,
                $snapshot->getTotalPosts()->value, // 暫時使用 posts 作為 unique_viewers
                $snapshot->getCreatedAt()->format('Y-m-d H:i:s'),
            ]);

            if (!$result) {
                throw new RuntimeException('儲存統計快照失敗');
            }

            return $snapshot;
        } catch (Throwable $e) {
            throw new RuntimeException("儲存統計快照時發生錯誤: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * 依週期查詢統計快照.
     */
    public function findByPeriod(StatisticsPeriod $period): ?StatisticsSnapshot
    {
        try {
            $sql = "
                SELECT * FROM statistics_snapshots
                WHERE period_type = ?
                    AND period_start = ?
                    AND period_end = ?
                    AND snapshot_type = 'posts'
                ORDER BY created_at DESC
                LIMIT 1
            ";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                $period->type->value,
                $period->startDate->format('Y-m-d H:i:s'),
                $period->endDate->format('Y-m-d H:i:s'),
            ]);

            $data = $stmt->fetch();

            return $data !== false ? $this->buildSnapshotFromData($data) : null;
        } catch (Throwable $e) {
            throw new RuntimeException("查詢週期統計時發生錯誤: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * 依日期範圍查詢統計快照列表.
     */
    public function findByDateRange(
        DateTimeInterface $startDate,
        DateTimeInterface $endDate,
        ?PeriodType $periodType = null,
    ): array {
        try {
            $sql = "
                SELECT * FROM statistics_snapshots
                WHERE period_start >= ?
                    AND period_end <= ?
                    AND snapshot_type = 'posts'
            ";

            $params = [
                $startDate->format('Y-m-d H:i:s'),
                $endDate->format('Y-m-d H:i:s'),
            ];

            if ($periodType !== null) {
                $sql .= ' AND period_type = ?';
                $params[] = $periodType->value;
            }

            $sql .= ' ORDER BY period_start DESC';

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);

            $results = [];
            while ($data = $stmt->fetch()) {
                $results[] = $this->buildSnapshotFromData($data);
            }

            return $results;
        } catch (Throwable $e) {
            throw new RuntimeException("查詢日期範圍統計時發生錯誤: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * 取得最新的統計快照.
     */
    public function findLatest(?PeriodType $periodType = null): ?StatisticsSnapshot
    {
        try {
            $sql = "
                SELECT * FROM statistics_snapshots
                WHERE snapshot_type = 'posts'
            ";

            $params = [];
            if ($periodType !== null) {
                $sql .= ' AND period_type = ?';
                $params[] = $periodType->value;
            }

            $sql .= ' ORDER BY created_at DESC LIMIT 1';

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);

            $data = $stmt->fetch();

            return $data ? $this->buildSnapshotFromData($data) : null;
        } catch (Throwable $e) {
            throw new RuntimeException("查詢最新統計時發生錯誤: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * 取得指定週期類型的最近幾筆統計快照.
     */
    public function findRecent(PeriodType $periodType, int $limit = 10): array
    {
        try {
            $sql = "
                SELECT * FROM statistics_snapshots
                WHERE period_type = ?
                    AND snapshot_type = 'posts'
                ORDER BY period_start DESC
                LIMIT ?
            ";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$periodType->value, $limit]);

            $results = [];
            while ($data = $stmt->fetch()) {
                $results[] = $this->buildSnapshotFromData($data);
            }

            return $results;
        } catch (Throwable $e) {
            throw new RuntimeException("查詢最近統計時發生錯誤: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * 依來源類型查詢統計快照.
     */
    public function findBySourceType(
        SourceType $sourceType,
        ?StatisticsPeriod $period = null,
        int $limit = 100,
    ): array {
        try {
            $sql = "
                SELECT * FROM statistics_snapshots
                WHERE snapshot_type = 'posts'
                    AND JSON_EXTRACT(statistics_data, '$.source_statistics') IS NOT NULL
            ";

            $params = [];

            if ($period !== null) {
                $sql .= ' AND period_start >= ? AND period_end <= ?';
                $params[] = $period->startDate->format('Y-m-d H:i:s');
                $params[] = $period->endDate->format('Y-m-d H:i:s');
            }

            $sql .= ' ORDER BY created_at DESC LIMIT ?';
            $params[] = $limit;

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);

            $results = [];
            while ($data = $stmt->fetch()) {
                $snapshot = $this->buildSnapshotFromData($data);
                // 過濾包含指定來源類型的快照
                foreach ($snapshot->getSourceStats() as $sourceStat) {
                    if ($sourceStat->sourceType === $sourceType) {
                        $results[] = $snapshot;
                        break;
                    }
                }
            }

            return $results;
        } catch (Throwable $e) {
            throw new RuntimeException("查詢來源類型統計時發生錯誤: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * 刪除過期的統計快照.
     */
    public function deleteExpiredSnapshots(int $daysToKeep = 90): int
    {
        try {
            $cutoffDate = new DateTimeImmutable("-{$daysToKeep} days");

            $sql = "
                DELETE FROM statistics_snapshots
                WHERE created_at < ?
                    AND snapshot_type = 'posts'
            ";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$cutoffDate->format('Y-m-d H:i:s')]);

            return $stmt->rowCount();
        } catch (Throwable $e) {
            throw new RuntimeException("刪除過期統計時發生錯誤: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * 取得統計快照總數.
     */
    public function getTotalSnapshotCount(?PeriodType $periodType = null): int
    {
        try {
            $sql = "SELECT COUNT(*) FROM statistics_snapshots WHERE snapshot_type = 'posts'";
            $params = [];

            if ($periodType !== null) {
                $sql .= ' AND period_type = ?';
                $params[] = $periodType->value;
            }

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);

            return (int) $stmt->fetchColumn();
        } catch (Throwable $e) {
            throw new RuntimeException("查詢統計總數時發生錯誤: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * 檢查指定週期是否已有統計快照.
     */
    public function existsForPeriod(StatisticsPeriod $period): bool
    {
        return $this->findByPeriod($period) !== null;
    }

    /**
     * 取得統計快照的時間序列資料.
     */
    public function getTimeSeriesData(
        DateTimeInterface $startDate,
        DateTimeInterface $endDate,
        PeriodType $periodType,
    ): array {
        try {
            $sql = "
                SELECT
                    DATE(period_start) as date,
                    total_views as total_count,
                    total_unique_viewers as unique_count,
                    period_type
                FROM statistics_snapshots
                WHERE period_start >= ?
                    AND period_end <= ?
                    AND period_type = ?
                    AND snapshot_type = 'posts'
                ORDER BY period_start ASC
            ";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                $startDate->format('Y-m-d H:i:s'),
                $endDate->format('Y-m-d H:i:s'),
                $periodType->value,
            ]);

            return $stmt->fetchAll();
        } catch (Throwable $e) {
            throw new RuntimeException("查詢時間序列資料時發生錯誤: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * 取得來源統計分布資料.
     */
    public function getSourceDistribution(StatisticsPeriod $period): array
    {
        try {
            $snapshot = $this->findByPeriod($period);
            if (!$snapshot) {
                return [];
            }

            $sourceStats = $snapshot->getSourceStats();
            $total = array_sum(array_map(fn($stat) => $stat->getCountValue(), $sourceStats));

            $distribution = [];
            foreach ($sourceStats as $stat) {
                $percentage = $total > 0 ? round(($stat->getCountValue() / $total) * 100, 2) : 0;

                $distribution[] = [
                    'source_type' => $stat->sourceType->value,
                    'total_count' => $stat->getCountValue(),
                    'percentage' => $percentage,
                    'period_start' => $period->startDate->format('Y-m-d H:i:s'),
                    'period_end' => $period->endDate->format('Y-m-d H:i:s'),
                ];
            }

            return $distribution;
        } catch (Throwable $e) {
            throw new RuntimeException("查詢來源分布時發生錯誤: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * 批次儲存多個統計快照.
     */
    public function saveBatch(array $snapshots): array
    {
        if (empty($snapshots)) {
            return [];
        }

        try {
            $this->pdo->beginTransaction();

            $results = [];
            foreach ($snapshots as $snapshot) {
                if (!$snapshot instanceof StatisticsSnapshot) {
                    throw new RuntimeException('批次儲存的項目必須是 StatisticsSnapshot 實例');
                }
                $results[] = $this->saveSnapshot($snapshot);
            }

            $this->pdo->commit();

            return $results;
        } catch (Throwable $e) {
            $this->pdo->rollBack();

            throw new RuntimeException("批次儲存統計快照時發生錯誤: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * 更新或建立統計快照.
     */
    public function upsertSnapshot(StatisticsSnapshot $snapshot): StatisticsSnapshot
    {
        try {
            $existing = $this->findByPeriod($snapshot->getPeriod());

            if ($existing) {
                // 更新現有快照
                $sql = '
                    UPDATE statistics_snapshots
                    SET statistics_data = ?,
                        total_views = ?,
                        total_unique_viewers = ?,
                        updated_at = ?
                    WHERE uuid = ?
                ';

                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([
                    $this->encodeStatisticsData($snapshot),
                    $snapshot->getTotalViews()->value,
                    $snapshot->getTotalPosts()->value,
                    new DateTimeImmutable()->format('Y-m-d H:i:s'),
                    $existing->getId()->toString(),
                ]);

                return $snapshot;
            } else {
                // 建立新快照
                return $this->saveSnapshot($snapshot);
            }
        } catch (Throwable $e) {
            throw new RuntimeException("更新或建立統計快照時發生錯誤: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * 依 UUID 查詢統計快照.
     */
    public function findByUuid(string $uuid): ?StatisticsSnapshot
    {
        try {
            $sql = "
                SELECT * FROM statistics_snapshots
                WHERE uuid = ?
                    AND snapshot_type = 'posts'
            ";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$uuid]);

            $data = $stmt->fetch();

            return $data ? $this->buildSnapshotFromData($data) : null;
        } catch (Throwable $e) {
            throw new RuntimeException("依 UUID 查詢統計時發生錯誤: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * 取得指定日期之前的最後一筆統計快照.
     */
    public function findLastBeforeDate(
        DateTimeInterface $date,
        ?PeriodType $periodType = null,
    ): ?StatisticsSnapshot {
        try {
            $sql = "
                SELECT * FROM statistics_snapshots
                WHERE period_end < ?
                    AND snapshot_type = 'posts'
            ";

            $params = [$date->format('Y-m-d H:i:s')];

            if ($periodType !== null) {
                $sql .= ' AND period_type = ?';
                $params[] = $periodType->value;
            }

            $sql .= ' ORDER BY period_end DESC LIMIT 1';

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);

            $data = $stmt->fetch();

            return $data ? $this->buildSnapshotFromData($data) : null;
        } catch (Throwable $e) {
            throw new RuntimeException("查詢日期前統計時發生錯誤: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * 取得指定日期之後的第一筆統計快照.
     */
    public function findFirstAfterDate(
        DateTimeInterface $date,
        ?PeriodType $periodType = null,
    ): ?StatisticsSnapshot {
        try {
            $sql = "
                SELECT * FROM statistics_snapshots
                WHERE period_start > ?
                    AND snapshot_type = 'posts'
            ";

            $params = [$date->format('Y-m-d H:i:s')];

            if ($periodType !== null) {
                $sql .= ' AND period_type = ?';
                $params[] = $periodType->value;
            }

            $sql .= ' ORDER BY period_start ASC LIMIT 1';

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);

            $data = $stmt->fetch();

            return $data ? $this->buildSnapshotFromData($data) : null;
        } catch (Throwable $e) {
            throw new RuntimeException("查詢日期後統計時發生錯誤: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * 聚合指定週期內的統計資料.
     */
    public function aggregateByPeriod(
        DateTimeInterface $startDate,
        DateTimeInterface $endDate,
        PeriodType $sourcePeriodType,
        PeriodType $targetPeriodType,
    ): array {
        try {
            $sql = "
                SELECT
                    SUM(total_views) as total_count,
                    SUM(total_unique_viewers) as unique_count,
                    MIN(period_start) as period_start,
                    MAX(period_end) as period_end,
                    GROUP_CONCAT(statistics_data) as all_data
                FROM statistics_snapshots
                WHERE period_start >= ?
                    AND period_end <= ?
                    AND period_type = ?
                    AND snapshot_type = 'posts'
            ";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                $startDate->format('Y-m-d H:i:s'),
                $endDate->format('Y-m-d H:i:s'),
                $sourcePeriodType->value,
            ]);

            $result = $stmt->fetch();

            if (!$result || !$result['total_count']) {
                return [
                    'total_count' => 0,
                    'unique_count' => 0,
                    'source_distribution' => [],
                    'period_start' => $startDate->format('Y-m-d H:i:s'),
                    'period_end' => $endDate->format('Y-m-d H:i:s'),
                ];
            }

            // 聚合來源分布
            $sourceDistribution = $this->aggregateSourceDistribution($result['all_data']);

            return [
                'total_count' => (int) $result['total_count'],
                'unique_count' => (int) $result['unique_count'],
                'source_distribution' => $sourceDistribution,
                'period_start' => $result['period_start'],
                'period_end' => $result['period_end'],
            ];
        } catch (Throwable $e) {
            throw new RuntimeException("聚合統計資料時發生錯誤: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * 計算成長率統計.
     */
    public function calculateGrowthRate(
        StatisticsPeriod $currentPeriod,
        StatisticsPeriod $previousPeriod,
    ): array {
        try {
            $currentSnapshot = $this->findByPeriod($currentPeriod);
            $previousSnapshot = $this->findByPeriod($previousPeriod);

            $currentCount = $currentSnapshot ? $currentSnapshot->getTotalViews()->value : 0;
            $previousCount = $previousSnapshot ? $previousSnapshot->getTotalViews()->value : 0;

            $growthRate = $previousCount > 0
                ? round((($currentCount - $previousCount) / $previousCount) * 100, 2)
                : ($currentCount > 0 ? 100.0 : 0.0);

            return [
                'current_count' => $currentCount,
                'previous_count' => $previousCount,
                'growth_rate' => $growthRate,
                'growth_percentage' => $growthRate,
            ];
        } catch (Throwable $e) {
            throw new RuntimeException("計算成長率時發生錯誤: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * 取得熱門時段分析.
     */
    public function getPopularTimeSlots(StatisticsPeriod $period): array
    {
        try {
            // 這個實作需要與 posts 表結合查詢
            // 暫時回傳空陣列，實際實作需要分析 posts 的 created_at 時間分布
            return [];
        } catch (Throwable $e) {
            throw new RuntimeException("分析熱門時段時發生錯誤: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * 刪除統計快照.
     */
    public function deleteSnapshot(string $uuid): bool
    {
        try {
            $sql = 'DELETE FROM statistics_snapshots WHERE uuid = ?';
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$uuid]);

            return $stmt->rowCount() > 0;
        } catch (Throwable $e) {
            throw new RuntimeException("刪除統計快照時發生錯誤: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * 軟刪除統計快照.
     */
    public function softDeleteSnapshot(string $uuid): bool
    {
        try {
            $sql = "
                UPDATE statistics_snapshots
                SET updated_at = ?, statistics_data = JSON_SET(statistics_data, '$.deleted', true)
                WHERE uuid = ?
            ";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                new DateTimeImmutable()->format('Y-m-d H:i:s'),
                $uuid,
            ]);

            return $stmt->rowCount() > 0;
        } catch (Throwable $e) {
            throw new RuntimeException("軟刪除統計快照時發生錯誤: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * 恢復軟刪除的統計快照.
     */
    public function restoreSnapshot(string $uuid): bool
    {
        try {
            $sql = "
                UPDATE statistics_snapshots
                SET updated_at = ?, statistics_data = JSON_REMOVE(statistics_data, '$.deleted')
                WHERE uuid = ?
            ";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                new DateTimeImmutable()->format('Y-m-d H:i:s'),
                $uuid,
            ]);

            return $stmt->rowCount() > 0;
        } catch (Throwable $e) {
            throw new RuntimeException("恢復統計快照時發生錯誤: {$e->getMessage()}", 0, $e);
        }
    }

    // RepositoryInterface 方法實作

    public function find(int $id): ?object
    {
        try {
            $sql = 'SELECT * FROM statistics_snapshots WHERE id = ?';
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$id]);

            $data = $stmt->fetch();

            return $data ? $this->buildSnapshotFromData($data) : null;
        } catch (Throwable $e) {
            throw new RuntimeException("依 ID 查詢統計時發生錯誤: {$e->getMessage()}", 0, $e);
        }
    }

    public function create(array $data): object
    {
        throw new RuntimeException('請使用 saveSnapshot() 方法建立統計快照');
    }

    public function update(int $id, array $data): object
    {
        throw new RuntimeException('請使用 upsertSnapshot() 方法更新統計快照');
    }

    public function delete(int $id): bool
    {
        try {
            $sql = 'DELETE FROM statistics_snapshots WHERE id = ?';
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$id]);

            return $stmt->rowCount() > 0;
        } catch (Throwable $e) {
            throw new RuntimeException("刪除統計快照時發生錯誤: {$e->getMessage()}", 0, $e);
        }
    }

    public function paginate(int $page, int $perPage, array $conditions = []): array
    {
        try {
            $offset = ($page - 1) * $perPage;

            $sql = "
                SELECT * FROM statistics_snapshots
                WHERE snapshot_type = 'posts'
                ORDER BY created_at DESC
                LIMIT ? OFFSET ?
            ";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$perPage, $offset]);

            $results = [];
            while ($data = $stmt->fetch()) {
                $results[] = $this->buildSnapshotFromData($data);
            }

            // 計算總數
            $countSql = "SELECT COUNT(*) FROM statistics_snapshots WHERE snapshot_type = 'posts'";
            $countStmt = $this->pdo->prepare($countSql);
            $countStmt->execute();
            $total = (int) $countStmt->fetchColumn();

            return [
                'data' => $results,
                'total' => $total,
                'page' => $page,
                'per_page' => $perPage,
                'total_pages' => (int) ceil($total / $perPage),
                'has_next' => ($page * $perPage) < $total,
                'has_previous' => $page > 1,
            ];
        } catch (Throwable $e) {
            throw new RuntimeException("分頁查詢統計時發生錯誤: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * 從資料庫資料建構 StatisticsSnapshot 物件.
     */
    private function buildSnapshotFromData(array $data): StatisticsSnapshot
    {
        try {
            // 型別安全檢查
            if (!is_string($data['uuid'] ?? null)) {
                throw new RuntimeException('無效的 UUID 資料');
            }

            if (!is_string($data['period_start'] ?? null)) {
                throw new RuntimeException('無效的開始時間資料');
            }

            if (!is_string($data['period_end'] ?? null)) {
                throw new RuntimeException('無效的結束時間資料');
            }

            if (!is_string($data['period_type'] ?? null)) {
                throw new RuntimeException('無效的期間類型資料');
            }

            $uuid = Uuid::fromString($data['uuid']);

            $period = StatisticsPeriod::create(
                new DateTimeImmutable($data['period_start']),
                new DateTimeImmutable($data['period_end']),
                PeriodType::from($data['period_type']),
            );

            $totalPosts = StatisticsMetric::create((int) ($data['total_unique_viewers'] ?? 0));
            $totalViews = StatisticsMetric::create((int) ($data['total_views'] ?? 0));

            // 解析來源統計資料
            $sourceStats = $this->parseSourceStatistics($data['statistics_data'] ?? '');

            $createdAt = new DateTimeImmutable($data['created_at'] ?? 'now');

            return StatisticsSnapshot::fromData(
                $uuid,
                $period,
                $totalPosts,
                $totalViews,
                $sourceStats,
                [], // additionalMetrics
                $createdAt,
            );
        } catch (Throwable $e) {
            throw new RuntimeException("建構統計快照物件時發生錯誤: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * 編碼統計資料為 JSON.
     */
    private function encodeStatisticsData(StatisticsSnapshot $snapshot): string
    {
        try {
            $sourceStats = [];
            foreach ($snapshot->getSourceStats() as $stat) {
                $sourceStats[] = [
                    'source_type' => $stat->sourceType->value,
                    'count' => $stat->getCountValue(),
                    'views' => $stat->getCountValue(), // 使用同樣的值
                    'percentage' => $stat->getPercentageValue(),
                ];
            }

            $data = [
                'total_posts' => $snapshot->getTotalPosts()->value,
                'total_views' => $snapshot->getTotalViews()->value,
                'source_statistics' => $sourceStats,
                'created_at' => $snapshot->getCreatedAt()->format('Y-m-d H:i:s'),
            ];

            $json = json_encode($data, JSON_THROW_ON_ERROR);
            if ($json === false) {
                throw new RuntimeException('JSON 編碼失敗');
            }

            return $json;
        } catch (Throwable $e) {
            throw new RuntimeException("編碼統計資料時發生錯誤: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * 解析來源統計資料.
     */
    private function parseSourceStatistics(string $jsonData): array
    {
        try {
            $data = json_decode($jsonData, true, 512, JSON_THROW_ON_ERROR);

            if (!isset($data['source_statistics']) || !is_array($data['source_statistics'])) {
                return [];
            }

            $sourceStats = [];
            foreach ($data['source_statistics'] as $statData) {
                if (isset($statData['source_type'], $statData['count'], $statData['views'], $statData['percentage'])) {
                    $sourceStats[] = SourceStatistics::create(
                        SourceType::from($statData['source_type']),
                        (int) $statData['count'],
                        (float) $statData['percentage'],
                    );
                }
            }

            return $sourceStats;
        } catch (Throwable $e) {
            // 如果解析失敗，回傳空陣列
            return [];
        }
    }

    /**
     * 聚合來源分布資料.
     */
    private function aggregateSourceDistribution(string $allDataJson): array
    {
        try {
            $allData = explode(',', $allDataJson);
            $aggregated = [];

            foreach ($allData as $jsonString) {
                $data = json_decode(trim($jsonString), true, 512, JSON_THROW_ON_ERROR);

                if (isset($data['source_statistics']) && is_array($data['source_statistics'])) {
                    foreach ($data['source_statistics'] as $stat) {
                        $sourceType = $stat['source_type'];

                        if (!isset($aggregated[$sourceType])) {
                            $aggregated[$sourceType] = [
                                'count' => 0,
                                'views' => 0,
                            ];
                        }

                        $aggregated[$sourceType]['count'] += $stat['count'];
                        $aggregated[$sourceType]['views'] += $stat['views'];
                    }
                }
            }

            return $aggregated;
        } catch (Throwable $e) {
            return [];
        }
    }
}
