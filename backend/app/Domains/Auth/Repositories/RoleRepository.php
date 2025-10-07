<?php

declare(strict_types=1);

namespace App\Domains\Auth\Repositories;

use App\Domains\Auth\Models\Role;
use PDO;

/**
 * 角色 Repository
 */
class RoleRepository
{
    public function __construct(
        private readonly PDO $db,
    ) {
    }

    /**
     * 取得所有角色
     * 
     * @return Role[]
     */
    public function findAll(): array
    {
        $sql = 'SELECT * FROM roles ORDER BY id ASC';
        $stmt = $this->db->query($sql);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map(fn($row) => Role::fromArray($row), $rows);
    }

    /**
     * 根據 ID 取得角色
     */
    public function findById(int $id): ?Role
    {
        $sql = 'SELECT * FROM roles WHERE id = :id';
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? Role::fromArray($row) : null;
    }

    /**
     * 根據名稱取得角色
     */
    public function findByName(string $name): ?Role
    {
        $sql = 'SELECT * FROM roles WHERE name = :name';
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['name' => $name]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? Role::fromArray($row) : null;
    }

    /**
     * 根據 IDs 取得多個角色
     * 
     * @param int[] $ids
     * @return Role[]
     */
    public function findByIds(array $ids): array
    {
        if (empty($ids)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $sql = "SELECT * FROM roles WHERE id IN ({$placeholders}) ORDER BY id ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($ids);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map(fn($row) => Role::fromArray($row), $rows);
    }

    /**
     * 建立角色
     */
    public function create(string $name, string $displayName, ?string $description = null): Role
    {
        $sql = 'INSERT INTO roles (name, display_name, description, created_at) 
                VALUES (:name, :display_name, :description, datetime(\'now\'))';
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'name' => $name,
            'display_name' => $displayName,
            'description' => $description,
        ]);

        $id = (int) $this->db->lastInsertId();
        
        return $this->findById($id) ?? throw new \RuntimeException('Failed to create role');
    }

    /**
     * 更新角色
     */
    public function update(int $id, ?string $displayName = null, ?string $description = null): bool
    {
        $updates = [];
        $params = ['id' => $id];

        if ($displayName !== null) {
            $updates[] = 'display_name = :display_name';
            $params['display_name'] = $displayName;
        }

        if ($description !== null) {
            $updates[] = 'description = :description';
            $params['description'] = $description;
        }

        $updates[] = 'updated_at = datetime(\'now\')';

        if (empty($updates)) {
            return false;
        }

        $sql = 'UPDATE roles SET ' . implode(', ', $updates) . ' WHERE id = :id';
        $stmt = $this->db->prepare($sql);
        
        return $stmt->execute($params);
    }

    /**
     * 刪除角色
     */
    public function delete(int $id): bool
    {
        $sql = 'DELETE FROM roles WHERE id = :id';
        $stmt = $this->db->prepare($sql);
        
        return $stmt->execute(['id' => $id]);
    }

    /**
     * 取得角色的權限 IDs
     * 
     * @return int[]
     */
    public function getRolePermissionIds(int $roleId): array
    {
        $sql = 'SELECT permission_id FROM role_permissions WHERE role_id = :role_id';
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['role_id' => $roleId]);
        
        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }

    /**
     * 設定角色的權限
     * 
     * @param int[] $permissionIds
     */
    public function setRolePermissions(int $roleId, array $permissionIds): bool
    {
        $this->db->beginTransaction();

        try {
            // 刪除舊的權限
            $deleteSql = 'DELETE FROM role_permissions WHERE role_id = :role_id';
            $deleteStmt = $this->db->prepare($deleteSql);
            $deleteStmt->execute(['role_id' => $roleId]);

            // 新增新的權限
            if (!empty($permissionIds)) {
                $insertSql = 'INSERT INTO role_permissions (role_id, permission_id, created_at) 
                              VALUES (:role_id, :permission_id, datetime(\'now\'))';
                $insertStmt = $this->db->prepare($insertSql);

                foreach ($permissionIds as $permissionId) {
                    $insertStmt->execute([
                        'role_id' => $roleId,
                        'permission_id' => $permissionId,
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
}
