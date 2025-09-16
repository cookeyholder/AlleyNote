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
     * 建立新使用者
     * @param array<string, mixed> $data 使用者資料
     * @return array<string, mixed> 建立的使用者資料
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
        if ($result === null) {
            throw new RuntimeException('Failed to create user: could not retrieve created user');
        }

        return $result;
    }

    /**
     * 更新使用者資料
     * @param string $id 使用者ID
     * @param array<string, mixed> $data 更新資料
     * @return array<string, mixed> 更新後的使用者資料
     */
    public function update(string $id, array $data): array
    {
        $fields = [];
        $params = ['id' => $id];

        foreach ($data as $key => $value) {
            if (in_array($key, ['username', 'email', 'status', 'password'])) {
                $fields[] = "{$key} = :{$key}";
                $params[$key] = $key === 'password'
                    ? password_hash((string) $value, PASSWORD_ARGON2ID) : $value;
            }
        }

        if (empty($fields)) {
            $result = $this->findById((int) $id);
            if ($result === null) {
                throw new RuntimeException('User not found');
            }

            return $result;
        }

        $fields[] = 'updated_at = CURRENT_TIMESTAMP';

        $sql = 'UPDATE users SET ' . implode(', ', $fields) . ' WHERE id = :id';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        $result = $this->findById((int) $id);
        if ($result === null) {
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
     * 根據ID查找使用者
     * @param int $id 使用者ID
     * @return array<string, mixed>|null 使用者資料
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
     * 根據UUID查找使用者
     * @param string $uuid 使用者UUID
     * @return array<string, mixed>|null 使用者資料
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
     * 根據使用者名查找使用者
     * @param string $username 使用者名
     * @return array<string, mixed>|null 使用者資料
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
     * 根據電子郵件查找使用者
     * @param string $email 電子郵件
     * @return array<string, mixed>|null 使用者資料
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
