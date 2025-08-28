<?php

declare(strict_types=1);

namespace App\Domains\Security\Repositories;

use App\Domains\Security\Contracts\ActivityLogRepositoryInterface;
use App\Domains\Security\DTOs\CreateActivityLogDTO;
use App\Domains\Security\Entities\ActivityLog;
use App\Domains\Security\Enums\ActivityCategory;
use App\Domains\Security\Enums\ActivityType;
use PDO;
use PDOException;

/**
 * 活動記錄存儲庫實現
 * 
 * 負責活動記錄的 CRUD 操作和複雜查詢
 */
class ActivityLogRepository implements ActivityLogRepositoryInterface
{
    private const TABLE_NAME = 'user_activity_logs';

    private const SELECT_FIELDS = 'id, uuid, user_id, session_id, action_type, action_category, 
        target_type, target_id, status, description, metadata, ip_address, user_agent, 
        request_method, request_path, created_at, occurred_at';

    public function __construct(
        private PDO $db
    ) {
        // 設定 SQLite 外鍵約束
        $this->db->exec('PRAGMA foreign_keys = ON');
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    /**
     * 建立活動記錄
     */
    public function create(CreateActivityLogDTO $dto): ?array
    {
        try {
            $this->db->beginTransaction();

            $entity = ActivityLog::fromDTO(
                actionType: $dto->getActionType(),
                userId: $dto->getUserId(),
                sessionId: $dto->getSessionId(),
                status: $dto->getStatus(),
                targetType: $dto->getTargetType(),
                targetId: $dto->getTargetId(),
                description: $dto->getDescription(),
                metadata: $dto->getMetadata(),
                ipAddress: $dto->getIpAddress(),
                userAgent: $dto->getUserAgent(),
                requestMethod: $dto->getRequestMethod(),
                requestPath: $dto->getRequestPath(),
                occurredAt: $dto->getOccurredAt()
            );

            $sql = "INSERT INTO " . self::TABLE_NAME . " (
                uuid, user_id, session_id, action_type, action_category, target_type, target_id,
                status, description, metadata, ip_address, user_agent, request_method, request_path,
                created_at, occurred_at
            ) VALUES (
                :uuid, :user_id, :session_id, :action_type, :action_category, :target_type, :target_id,
                :status, :description, :metadata, :ip_address, :user_agent, :request_method, :request_path,
                :created_at, :occurred_at
            )";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':uuid' => $entity->getUuid(),
                ':user_id' => $entity->getUserId(),
                ':session_id' => $entity->getSessionId(),
                ':action_type' => $entity->getActionType()->value,
                ':action_category' => $entity->getActionCategory()->value,
                ':target_type' => $entity->getTargetType(),
                ':target_id' => $entity->getTargetId(),
                ':status' => $entity->getStatus()->value,
                ':description' => $entity->getDescription(),
                ':metadata' => $entity->getMetadataAsJson(),
                ':ip_address' => $entity->getIpAddress(),
                ':user_agent' => $entity->getUserAgent(),
                ':request_method' => $entity->getRequestMethod(),
                ':request_path' => $entity->getRequestPath(),
                ':created_at' => $entity->getCreatedAt()->format('Y-m-d H:i:s'),
                ':occurred_at' => $entity->getOccurredAt()->format('Y-m-d H:i:s'),
            ]);

            // 取得剛插入的記錄 ID
            $insertId = (int) $this->db->lastInsertId();

            $this->db->commit();

