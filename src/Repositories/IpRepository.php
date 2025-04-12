namespace App\Repositories;

use PDO;
use App\Exceptions\ValidationException;

class IpRepository extends AbstractRepository
{
protected string $table = 'ip_lists';
protected array $fillable = [
'uuid',
'ip_address',
'type',
'unit_id',
'description'
];

public function __construct(PDO $db)
{
parent::__construct($db);
}

public function create(array $data): array
{
$this->validateIpAddress($data['ip_address']);
$data['uuid'] = $this->generateUuid();
return parent::create($data);
}

public function findByUuid(string $uuid): ?array
{
return $this->findBy('uuid', $uuid);
}

public function isIpAllowed(string $ip, int $unitId): bool
{
// 檢查 IP 是否在黑名單中
$blacklisted = $this->isIpInList($ip, 0, $unitId);
if ($blacklisted) {
return false;
}

// 檢查是否有白名單規則
$hasWhitelist = $this->hasWhitelistRules($unitId);
if (!$hasWhitelist) {
return true; // 如果沒有白名單規則，則允許所有未被黑名單的 IP
}

// 如果有白名單規則，則檢查 IP 是否在白名單中
return $this->isIpInList($ip, 1, $unitId);
}

private function isIpInList(string $ip, int $type, int $unitId): bool
{
$sql = "SELECT ip_address FROM {$this->table}
WHERE type = ? AND unit_id = ?";
$stmt = $this->db->prepare($sql);
$stmt->execute([$type, $unitId]);

$ipLong = ip2long($ip);
while ($rule = $stmt->fetch(PDO::FETCH_ASSOC)) {
if ($this->ipMatchesRule($ip, $ipLong, $rule['ip_address'])) {
return true;
}
}

return false;
}

private function hasWhitelistRules(int $unitId): bool
{
$sql = "SELECT COUNT(*) FROM {$this->table}
WHERE type = 1 AND unit_id = ?";
$stmt = $this->db->prepare($sql);
$stmt->execute([$unitId]);
return $stmt->fetchColumn() > 0;
}

private function ipMatchesRule(string $ip, int $ipLong, string $rule): bool
{
// 檢查是否為 CIDR 格式
if (str_contains($rule, '/')) {
[$network, $bits] = explode('/', $rule);
$networkLong = ip2long($network);
$mask = -1 << (32 - $bits);
    return ($ipLong & $mask)===($networkLong & $mask);
    }

    // 一般 IP 位址比對
    return $ip===$rule;
    }

    private function validateIpAddress(string $ip): void
    {
    // 檢查 CIDR 格式
    if (str_contains($ip, '/' )) {
    [$network, $bits]=explode('/', $ip);
    if (!filter_var($network, FILTER_VALIDATE_IP) ||
    !is_numeric($bits) ||
    $bits < 0 ||
    $bits> 32) {
    throw new ValidationException('無效的 CIDR 格式');
    }
    return;
    }

    // 檢查一般 IP 位址
    if (!filter_var($ip, FILTER_VALIDATE_IP)) {
    throw new ValidationException('無效的 IP 位址格式');
    }
    }

    private function generateUuid(): string
    {
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
    mt_rand(0, 0xffff), mt_rand(0, 0xffff),
    mt_rand(0, 0xffff),
    mt_rand(0, 0x0fff) | 0x4000,
    mt_rand(0, 0x3fff) | 0x8000,
    mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
    }
    }