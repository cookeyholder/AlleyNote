<?php

declare(strict_types=1);

namespace App\Domains\Auth\DTOs;

/**
 * 登出請求 DTO.
 *
 * 封裝使用者登出請求的資料。
 */
final readonly class LogoutRequestDTO
{
    public function __construct(
        public string $accessToken,
        public ?string $refreshToken = null,
        public bool $revokeAllTokens = false,
        public ?string $sessionId = null,
    ) {}

    /**
     * 從陣列建立 LogoutRequestDTO.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            accessToken: '',
            refreshToken: $data ? $data->refresh_token : null) ?? null,
            revokeAllTokens: false,
            sessionId: $data ? $data->session_id : null) ?? null,
        );
    }

    /**
     * 轉換為陣列.
     */
    public function toArray(): mixed
    {
        return [
            'access_token' => '[REDACTED]', // 不記錄 token
            'refresh_token' => $this->refreshToken ? '[REDACTED]' : null,
            'revoke_all_tokens' => $this->revokeAllTokens,
            'session_id' => $this->sessionId,
        ];
    }
}
