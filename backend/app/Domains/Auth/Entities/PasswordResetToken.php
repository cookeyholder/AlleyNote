<?php

declare(strict_types=1);

namespace App\Domains\Auth\Entities;

use DateTimeImmutable;
use InvalidArgumentException;
use JsonSerializable;

/**
 * 密碼重設憑證實體.
 */
final class PasswordResetToken implements JsonSerializable
{
    public const DEFAULT_TTL_SECONDS = 3600;

    public function __construct(
        private readonly ?int $id,
        private readonly int $userId,
        private readonly string $tokenHash,
        private readonly DateTimeImmutable $expiresAt,
        private readonly DateTimeImmutable $createdAt,
        private readonly ?string $requestedIp = null,
        private readonly ?string $requestedUserAgent = null,
        private readonly ?DateTimeImmutable $usedAt = null,
        private readonly ?string $usedIp = null,
        private readonly ?string $usedUserAgent = null,
    ) {
        $this->assertUserId($userId);
        $this->assertTokenHash($tokenHash);
    }

    public static function issue(
        int $userId,
        string $tokenHash,
        DateTimeImmutable $expiresAt,
        ?string $requestedIp = null,
        ?string $requestedUserAgent = null,
        ?DateTimeImmutable $createdAt = null,
    ): self {
        return new self(
            id: null,
            userId: $userId,
            tokenHash: $tokenHash,
            expiresAt: $expiresAt,
            createdAt: $createdAt ?? new DateTimeImmutable(),
            requestedIp: $requestedIp,
            requestedUserAgent: $requestedUserAgent,
        );
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getTokenHash(): string
    {
        return $this->tokenHash;
    }

    public function getExpiresAt(): DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getRequestedIp(): ?string
    {
        return $this->requestedIp;
    }

    public function getRequestedUserAgent(): ?string
    {
        return $this->requestedUserAgent;
    }

    public function getUsedAt(): ?DateTimeImmutable
    {
        return $this->usedAt;
    }

    public function getUsedIp(): ?string
    {
        return $this->usedIp;
    }

    public function getUsedUserAgent(): ?string
    {
        return $this->usedUserAgent;
    }

    public function isExpired(?DateTimeImmutable $now = null): bool
    {
        $now ??= new DateTimeImmutable();

        return $this->expiresAt <= $now;
    }

    public function isUsed(): bool
    {
        return $this->usedAt !== null;
    }

    public function withPersistenceState(int $id): self
    {
        return new self(
            id: $id,
            userId: $this->userId,
            tokenHash: $this->tokenHash,
            expiresAt: $this->expiresAt,
            createdAt: $this->createdAt,
            requestedIp: $this->requestedIp,
            requestedUserAgent: $this->requestedUserAgent,
            usedAt: $this->usedAt,
            usedIp: $this->usedIp,
            usedUserAgent: $this->usedUserAgent,
        );
    }

    public function markAsUsed(
        ?DateTimeImmutable $usedAt = null,
        ?string $usedIp = null,
        ?string $usedUserAgent = null,
    ): self {
        return new self(
            id: $this->id,
            userId: $this->userId,
            tokenHash: $this->tokenHash,
            expiresAt: $this->expiresAt,
            createdAt: $this->createdAt,
            requestedIp: $this->requestedIp,
            requestedUserAgent: $this->requestedUserAgent,
            usedAt: $usedAt ?? new DateTimeImmutable(),
            usedIp: $usedIp,
            usedUserAgent: $usedUserAgent,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->userId,
            'expires_at' => $this->expiresAt->format(DATE_ATOM),
            'created_at' => $this->createdAt->format(DATE_ATOM),
            'requested_ip' => $this->requestedIp,
            'requested_user_agent' => $this->requestedUserAgent,
            'used_at' => $this->usedAt?->format(DATE_ATOM),
            'used_ip' => $this->usedIp,
            'used_user_agent' => $this->usedUserAgent,
        ];
    }

    private function assertUserId(int $userId): void
    {
        if ($userId <= 0) {
            throw new InvalidArgumentException('User ID 必須為正整數');
        }
    }

    private function assertTokenHash(string $tokenHash): void
    {
        if (!preg_match('/^[a-f0-9]{64}$/', $tokenHash)) {
            throw new InvalidArgumentException('Token 雜湊值必須為有效的 SHA-256 16 進位字串');
        }
    }
}
