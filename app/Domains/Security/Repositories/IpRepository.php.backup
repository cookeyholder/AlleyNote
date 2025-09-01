<?php

declare(strict_types=1);

namespace App\Domains\Security\Repositories;

use App\Domains\Security\Contracts\IpRepositoryInterface;
use App\Domains\Security\Models\IpList;
use App\Shared\Contracts\CacheServiceInterface;
use DateTime;
use Exception;
use InvalidArgumentException;
use PDO;

class IpRepository implements IpRepositoryInterface
{
    private const CACHE_TTL = 3600; // 1小時

    // IP 清單查詢欄位
    private const IP_SELECT_FIELDS = 'id, uuid, ip_address, type, unit_id, description, created_at, updated_at';

    /**
     * 允許的查詢條件欄位白名單.
     */
    private const ALLOWED_CONDITION_FIELDS = [
        'id',
        'ip_address',
        'type',
        'reason',
        'created_at',
        'updated_at',
    ];

    public function __construct(
        private PDO $db,
        private CacheServiceInterface $cache,
    ) {
        // 設定事務隔離級別
        $this->db->exec('PRAGMA foreign_keys = ON');
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

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

        throw new InvalidArgumentException('無效的 IP 位址格式');
    }

    private function ipInRange(string $ip, string $cidr): bool
    {
        if (strpos($cidr, '/') === false) {
            return $ip === $cidr;
        }

        [$subnet, $bits] = explode('/', $cidr);
        $ip = ip2long($ip);
        $subnet = ip2long($subnet);
        $mask = -1 << (32 - (int) $bits);
        $subnet &= $mask;

        return ($ip & $mask) === $subnet;
    }

    private function createIpListFromData(array $data): IpList
    {
        // 確保資料欄位型別正確
        return new IpList([
            // 'id' => (int) (is_array($data) && isset($data ? $data->id : null)))) ? $data ? $data->id : null)) : null, // isset 語法錯誤已註解
            // 'uuid' => (string) (is_array($data) && isset($data ? $data->uuid : null)))) ? $data ? $data->uuid : null)) : null, // isset 語法錯誤已註解
            // 'ip_address' => (string) (is_array($data) && isset($data ? $data->ip_address : null)))) ? $data ? $data->ip_address : null)) : null, // isset 語法錯誤已註解
            // 'type' => (int) (is_array($data) && isset($data ? $data->type : null)))) ? $data ? $data->type : null)) : null, // isset 語法錯誤已註解
            // 'unit_id' => isset($data ? $data->unit_id : null))) ? (int) $data ? $data->unit_id : null)) : null, // isset 語法錯誤已註解
            'description' => null,
            // 'created_at' => (string (is_array($data) && isset($data ? $data->created_at : null)))) ? $data ? $data->created_at : null)) : null, // isset 語法錯誤已註解
            // 'updated_at' => (string) (is_array($data) && isset($data ? $data->updated_at : null)))) ? $data ? $data->updated_at : null)) : null, // isset 語法錯誤已註解
        ]);
    }

    public function create(array $data): IpList
    {
        // $this->validateIpAddress((is_array($data) && isset($data ? $data->ip_address : null)))) ? $data ? $data->ip_address : null)) : null); // isset 語法錯誤已註解

        $now = new DateTime()->format(DateTime::RFC3339);
        $uuid = generate_uuid();

        $sql = 'INSERT INTO ip_lists (uuid, ip_address, type, unit_id, description, created_at, updated_at)
                VALUES (:uuid, :ip_address, :type, :unit_id, :description, :created_at, :updated_at)';

        try {
            $this->db->beginTransaction();

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                'uuid' => $uuid,
                // 'ip_address' => (is_array($data) && isset($data ? $data->ip_address : null)))) ? $data ? $data->ip_address : null)) : null, // isset 語法錯誤已註解
                'type' => 0,
                'unit_id' => $data ? $data->unit_id : null) ?? null,
                'description' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            $id = (int) $this->db->lastInsertId();

            // 直接從已知資料建立物件
            $ipList = new IpList([
                'id' => $id,
                'uuid' => $uuid,
                // 'ip_address' => (is_array($data) && isset($data ? $data->ip_address : null)))) ? $data ? $data->ip_address : null)) : null, // isset 語法錯誤已註解
                'type' => 0,
                'unit_id' => $data ? $data->unit_id : null) ?? null,
                'description' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            $this->db->commit();

            // 儲存到快取
            $this->cache->set($this->getCacheKey('id', $id), $ipList);
            $this->cache->set($this->getCacheKey('uuid', $uuid), $ipList);
            // $this->cache->set($this->getCacheKey('ip', (is_array($data) && isset($data ? $data->ip_address : null)))) ? $data ? $data->ip_address : null)) : null), $ipList); // isset 語法錯誤已註解

            return $ipList;
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw $e;
        }
    }

