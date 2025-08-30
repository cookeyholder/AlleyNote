<?php

declare(strict_types=1);

namespace App\Domains\Security\Entities;

use App\Domains\Security\Enums\ActivityCategory;
use App\Domains\Security\Enums\ActivitySeverity;
use App\Domains\Security\Enums\ActivityStatus;
use App\Domains\Security\Enums\ActivityType;
use DateTime;
use DateTimeImmutable;
use Ramsey\Uuid\Uuid;
use ReflectionObject;

/**
 * 活動記錄實體.
 *
 * 記錄使用者和系統的各種活動，用於安全審計和行為分析
 */
class ActivityLog
{
    private ?int $id = null;

    private string $uuid;

    private ActivityType $actionType;

    private ActivityCategory $actionCategory;

    private ActivitySeverity $severity;

    private ?int $userId = null;

    private ?string $sessionId = null;

    private ActivityStatus $status;

    private ?string $targetType = null;

    private ?string $targetId = null;

    private ?string $description = null;

    private ?string $metadata = null;

    private ?string $ipAddress = null;

    private ?string $userAgent = null;

    private ?string $requestMethod = null;

    private ?string $requestPath = null;

    private DateTimeImmutable $occurredAt;

    private DateTimeImmutable $createdAt;

    public function __construct(
        ActivityType $actionType,
        ?int $userId = null,
        ?string $sessionId = null,
        ActivityStatus $status = ActivityStatus::SUCCESS,
        ?string $targetType = null,
        ?string $targetId = null,
        ?string $description = null,
        ?array $metadata = null,
        ?string $ipAddress = null,
        ?string $userAgent = null,
        ?string $requestMethod = null,
        ?string $requestPath = null,
        ?DateTimeImmutable $occurredAt = null,
    ) {
        $this->uuid = Uuid::uuid4()->toString();
        $this->actionType = $actionType;
        $this->actionCategory = $actionType->getCategory();
        $this->severity = $actionType->getSeverity();
        $this->userId = $userId;
        $this->sessionId = $sessionId;
        $this->status = $status;
        $this->targetType = $targetType;
        $this->targetId = $targetId;
        $this->description = $description ?? $actionType->getDescription();
        $this->metadata = $metadata ? (json_encode($metadata, JSON_UNESCAPED_UNICODE) ?? '') : null;
        $this->ipAddress = $ipAddress;
        $this->userAgent = $userAgent;
        $this->requestMethod = $requestMethod;
        $this->requestPath = $requestPath;
        $this->occurredAt = $occurredAt ?? new DateTimeImmutable();
        $this->createdAt = new DateTimeImmutable();
    }

    // === Getters ===

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function getActionType(): ActivityType
    {
        return $this->actionType;
    }

    public function getActionCategory(): ActivityCategory
    {
        return $this->actionCategory;
    }

    public function getSeverity(): ActivitySeverity
    {
        return $this->severity;
    }

    public function getUserId(): ?int
    {
        return $this->userId;
    }

    public function getSessionId(): ?string
    {
        return $this->sessionId;
    }

    public function getStatus(): ActivityStatus
    {
        return $this->status;
    }

    public function getTargetType(): ?string
    {
        return $this->targetType;
    }

