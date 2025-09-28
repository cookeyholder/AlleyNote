<?php

declare(strict_types=1);

namespace App\Domains\Statistics\Events;

use App\Shared\Events\AbstractDomainEvent;
use DateTimeImmutable;

/**
 * 文章瀏覽事件.
 *
 * 當使用者瀏覽文章時觸發，用於統計文章瀏覽數和使用者行為分析
 */
class PostViewed extends AbstractDomainEvent
{
    public function __construct(
        private readonly int $postId,
        private readonly ?int $userId,
        private readonly string $userIp,
        private readonly ?string $userAgent = null,
        private readonly ?string $referrer = null,
        private readonly ?DateTimeImmutable $viewedAt = null,
    ) {
        parent::__construct();
    }

    public function getEventName(): string
    {
        return 'statistics.post.viewed';
    }

    public function getEventData(): array
    {
        return [
            'post_id' => $this->postId,
            'user_id' => $this->userId,
            'user_ip' => $this->userIp,
            'user_agent' => $this->userAgent,
            'referrer' => $this->referrer,
            'viewed_at' => ($this->viewedAt ?? $this->getOccurredOn())->format('c'),
            'is_authenticated' => $this->userId !== null,
        ];
    }

    public function getPostId(): int
    {
        return $this->postId;
    }

    public function getUserId(): ?int
    {
        return $this->userId;
    }

    public function getUserIp(): string
    {
        return $this->userIp;
    }

    public function getUserAgent(): ?string
    {
        return $this->userAgent;
    }

    public function getReferrer(): ?string
    {
        return $this->referrer;
    }

    public function getViewedAt(): DateTimeImmutable
    {
        return $this->viewedAt ?? $this->getOccurredOn();
    }

    public function isAuthenticatedUser(): bool
    {
        return $this->userId !== null;
    }

    /**
     * 建立匿名使用者瀏覽事件.
     */
    public static function createAnonymous(
        int $postId,
        string $userIp,
        ?string $userAgent = null,
        ?string $referrer = null,
    ): self {
        return new self(
            postId: $postId,
            userId: null,
            userIp: $userIp,
            userAgent: $userAgent,
            referrer: $referrer,
        );
    }

    /**
     * 建立已認證使用者瀏覽事件.
     */
    public static function createAuthenticated(
        int $postId,
        int $userId,
        string $userIp,
        ?string $userAgent = null,
        ?string $referrer = null,
    ): self {
        return new self(
            postId: $postId,
            userId: $userId,
            userIp: $userIp,
            userAgent: $userAgent,
            referrer: $referrer,
        );
    }
}
