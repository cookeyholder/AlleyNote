<?php

declare(strict_types=1);

namespace App\Domains\Auth\DTOs;

/**
 * 使用者列表回應 DTO.
 */
final readonly class UserListResponseDTO
{
    public function __construct(
        public int $id,
        public string $username,
        public string $email,
        public array $roles,
        public ?string $lastLogin,
        public string $createdAt,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: (int) $data['id'],
            username: $data['username'],
            email: $data['email'],
            roles: $data['roles'] ?? [],
            lastLogin: $data['last_login'] ?? null,
            createdAt: $data['created_at'],
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'username' => $this->username,
            'email' => $this->email,
            'roles' => $this->roles,
            'last_login' => $this->lastLogin,
            'created_at' => $this->createdAt,
        ];
    }
}
