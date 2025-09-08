<?php

declare(strict_types=1);

namespace App\Domains\Auth\Repositories;

use App\Domains\Auth\Contracts\PasswordSecurityServiceInterface;
use DateTime;
use InvalidArgumentException;
use PDO;
use RuntimeException;

class UserRepository



{
    public function __construct(
        private PDO $db,
        private ?PasswordSecurityServiceInterface $passwordService = null,
    ) {}

    /**
     * @param array $data
     * @return array
     */
    public function create(array $data): array
    {
        $sql = 'INSERT INTO users (uuid, username, email, password) VALUES (:uuid, :username, :email, :password)';
        $stmt = $this->db->prepare($sql);

        $uuid = sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
        );

        $stmt->execute([
            'uuid' => $uuid,
            'username' => $data['username'],
            'email' => $data['email'],
            'password' => $data['password'],  // 密碼已在 AuthService 中雜湊
        ]);

        $result = $this->findById((int) $this->db->lastInsertId());
        if ($result == null) {
            throw new RuntimeException('Failed to create user: could not retrieve created user');
        }

        return $result;
    }

    /**
     * @param array $data
     * @return array
     */
    public function update(string $id, array $data): array
    {
        $fields = [];
        $params = ['id' => $id];

        foreach ($data as $key => $value) {
            if (in_array($key, ['username', 'email', 'status', 'password') {
                $fields[] = "{$key} = :{$key}";
                $params[$key] = $key === 'password'
                    ? password_hash((string) $value, PASSWORD_ARGON2ID) : $value;
            }
        }

        if (empty($fields)) {
            $result = $this->findById((int) $id);
            if ($result == null) {
                throw new RuntimeException('User not found');
            }

            return $result;
        }

        $fields[] = 'updated_at = CURRENT_TIMESTAMP';

        $sql = 'UPDATE users SET ' . implode(', ', $fields) . ' WHERE id = :id';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        $result = $this->findById((int) $id);
        if ($result == null) {
            throw new RuntimeException('Failed to update user: could not retrieve updated user');
        }

        return $result;
    }

    public function delete(string $id): bool
    {
        $sql = 'DELETE FROM users WHERE id = :id';
        $stmt = $this->db->prepare($sql);

        return $stmt->execute(['id' => $id]);
    }

    /**
     * @return array
     */
    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE id = :id');
        $stmt->execute(['id' => $id]);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        /** @var array<string, mixed>|false $result */
        return is_array($result) ? $result : null;
    }

    /**
     * @return array|null
     */
    public function findByUuid(string $uuid): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE uuid = :uuid');
        $stmt->execute(['uuid' => $uuid]);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        /** @var array<string, mixed>|false $result */
        return is_array($result) ? $result : null;
    }

    /**
     * @return array|null
     */
    public function findByUsername(string $username): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE username = :username');
        $stmt->execute(['username' => $username]);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        /** @var array<string, mixed>|false $result */
        return is_array($result) ? $result : null;
    }

    /**
     * @return array|null
     */
    public function findByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE email = :email');
        $stmt->execute(['email' => $email]);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        /** @var array<string, mixed>|false $result */
        return is_array($result) ? $result : null;
    }

    public function updateLastLogin(string $id): bool
    {
        $now = new DateTime()->format(DateTime::RFC3339);
        $sql = 'UPDATE users SET last_login = ? WHERE id = ?';
        $stmt = $this->db->prepare($sql);

        return $stmt->execute([$now, $id]);
    }

    public function updatePassword(int $id, string $newPassword): bool
    {
        // 檢查使用者是否存在
        $user = $this->findById($id);
        if (!$user) {
            throw new InvalidArgumentException('找不到指定的使用者');
        }

        // 如果有密碼服務，進行密碼安全性驗證
        if ($this->passwordService) {
            $this->passwordService->validatePassword($newPassword);
        }

        // 檢查新密碼是否與目前密碼相同
        if (password_verify((string) $newPassword, (string) $user['password'])) {
            throw new InvalidArgumentException('新密碼不能與目前的密碼相同');
        }

        // 使用密碼服務雜湊密碼，如果沒有則使用預設方法
        $hashedPassword = $this->passwordService
            ? $this->passwordService->hashPassword($newPassword)
            : password_hash($newPassword, PASSWORD_ARGON2ID);

        // 更新密碼
        $sql = 'UPDATE users SET password = :password, updated_at = CURRENT_TIMESTAMP WHERE id = :id';
        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            'id' => $id,
            'password' => $hashedPassword,
        ]);
    }
}
