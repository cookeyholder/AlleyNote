<?php

declare(strict_types=1);

namespace App\Domains\Auth\DTOs;
use App\Shared\ValueObjects\SecurePassword;
use InvalidArgumentException;
final readonly class CreateUserDTO
{
    /**
     * @param list<int> $roleIds
     */
    public function __construct(
        public string $username,
        public string $email,
        public string $password,
        public array $roleIds = [],
    ) {
        self::assertRoleIds($this->roleIds);
        // 驗證密碼安全性
        new SecurePassword($this->password, $this->username, $this->email);
    }
    /** @param array<mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            username: is_string($data['username'] ?? null) ? $data['username'] : '',
            email: is_string($data['email'] ?? null) ? $data['email'] : '',
            password: is_string($data['password'] ?? null) ? $data['password'] : '',
            roleIds: self::normalizeRoleIds($data['role_ids'] ?? []),
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
    /**
     * @return list<int>
     */
    private static function normalizeRoleIds(mixed $roleIds): array
    {
        if (!is_array($roleIds)) {
            return [];
        }
        $normalizedRoleIds = [];
        foreach ($roleIds as $roleId) {
            if (is_int($roleId) && $roleId > 0) {
                $normalizedRoleIds[] = $roleId;
            }
        }
        return array_values(array_unique($normalizedRoleIds));
    }
    /**
     * @param list<int> $roleIds
     */
    private static function assertRoleIds(array $roleIds): void
    {
        foreach ($roleIds as $roleId) {
            if (!is_int($roleId) || $roleId <= 0) {
                throw new InvalidArgumentException('Role IDs must be a positive integer list');
            }
        }
    }
}
