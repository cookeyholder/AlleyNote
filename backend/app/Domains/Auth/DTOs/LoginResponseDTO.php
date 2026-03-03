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
        public ?string $userName = null,
        public ?string $sessionId = null,
        public ?array $permissions = null,
        public ?array $roles = null,
    ) {}

    /**
     * 轉換為陣列.
     */
    public function toArray(): array
    {
        // 從 roles 中提取第一個角色名稱作為 role
        $primaryRole = null;
        if (is_array($this->roles) && count($this->roles) > 0) {
            $primaryRole = $this->roles[0]['name'] ?? null;
        }

        return [
            'access_token' => $this->tokens->getAccessToken(),
            'refresh_token' => $this->tokens->getRefreshToken(),
            'token_type' => $this->tokens->getTokenType(),
            'expires_in' => $this->expiresAt - time(),
            'expires_at' => $this->expiresAt,
            'user' => [
                'id' => $this->userId,
                'email' => $this->userEmail,
                'name' => $this->userName, // 添加 name 欄位
                'role' => $primaryRole, // 添加 role 欄位
                'roles' => $this->roles,
            ],
            'session_id' => $this->sessionId,
            'permissions' => $this->permissions,
        ];
    }
}