            // 回傳完整的實體資料
            return $this->findById($insertId);
        } catch (PDOException $e) {
            $this->db->rollBack();
            throw new \RuntimeException('Failed to create activity log: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * 批次建立多個活動記錄
     */
    public function createBatch(array $dtos): int
    {
        if (empty($dtos)) {
            return 0;
        }

        try {
            $this->db->beginTransaction();

            $sql = "INSERT INTO " . self::TABLE_NAME . " (
                uuid, user_id, session_id, action_type, action_category, target_type, target_id,
                status, description, metadata, ip_address, user_agent, request_method, request_path,
                created_at, occurred_at
            ) VALUES (
                :uuid, :user_id, :session_id, :action_type, :action_category, :target_type, :target_id,
                :status, :description, :metadata, :ip_address, :user_agent, :request_method, :request_path,
                :created_at, :occurred_at
            )";

            $stmt = $this->db->prepare($sql);
            $count = 0;

            foreach ($dtos as $dto) {
                if (!$dto instanceof CreateActivityLogDTO) {
                    throw new \InvalidArgumentException('All items must be CreateActivityLogDTO instances');
                }

                $entity = ActivityLog::fromDTO(
                    actionType: $dto->getActionType(),
                    userId: $dto->getUserId(),
                    sessionId: $dto->getSessionId(),
                    status: $dto->getStatus(),
                    targetType: $dto->getTargetType(),
                    targetId: $dto->getTargetId(),
                    description: $dto->getDescription(),
                    metadata: $dto->getMetadata(),
                    ipAddress: $dto->getIpAddress(),
                    userAgent: $dto->getUserAgent(),
                    requestMethod: $dto->getRequestMethod(),
                    requestPath: $dto->getRequestPath(),
                    occurredAt: $dto->getOccurredAt()
                );

                $stmt->execute([
                    ':uuid' => $entity->getUuid(),
                    ':user_id' => $entity->getUserId(),
                    ':session_id' => $entity->getSessionId(),
                    ':action_type' => $entity->getActionType()->value,
                    ':action_category' => $entity->getActionCategory()->value,
                    ':target_type' => $entity->getTargetType(),
                    ':target_id' => $entity->getTargetId(),
                    ':status' => $entity->getStatus()->value,
                    ':description' => $entity->getDescription(),
                    ':metadata' => $entity->getMetadataAsJson(),
                    ':ip_address' => $entity->getIpAddress(),
                    ':user_agent' => $entity->getUserAgent(),
                    ':request_method' => $entity->getRequestMethod(),
                    ':request_path' => $entity->getRequestPath(),
                    ':created_at' => $entity->getCreatedAt()->format('Y-m-d H:i:s'),
                    ':occurred_at' => $entity->getOccurredAt()->format('Y-m-d H:i:s'),
                ]);

                $count++;
            }

            $this->db->commit();
            return $count;
        } catch (PDOException $e) {
            $this->db->rollBack();
            throw new \RuntimeException('Failed to create batch activity logs: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * 根據 ID 查詢活動記錄
     */
    public function findById(int $id): ?array
    {
        $sql = "SELECT " . self::SELECT_FIELDS . " FROM " . self::TABLE_NAME . " WHERE id = :id";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);

        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$data) {
            return null;
        }

        $entity = ActivityLog::fromDatabaseRow($data);
        return $entity->toArray();
    }

    /**
     * 根據 UUID 查詢活動記錄
     */
    public function findByUuid(string $uuid): ?array
    {
        $sql = "SELECT " . self::SELECT_FIELDS . " FROM " . self::TABLE_NAME . " WHERE uuid = :uuid";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':uuid' => $uuid]);

        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$data) {
            return null;
        }

        $entity = ActivityLog::fromDatabaseRow($data);
        return $entity->toArray();
    }

    /**
     * 查詢使用者的活動記錄
     */
    public function findByUser(
        int $userId,
        int $limit = 50,
        int $offset = 0,
        ?ActivityCategory $category = null,
        ?ActivityType $actionType = null
    ): array {
        $conditions = ['user_id = :user_id'];
        $params = [':user_id' => $userId];

        if ($category !== null) {
            $conditions[] = 'action_category = :category';
            $params[':category'] = $category->value;
        }

        if ($actionType !== null) {
            $conditions[] = 'action_type = :action_type';
            $params[':action_type'] = $actionType->value;
        }

        $sql = "SELECT " . self::SELECT_FIELDS . " FROM " . self::TABLE_NAME . "
                WHERE " . implode(' AND ', $conditions) . "
                ORDER BY occurred_at DESC 
                LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);

        foreach ($params as $param => $value) {
            $stmt->bindValue($param, $value);
        }

        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

        $stmt->execute();

        $results = [];
        while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $entity = ActivityLog::fromDatabaseRow($data);
            $results[] = $entity->toArray();
        }

        return $results;
    }

    /**
     * 查詢指定時間範圍的活動記錄
     */
    public function findByTimeRange(
        \DateTimeInterface $startTime,
        \DateTimeInterface $endTime,
        int $limit = 100,
        int $offset = 0,
        ?ActivityCategory $category = null
    ): array {
        $conditions = ['occurred_at BETWEEN :start_time AND :end_time'];
        $params = [
            ':start_time' => $startTime->format('Y-m-d H:i:s'),
            ':end_time' => $endTime->format('Y-m-d H:i:s'),
        ];

        if ($category !== null) {
            $conditions[] = 'action_category = :category';
            $params[':category'] = $category->value;
        }

        $sql = "SELECT " . self::SELECT_FIELDS . " FROM " . self::TABLE_NAME . "
                WHERE " . implode(' AND ', $conditions) . "
                ORDER BY occurred_at DESC 
                LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);

        foreach ($params as $param => $value) {
            $stmt->bindValue($param, $value);
        }

        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

        $stmt->execute();

        $results = [];
        while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $entity = ActivityLog::fromDatabaseRow($data);
            $results[] = $entity->toArray();
        }

        return $results;
    }

    /**
     * 查詢安全相關的活動記錄
     */
    public function findSecurityEvents(
        int $limit = 100,
        int $offset = 0,
        ?string $ipAddress = null
    ): array {
        $conditions = ["(action_category = 'security' OR status IN ('failed', 'blocked'))"];
        $params = [];

        if ($ipAddress !== null) {
            $conditions[] = 'ip_address = :ip_address';
            $params[':ip_address'] = $ipAddress;
        }

        $sql = "SELECT " . self::SELECT_FIELDS . " FROM " . self::TABLE_NAME . "
                WHERE " . implode(' AND ', $conditions) . "
                ORDER BY occurred_at DESC 
                LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);

        foreach ($params as $param => $value) {
            $stmt->bindValue($param, $value);
        }

        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

        $stmt->execute();

        $results = [];
        while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $entity = ActivityLog::fromDatabaseRow($data);
            $results[] = $entity->toArray();
        }

        return $results;
    }

    /**
     * 查詢失敗的活動記錄
     */
    public function findFailedActivities(
        int $limit = 100,
        int $offset = 0,
        ?int $userId = null,
        ?ActivityType $actionType = null
    ): array {
        $conditions = ["status = 'failed'"];
        $params = [];

        if ($userId !== null) {
            $conditions[] = 'user_id = :user_id';
            $params[':user_id'] = $userId;
        }

        if ($actionType !== null) {
            $conditions[] = 'action_type = :action_type';
            $params[':action_type'] = $actionType->value;
        }

        $sql = "SELECT " . self::SELECT_FIELDS . " FROM " . self::TABLE_NAME . "
                WHERE " . implode(' AND ', $conditions) . "
                ORDER BY occurred_at DESC 
                LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);

        foreach ($params as $param => $value) {
            $stmt->bindValue($param, $value);
        }

        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

        $stmt->execute();

        $results = [];
        while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $entity = ActivityLog::fromDatabaseRow($data);
            $results[] = $entity->toArray();
        }

        return $results;
    }

    /**
     * 統計活動記錄數量
     */
    public function countByCategory(ActivityCategory $category): int
    {
        $sql = "SELECT COUNT(*) FROM " . self::TABLE_NAME . " WHERE action_category = :category";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':category' => $category->value]);

        return (int) $stmt->fetchColumn();
    }

    /**
     * 統計使用者在指定時間內的活動數量
     */
    public function countUserActivities(
        int $userId,
        \DateTimeInterface $startTime,
        \DateTimeInterface $endTime
    ): int {
        $sql = "SELECT COUNT(*) FROM " . self::TABLE_NAME . " 
                WHERE user_id = :user_id 
                AND occurred_at BETWEEN :start_time AND :end_time";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':user_id' => $userId,
            ':start_time' => $startTime->format('Y-m-d H:i:s'),
            ':end_time' => $endTime->format('Y-m-d H:i:s'),
        ]);

        return (int) $stmt->fetchColumn();
    }

    /**
     * 取得活動統計資料（依類型分組）
     */
    public function getActivityStatistics(
        \DateTimeInterface $startTime,
        \DateTimeInterface $endTime
    ): array {
        $sql = "SELECT action_category, action_type, COUNT(*) as count 
                FROM " . self::TABLE_NAME . " 
                WHERE occurred_at BETWEEN :start_time AND :end_time 
                GROUP BY action_category, action_type 
                ORDER BY count DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':start_time' => $startTime->format('Y-m-d H:i:s'),
            ':end_time' => $endTime->format('Y-m-d H:i:s'),
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * 取得熱門活動類型
     */
    public function getPopularActivityTypes(int $limit = 10): array
    {
        $sql = "SELECT action_type, COUNT(*) as count 
                FROM " . self::TABLE_NAME . " 
                GROUP BY action_type 
                ORDER BY count DESC 
                LIMIT :limit";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * 取得可疑 IP 清單（基於失敗嘗試次數）
     */
    public function getSuspiciousIpAddresses(
        int $failureThreshold = 10,
        ?\DateTimeInterface $timeWindow = null
    ): array {
        $conditions = ["status = 'failed'", "ip_address IS NOT NULL"];
        $params = [];

        if ($timeWindow !== null) {
            $conditions[] = 'occurred_at >= :time_window';
            $params[':time_window'] = $timeWindow->format('Y-m-d H:i:s');
        }

        $sql = "SELECT ip_address, COUNT(*) as failure_count 
                FROM " . self::TABLE_NAME . " 
                WHERE " . implode(' AND ', $conditions) . " 
                GROUP BY ip_address 
                HAVING failure_count >= :threshold 
                ORDER BY failure_count DESC";

        $stmt = $this->db->prepare($sql);

        foreach ($params as $param => $value) {
            $stmt->bindValue($param, $value);
        }

        $stmt->bindValue(':threshold', $failureThreshold, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * 刪除舊的活動記錄
     */
    public function deleteOldRecords(\DateTimeInterface $before): int
    {
        $sql = "DELETE FROM " . self::TABLE_NAME . " WHERE created_at < :before";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':before' => $before->format('Y-m-d H:i:s')]);

        return $stmt->rowCount();
    }

    /**
     * 根據條件刪除記錄
     */
    public function deleteByConditions(array $conditions): int
    {
        if (empty($conditions)) {
            throw new \InvalidArgumentException('Conditions cannot be empty for safety');
        }

        $whereClauses = [];
        $params = [];

        foreach ($conditions as $field => $value) {
            $whereClauses[] = "{$field} = :{$field}";
            $params[":{$field}"] = $value;
        }

        $sql = "DELETE FROM " . self::TABLE_NAME . " WHERE " . implode(' AND ', $whereClauses);

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->rowCount();
    }

    /**
     * 搜尋活動記錄
     */
    public function search(
        ?string $searchTerm = null,
        ?int $userId = null,
        ?ActivityCategory $category = null,
        ?ActivityType $actionType = null,
        ?\DateTimeInterface $startTime = null,
        ?\DateTimeInterface $endTime = null,
        int $limit = 50,
        int $offset = 0,
        string $sortBy = 'occurred_at',
        string $sortOrder = 'DESC'
    ): array {
        $conditions = [];
        $params = [];

        if ($searchTerm !== null) {
            $conditions[] = '(description LIKE :search_term OR metadata LIKE :search_term)';
            $params[':search_term'] = '%' . $searchTerm . '%';
        }

        if ($userId !== null) {
            $conditions[] = 'user_id = :user_id';
            $params[':user_id'] = $userId;
        }

        if ($category !== null) {
            $conditions[] = 'action_category = :category';
            $params[':category'] = $category->value;
        }

        if ($actionType !== null) {
            $conditions[] = 'action_type = :action_type';
            $params[':action_type'] = $actionType->value;
        }

        if ($startTime !== null) {
            $conditions[] = 'occurred_at >= :start_time';
            $params[':start_time'] = $startTime->format('Y-m-d H:i:s');
        }

        if ($endTime !== null) {
            $conditions[] = 'occurred_at <= :end_time';
            $params[':end_time'] = $endTime->format('Y-m-d H:i:s');
        }

        $whereClause = empty($conditions) ? '' : 'WHERE ' . implode(' AND ', $conditions);

        // 驗證排序欄位
        $allowedSortFields = ['occurred_at', 'created_at', 'action_type', 'action_category', 'status', 'user_id'];
        $sortBy = in_array($sortBy, $allowedSortFields, true) ? $sortBy : 'occurred_at';
        $sortOrder = strtoupper($sortOrder) === 'ASC' ? 'ASC' : 'DESC';

        $sql = "SELECT " . self::SELECT_FIELDS . " FROM " . self::TABLE_NAME . " 
                {$whereClause} 
                ORDER BY {$sortBy} {$sortOrder} 
                LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);

        foreach ($params as $param => $value) {
            $stmt->bindValue($param, $value);
        }

        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

        $stmt->execute();

        $results = [];
        while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $entity = ActivityLog::fromDatabaseRow($data);
            $results[] = $entity->toArray();
        }

        return $results;
    }

    /**
     * 取得搜尋結果總數
     */
    public function getSearchCount(
        ?string $searchTerm = null,
        ?int $userId = null,
        ?ActivityCategory $category = null,
        ?ActivityType $actionType = null,
        ?\DateTimeInterface $startTime = null,
        ?\DateTimeInterface $endTime = null
    ): int {
        $conditions = [];
        $params = [];

        if ($searchTerm !== null) {
            $conditions[] = '(description LIKE :search_term OR metadata LIKE :search_term)';
            $params[':search_term'] = '%' . $searchTerm . '%';
        }

        if ($userId !== null) {
            $conditions[] = 'user_id = :user_id';
            $params[':user_id'] = $userId;
        }

        if ($category !== null) {
            $conditions[] = 'action_category = :category';
            $params[':category'] = $category->value;
        }

        if ($actionType !== null) {
            $conditions[] = 'action_type = :action_type';
            $params[':action_type'] = $actionType->value;
        }

        if ($startTime !== null) {
            $conditions[] = 'occurred_at >= :start_time';
            $params[':start_time'] = $startTime->format('Y-m-d H:i:s');
        }

        if ($endTime !== null) {
            $conditions[] = 'occurred_at <= :end_time';
            $params[':end_time'] = $endTime->format('Y-m-d H:i:s');
        }

        $whereClause = empty($conditions) ? '' : 'WHERE ' . implode(' AND ', $conditions);

        $sql = "SELECT COUNT(*) FROM " . self::TABLE_NAME . " {$whereClause}";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return (int) $stmt->fetchColumn();
    }

    /**
     * 取得可疑 IP 清單（基於失敗嘗試次數）
     */
    public function getSuspiciousIPs(int $minFailedAttempts = 5): array
    {
        $stmt = $this->db->prepare("
            SELECT 
                ip_address,
                COUNT(*) as failed_attempts,
                MAX(occurred_at) as latest_attempt
            FROM " . self::TABLE_NAME . " 
            WHERE action_type IN ('login_failed', 'auth_failed')
            GROUP BY ip_address 
            HAVING COUNT(*) >= :min_failed_attempts
            ORDER BY failed_attempts DESC
        ");

        $stmt->bindValue(':min_failed_attempts', $minFailedAttempts, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Find activity logs by user ID within time window
     */
    public function findByUserIdAndTimeWindow(int $userId, ?\DateTimeInterface $timeWindow = null): array
    {
        if ($timeWindow === null) {
            return $this->findByUser($userId);
        }

        $stmt = $this->db->prepare("
            SELECT " . self::SELECT_FIELDS . " 
            FROM " . self::TABLE_NAME . "
            WHERE user_id = :user_id 
                AND occurred_at >= :time_window 
            ORDER BY occurred_at DESC
        ");

        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':time_window', $timeWindow->format('Y-m-d H:i:s'));
        $stmt->execute();

        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map([$this, 'mapToArray'], $results);
    }
}
