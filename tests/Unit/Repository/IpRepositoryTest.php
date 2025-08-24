<?php

declare(strict_types=1);

namespace Tests\Unit\Repository;

use App\Domains\Security\Repositories\IpRepository;
use App\Infrastructure\Services\CacheService;
use InvalidArgumentException;
use Mockery;
use PDO;
use PHPUnit\Framework\TestCase;

class IpRepositoryTest extends TestCase
{
    private PDO $db;

    private CacheService $cache;

    private IpRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->db = new PDO('sqlite::memory:');
        $this->createTestTables();

        $this->cache = Mockery::mock(CacheService::class);
        $this->cache->shouldReceive('remember')
            ->byDefault()
            ->andReturnUsing(function ($key, $callback) {
                return $callback();
            });
        $this->cache->shouldReceive('delete')->byDefault();
        $this->cache->shouldReceive('set')
            ->byDefault()
            ->andReturn(true);
        $this->cache->shouldReceive('get')
            ->byDefault()
            ->andReturn(null);
        $this->cache->shouldReceive('has')
            ->byDefault()
            ->andReturn(false);

        $this->repository = new IpRepository($this->db, $this->cache);
    }

    protected function createTestTables(): void
    {
        // 建立 IP 黑白名單資料表
        $this->db->exec('
            CREATE TABLE ip_lists (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                uuid VARCHAR(36) NOT NULL,
                ip_address VARCHAR(45) NOT NULL,
                type INTEGER NOT NULL DEFAULT 0,
                unit_id INTEGER,
                description TEXT,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL
            )
        ');

        // 建立索引
        $this->db->exec('CREATE INDEX idx_ip_lists_ip_address ON ip_lists(ip_address)');
        $this->db->exec('CREATE INDEX idx_ip_lists_type ON ip_lists(type)');
        $this->db->exec('CREATE INDEX idx_ip_lists_unit_id ON ip_lists(unit_id)');
    }

    public function testCanCreateIpRule(): void
    {
        $data = [
            'ip_address' => '192.168.1.1',
            'type' => 1,
            'description' => '測試白名單',
        ];

        $result = $this->repository->create($data);

        $this->assertNotNull($result->getId());
        $this->assertEquals('192.168.1.1', $result->getIpAddress());
        $this->assertEquals(1, $result->getType());
        $this->assertEquals('測試白名單', $result->getDescription());
    }

    public function testCannotCreateInvalidIpAddress(): void
    {
        $data = [
            'ip_address' => 'invalid-ip',
            'type' => 1,
        ];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('無效的 IP 位址格式');

        $this->repository->create($data);
    }

    public function testCanCreateCidrRange(): void
    {
        $data = [
            'ip_address' => '192.168.1.0/24',
            'type' => 0,
            'description' => '測試子網路遮罩',
        ];

        $result = $this->repository->create($data);

        $this->assertEquals('192.168.1.0/24', $result->getIpAddress());
    }

    public function testFindByIpAddress(): void
    {
        // 先建立一筆資料
        $this->repository->create([
            'ip_address' => '192.168.1.1',
            'type' => 1,
        ]);

        $result = $this->repository->findByIpAddress('192.168.1.1');

        $this->assertNotNull($result);
        $this->assertEquals('192.168.1.1', $result->getIpAddress());
    }

    public function testGetByType(): void
    {
        // 建立白名單
        $this->repository->create([
            'ip_address' => '192.168.1.1',
            'type' => 1,
        ]);

        // 建立黑名單
        $this->repository->create([
            'ip_address' => '192.168.1.2',
            'type' => 0,
        ]);

        $whitelist = $this->repository->getByType(1);
        $blacklist = $this->repository->getByType(0);

        $this->assertCount(1, $whitelist);
        $this->assertCount(1, $blacklist);
        $this->assertEquals('192.168.1.1', $whitelist[0]->getIpAddress());
        $this->assertEquals('192.168.1.2', $blacklist[0]->getIpAddress());
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }
}