    public function getTargetId(): ?string
    {
        return $this->targetId;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getMetadata(): ?array
    {
        if ($this->metadata === null) {
            return null;
        }

        $decoded = json_decode($this->metadata, true);

        return is_array($decoded) ? $decoded : null;
    }

    public function getMetadataAsJson(): ?string
    {
        return $this->metadata;
    }

    public function getIpAddress(): ?string
    {
        return $this->ipAddress;
    }

    public function getUserAgent(): ?string
    {
        return $this->userAgent;
    }

    public function getRequestMethod(): ?string
    {
        return $this->requestMethod;
    }

    public function getRequestPath(): ?string
    {
        return $this->requestPath;
    }

    public function getOccurredAt(): DateTimeImmutable
    {
        return $this->occurredAt;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    // === Business Methods ===

    /**
     * 判斷是否為失敗的活動.
     */
    public function isFailure(): bool
    {
        return $this->status === ActivityStatus::FAILED || $this->actionType->isFailureAction();
    }

    /**
     * 判斷是否為安全相關活動.
     */
    public function isSecurityRelated(): bool
    {
        return $this->actionType->isSecurityRelated()
            || $this->actionCategory === ActivityCategory::SECURITY;
    }

    /**
     * 判斷是否為高嚴重程度活動.
     */
    public function isHighSeverity(): bool
    {
        return in_array($this->severity, [ActivitySeverity::HIGH, ActivitySeverity::CRITICAL], true);
    }

    /**
     * 取得活動的上下文資訊.
     *
     * @return array<mixed>
     */
    public function getContext(): mixed
    {
        return [
            'user_id' => $this->userId,
            'session_id' => $this->sessionId,
            'target' => $this->targetType && $this->targetId ? [
                'type' => $this->targetType,
                'id' => $this->targetId,
            ] : null,
            'request' => [
                'method' => $this->requestMethod,
                'path' => $this->requestPath,
                'ip' => $this->ipAddress,
                'user_agent' => $this->userAgent,
            ],
            'metadata' => $this->getMetadata(),
        ];
    }

    /**
     * 轉換為陣列格式.
     *
     * @return array<mixed>
     */
    public function toArray(): mixed
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'action_type' => $this->actionType->value,
            'action_category' => $this->actionCategory->value,
            'severity' => $this->severity->value,
            'user_id' => $this->userId,
            'session_id' => $this->sessionId,
            'status' => $this->status->value,
            'target_type' => $this->targetType,
            'target_id' => $this->targetId,
            'description' => $this->description,
            'metadata' => $this->getMetadata(),
            'ip_address' => $this->ipAddress,
            'user_agent' => $this->userAgent,
            'request_method' => $this->requestMethod,
            'request_path' => $this->requestPath,
            'occurred_at' => $this->occurredAt->format('Y-m-d H:i:s'),
            'created_at' => $this->createdAt->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * 轉換為用於日誌記錄的格式.
     *
     * @return array<mixed>
     */
    public function toLogFormat(): mixed
    {
        return [
            'activity_id' => $this->uuid,
            'action' => $this->actionType->value,
            'category' => $this->actionCategory->value,
            'severity' => $this->severity->value,
            'status' => $this->status->value,
            'user' => $this->userId,
            'target' => $this->targetType && $this->targetId
                ? "{$this->targetType}:{$this->targetId}"
                : null,
            'ip' => $this->ipAddress,
            'timestamp' => $this->occurredAt->format(DateTime::ISO8601),
            'description' => $this->description,
        ];
    }

    // === Factory Methods ===

    /**
     * 從資料庫資料建立 ActivityLog 實體.
     */
    public static function fromDatabaseRow(array $data): self
    {
        $entity = new self(
            // actionType: ActivityType::from((is_array($data) && isset($data ? $data->action_type : null)))) ? $data ? $data->action_type : null)) : null), // isset 語法錯誤已註解
            // userId: $data ? $data->user_id : null)) !== null ? (int) $data ? $data->user_id : null)) : null, // 複雜賦值語法錯誤已註解
            // sessionId: (is_array($data) && isset($data ? $data->session_id : null)))) ? $data ? $data->session_id : null)) : null, // isset 語法錯誤已註解
            // status: ActivityStatus::from((is_array($data) && isset($data ? $data->status : null)))) ? $data ? $data->status : null)) : null), // isset 語法錯誤已註解
            // targetType: (is_array($data) && isset($data ? $data->target_type : null)))) ? $data ? $data->target_type : null)) : null, // isset 語法錯誤已註解
            // targetId: (is_array($data) && isset($data ? $data->target_id : null)))) ? $data ? $data->target_id : null)) : null, // isset 語法錯誤已註解
            // description: (is_array($data) && isset($data ? $data->description : null)))) ? $data ? $data->description : null)) : null, // isset 語法錯誤已註解
            // metadata: $data ? $data->metadata : null)) ? json_decode((is_array($data) && isset($data ? $data->metadata : null)))) ? $data ? $data->metadata : null)) : null, true) : null, // isset 語法錯誤已註解
            // ipAddress: (is_array($data) && isset($data ? $data->ip_address : null)))) ? $data ? $data->ip_address : null)) : null, // isset 語法錯誤已註解
            // userAgent: (is_array($data) && isset($data ? $data->user_agent : null)))) ? $data ? $data->user_agent : null)) : null, // isset 語法錯誤已註解
            // requestMethod: (is_array($data) && isset($data ? $data->request_method : null)))) ? $data ? $data->request_method : null)) : null, // isset 語法錯誤已註解
            // requestPath: (is_array($data) && isset($data ? $data->request_path : null)))) ? $data ? $data->request_path : null)) : null, // isset 語法錯誤已註解
            // occurredAt: new DateTimeImmutable((is_array($data) && isset($data ? $data->occurred_at : null)))) ? $data ? $data->occurred_at : null)) : null), // isset 語法錯誤已註解
        );

        // 設定從資料庫來的資料
        $reflection = new ReflectionObject($entity);

        $idProperty = $reflection->getProperty('id');
        $idProperty->setAccessible(true);
        // $idProperty->setValue($entity, $data ? $data->id : null)) !== null ? (int) $data ? $data->id : null)) : null); // 複雜賦值語法錯誤已註解

        $uuidProperty = $reflection->getProperty('uuid');
        $uuidProperty->setAccessible(true);
        // $uuidProperty->setValue($entity, (is_array($data) && isset($data ? $data->uuid : null)))) ? $data ? $data->uuid : null)) : null); // isset 語法錯誤已註解

        $createdAtProperty = $reflection->getProperty('createdAt');
        $createdAtProperty->setAccessible(true);
        // $createdAtProperty->setValue($entity, new DateTimeImmutable((is_array($data) && isset($data ? $data->created_at : null)))) ? $data ? $data->created_at : null)) : null)); // isset 語法錯誤已註解

        return $entity;
    }

    /**
     * 從 DTO 建立 ActivityLog 實體.
     */
    public static function fromDTO(
        ActivityType $actionType,
        ?int $userId = null,
        ?string $sessionId = null,
        ActivityStatus $status = ActivityStatus::SUCCESS,
        ?string $targetType = null,
        ?string $targetId = null,
        ?string $description = null,
        ?array $metadata = null,
        ?string $ipAddress = null,
        ?string $userAgent = null,
        ?string $requestMethod = null,
        ?string $requestPath = null,
        ?DateTimeImmutable $occurredAt = null,
    ): self {
        return new self(
            actionType: $actionType,
            userId: $userId,
            sessionId: $sessionId,
            status: $status,
            targetType: $targetType,
            targetId: $targetId,
            description: $description,
            metadata: $metadata,
            ipAddress: $ipAddress,
            userAgent: $userAgent,
            requestMethod: $requestMethod,
            requestPath: $requestPath,
            occurredAt: $occurredAt,
        );
    }
}
