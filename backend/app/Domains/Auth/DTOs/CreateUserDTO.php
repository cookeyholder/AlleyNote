<?php

declare(strict_types=1);

namespace App\Domains\Auth\DTOs;

use App\Shared\ValueObjects\SecurePassword;

/**
 * 建立使用者 DTO.
 */
final readonly class CreateUserDTO
{
    public function __construct(
        public string $username,
        public string $email,
        public string $password,
        public array $roleIds = [],
    ) {
        // 驗證密碼安全性
        new SecurePassword($this->password, $this->username, $this->email);
    }

    public static function fromArray(array $data): self
    {
        return new self(
            username: $data['username'] ?? '',
            email: $data['email'] ?? '',
            password: $data['password'] ?? '',
            roleIds: $data['role_ids'] ?? [],
        );
    }

    public function toArray(): array
    {
        return [
            'username' => $this->username,
            'email' => $this->email,
            'password' => $this->password,
            'role_ids' => $this->roleIds,
        ];
    }
}
