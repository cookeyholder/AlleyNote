<?php

declare(strict_types=1);

namespace App\Services\Security;

use PDO;
use App\Services\Security\Contracts\AuthorizationServiceInterface;
use App\Services\CacheService;

class AuthorizationService implements AuthorizationServiceInterface
{
    private PDO $db;
    private CacheService $cache;
    private const CACHE_TTL = 3600; // 1 hour

    public function __construct(PDO $db, CacheService $cache)
    {
        $this->db = $db;
        $this->cache = $cache;
    }

    public function hasPermission(int $userId, string $permission): bool
    {
        $cacheKey = "user_permissions:{$userId}";

        $permissions = $this->cache->remember($cacheKey, function () use ($userId) {
            return $this->getUserPermissions($userId);
        }, self::CACHE_TTL);

        return in_array($permission, $permissions, true);
    }

    public function hasRole(int $userId, string $roleName): bool
    {
        $cacheKey = "user_roles:{$userId}";

        $roles = $this->cache->remember($cacheKey, function () use ($userId) {
            return $this->getUserRoles($userId);
        }, self::CACHE_TTL);

        return in_array($roleName, array_column($roles, 'name'), true);
    }

    public function can(int $userId, string $resource, string $action): bool
    {
        // 檢查是否為超級管理員
        if ($this->isSuperAdmin($userId)) {
            return true;
        }

        // 檢查具體權限
        $permission = "{$resource}:{$action}";
        return $this->hasPermission($userId, $permission);
    }

    public function assignRole(int $userId, string $roleName): bool
    {
        try {
            // 先檢查角色是否存在
            $stmt = $this->db->prepare('SELECT id FROM roles WHERE name = ?');
            $stmt->execute([$roleName]);
            $role = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$role) {
                return false;
            }

            // 檢查是否已經分配
            $stmt = $this->db->prepare('SELECT COUNT(*) FROM user_roles WHERE user_id = ? AND role_id = ?');
            $stmt->execute([$userId, $role['id']]);

            if ($stmt->fetchColumn() > 0) {
                return true; // 已經存在
            }

            // 分配角色
            $stmt = $this->db->prepare('INSERT INTO user_roles (user_id, role_id) VALUES (?, ?)');
            $result = $stmt->execute([$userId, $role['id']]);

            if ($result) {
                $this->clearUserCache($userId);
            }

            return $result;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function removeRole(int $userId, string $roleName): bool
    {
        try {
            $stmt = $this->db->prepare('
                DELETE FROM user_roles 
                WHERE user_id = ? AND role_id = (
                    SELECT id FROM roles WHERE name = ?
                )
            ');
            $result = $stmt->execute([$userId, $roleName]);

            if ($result) {
                $this->clearUserCache($userId);
            }

            return $result;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function givePermission(int $userId, string $permission): bool
    {
        try {
            // 先檢查權限是否存在
            $stmt = $this->db->prepare('SELECT id FROM permissions WHERE name = ?');
            $stmt->execute([$permission]);
            $perm = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$perm) {
                return false;
            }

            // 檢查是否已經分配
            $stmt = $this->db->prepare('SELECT COUNT(*) FROM user_permissions WHERE user_id = ? AND permission_id = ?');
            $stmt->execute([$userId, $perm['id']]);

            if ($stmt->fetchColumn() > 0) {
                return true; // 已經存在
            }

            // 分配權限
            $stmt = $this->db->prepare('INSERT INTO user_permissions (user_id, permission_id) VALUES (?, ?)');
            $result = $stmt->execute([$userId, $perm['id']]);

            if ($result) {
                $this->clearUserCache($userId);
            }

            return $result;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function revokePermission(int $userId, string $permission): bool
    {
        try {
            $stmt = $this->db->prepare('
                DELETE FROM user_permissions 
                WHERE user_id = ? AND permission_id = (
                    SELECT id FROM permissions WHERE name = ?
                )
            ');
            $result = $stmt->execute([$userId, $permission]);

            if ($result) {
                $this->clearUserCache($userId);
            }

            return $result;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function getUserRoles(int $userId): array
    {
        $stmt = $this->db->prepare('
            SELECT r.id, r.name, r.description, r.created_at, r.updated_at
            FROM roles r
            INNER JOIN user_roles ur ON r.id = ur.role_id
            WHERE ur.user_id = ?
        ');
        $stmt->execute([$userId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getUserPermissions(int $userId): array
    {
        // 取得角色權限
        $stmt = $this->db->prepare('
            SELECT DISTINCT p.name
            FROM permissions p
            INNER JOIN role_permissions rp ON p.id = rp.permission_id
            INNER JOIN user_roles ur ON rp.role_id = ur.role_id
            WHERE ur.user_id = ?
        ');
        $stmt->execute([$userId]);
        $rolePermissions = $stmt->fetchAll(PDO::FETCH_COLUMN);

        // 取得直接權限
        $stmt = $this->db->prepare('
            SELECT DISTINCT p.name
            FROM permissions p
            INNER JOIN user_permissions up ON p.id = up.permission_id
            WHERE up.user_id = ?
        ');
        $stmt->execute([$userId]);
        $directPermissions = $stmt->fetchAll(PDO::FETCH_COLUMN);

        // 合併並去重
        return array_unique(array_merge($rolePermissions, $directPermissions));
    }

    public function isSuperAdmin(int $userId): bool
    {
        return $this->hasRole($userId, 'admin');
    }

    private function clearUserCache(int $userId): void
    {
        $this->cache->delete("user_permissions:{$userId}");
        $this->cache->delete("user_roles:{$userId}");
    }
}
