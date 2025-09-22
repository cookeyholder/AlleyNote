<?php

declare(strict_types=1);

namespace App\Infrastructure\Statistics\Repositories;

use App\Domains\Statistics\Contracts\StatisticsRepositoryInterface;
use App\Domains\Statistics\Entities\StatisticsSnapshot;
use App\Domains\Statistics\ValueObjects\StatisticsPeriod;
use DateTimeImmutable;
use DateTimeInterface;
use Exception;
use InvalidArgumentException;
use PDO;
use PDOException;
use PDOStatement;
use RuntimeException;

/**
 * 統計快照 Repository 實作.
 *
 * 提供統計快照的資料庫存取功能，使用原生 SQL 最佳化效能。
 * 支援 JSON 資料序列化/反序列化，以及複雜的統計查詢。
 */
final class StatisticsRepository implements StatisticsRepositoryInterface
{
    // SQL 查詢常數
    private const SELECT_FIELDS = 'id, uuid, snapshot_type, period_type, period_start, period_end, statistics_data, metadata, expires_at, total_views, total_unique_viewers, created_at, updated_at';

    private const SQL_FIND_BY_ID = 'SELECT ' . self::SELECT_FIELDS . ' FROM statistics_snapshots WHERE id = :id';

    private const SQL_FIND_BY_UUID = 'SELECT ' . self::SELECT_FIELDS . ' FROM statistics_snapshots WHERE uuid = :uuid';

    private const SQL_FIND_BY_TYPE_AND_PERIOD = 'SELECT ' . self::SELECT_FIELDS . ' FROM statistics_snapshots WHERE snapshot_type = :snapshot_type AND period_type = :period_type AND period_start = :period_start AND period_end = :period_end LIMIT 1';

    private const SQL_FIND_LATEST_BY_TYPE = 'SELECT ' . self::SELECT_FIELDS . ' FROM statistics_snapshots WHERE snapshot_type = :snapshot_type ORDER BY created_at DESC LIMIT 1';

    private const SQL_FIND_BY_TYPE_AND_DATE_RANGE = 'SELECT ' . self::SELECT_FIELDS . ' FROM statistics_snapshots WHERE snapshot_type = :snapshot_type AND period_start >= :start_date AND period_end <= :end_date ORDER BY period_start ASC';

    private const SQL_FIND_EXPIRED = 'SELECT ' . self::SELECT_FIELDS . ' FROM statistics_snapshots WHERE expires_at IS NOT NULL AND expires_at < :before_date ORDER BY expires_at ASC';

    private const SQL_INSERT = 'INSERT INTO statistics_snapshots (uuid, snapshot_type, period_type, period_start, period_end, statistics_data, metadata, expires_at, total_views, total_unique_viewers, created_at, updated_at) VALUES (:uuid, :snapshot_type, :period_type, :period_start, :period_end, :statistics_data, :metadata, :expires_at, :total_views, :total_unique_viewers, :created_at, :updated_at)';

    private const SQL_UPDATE = 'UPDATE statistics_snapshots SET snapshot_type = :snapshot_type, period_type = :period_type, period_start = :period_start, period_end = :period_end, statistics_data = :statistics_data, metadata = :metadata, expires_at = :expires_at, total_views = :total_views, total_unique_viewers = :total_unique_viewers, updated_at = :updated_at WHERE id = :id';

    private const SQL_DELETE = 'DELETE FROM statistics_snapshots WHERE id = :id';

    private const SQL_DELETE_EXPIRED = 'DELETE FROM statistics_snapshots WHERE expires_at IS NOT NULL AND expires_at < :before_date';

    private const SQL_EXISTS = 'SELECT COUNT(*) FROM statistics_snapshots WHERE snapshot_type = :snapshot_type AND period_type = :period_type AND period_start = :period_start AND period_end = :period_end';

    private const SQL_COUNT = 'SELECT COUNT(*) FROM statistics_snapshots';

