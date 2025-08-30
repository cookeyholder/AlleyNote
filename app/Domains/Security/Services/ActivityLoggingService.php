<?php

declare(strict_types=1);

namespace App\Domains\Security\Services;

use App\Domains\Security\Contracts\ActivityLoggingServiceInterface;
use App\Domains\Security\Contracts\ActivityLogRepositoryInterface;
use App\Domains\Security\DTOs\CreateActivityLogDTO;
use App\Domains\Security\Enums\ActivityStatus;
use App\Domains\Security\Enums\ActivityType;
use DateTimeImmutable;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * 活動記錄服務實作.
 *
 * 這是用戶活動記錄系統的核心服務類別，提供高層次的活動記錄功能，
 * 包括記錄控制、批次處理、錯誤處理和記錄清理等完整功能。
 *
 * 主要功能:
 * - 單一和批次活動記錄
 * - 記錄等級和類型控制
 * - 自動錯誤處理和日誌記錄
 * - 資料保留政策和自動清理
 * - 記錄效能監控
 *
 * 設計原則:
 * - 符合 SOLID 原則，特別是單一職責和開閉原則
 * - 使用依賴注入提高可測試性
 * - 提供豐富的日誌記錄用於監控和除錯
 * - 優雅的錯誤處理，不會因為記錄失敗影響業務邏輯
 *
 * @author AlleyNote Development Team
 * @since 1.0.0
 * @version 1.2.0
 *
 * @example
 * ```php
 * // 基本使用
 * $service = new ActivityLoggingService($repository, $logger);
 * $service->logSuccess(ActivityType::LOGIN_SUCCESS, 123);
 *
 * // 批次記錄
 * $activities = [dto1, dto2, dto3];
 * $results = $service->logBatch($activities);
 *
 * // 記錄控制
 * $service->setLogLevel(3);
 * $service->disableLogging(ActivityType::POST_VIEWED);
 * ```
 */
class ActivityLoggingService implements ActivityLoggingServiceInterface
{
    /** @var array<string, bool> 已停用的活動類型映射表 */
    private array $disabledActionTypes = [];

    /** @var int 記錄等級閾值 (1-5，數字越高記錄越嚴格) */
    private int $logLevel = 1;

    /** @var int 預設的記錄保留天數 */
    private const DEFAULT_RETENTION_DAYS = 30;

    /**
     * 建構活動記錄服務.
     *
     * @param ActivityLogRepositoryInterface $repository 活動記錄存儲庫
     * @param LoggerInterface $logger 系統日誌記錄器
     */
    public function __construct(
        private ActivityLogRepositoryInterface $repository,
        private LoggerInterface $logger,
    ) {}

