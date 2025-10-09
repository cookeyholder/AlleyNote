<?php

declare(strict_types=1);

namespace App\Domains\Auth\DTOs;

/**
 * 更新使用者 DTO.
 */
final readonly class UpdateUserDTO
{
    public function __construct(
        public ?string $username = null,
        public ?string $email = null,
        public ?string $password = null,
        public ?array $roleIds = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            username: $data['username'] ?? null,
            email: $data['email'] ?? null,
            password: $data['password'] ?? null,
            roleIds: $data['role_ids'] ?? null,
        );
    }

    public function toArray(): array
    {
        $result = [];

        if ($this->username !== null) {
            $result['username'] = $this->username;
        }
        if ($this->email !== null) {
            $result['email'] = $this->email;
        }
        if ($this->password !== null) {
            $result['password'] = $this->password;
        }
        if ($this->roleIds !== null) {
            $result['role_ids'] = $this->roleIds;
        }

        return $result;
    }

    public function hasUpdates(): bool
    {
        return $this->username !== null
            || $this->email !== null
            || $this->password !== null
            || $this->roleIds !== null;
    }
}
