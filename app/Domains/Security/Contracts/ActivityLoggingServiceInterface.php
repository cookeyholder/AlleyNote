<?php

declare(strict_types=1);

namespace App\Domains\Security\Contracts;

use App\Domains\Security\DTOs\CreateActivityLogDTO;
use App\Domains\Security\Enums\ActivityCategory;
use App\Domains\Security\Enums\ActivityType;

/**
 * 活動記錄服務介面
 * 定義使用者行為記錄的核心功能
 */
interface ActivityLoggingServiceInterface
{
    /**
     * 記錄使用者活動
     */
    public function log(CreateActivityLogDTO $dto): bool;

    /**
     * 記錄成功操作
     */
    public function logSuccess(
        ActivityType $actionType,
        ?int $userId = null,
        ?string $targetType = null,
        ?string $targetId = null,
        ?array $metadata = null
    ): bool;

    /**
     * 記錄失敗操作
     */
    public function logFailure(
        ActivityType $actionType,
        ?int $userId = null,
        string $reason = '',
        ?array $metadata = null
    ): bool;

    /**
     * 記錄安全事件
     */
    public function logSecurityEvent(
        ActivityType $actionType,
        string $description,
        ?array $metadata = null
    ): bool;

    /**
     * 批次記錄多個活動
     */
    public function logBatch(array $dtos): int;

    /**
     * 啟用/停用特定類型的記錄
     */
    public function enableLogging(ActivityType $actionType): void;
    public function disableLogging(ActivityType $actionType): void;

    /**
     * 檢查特定類型是否啟用記錄
     */
    public function isLoggingEnabled(ActivityType $actionType): bool;

    /**
     * 設定記錄等級（只記錄指定等級以上的活動）
     */
    public function setLogLevel(int $level): void;

    /**
     * 清理舊的活動記錄（根據保留政策）
     */
    public function cleanup(): int;
}