    /**
     * 記錄使用者活動.
     *
     * 這是記錄活動的主要方法，提供完整的錯誤處理和日誌記錄。
     * 在記錄前會檢查活動類型是否啟用以及記錄等級是否符合要求。
     *
     * @param CreateActivityLogDTO $dto 活動記錄資料傳輸物件
     * @return bool 記錄是否成功
     *
     * @example
     * ```php
     * $dto = CreateActivityLogDTO::success(
     *     actionType: ActivityType::LOGIN_SUCCESS,
     *     userId: 123,
     *     description: '使用者登入成功'
     * );
     * $success = $service->log($dto);
     * ```
     */
    public function log(CreateActivityLogDTO $dto): bool
    {
        try {
            // 檢查是否啟用記錄
            if (!$this->isLoggingEnabled($dto->getActionType())) {
                $this->logger->warning('Logging disabled for action type', [
                    'action_type' => $dto->getActionType()->value,
                ]);

                return false;
            }

            // 檢查記錄等級
            if (!$this->shouldLogBasedOnSeverity($dto->getActionType())) {
                return false;
            }

            // 記錄到資料庫
            $result = $this->repository->create($dto);

            if ($result === null) {
                $this->logger->error('Repository returned null for activity log creation', [
                    'action_type' => $dto->getActionType()->value,
                    'user_id' => $dto->getUserId(),
                ]);

                return false;
            }

            return true;
        } catch (Throwable $e) {
            $this->logger->error('Failed to log activity', [
                'action_type' => $dto->getActionType()->value,
                'user_id' => $dto->getUserId(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return false;
        }
    }

    /**
     * 記錄成功操作.
     *
     * 這是記錄成功操作的便利方法，會自動建立成功狀態的 DTO。
     * 相比直接使用 log() 方法，此方法更簡潔且語意更清晰。
     *
     * @param ActivityType $actionType 活動類型
     * @param int|null $userId 使用者ID，匿名操作時可為 null
     * @param string|null $targetType 目標類型 (如: 'post', 'user', 'file')
     * @param string|null $targetId 目標ID
     * @param array|null $metadata 額外的元資料
     * @return bool 記錄是否成功
     *
     * @example
     * ```php
     * // 記錄使用者登入成功
     * $service->logSuccess(
     *     ActivityType::LOGIN_SUCCESS,
     *     userId: 123,
     *     metadata: ['ip_address' => '192.168.1.100']
     * );
     *
     * // 記錄文章檢視
     * $service->logSuccess(
     *     ActivityType::POST_VIEWED,
     *     userId: 123,
     *     targetType: 'post',
     *     targetId: '456'
     * );
     * ```
     */
    public function logSuccess(
        ActivityType $actionType,
        ?int $userId = null,
        ?string $targetType = null,
        ?string $targetId = null,
        ?array $metadata = null,
    ): bool {
        // 檢查記錄等級
        if (!$this->shouldLogBasedOnSeverity($actionType)) {
            return false;
        }

        $dto = CreateActivityLogDTO::success(
            actionType: $actionType,
            userId: $userId,
            targetType: $targetType,
            targetId: $targetId,
            metadata: $metadata,
        );

        return $this->log($dto);
    }

    /**
     * 記錄失敗操作.
     *
     * 專門用於記錄失敗的操作，會自動設定狀態為失敗並記錄失敗原因。
     * 這對於追蹤系統錯誤、使用者操作失敗等情況特別有用。
     *
     * @param ActivityType $actionType 活動類型
     * @param int|null $userId 使用者ID
     * @param string $reason 失敗原因描述
     * @param array|null $metadata 額外的元資料，如錯誤代碼、堆疊追蹤等
     * @return bool 記錄是否成功
     *
     * @example
     * ```php
     * // 記錄登入失敗
     * $service->logFailure(
     *     ActivityType::LOGIN_FAILED,
     *     userId: 123,
     *     reason: '密碼錯誤',
     *     metadata: ['attempt_count' => 3, 'ip_address' => '192.168.1.100']
     * );
     *
     * // 記錄檔案上傳失敗
     * $service->logFailure(
     *     ActivityType::ATTACHMENT_UPLOADED,
     *     userId: 123,
     *     reason: '檔案大小超過限制',
     *     metadata: ['file_size' => 1048576, 'max_size' => 512000]
     * );
     * ```
     */
    public function logFailure(
        ActivityType $actionType,
        ?int $userId = null,
        string $reason = '',
        ?array $metadata = null,
    ): bool {
        $dto = CreateActivityLogDTO::failure(
            actionType: $actionType,
            userId: $userId,
            description: $reason ?: $actionType->getDescription(),
            metadata: $metadata,
        );

        return $this->log($dto);
    }

    /**
     * 記錄安全事件.
     *
     * 專門用於記錄安全相關的事件，如可疑登入、權限違規、IP 封鎖等。
     * 這些事件通常需要特殊的關注和處理，系統會自動判斷事件的嚴重程度。
     *
     * @param ActivityType $actionType 活動類型（必須是安全相關類型）
     * @param string $description 安全事件的詳細描述
     * @param array|null $metadata 相關的安全資訊和上下文
     * @return bool 記錄是否成功
     *
     * @example
     * ```php
     * // 記錄可疑登入活動
     * $service->logSecurityEvent(
     *     ActivityType::LOGIN_FAILED,
     *     '檢測到異常登入嘗試',
     *     metadata: [
     *         'failed_attempts' => 5,
     *         'time_span' => '5 minutes',
     *         'ip_address' => '192.168.1.100',
     *         'user_agent' => 'Mozilla/5.0...',
     *         'risk_score' => 85
     *     ]
     * );
     *
     * // 記錄權限違規
     * $service->logSecurityEvent(
     *     ActivityType::ACCESS_DENIED,
     *     '嘗試存取未授權資源',
     *     metadata: [
     *         'requested_resource' => '/admin/users',
     *         'user_role' => 'user',
     *         'required_permission' => 'admin.users.read'
     *     ]
     * );
     * ```
     */
    public function logSecurityEvent(
        ActivityType $actionType,
        string $description,
        ?array $metadata = null,
    ): bool {
        $dto = new CreateActivityLogDTO(
            actionType: $actionType,
            status: $this->determineSecurityEventStatus($actionType),
            description: $description,
            metadata: $metadata,
        );

        return $this->log($dto);
    }

    /**
     * 批次記錄多個活動.
     */
    public function logBatch(array $dtos): int
    {
        try {
            // 過濾掉被停用或不符合等級的記錄
            $filteredDtos = [];
            foreach ($dtos as $dto) {
                if (!$dto instanceof CreateActivityLogDTO) {
                    $this->logger->warning('Invalid DTO type in batch', [
                        'type' => gettype($dto),
                    ]);
                    continue;
                }

                if (
                    $this->isLoggingEnabled($dto->getActionType())
                    && $this->shouldLogBasedOnSeverity($dto->getActionType())
                ) {
                    $filteredDtos[] = $dto;
                }
            }

            if (empty($filteredDtos)) {
                return 0;
            }

            return $this->repository->createBatch($filteredDtos);
        } catch (Throwable $e) {
            $this->logger->error('Failed to log batch activities', [
                'count' => count($dtos),
                'error' => $e->getMessage(),
            ]);

            return 0;
        }
    }

    /**
     * 啟用特定類型的記錄.
     */
    public function enableLogging(ActivityType $actionType): void
    {
        unset($this->disabledActionTypes[$actionType->value]);
    }

    /**
     * 停用特定類型的記錄.
     */
    public function disableLogging(ActivityType $actionType): void
    {
        $this->disabledActionTypes[$actionType->value] = true;
    }

    /**
     * 檢查特定類型是否啟用記錄.
     */
    public function isLoggingEnabled(ActivityType $actionType): bool
    {
        return !isset($this->disabledActionTypes[$actionType->value]);
    }

    /**
     * 設定記錄等級（只記錄指定等級以上的活動）.
     */
    public function setLogLevel(int $level): void
    {
        $this->logLevel = max(1, min(5, $level)); // 限制在 1-5 之間
    }

    /**
     * 清理舊的活動記錄（根據保留政策）.
     */
    public function cleanup(): int
    {
        try {
            $cutoffDate = new DateTimeImmutable('-' . self::DEFAULT_RETENTION_DAYS . ' days');

            $deletedCount = $this->repository->deleteOldRecords($cutoffDate);

            $this->logger->info('Activity log cleanup completed', [
                'deleted_count' => $deletedCount,
                'cutoff_date' => $cutoffDate->format('Y-m-d H:i:s'),
            ]);

            return $deletedCount;
        } catch (Throwable $e) {
            $this->logger->error('Failed to cleanup activity logs', [
                'error' => $e->getMessage(),
            ]);

            return 0;
        }
    }

    /**
     * 根據嚴重程度決定是否應該記錄.
     */
    private function shouldLogBasedOnSeverity(ActivityType $actionType): bool
    {
        $severity = $actionType->getSeverity();

        return $severity->value >= $this->logLevel;
    }

    /**
     * 為安全事件決定適當的狀態.
     */
    private function determineSecurityEventStatus(ActivityType $actionType): ActivityStatus
    {
        return match ($actionType) {
            ActivityType::IP_BLOCKED,
            ActivityType::CSRF_ATTACK_BLOCKED,
            ActivityType::XSS_ATTACK_BLOCKED,
            ActivityType::SQL_INJECTION_BLOCKED => ActivityStatus::BLOCKED,

            ActivityType::LOGIN_FAILED => ActivityStatus::FAILED,

            default => ActivityStatus::SUCCESS,
        };
    }
}
