<?php

declare(strict_types=1);

namespace App\Domains\Auth\DTOs;

use App\Domains\Auth\ValueObjects\TokenPair;

/**
 * 登入回應 DTO.
 *
 * 封裝使用者登入成功後的回應資料，包括令牌、使用者資訊和相關中繼資料。
 */
final readonly class LoginResponseDTO
{
    public function __construct(
        public TokenPair $tokens,
        public int $userId,
        public string $userEmail,
        public int $expiresAt,
        public ?string $sessionId = null,
        public ?array $permissions = null,
    ) {}

    /**
     * 轉換為陣列.
     */
    public function toArray(): array
    {
        return [
            'access_token' => $this->tokens->getAccessToken(),
            'refresh_token' => $this->tokens->getRefreshToken(),
            'token_type' => $this->tokens->getTokenType(),
            'expires_in' => $this->expiresAt - time(),
            'expires_at' => $this->expiresAt,
            'user' => [
                'id' => $this->userId,
                'email' => $this->userEmail,
            ],
            'session_id' => $this->sessionId,
            'permissions' => $this->permissions,
        ];
    }
}
