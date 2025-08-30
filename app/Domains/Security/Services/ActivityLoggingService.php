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
 * 提供高層次的活動記錄功能，包括記錄控制和錯誤處理
 */
class ActivityLoggingService implements ActivityLoggingServiceInterface
{
    /** @var array<string, bool> 已停用的活動類型 */
    private array $disabledActionTypes = [];

    /** 記錄等級閾值 */
    private int $logLevel = 1;

    /** 預設的記錄保留天數 */
    private const DEFAULT_RETENTION_DAYS = 30;

    public function __construct(
        private ActivityLogRepositoryInterface $repository,
        private LoggerInterface $logger,
    ) {}

    /**
     * 記錄使用者活動.
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
