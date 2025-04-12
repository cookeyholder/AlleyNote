namespace Tests\Unit\Repositories;

use App\Database\DatabaseConnection;
use App\Repositories\IpRepository;
use PDO;
use PHPUnit\Framework\TestCase;

class IpRepositoryTest extends TestCase
{
private PDO $db;
private IpRepository $repository;

protected function setUp(): void
{
parent::setUp();
$this->db = new PDO('sqlite::memory:');
$this->setupTestDatabase();
$this->repository = new IpRepository($this->db);
}

/** @test */
public function shouldCreateNewIpRule(): void
{
// 安排
$data = [
'ip_address' => '192.168.1.1',
'type' => 1, // 白名單
'unit_id' => 1,
'description' => '測試 IP 規則'
];

// 執行
$rule = $this->repository->create($data);

// 驗證
$this->assertNotNull($rule['id']);
$this->assertNotNull($rule['uuid']);
$this->assertEquals('192.168.1.1', $rule['ip_address']);
$this->assertEquals(1, $rule['type']);
}

/** @test */
public function shouldFindRuleByUuid(): void
{
// 安排
$data = [
'uuid' => 'test-uuid',
'ip_address' => '192.168.1.1',
'type' => 1,
'unit_id' => 1
];
$this->insertTestRule($data);

// 執行
$rule = $this->repository->findByUuid('test-uuid');

// 驗證
$this->assertNotNull($rule);
$this->assertEquals('192.168.1.1', $rule['ip_address']);
}

/** @test */
public function shouldCheckIfIpIsAllowed(): void
{
// 安排
$this->insertTestRule([
'ip_address' => '192.168.1.1',
'type' => 1, // 白名單
'unit_id' => 1
]);

$this->insertTestRule([
'ip_address' => '192.168.1.2',
'type' => 0, // 黑名單
'unit_id' => 1
]);

// 執行與驗證
$this->assertTrue($this->repository->isIpAllowed('192.168.1.1', 1));
$this->assertFalse($this->repository->isIpAllowed('192.168.1.2', 1));
}

/** @test */
public function shouldHandleCidrRanges(): void
{
// 安排
$this->insertTestRule([
'ip_address' => '192.168.1.0/24',
'type' => 1, // 白名單
'unit_id' => 1
]);

// 執行與驗證
$this->assertTrue($this->repository->isIpAllowed('192.168.1.100', 1));
$this->assertFalse($this->repository->isIpAllowed('192.168.2.1', 1));
}

private function setupTestDatabase(): void
{
$this->db->exec("
CREATE TABLE ip_lists (
id INTEGER PRIMARY KEY AUTOINCREMENT,
uuid TEXT NOT NULL,
ip_address TEXT NOT NULL,
type INTEGER NOT NULL,
unit_id INTEGER,
description TEXT,
created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
FOREIGN KEY (unit_id) REFERENCES units (id)
)
");
}

private function insertTestRule(array $data): void
{
$sql = "INSERT INTO ip_lists (uuid, ip_address, type, unit_id, description)
VALUES (:uuid, :ip_address, :type, :unit_id, :description)";

$stmt = $this->db->prepare($sql);
$stmt->execute([
'uuid' => $data['uuid'] ?? 'test-uuid',
'ip_address' => $data['ip_address'],
'type' => $data['type'],
'unit_id' => $data['unit_id'],
'description' => $data['description'] ?? null
]);
}
}