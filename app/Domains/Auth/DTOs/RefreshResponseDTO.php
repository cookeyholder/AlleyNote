<?php

declare(strict_types=1);

namespace App\Domains\Auth\DTOs;

use App\Domains\Auth\ValueObjects\TokenPair;

/**
 * 刷新回應 DTO.
 *
 * 封裝使用者 Token 刷新成功後的回應資料。
 */
final readonly class RefreshResponseDTO
{
    public function __construct(
        public TokenPair $tokens,
        public int $userId,
        public int $expiresAt,
        public ?string $sessionId = null,
        public ?array $permissions = null,
    ) {}

    /**
     * 轉換為陣列.
     */
    public function toArray(): mixed
    {
        return [
            'access_token' => $this->tokens->getAccessToken(),
            'refresh_token' => $this->tokens->getRefreshToken(),
            'token_type' => $this->tokens->getTokenType(),
            'expires_in' => $this->expiresAt - time(),
            'expires_at' => $this->expiresAt,
            'user_id' => $this->userId,
            'session_id' => $this->sessionId,
            'permissions' => $this->permissions,
        ];
    }
}