    private const SQL_COUNT_BY_TYPE = 'SELECT COUNT(*) FROM statistics_snapshots WHERE snapshot_type = :snapshot_type';

    private const SQL_FIND_BY_TYPE_WITH_PAGINATION = 'SELECT ' . self::SELECT_FIELDS . ' FROM statistics_snapshots WHERE snapshot_type = :snapshot_type ORDER BY :order_by :direction LIMIT :limit OFFSET :offset';

    public function __construct(
        private readonly PDO $db,
    ) {}

    public function findById(int $id): ?StatisticsSnapshot
    {
        try {
            $stmt = $this->db->prepare(self::SQL_FIND_BY_ID);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();

            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!is_array($row)) {
                return null;
            }

            /** @phpstan-ignore-next-line argument.type */
            return $this->mapRowToEntity($row);
        } catch (PDOException $e) {
            throw new RuntimeException("查詢統計快照失敗 (ID: {$id}): " . $e->getMessage(), 0, $e);
        }
    }

    public function findByUuid(string $uuid): ?StatisticsSnapshot
    {
        if (empty(trim($uuid))) {
            throw new InvalidArgumentException('UUID 不能為空');
        }

        try {
            $stmt = $this->db->prepare(self::SQL_FIND_BY_UUID);
            $stmt->bindValue(':uuid', $uuid, PDO::PARAM_STR);
            $stmt->execute();

            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!is_array($row)) {
                return null;
            }

            /** @phpstan-ignore-next-line argument.type */
            return $this->mapRowToEntity($row);
        } catch (PDOException $e) {
            throw new RuntimeException("查詢統計快照失敗 (UUID: {$uuid}): " . $e->getMessage(), 0, $e);
        }
    }

    public function findByTypeAndPeriod(string $snapshotType, StatisticsPeriod $period): ?StatisticsSnapshot
    {
        if (empty(trim($snapshotType))) {
            throw new InvalidArgumentException('快照類型不能為空');
        }

        try {
            $stmt = $this->db->prepare(self::SQL_FIND_BY_TYPE_AND_PERIOD);
            $stmt->bindValue(':snapshot_type', $snapshotType, PDO::PARAM_STR);
            $stmt->bindValue(':period_type', $period->type->value, PDO::PARAM_STR);
            $stmt->bindValue(':period_start', $period->startTime->format('Y-m-d H:i:s'), PDO::PARAM_STR);
            $stmt->bindValue(':period_end', $period->endTime->format('Y-m-d H:i:s'), PDO::PARAM_STR);
            $stmt->execute();

            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!is_array($row)) {
                return null;
            }

            /** @phpstan-ignore-next-line argument.type */
            return $this->mapRowToEntity($row);
        } catch (PDOException $e) {
            throw new RuntimeException("查詢統計快照失敗 (類型: {$snapshotType}): " . $e->getMessage(), 0, $e);
        }
    }

    public function findLatestByType(string $snapshotType): ?StatisticsSnapshot
    {
        if (empty(trim($snapshotType))) {
            throw new InvalidArgumentException('快照類型不能為空');
        }

        try {
            $stmt = $this->db->prepare(self::SQL_FIND_LATEST_BY_TYPE);
            $stmt->bindValue(':snapshot_type', $snapshotType, PDO::PARAM_STR);
            $stmt->execute();

            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!is_array($row)) {
                return null;
            }

            /** @phpstan-ignore-next-line argument.type */
            return $this->mapRowToEntity($row);
        } catch (PDOException $e) {
            throw new RuntimeException("查詢最新統計快照失敗 (類型: {$snapshotType}): " . $e->getMessage(), 0, $e);
        }
    }

    public function findByTypeAndDateRange(
        string $snapshotType,
        DateTimeInterface $startDate,
        DateTimeInterface $endDate,
    ): array {
        if (empty(trim($snapshotType))) {
            throw new InvalidArgumentException('快照類型不能為空');
        }

        if ($startDate >= $endDate) {
            throw new InvalidArgumentException('開始日期必須小於結束日期');
        }

        try {
            $stmt = $this->db->prepare(self::SQL_FIND_BY_TYPE_AND_DATE_RANGE);
            $stmt->bindValue(':snapshot_type', $snapshotType, PDO::PARAM_STR);
            $stmt->bindValue(':start_date', $startDate->format('Y-m-d H:i:s'), PDO::PARAM_STR);
            $stmt->bindValue(':end_date', $endDate->format('Y-m-d H:i:s'), PDO::PARAM_STR);
            $stmt->execute();

            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return array_map([$this, 'mapRowToEntity'], $rows);
        } catch (PDOException $e) {
            throw new RuntimeException('查詢日期範圍統計快照失敗: ' . $e->getMessage(), 0, $e);
        }
    }

    public function findExpiredSnapshots(?DateTimeInterface $beforeDate = null): array
    {
        $beforeDate ??= new DateTimeImmutable();

        try {
            $stmt = $this->db->prepare(self::SQL_FIND_EXPIRED);
            $stmt->bindValue(':before_date', $beforeDate->format('Y-m-d H:i:s'), PDO::PARAM_STR);
            $stmt->execute();

            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return array_map([$this, 'mapRowToEntity'], $rows);
        } catch (PDOException $e) {
            throw new RuntimeException('查詢過期統計快照失敗: ' . $e->getMessage(), 0, $e);
        }
    }

    public function save(StatisticsSnapshot $snapshot): StatisticsSnapshot
    {
        try {
            $this->db->beginTransaction();

            $stmt = $this->db->prepare(self::SQL_INSERT);
            $this->bindSnapshotParams($stmt, $snapshot);
            $stmt->execute();

            $id = (int) $this->db->lastInsertId();

            $this->db->commit();

            // 返回帶有 ID 的快照實體
            return $this->findById($id) ?? $snapshot;
        } catch (PDOException $e) {
            $this->db->rollBack();

            throw new RuntimeException('儲存統計快照失敗: ' . $e->getMessage(), 0, $e);
        }
    }

    public function update(StatisticsSnapshot $snapshot): StatisticsSnapshot
    {
        if ($snapshot->getId() <= 0) {
            throw new InvalidArgumentException('無法更新沒有 ID 的統計快照');
        }

        try {
            $this->db->beginTransaction();

            $stmt = $this->db->prepare(self::SQL_UPDATE);
            $this->bindSnapshotParams($stmt, $snapshot);
            $stmt->bindValue(':id', $snapshot->getId(), PDO::PARAM_INT);

            $affectedRows = $stmt->execute();

            if ($stmt->rowCount() === 0) {
                $this->db->rollBack();

                throw new RuntimeException("統計快照不存在或更新失敗 (ID: {$snapshot->getId()})");
            }

            $this->db->commit();

            return $this->findById($snapshot->getId()) ?? $snapshot;
        } catch (PDOException $e) {
            $this->db->rollBack();

            throw new RuntimeException("更新統計快照失敗 (ID: {$snapshot->getId()}): " . $e->getMessage(), 0, $e);
        }
    }

    public function delete(StatisticsSnapshot $snapshot): bool
    {
        if ($snapshot->getId() <= 0) {
            throw new InvalidArgumentException('無法刪除沒有 ID 的統計快照');
        }

        return $this->deleteById($snapshot->getId());
    }

    public function deleteById(int $id): bool
    {
        try {
            $stmt = $this->db->prepare(self::SQL_DELETE);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            throw new RuntimeException("刪除統計快照失敗 (ID: {$id}): " . $e->getMessage(), 0, $e);
        }
    }

    public function deleteExpiredSnapshots(?DateTimeInterface $beforeDate = null): int
    {
        $beforeDate ??= new DateTimeImmutable();

        try {
            $stmt = $this->db->prepare(self::SQL_DELETE_EXPIRED);
            $stmt->bindValue(':before_date', $beforeDate->format('Y-m-d H:i:s'), PDO::PARAM_STR);
            $stmt->execute();

            return $stmt->rowCount();
        } catch (PDOException $e) {
            throw new RuntimeException('刪除過期統計快照失敗: ' . $e->getMessage(), 0, $e);
        }
    }

    public function exists(string $snapshotType, StatisticsPeriod $period): bool
    {
        if (empty(trim($snapshotType))) {
            throw new InvalidArgumentException('快照類型不能為空');
        }

        try {
            $stmt = $this->db->prepare(self::SQL_EXISTS);
            $stmt->bindValue(':snapshot_type', $snapshotType, PDO::PARAM_STR);
            $stmt->bindValue(':period_type', $period->type->value, PDO::PARAM_STR);
            $stmt->bindValue(':period_start', $period->startTime->format('Y-m-d H:i:s'), PDO::PARAM_STR);
            $stmt->bindValue(':period_end', $period->endTime->format('Y-m-d H:i:s'), PDO::PARAM_STR);
            $stmt->execute();

            return $stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            throw new RuntimeException('檢查統計快照存在性失敗: ' . $e->getMessage(), 0, $e);
        }
    }

    public function count(?string $snapshotType = null): int
    {
        try {
            if ($snapshotType === null) {
                $stmt = $this->db->prepare(self::SQL_COUNT);
            } else {
                $stmt = $this->db->prepare(self::SQL_COUNT_BY_TYPE);
                $stmt->bindValue(':snapshot_type', $snapshotType, PDO::PARAM_STR);
            }

            $stmt->execute();

            return (int) $stmt->fetchColumn();
        } catch (PDOException $e) {
            throw new RuntimeException('計算統計快照數量失敗: ' . $e->getMessage(), 0, $e);
        }
    }

    public function findByTypeWithPagination(
        string $snapshotType,
        int $page = 1,
        int $limit = 20,
        string $orderBy = 'created_at',
        string $direction = 'desc',
    ): array {
        if (empty(trim($snapshotType))) {
            throw new InvalidArgumentException('快照類型不能為空');
        }

        if ($page < 1) {
            throw new InvalidArgumentException('頁碼必須大於 0');
        }

        if ($limit < 1 || $limit > 1000) {
            throw new InvalidArgumentException('每頁數量必須在 1-1000 之間');
        }

        if (!in_array(strtolower($direction), ['asc', 'desc'], true)) {
            throw new InvalidArgumentException('排序方向只能是 asc 或 desc');
        }

        // 允許的排序欄位白名單
        $allowedOrderBy = ['id', 'created_at', 'updated_at', 'period_start', 'period_end'];
        if (!in_array($orderBy, $allowedOrderBy, true)) {
            throw new InvalidArgumentException('不支援的排序欄位: ' . $orderBy);
        }

        $offset = ($page - 1) * $limit;

        try {
            // 由於 PDO 不支援在 prepared statement 中綁定欄位名，我們需要手動構建 SQL
            $sql = str_replace(
                [':order_by', ':direction'],
                [$orderBy, strtoupper($direction)],
                self::SQL_FIND_BY_TYPE_WITH_PAGINATION,
            );

            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':snapshot_type', $snapshotType, PDO::PARAM_STR);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();

            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return array_map([$this, 'mapRowToEntity'], $rows);
        } catch (PDOException $e) {
            throw new RuntimeException('分頁查詢統計快照失敗: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * 將資料庫記錄映射為實體物件.
     *
     * @param array<string, mixed> $row
     * @phpstan-ignore-next-line cast.int, cast.string
     */
    private function mapRowToEntity(array $row): StatisticsSnapshot
    {
        try {
            // 確保所有需要的鍵都存在並進行型態轉換
            /** @phpstan-ignore-next-line cast.int */
            $id = isset($row['id']) ? (int) $row['id'] : 0;
            /** @phpstan-ignore-next-line cast.string */
            $uuid = isset($row['uuid']) ? (string) $row['uuid'] : '';
            /** @phpstan-ignore-next-line cast.string */
            $snapshotType = isset($row['snapshot_type']) ? (string) $row['snapshot_type'] : '';
            /** @phpstan-ignore-next-line cast.string */
            $periodType = isset($row['period_type']) ? (string) $row['period_type'] : '';
            /** @phpstan-ignore-next-line cast.string */
            $periodStart = isset($row['period_start']) ? (string) $row['period_start'] : '';
            /** @phpstan-ignore-next-line cast.string */
            $periodEnd = isset($row['period_end']) ? (string) $row['period_end'] : '';
            /** @phpstan-ignore-next-line cast.string */
            $statisticsData = isset($row['statistics_data']) ? (string) $row['statistics_data'] : '{}';
            /** @phpstan-ignore-next-line cast.string */
            $metadata = isset($row['metadata']) ? (string) $row['metadata'] : '{}';
            $expiresAt = $row['expires_at'] ?? null;
            /** @phpstan-ignore-next-line cast.string */
            $createdAt = isset($row['created_at']) ? (string) $row['created_at'] : '';
            /** @phpstan-ignore-next-line cast.string */
            $updatedAt = isset($row['updated_at']) ? (string) $row['updated_at'] : '';

            // 準備實體建構所需的資料陣列
            $data = [
                'id' => $id,
                'uuid' => $uuid,
                'snapshot_type' => $snapshotType,
                'period_type' => $periodType,
                'period_start' => $periodStart,
                'period_end' => $periodEnd,
                'statistics_data' => $statisticsData,
                'metadata' => $metadata,
                'expires_at' => $expiresAt,
                'created_at' => $createdAt,
                'updated_at' => $updatedAt,
            ];

            return StatisticsSnapshot::fromArray($data);
        } catch (Exception $e) {
            /** @phpstan-ignore-next-line cast.string */
            $idStr = isset($row['id']) ? (string) $row['id'] : 'unknown';

            throw new RuntimeException("實體映射失敗 (ID: {$idStr}): " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * 綁定統計快照參數到 PDO statement.
     */
    private function bindSnapshotParams(PDOStatement $stmt, StatisticsSnapshot $snapshot): void
    {
        $now = new DateTimeImmutable();
        $snapshotArray = $snapshot->toArray();

        $stmt->bindValue(':uuid', $snapshot->getUuid(), PDO::PARAM_STR);
        $stmt->bindValue(':snapshot_type', $snapshot->getSnapshotType(), PDO::PARAM_STR);
        $stmt->bindValue(':period_type', $snapshot->getPeriod()->type->value, PDO::PARAM_STR);
        $stmt->bindValue(':period_start', $snapshot->getPeriod()->startTime->format('Y-m-d H:i:s'), PDO::PARAM_STR);
        $stmt->bindValue(':period_end', $snapshot->getPeriod()->endTime->format('Y-m-d H:i:s'), PDO::PARAM_STR);
        $stmt->bindValue(':statistics_data', $snapshotArray['statistics_data'], PDO::PARAM_STR);
        $stmt->bindValue(':metadata', $snapshotArray['metadata'], PDO::PARAM_STR);
        $stmt->bindValue(':expires_at', $snapshot->getExpiresAt()?->format('Y-m-d H:i:s'), PDO::PARAM_STR);
        $stmt->bindValue(':total_views', 0, PDO::PARAM_INT); // 預設值，實體中沒有此欄位
        $stmt->bindValue(':total_unique_viewers', 0, PDO::PARAM_INT); // 預設值，實體中沒有此欄位
        $stmt->bindValue(':created_at', $snapshot->getCreatedAt()->format('Y-m-d H:i:s'), PDO::PARAM_STR);
        $stmt->bindValue(':updated_at', $now->format('Y-m-d H:i:s'), PDO::PARAM_STR);
    }
}
