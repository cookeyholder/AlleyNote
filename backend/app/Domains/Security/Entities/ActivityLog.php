<?php

declare(strict_types=1);

namespace App\Domains\Security\Entities;

use App\Domains\Security\Enums\ActivityCategory;
use App\Domains\Security\Enums\ActivitySeverity;
use App\Domains\Security\Enums\ActivityStatus;
use App\Domains\Security\Enums\ActivityType;
use DateTime;
use DateTimeImmutable;
use InvalidArgumentException;
use Ramsey\Uuid\Uuid;
use ReflectionObject;

/**
 * 活動記錄實體.
 *
 * 記錄使用者和系統的各種活動，用於安全審計和行為分析
 */
class ActivityLog
{
    /** @phpstan-ignore-next-line property.onlyRead */
    private ?int $id;

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
        if ($metadata !== null) {
            $encoded = json_encode($metadata, JSON_UNESCAPED_UNICODE);
            $this->metadata = $encoded !== false ? $encoded : null;
        } else {
            $this->metadata = null;
        }
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
     * @return array<string, mixed>
     */
    public function getContext(): array
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
     * @return array<string, mixed>
     */
    public function toArray(): array
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
     * @return array<string, mixed>
     */
    public function toLogFormat(): array
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
     *
     * @param array<string, mixed> $data
     */
    public static function fromDatabaseRow(array $data): self
    {
        // Validate and extract action_type
        $actionTypeValue = $data['action_type'] ?? null;
        if (!is_string($actionTypeValue) && !is_int($actionTypeValue)) {
            throw new InvalidArgumentException('action_type must be a string or integer');
        }

        // Validate user_id
        $userId = null;
        if (isset($data['user_id']) && is_numeric($data['user_id'])) {
            $userId = (int) $data['user_id'];
        }

        // Validate session_id
        $sessionId = isset($data['session_id']) && is_string($data['session_id']) ? $data['session_id'] : null;

        // Validate status
        $statusValue = $data['status'] ?? null;
        if (!is_string($statusValue) && !is_int($statusValue)) {
            $statusValue = ActivityStatus::SUCCESS->value;
        }

        // Validate nullable string fields
        $targetType = isset($data['target_type']) && is_string($data['target_type']) ? $data['target_type'] : null;
        $targetId = isset($data['target_id']) && is_string($data['target_id']) ? $data['target_id'] : null;
        $description = isset($data['description']) && is_string($data['description']) ? $data['description'] : null;

        // Validate metadata
        $metadata = null;
        if (isset($data['metadata']) && is_string($data['metadata']) && $data['metadata'] !== '') {
            $decoded = json_decode($data['metadata'], true);
            $metadata = is_array($decoded) ? $decoded : null;
        }

        // Validate request-related fields
        $ipAddress = isset($data['ip_address']) && is_string($data['ip_address']) ? $data['ip_address'] : null;
        $userAgent = isset($data['user_agent']) && is_string($data['user_agent']) ? $data['user_agent'] : null;
        $requestMethod = isset($data['request_method']) && is_string($data['request_method']) ? $data['request_method'] : null;
        $requestPath = isset($data['request_path']) && is_string($data['request_path']) ? $data['request_path'] : null;

        // Validate occurredAt
        $occurredAtValue = $data['occurred_at'] ?? null;
        $occurredAt = is_string($occurredAtValue) ? new DateTimeImmutable($occurredAtValue) : new DateTimeImmutable();

        $entity = new self(
            actionType: ActivityType::from($actionTypeValue),
            userId: $userId,
            sessionId: $sessionId,
            status: ActivityStatus::from($statusValue),
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

        // 設定從資料庫來的資料
        $reflection = new ReflectionObject($entity);

        $idProperty = $reflection->getProperty('id');
        $idProperty->setAccessible(true);
        $idValue = isset($data['id']) && is_numeric($data['id']) ? (int) $data['id'] : null;
        $idProperty->setValue($entity, $idValue);

        $uuidProperty = $reflection->getProperty('uuid');
        $uuidProperty->setAccessible(true);
        $uuidProperty->setValue($entity, $data['uuid']);

        $createdAtProperty = $reflection->getProperty('createdAt');
        $createdAtProperty->setAccessible(true);
        $createdAtValue = $data['created_at'] ?? null;
        $createdAt = is_string($createdAtValue) ? new DateTimeImmutable($createdAtValue) : new DateTimeImmutable();
        $createdAtProperty->setValue($entity, $createdAt);

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
