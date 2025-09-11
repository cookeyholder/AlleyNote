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
        /** @var array<string>|null */
        public ?array $scopes = null,
    ) {}

    /**
     * 從陣列建立 RefreshRequestDTO.
     */
    public static function fromArray(array $data): self
    {
        $scopes = null;
        if (isset($data['scopes']) && is_array($data['scopes'])) {
            // Normalize scopes to an array of strings to satisfy strict typing
            $scopes = array_map(static fn(mixed $value): string => (string) $value, $data['scopes']);
        }

        return new self(
            refreshToken: (string) ($data['refresh_token'] ?? ''),
            scopes: $scopes,
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
