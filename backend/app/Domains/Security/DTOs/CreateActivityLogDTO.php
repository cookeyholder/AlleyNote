<?php

declare(strict_types=1);

namespace App\Domains\Security\DTOs;

use App\Domains\Security\Enums\ActivityStatus;
use App\Domains\Security\Enums\ActivityType;
use DateTimeImmutable;
use InvalidArgumentException;
use JsonException;
use JsonSerializable;

/**
 * 建立活動記錄的 DTO.
 */
final class CreateActivityLogDTO implements JsonSerializable
{
    public function __construct(
        private ActivityType $actionType,
        private ?int $userId = null,
        private ?string $sessionId = null,
        private ActivityStatus $status = ActivityStatus::SUCCESS,
        private ?string $targetType = null,
        private ?string $targetId = null,
        private ?string $description = null,
        /** @var array<string, mixed>|null */
        private ?array $metadata = null,
        private ?string $ipAddress = null,
        private ?string $userAgent = null,
        private ?string $requestMethod = null,
        private ?string $requestPath = null,
        private ?DateTimeImmutable $occurredAt = null,
    ) {
        $this->occurredAt ??= new DateTimeImmutable();

        // 驗證 metadata 只能包含可序列化的資料
        if ($this->metadata !== null) {
            $this->validateMetadata($this->metadata);
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $actionTypeValue = $data['action_type'] ?? null;
        if (!is_string($actionTypeValue) && !is_int($actionTypeValue)) {
            throw new InvalidArgumentException('action_type must be a string or integer');
        }
        $actionType = ActivityType::from($actionTypeValue);
        $userId = isset($data['user_id']) && is_numeric($data['user_id']) ? (int) $data['user_id'] : null;
        $sessionId = isset($data['session_id']) && is_string($data['session_id']) ? $data['session_id'] : null;

        $statusValue = $data['status'] ?? null;
        $status = (is_string($statusValue) || is_int($statusValue))
            ? ActivityStatus::from($statusValue)
            : ActivityStatus::SUCCESS;

        $targetType = isset($data['target_type']) && is_string($data['target_type']) ? $data['target_type'] : null;
        $targetId = isset($data['target_id']) && is_string($data['target_id']) ? $data['target_id'] : null;
        $description = isset($data['description']) && is_string($data['description']) ? $data['description'] : null;

        // Validate metadata structure
        $metadata = null;
        if (isset($data['metadata']) && is_array($data['metadata'])) {
            $validMetadata = [];
            foreach ($data['metadata'] as $key => $value) {
                if (is_string($key)) {
                    $validMetadata[$key] = $value;
                }
            }
            $metadata = $validMetadata !== [] ? $validMetadata : null;
        }

        $ipAddress = isset($data['ip_address']) && is_string($data['ip_address']) ? $data['ip_address'] : null;
        $userAgent = isset($data['user_agent']) && is_string($data['user_agent']) ? $data['user_agent'] : null;
        $requestMethod = isset($data['request_method']) && is_string($data['request_method']) ? $data['request_method'] : null;
        $requestPath = isset($data['request_path']) && is_string($data['request_path']) ? $data['request_path'] : null;

        $occurredAtValue = $data['occurred_at'] ?? null;
        $occurredAt = is_string($occurredAtValue)
            ? new DateTimeImmutable($occurredAtValue)
            : new DateTimeImmutable();

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

    /**
     * 快速建立成功操作的記錄.
     *
     * @param array<string, mixed>|null $metadata
     */
    public static function success(
        ActivityType $actionType,
        ?int $userId = null,
        ?string $targetType = null,
        ?string $targetId = null,
        ?string $description = null,
        ?array $metadata = null,
    ): self {
        return new self(
            actionType: $actionType,
            userId: $userId,
            status: ActivityStatus::SUCCESS,
            targetType: $targetType,
            targetId: $targetId,
            description: $description,
            metadata: $metadata,
        );
    }

    /**
     * 快速建立失敗操作的記錄.
     *
     * @param array<string, mixed>|null $metadata
     */
    public static function failure(
        ActivityType $actionType,
        ?int $userId = null,
        ?string $targetType = null,
        ?string $targetId = null,
        ?string $description = null,
        ?array $metadata = null,
    ): self {
        return new self(
            actionType: $actionType,
            userId: $userId,
            status: ActivityStatus::FAILED,
            targetType: $targetType,
            targetId: $targetId,
            description: $description,
            metadata: $metadata,
        );
    }

    /**
     * 快速建立安全事件的記錄.
     *
     * @param array<string, mixed>|null $metadata
     */
    public static function securityEvent(
        ActivityType $actionType,
        ?string $ipAddress = null,
        ?string $userAgent = null,
        ?string $description = null,
        ?array $metadata = null,
    ): self {
        return new self(
            actionType: $actionType,
            status: ActivityStatus::BLOCKED,
            description: $description,
            metadata: $metadata,
            ipAddress: $ipAddress,
            userAgent: $userAgent,
        );
    }

    // === Getters ===

    public function getActionType(): ActivityType
    {
        return $this->actionType;
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

    /**
     * @return array<string, mixed>|null
     */
    public function getMetadata(): ?array
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
        return $this->occurredAt ?? new DateTimeImmutable();
    }

    // === Fluent Setters ===

    public function withUserId(?int $userId): self
    {
        $new = clone $this;
        $new->userId = $userId;

        return $new;
    }

    public function withSessionId(?string $sessionId): self
    {
        $new = clone $this;
        $new->sessionId = $sessionId;

        return $new;
    }

    public function withRequestInfo(?string $method, ?string $path): self
    {
        $new = clone $this;
        $new->requestMethod = $method;
        $new->requestPath = $path;

        return $new;
    }

    public function withNetworkInfo(?string $ipAddress, ?string $userAgent): self
    {
        $new = clone $this;
        $new->ipAddress = $ipAddress;
        $new->userAgent = $userAgent;

        return $new;
    }

    /**
     * @param array<string, mixed> $metadata
     */
    public function withMetadata(array $metadata): self
    {
        $this->validateMetadata($metadata);
        $new = clone $this;
        $new->metadata = $metadata;

        return $new;
    }

    public function addMetadata(string $key, mixed $value): self
    {
        $new = clone $this;
        if ($new->metadata === null) {
            $new->metadata = [];
        }
        $new->metadata[$key] = $value;
        $this->validateMetadata($new->metadata);

        return $new;
    }

    /**
     * 轉換為資料庫儲存格式.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'action_type' => $this->actionType->value,
            'action_category' => $this->actionType->getCategory()->value,
            'user_id' => $this->userId,
            'session_id' => $this->sessionId,
            'status' => $this->status->value,
            'target_type' => $this->targetType,
            'target_id' => $this->targetId,
            'description' => $this->description ?? $this->actionType->getDescription(),
            'metadata' => $this->metadata !== null ? json_encode($this->metadata, JSON_UNESCAPED_UNICODE) : null,
            'ip_address' => $this->ipAddress,
            'user_agent' => $this->userAgent,
            'request_method' => $this->requestMethod,
            'request_path' => $this->requestPath,
            'occurred_at' => $this->getOccurredAt()->format('Y-m-d H:i:s'),
            'created_at' => new DateTimeImmutable()->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * 驗證 metadata 是否可序列化.
     *
     * @param array<string, mixed> $metadata
     */
    private function validateMetadata(array $metadata): void
    {
        try {
            json_encode($metadata, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new InvalidArgumentException(
                'Metadata must be JSON serializable: ' . $e->getMessage(),
            );
        }

        // 檢查 metadata 大小不超過 64KB（文字欄位限制）
        $json = json_encode($metadata);
        if ($json === false) {
            throw new InvalidArgumentException('Failed to encode metadata to JSON');
        }
        $jsonSize = strlen($json);
        if ($jsonSize > 65535) {
            throw new InvalidArgumentException(
                "Metadata size ({$jsonSize} bytes) exceeds maximum limit (65535 bytes)",
            );
        }
    }
}
