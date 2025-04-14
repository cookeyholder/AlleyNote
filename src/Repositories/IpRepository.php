<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;
use App\Models\IpList;
use App\Services\CacheService;
use App\Repositories\Contracts\IpRepositoryInterface;

class IpRepository implements IpRepositoryInterface
{
    private const CACHE_TTL = 3600; // 1小時

    public function __construct(
        private PDO $db,
        private CacheService $cache
    ) {}

    private function getCacheKey(string $type, mixed $identifier): string
    {
        return "ip_list:{$type}:{$identifier}";
    }

    private function validateIpAddress(string $ipAddress): void
    {
        // 驗證一般 IP 位址
        if (filter_var($ipAddress, FILTER_VALIDATE_IP)) {
            return;
        }

        // 驗證 CIDR 格式
        if (preg_match('/^([0-9]{1,3}\.){3}[0-9]{1,3}\/([0-9]|[1-2][0-9]|3[0-2])$/', $ipAddress)) {
            return;
        }

        throw new \InvalidArgumentException('無效的 IP 位址格式');
    }

    public function create(array $data): IpList
    {
        $this->validateIpAddress($data['ip_address']);

        $now = date('Y-m-d H:i:s');
        $data['uuid'] = generate_uuid();
        $data['created_at'] = $now;
        $data['updated_at'] = $now;

        $sql = "INSERT INTO ip_lists (uuid, ip_address, type, unit_id, description, created_at, updated_at) 
                VALUES (:uuid, :ip_address, :type, :unit_id, :description, :created_at, :updated_at)";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'uuid' => $data['uuid'],
            'ip_address' => $data['ip_address'],
            'type' => $data['type'] ?? 0,
            'unit_id' => $data['unit_id'] ?? null,
            'description' => $data['description'] ?? null,
            'created_at' => $data['created_at'],
            'updated_at' => $data['updated_at']
        ]);

        $id = (int) $this->db->lastInsertId();
        return $this->find($id);
    }

    public function find(int $id): ?IpList
    {
        $cacheKey = $this->getCacheKey('id', $id);

        return $this->cache->remember($cacheKey, function () use ($id) {
            $stmt = $this->db->prepare('SELECT * FROM ip_lists WHERE id = ?');
            $stmt->execute([$id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return $result ? new IpList($result) : null;
        }, self::CACHE_TTL);
    }

    public function findByUuid(string $uuid): ?IpList
    {
        $cacheKey = $this->getCacheKey('uuid', $uuid);

        return $this->cache->remember($cacheKey, function () use ($uuid) {
            $stmt = $this->db->prepare('SELECT * FROM ip_lists WHERE uuid = ?');
            $stmt->execute([$uuid]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return $result ? new IpList($result) : null;
        }, self::CACHE_TTL);
    }

    public function findByIpAddress(string $ipAddress): ?IpList
    {
        $this->validateIpAddress($ipAddress);
        $cacheKey = $this->getCacheKey('ip', $ipAddress);

        return $this->cache->remember($cacheKey, function () use ($ipAddress) {
            $stmt = $this->db->prepare('SELECT * FROM ip_lists WHERE ip_address = ?');
            $stmt->execute([$ipAddress]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return $result ? new IpList($result) : null;
        }, self::CACHE_TTL);
    }

    public function update(int $id, array $data): IpList
    {
        if (isset($data['ip_address'])) {
            $this->validateIpAddress($data['ip_address']);
        }

        $data['updated_at'] = date('Y-m-d H:i:s');

        $sets = [];
        $params = ['id' => $id];

        foreach ($data as $key => $value) {
            if ($key !== 'id' && $key !== 'uuid' && $key !== 'created_at') {
                $sets[] = "{$key} = :{$key}";
                $params[$key] = $value;
            }
        }

        if (empty($sets)) {
            return $this->find($id);
        }

        $sql = "UPDATE ip_lists SET " . implode(', ', $sets) . " WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        // 清除快取
        $ipList = $this->find($id);
        if ($ipList) {
            $this->cache->delete($this->getCacheKey('id', $ipList->getId()));
            $this->cache->delete($this->getCacheKey('uuid', $ipList->getUuid()));
            $this->cache->delete($this->getCacheKey('ip', $ipList->getIpAddress()));
            $this->cache->delete('ip_lists:type:' . $ipList->getType());
        }

        return $this->find($id);
    }

    public function delete(int $id): bool
    {
        // 先取得資料以便清除快取
        $ipList = $this->find($id);
        if ($ipList) {
            $this->cache->delete($this->getCacheKey('id', $ipList->getId()));
            $this->cache->delete($this->getCacheKey('uuid', $ipList->getUuid()));
            $this->cache->delete($this->getCacheKey('ip', $ipList->getIpAddress()));
            $this->cache->delete('ip_lists:type:' . $ipList->getType());
        }

        $stmt = $this->db->prepare('DELETE FROM ip_lists WHERE id = ?');
        return $stmt->execute([$id]);
    }

    public function getByType(int $type): array
    {
        $cacheKey = 'ip_lists:type:' . $type;

        return $this->cache->remember($cacheKey, function () use ($type) {
            $stmt = $this->db->prepare('SELECT * FROM ip_lists WHERE type = ? ORDER BY created_at DESC');
            $stmt->execute([$type]);

            return array_map(
                fn($row) => new IpList($row),
                $stmt->fetchAll(PDO::FETCH_ASSOC)
            );
        }, self::CACHE_TTL);
    }

    public function paginate(int $page = 1, int $perPage = 10, array $conditions = []): array
    {
        $offset = ($page - 1) * $perPage;
        $where = [];
        $params = [];

        if (!empty($conditions)) {
            foreach ($conditions as $key => $value) {
                $where[] = "{$key} = ?";
                $params[] = $value;
            }
        }

        // 計算總筆數
        $countSql = 'SELECT COUNT(*) FROM ip_lists' .
            (empty($where) ? '' : ' WHERE ' . implode(' AND ', $where));
        $stmt = $this->db->prepare($countSql);
        $stmt->execute($params);
        $total = (int) $stmt->fetchColumn();

        // 取得分頁資料
        $sql = 'SELECT * FROM ip_lists' .
            (empty($where) ? '' : ' WHERE ' . implode(' AND ', $where)) .
            ' ORDER BY created_at DESC LIMIT ?, ?';

        $stmt = $this->db->prepare($sql);
        $params[] = $offset;
        $params[] = $perPage;
        $stmt->execute($params);

        $items = array_map(
            fn($row) => new IpList($row),
            $stmt->fetchAll(PDO::FETCH_ASSOC)
        );

        return [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'last_page' => ceil($total / $perPage)
        ];
    }

    public function isBlacklisted(string $ipAddress): bool
    {
        $this->validateIpAddress($ipAddress);

        $stmt = $this->db->prepare('
            SELECT COUNT(*) FROM ip_lists 
            WHERE type = 0 
            AND (ip_address = ? OR ? LIKE REPLACE(ip_address, "/24", ".%"))
        ');
        $stmt->execute([$ipAddress, $ipAddress]);

        return (bool) $stmt->fetchColumn();
    }

    public function isWhitelisted(string $ipAddress): bool
    {
        $this->validateIpAddress($ipAddress);

        $stmt = $this->db->prepare('
            SELECT COUNT(*) FROM ip_lists 
            WHERE type = 1 
            AND (ip_address = ? OR ? LIKE REPLACE(ip_address, "/24", ".%"))
        ');
        $stmt->execute([$ipAddress, $ipAddress]);

        return (bool) $stmt->fetchColumn();
    }
}