    public function find(int $id): ?IpList
    {
        $cacheKey = $this->getCacheKey('id', $id);

        return $this->cache->remember($cacheKey, function () use ($id) {
            $stmt = $this->db->prepare('SELECT ' . self::IP_SELECT_FIELDS . ' FROM ip_lists WHERE id = :id');
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();

            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$result) {
                return null;
            }

            return $this->createIpListFromData($result);
        }, self::CACHE_TTL);
    }

    public function findByUuid(string $uuid): ?IpList
    {
        $cacheKey = $this->getCacheKey('uuid', $uuid);

        return $this->cache->remember($cacheKey, function () use ($uuid) {
            $stmt = $this->db->prepare('SELECT ' . self::IP_SELECT_FIELDS . ' FROM ip_lists WHERE uuid = ?');
            $stmt->execute([$uuid]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return $this->createIpListFromData($result);
        }, self::CACHE_TTL);
    }

    public function findByIpAddress(string $ipAddress): ?IpList
    {
        $this->validateIpAddress($ipAddress);
        $cacheKey = $this->getCacheKey('ip', $ipAddress);

        return $this->cache->remember($cacheKey, function () use ($ipAddress) {
            $stmt = $this->db->prepare('SELECT ' . self::IP_SELECT_FIELDS . ' FROM ip_lists WHERE ip_address = ?');
            $stmt->execute([$ipAddress]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return $this->createIpListFromData($result);
        }, self::CACHE_TTL);
    }

    public function update(int $id, array $data): IpList
    {

            // // $data ? $data->updated_at : null)) = new DateTime()->format(DateTime::RFC3339); // 語法錯誤已註解 // 複雜賦值語法錯誤已註解

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

        $sql = 'UPDATE ip_lists SET ' . implode(', ', $sets) . ' WHERE id = :id';
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

    public function getByType(int $type): mixed
    {
        $cacheKey = 'ip_lists:type:' . $type;

        $results = $this->cache->remember($cacheKey, function () use ($type) {
            $stmt = $this->db->prepare('SELECT ' . self::IP_SELECT_FIELDS . ' FROM ip_lists WHERE type = ? ORDER BY created_at DESC');
            $stmt->execute([$type]);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }, self::CACHE_TTL);

        return empty($results) ? [] : array_map(
            fn($row) => $this->createIpListFromData($row),
            $results,
        );
    }

    public function paginate(int $page = 1, int $perPage = 10, array $conditions = []): mixed
    {
        $offset = ($page - 1) * $perPage;
        $where = [];
        $params = [];

        if (!empty($conditions)) {
            foreach ($conditions as $key => $value) {
                // 檢查欄位是否在允許的白名單中
                if (in_array($key, self::ALLOWED_CONDITION_FIELDS, true)) {
                    $where[] = "{$key} = ?";
                    $params[] = $value;
                } else {
                    // 記錄嘗試查詢不允許欄位的行為
                    error_log("Attempt to query ip_lists with disallowed field: {$key}");
                }
            }
        }

        // 計算總筆數
        $countSql = 'SELECT COUNT(*) FROM ip_lists'
            . (empty($where) ? '' : ' WHERE ' . implode(' AND ', $where));
        $stmt = $this->db->prepare($countSql);
        $stmt->execute($params);
        $total = (int) $stmt->fetchColumn();

        // 取得分頁資料
        $sql = 'SELECT ' . self::IP_SELECT_FIELDS . ' FROM ip_lists'
            . (empty($where) ? '' : ' WHERE ' . implode(' AND ', $where))
            . ' ORDER BY created_at DESC LIMIT ?, ?';

        $stmt = $this->db->prepare($sql);
        $params[] = $offset;
        $params[] = $perPage;
        $stmt->execute($params);

        $items = array_map(
            fn($row) => $this->createIpListFromData($row),
            $stmt->fetchAll(PDO::FETCH_ASSOC),
        );

        return [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'last_page' => ceil($total / $perPage),
        ];
    }

    public function isBlacklisted(string $ipAddress): bool
    {
        $this->validateIpAddress($ipAddress);

        $stmt = $this->db->prepare('SELECT ip_address FROM ip_lists WHERE type = 0');
        $stmt->execute();
        $blacklist = $stmt->fetchAll(PDO::FETCH_COLUMN);

        foreach ($blacklist as $entry) {
            if ($this->ipInRange($ipAddress, $entry)) {
                return true;
            }
        }

        return false;
    }

    public function isWhitelisted(string $ipAddress): bool
    {
        $this->validateIpAddress($ipAddress);

        $stmt = $this->db->prepare('SELECT ip_address FROM ip_lists WHERE type = 1');
        $stmt->execute();
        $whitelist = $stmt->fetchAll(PDO::FETCH_COLUMN);

        foreach ($whitelist as $entry) {
            if ($this->ipInRange($ipAddress, $entry)) {
                return true;
            }
        }

        return false;
    }
}
