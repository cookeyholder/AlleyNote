<?php

declare(strict_types=1);

namespace App\Domains\Auth\DTOs;
use App\Shared\ValueObjects\SecurePassword;
use InvalidArgumentException;
final readonly class UpdateUserDTO
{
    /**
     * @param list<int>|null $roleIds
     */
    public function __construct(
        public ?string $username = null,
        public ?string $email = null,
        public ?string $password = null,
        public ?array $roleIds = null,
    ) {
        self::assertRoleIds($this->roleIds);
        // 如果有更新密碼，驗證密碼安全性
        if ($this->password !== null) {
            new SecurePassword($this->password, $this->username, $this->email);
        }
    }
    /** @param array<mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            username: is_string($data['username'] ?? null) ? $data['username'] : null,
            email: is_string($data['email'] ?? null) ? $data['email'] : null,
            password: is_string($data['password'] ?? null) ? $data['password'] : null,
            roleIds: self::normalizeRoleIds($data['role_ids'] ?? null),
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
    /**
     * @return list<int>|null
     */
    private static function normalizeRoleIds(mixed $roleIds): ?array
    {
        if (!is_array($roleIds)) {
            return null;
        }
        $normalizedRoleIds = [];
        foreach ($roleIds as $roleId) {
            if (is_int($roleId) && $roleId > 0) {
                $normalizedRoleIds[] = $roleId;
            }
        }
        return $normalizedRoleIds === [] ? [] : array_values(array_unique($normalizedRoleIds));
    }
    /**
     * @param list<int>|null $roleIds
     */
    private static function assertRoleIds(?array $roleIds): void
    {
        if ($roleIds === null) {
            return;
        }
        foreach ($roleIds as $roleId) {
            if (!is_int($roleId) || $roleId <= 0) {
                throw new InvalidArgumentException('Role IDs must be a positive integer list');
            }
        }
    }
}
