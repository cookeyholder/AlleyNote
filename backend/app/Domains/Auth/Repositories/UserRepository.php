<?php

declare(strict_types=1);

namespace App\Domains\Auth\Repositories;

use App\Domains\Auth\Contracts\PasswordSecurityServiceInterface;
use DateTime;
use InvalidArgumentException;
use PDO;

class UserRepository
{
    public function __construct(
        private PDO $db,
        private ?PasswordSecurityServiceInterface $passwordService = null,
    ) {}

    public function create(array $data): array
    {
        $sql = 'INSERT INTO users (username, email, password_hash) VALUES (:username, :email, :password)';
        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            'username' => $data['username'],
            'email' => $data['email'],
            'password' => $data['password'],  // 密碼已在 Service 中雜湊
        ]);

        return $this->findById((int) $this->db->lastInsertId());
    }

    public function update(string $id, array $data): array
    {
        $fields = [];
        $params = ['id' => $id];

        foreach ($data as $key => $value) {
            if (in_array($key, ['username', 'email', 'status', 'password'])) {
                // 如果是 password 欄位，要對應到資料庫的 password_hash
                if ($key === 'password') {
                    $fields[] = "password_hash = :password_hash";
                    $params['password_hash'] = password_hash($value, PASSWORD_ARGON2ID);
                } else {
                    $fields[] = "{$key} = :{$key}";
                    $params[$key] = $value;
                }
            }
        }

        if (empty($fields)) {
            return $this->findById($id);
        }

        $fields[] = 'updated_at = CURRENT_TIMESTAMP';

        $sql = 'UPDATE users SET ' . implode(', ', $fields) . ' WHERE id = :id';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $this->findById((int) $id);
    }

    public function delete(string $id): bool
    {
        $sql = 'DELETE FROM users WHERE id = :id';
        $stmt = $this->db->prepare($sql);

        return $stmt->execute(['id' => $id]);
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE id = :id');
        $stmt->execute(['id' => $id]);

        $result = $stmt->fetch();

        return $result ?: null;
    }

    public function findByUuid(string $uuid): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE uuid = :uuid');
        $stmt->execute(['uuid' => $uuid]);

        $result = $stmt->fetch();

        return $result ?: null;
    }

    public function findByUsername(string $username): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE username = :username');
        $stmt->execute(['username' => $username]);

        $result = $stmt->fetch();

        return $result ?: null;
    }

    public function findByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE email = :email');
        $stmt->execute(['email' => $email]);

        $result = $stmt->fetch();

        return $result ?: null;
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
        if (password_verify($newPassword, $user['password'])) {
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

    /**
     * 取得使用者列表（分頁）
     */
    public function paginate(int $page = 1, int $perPage = 10, array $filters = []): array
    {
        $offset = ($page - 1) * $perPage;
        
        // 建立 WHERE 條件
        $where = [];
        $params = [];
        
        if (!empty($filters['search'])) {
            $where[] = '(username LIKE :search OR email LIKE :search)';
            $params['search'] = '%' . $filters['search'] . '%';
        }
        
        $whereClause = empty($where) ? '' : ' WHERE ' . implode(' AND ', $where);
        
        // 計算總數
        $countSql = 'SELECT COUNT(*) FROM users' . $whereClause;
        $countStmt = $this->db->prepare($countSql);
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();
        
        // 取得資料
        $sql = 'SELECT u.*, 
                GROUP_CONCAT(r.id) as role_ids,
                GROUP_CONCAT(r.name) as role_names,
                GROUP_CONCAT(r.display_name) as role_display_names
                FROM users u
                LEFT JOIN user_roles ur ON u.id = ur.user_id
                LEFT JOIN roles r ON ur.role_id = r.id'
                . $whereClause .
                ' GROUP BY u.id
                ORDER BY u.id DESC
                LIMIT :limit OFFSET :offset';
        
        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue(":{$key}", $value);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        $users = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $roleIds = $row['role_ids'] ? explode(',', $row['role_ids']) : [];
            $roleNames = $row['role_names'] ? explode(',', $row['role_names']) : [];
            $roleDisplayNames = $row['role_display_names'] ? explode(',', $row['role_display_names']) : [];
            
            $roles = [];
            for ($i = 0; $i < count($roleIds); $i++) {
                $roles[] = [
                    'id' => (int) $roleIds[$i],
                    'name' => $roleNames[$i] ?? '',
                    'display_name' => $roleDisplayNames[$i] ?? '',
                ];
            }
            
            // 移除敏感欄位
            unset($row['role_ids'], $row['role_names'], $row['role_display_names'], $row['password_hash'], $row['password']);
            $row['roles'] = $roles;
            $users[] = $row;
        }
        
        return [
            'items' => $users,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'last_page' => ceil($total / $perPage),
        ];
    }

    /**
     * 取得使用者的角色
     * 
     * @return int[]
     */
    public function getUserRoleIds(int $userId): array
    {
        $sql = 'SELECT role_id FROM user_roles WHERE user_id = :user_id';
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['user_id' => $userId]);
        
        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }

    /**
     * 設定使用者的角色
     * 
     * @param int[] $roleIds
     */
    public function setUserRoles(int $userId, array $roleIds): bool
    {
        $this->db->beginTransaction();

        try {
            // 刪除舊的角色
            $deleteSql = 'DELETE FROM user_roles WHERE user_id = :user_id';
            $deleteStmt = $this->db->prepare($deleteSql);
            $deleteStmt->execute(['user_id' => $userId]);

            // 新增新的角色
            if (!empty($roleIds)) {
                $insertSql = 'INSERT INTO user_roles (user_id, role_id, created_at) 
                              VALUES (:user_id, :role_id, datetime(\'now\'))';
                $insertStmt = $this->db->prepare($insertSql);

                foreach ($roleIds as $roleId) {
                    $insertStmt->execute([
                        'user_id' => $userId,
                        'role_id' => $roleId,
                    ]);
                }
            }

            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * 取得使用者完整資訊（包含角色）
     */
    public function findByIdWithRoles(int $id): ?array
    {
        $sql = 'SELECT u.*,
                GROUP_CONCAT(r.id) as role_ids,
                GROUP_CONCAT(r.name) as role_names,
                GROUP_CONCAT(r.display_name) as role_display_names
                FROM users u
                LEFT JOIN user_roles ur ON u.id = ur.user_id
                LEFT JOIN roles r ON ur.role_id = r.id
                WHERE u.id = :id
                GROUP BY u.id';
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$row) {
            return null;
        }
        
        $roleIds = $row['role_ids'] ? explode(',', $row['role_ids']) : [];
        $roleNames = $row['role_names'] ? explode(',', $row['role_names']) : [];
        $roleDisplayNames = $row['role_display_names'] ? explode(',', $row['role_display_names']) : [];
        
        $roles = [];
        for ($i = 0; $i < count($roleIds); $i++) {
            $roles[] = [
                'id' => (int) $roleIds[$i],
                'name' => $roleNames[$i] ?? '',
                'display_name' => $roleDisplayNames[$i] ?? '',
            ];
        }
        
        unset($row['role_ids'], $row['role_names'], $row['role_display_names']);
        $row['roles'] = $roles;
        
        return $row;
    }
}
