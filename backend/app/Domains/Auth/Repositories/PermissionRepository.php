<?php

declare(strict_types=1);

namespace App\Domains\Auth\Repositories;

use App\Domains\Auth\Models\Permission;
use PDO;

/**
 * 權限 Repository
 */
class PermissionRepository
{
    public function __construct(
        private readonly PDO $db,
    ) {
    }

    /**
     * 取得所有權限
     * 
     * @return Permission[]
     */
    public function findAll(): array
    {
        $sql = 'SELECT * FROM permissions ORDER BY resource ASC, action ASC';
        $stmt = $this->db->query($sql);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map(fn($row) => Permission::fromArray($row), $rows);
    }

    /**
     * 根據 ID 取得權限
     */
    public function findById(int $id): ?Permission
    {
        $sql = 'SELECT * FROM permissions WHERE id = :id';
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? Permission::fromArray($row) : null;
    }

    /**
     * 根據名稱取得權限
     */
    public function findByName(string $name): ?Permission
    {
        $sql = 'SELECT * FROM permissions WHERE name = :name';
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['name' => $name]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? Permission::fromArray($row) : null;
    }

    /**
     * 根據 IDs 取得多個權限
     * 
     * @param int[] $ids
     * @return Permission[]
     */
    public function findByIds(array $ids): array
    {
        if (empty($ids)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $sql = "SELECT * FROM permissions WHERE id IN ({$placeholders}) ORDER BY resource ASC, action ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($ids);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map(fn($row) => Permission::fromArray($row), $rows);
    }

    /**
     * 根據資源取得權限
     * 
     * @return Permission[]
     */
    public function findByResource(string $resource): array
    {
        $sql = 'SELECT * FROM permissions WHERE resource = :resource ORDER BY action ASC';
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['resource' => $resource]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map(fn($row) => Permission::fromArray($row), $rows);
    }

    /**
     * 取得所有權限，按資源分組
     * 
     * @return array<string, Permission[]>
     */
    public function findAllGroupedByResource(): array
    {
        $permissions = $this->findAll();
        $grouped = [];

        foreach ($permissions as $permission) {
            $resource = $permission->getResource();
            if (!isset($grouped[$resource])) {
                $grouped[$resource] = [];
            }
            $grouped[$resource][] = $permission;
        }

        return $grouped;
    }
}
