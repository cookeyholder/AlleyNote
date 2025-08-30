<?php

declare(strict_types=1);

namespace App\Domains\Auth\DTOs;

/**
 * 刷新請求 DTO.
 *
 * 封裝使用者 Token 刷新請求的資料。
 */
final readonly class RefreshRequestDTO
{
    public function __construct(
        public string $refreshToken,
        public ?array $scopes = null,
    ) {}

    /**
     * 從陣列建立 RefreshRequestDTO.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            refreshToken: $data['refresh_token'] ?? '',
            scopes: $data['scopes'] ?? null,
        );
    }

    /**
     * 轉換為陣列.
     */
    public function toArray(): array
    {
        return [
            'refresh_token' => '[REDACTED]', // 不記錄 token
            'scopes' => $this->scopes,
        ];
    }
}
