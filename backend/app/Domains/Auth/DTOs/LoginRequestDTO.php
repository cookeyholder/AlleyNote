<?php

declare(strict_types=1);

namespace App\Domains\Auth\DTOs;

/**
 * 登入請求 DTO.
 *
 * 封裝使用者登入請求的資料，包括使用者憑證和相關選項。
 */
final readonly class LoginRequestDTO
{
    /**
     * @param array<string>|null $scopes
     */
    public function __construct(
        public string $email,
        public string $password,
        public bool $rememberMe = false,
        /** @var array<string>|null */
        public ?array $scopes = null,
    ) {}

    /**
     * 從陣列建立 LoginRequestDTO.
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $scopes = null;
        if (isset($data['scopes']) && is_array($data['scopes'])) {
            // Normalize scope values to strings to satisfy strict typing expectations
            $scopes = array_map(static fn(mixed $value): string => (string) $value, $data['scopes']);
        }

        return new self(
            email: (string) ($data['email'] ?? ''),
            password: (string) ($data['password'] ?? ''),
            rememberMe: (bool) ($data['remember_me'] ?? false),
            scopes: $scopes,
        );
    }

    /**
     * 轉換為陣列.
     * @return array<string, mixed><string, mixed>
     */
    public function toArray(): array
    {
        return [
            'email' => $this->email,
            'password' => '[REDACTED]', // 不記錄密碼
            'remember_me' => $this->rememberMe,
            'scopes' => $this->scopes,
        ];
    }
}
