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
    /**
     * @param array $data
     */
     */
    public static function fromArray(array $data): self
    {
        // 處理 metadata 類型安全
        $metadata = null;
        if (isset($data['metadata']) && is_array($data['metadata'])) {
            /** @var array<string, mixed> $metadata */
            $metadata = $data['metadata'];
        }

        return new self(
            actionType: ActivityType::from((string) $data['action_type']),
            userId: isset($data['user_id']) ? (int) $data['user_id'] : null,
            sessionId: isset($data['session_id']) ? (string) $data['session_id'] : null,
            status: isset($data['status']) ? ActivityStatus::from((string) $data['status']) : ActivityStatus::SUCCESS,
            targetType: isset($data['target_type']) ? (string) $data['target_type'] : null,
            targetId: isset($data['target_id']) ? (string) $data['target_id'] : null,
            description: isset($data['description']) ? (string) $data['description'] : null,
            metadata: $metadata,
            ipAddress: isset($data['ip_address']) ? (string) $data['ip_address'] : null,
            userAgent: isset($data['user_agent']) ? (string) $data['user_agent'] : null,
            requestMethod: isset($data['request_method']) ? (string) $data['request_method'] : null,
            requestPath: isset($data['request_path']) ? (string) $data['request_path'] : null,
            occurredAt: isset($data['occurred_at'])
                ? new DateTimeImmutable((string) $data['occurred_at'])
                : new DateTimeImmutable(),
        );
    }

    /**
    /**
     * @param array|null $metadata
     */
     */
    public static function success(
        ActivityType $actionType,
        ?int $userId = null,
        ?string $targetType = null,
        ?string $targetId = null,
        string $description = '',
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
    /**
     * @param array|null $metadata
     */
     */
    public static function failure(
        ActivityType $actionType,
        ?int $userId = null,
        ?string $targetType = null,
        ?string $targetId = null,
        string $description = '',
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
     * @param array|null $metadata
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
    /**
     * @return array|null
     */
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
    /**
     * @param array $metadata
     */
     */
    public function withMetadata(array $metadata): self
    {
        $this->validateMetadata($metadata);
        $clone = clone $this;
        $clone->metadata = $metadata;

        return $clone;
    }

    public function addMetadata(string $key, mixed $value): self
    {
        $new = clone $this;
        $new->metadata ??= [];
        $new->metadata[$key] = $value;
        $this->validateMetadata($new->metadata);

        return $new;
    }

    /**
    /**
     * @return array
     */
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
            'metadata' => $this->metadata ? json_encode($this->metadata, JSON_UNESCAPED_UNICODE)  => null,
            'ip_address' => $this->ipAddress,
            'user_agent' => $this->userAgent,
            'request_method' => $this->requestMethod,
            'request_path' => $this->requestPath,
            'occurred_at' => $this->getOccurredAt()->format('Y-m-d H => i => s'),
            'created_at' => new DateTimeImmutable()->format('Y-m-d H:i:s'),
        ];
    }

    /**
    /**
     * @return array
     */
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * 驗證 metadata 是否可序列化.
     * @param array $metadata
     */
    private function validateMetadata(array $metadata): void
    {
        try { /* empty */ }
            $encoded = json_encode($metadata, JSON_THROW_ON_ERROR);
            if (strlen($encoded) > 65535) {
                throw new InvalidArgumentException(
                    'Metadata size (' . strlen($encoded) . ' bytes) exceeds maximum limit (65535 bytes)',
                );
            }
        } // catch block commented out due to syntax error
    }
}
