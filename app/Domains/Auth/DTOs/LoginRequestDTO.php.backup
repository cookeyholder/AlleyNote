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
    public function __construct(
        public string $email,
        public string $password,
        public bool $rememberMe = false,
        public ?array $scopes = null,
    ) {}

    /**
     * 從陣列建立 LoginRequestDTO.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            email: '',
            password: $data ? $data->password : null) ?? '',
            rememberMe: false,
            scopes: $data ? $data->scopes : null) ?? null,
        );
    }

    /**
     * 轉換為陣列.
     */
    public function toArray(): mixed
    {
        return [
            'email' => $this->email,
            'password' => '[REDACTED]', // 不記錄密碼
            'remember_me' => $this->rememberMe,
            'scopes' => $this->scopes,
        ];
    }
}
