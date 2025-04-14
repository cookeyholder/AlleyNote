<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Controllers\IpController;
use App\Services\IpService;
use App\Repositories\IpRepository;
use App\Services\CacheService;
use App\Models\IpList;
use PDO;
use Tests\TestCase;
use Mockery;

class IpManagementTest extends TestCase
{
    protected IpService $service;
    protected IpRepository $repository;
    protected IpController $controller;

    protected function setUp(): void
    {
        parent::setUp();

        // 建立測試資料庫連線
        $this->db = new PDO('sqlite::memory:', null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // 初始化測試依賴
        $this->cache = $this->createMock(CacheService::class);
        $this->repository = new IpRepository($this->db, $this->cache);
        $this->service = new IpService($this->repository);
        $this->controller = new IpController($this->service);

        $this->createTestTables();
    }

    private function createTestTables(): void
    {
        // 建立 IP 黑白名單資料表
        $this->db->exec('DROP TABLE IF EXISTS ip_lists');
        $this->db->exec('
            CREATE TABLE ip_lists (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                uuid VARCHAR(36) NOT NULL UNIQUE,
                ip_address VARCHAR(45) NOT NULL,
                type INTEGER NOT NULL DEFAULT 0,
                unit_id INTEGER NULL,
                description TEXT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            )
        ');

        // 建立索引
        $this->db->exec('CREATE INDEX idx_ip_lists_ip_address ON ip_lists(ip_address)');
        $this->db->exec('CREATE INDEX idx_ip_lists_type ON ip_lists(type)');
        $this->db->exec('CREATE INDEX idx_ip_lists_unit_id ON ip_lists(unit_id)');
    }

    public function testCompleteIpManagementFlow(): void
    {
        // 1. 測試建立 IP 規則
        $createRequest = [
            'ip_address' => '192.168.1.1',
            'type' => 1,
            'description' => '測試白名單'
        ];
        $createResponse = $this->controller->create($createRequest);

        $this->assertEquals(201, $createResponse['status']);
        $this->assertEquals('192.168.1.1', $createResponse['data']['ip_address']);
        $this->assertEquals(1, $createResponse['data']['type']);

        // 2. 測試查詢白名單
        $getWhitelistResponse = $this->controller->getByType(['type' => 1]);

        $this->assertEquals(200, $getWhitelistResponse['status']);
        $this->assertCount(1, $getWhitelistResponse['data']);
        $this->assertEquals('192.168.1.1', $getWhitelistResponse['data'][0]['ip_address']);

        // 3. 測試 IP 存取檢查 - 白名單
        $checkWhitelistedResponse = $this->controller->checkAccess(['ip' => '192.168.1.1']);

        $this->assertEquals(200, $checkWhitelistedResponse['status']);
        $this->assertTrue($checkWhitelistedResponse['data']['allowed']);

        // 4. 測試建立黑名單 IP
        $createBlacklistRequest = [
            'ip_address' => '10.0.0.1',
            'type' => 0,
            'description' => '測試黑名單'
        ];
        $createBlacklistResponse = $this->controller->create($createBlacklistRequest);

        $this->assertEquals(201, $createBlacklistResponse['status']);
        $this->assertEquals('10.0.0.1', $createBlacklistResponse['data']['ip_address']);
        $this->assertEquals(0, $createBlacklistResponse['data']['type']);

        // 5. 測試 IP 存取檢查 - 黑名單
        $checkBlacklistedResponse = $this->controller->checkAccess(['ip' => '10.0.0.1']);

        $this->assertEquals(200, $checkBlacklistedResponse['status']);
        $this->assertFalse($checkBlacklistedResponse['data']['allowed']);

        // 6. 測試 CIDR 範圍
        $createCidrRequest = [
            'ip_address' => '172.16.0.0/16',
            'type' => 0,
            'description' => '測試 CIDR 黑名單'
        ];
        $createCidrResponse = $this->controller->create($createCidrRequest);

        $this->assertEquals(201, $createCidrResponse['status']);
        $this->assertEquals('172.16.0.0/16', $createCidrResponse['data']['ip_address']);

        // 7. 測試 CIDR 範圍內的 IP
        $checkCidrResponse = $this->controller->checkAccess(['ip' => '172.16.1.1']);

        $this->assertEquals(200, $checkCidrResponse['status']);
        $this->assertFalse($checkCidrResponse['data']['allowed']);
    }

    public function testErrorHandling(): void
    {
        // 1. 測試無效的 IP 位址
        $invalidIpRequest = [
            'ip_address' => 'invalid-ip',
            'type' => 1
        ];
        $invalidIpResponse = $this->controller->create($invalidIpRequest);

        $this->assertEquals(400, $invalidIpResponse['status']);
        $this->assertStringContainsString('無效的 IP 位址', $invalidIpResponse['error']);

        // 2. 測試無效的類型值
        $invalidTypeRequest = [
            'ip_address' => '192.168.1.1',
            'type' => 2
        ];
        $invalidTypeResponse = $this->controller->create($invalidTypeRequest);

        $this->assertEquals(400, $invalidTypeResponse['status']);
        $this->assertStringContainsString('無效的名單類型', $invalidTypeResponse['error']);

        // 3. 測試無效的 CIDR 格式
        $invalidCidrRequest = [
            'ip_address' => '192.168.1.0/33',
            'type' => 0
        ];
        $invalidCidrResponse = $this->controller->create($invalidCidrRequest);

        $this->assertEquals(400, $invalidCidrResponse['status']);
        $this->assertStringContainsString('無效的 IP 位址', $invalidCidrResponse['error']);
    }

    public function testCacheIntegration(): void
    {
        // 1. 建立測試資料
        $data = [
            'ip_address' => '192.168.1.1',
            'type' => 1,
            'description' => '測試快取'
        ];
        $createResponse = $this->controller->create($data);
        $uuid = $createResponse['data']['uuid'];

        // 2. 重複查詢應該使用快取
        $startTime = microtime(true);

        for ($i = 0; $i < 100; $i++) {
            $this->controller->checkAccess(['ip' => '192.168.1.1']);
        }

        $endTime = microtime(true);
        $duration = ($endTime - $startTime) * 1000;

        // 100次查詢應該在 100ms 內完成（平均每次 1ms）
        $this->assertLessThan(100, $duration, '快取查詢效能不符合預期');
    }

    public function testConcurrentOperations(): void
    {
        // 模擬 10 個併發請求
        $ips = [];
        for ($i = 1; $i <= 10; $i++) {
            $ips[] = "192.168.1.{$i}";
        }

        $startTime = microtime(true);

        foreach ($ips as $ip) {
            $this->controller->create([
                'ip_address' => $ip,
                'type' => 1,
                'description' => '併發測試'
            ]);
        }

        $endTime = microtime(true);
        $duration = ($endTime - $startTime) * 1000;

        // 10 個併發請求應該在 1 秒內完成
        $this->assertLessThan(1000, $duration, '併發處理效能不符合預期');

        // 驗證所有資料都正確寫入
        $response = $this->controller->getByType(['type' => 1]);
        $this->assertCount(10, $response['data']);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        if ($this->db !== null) {
            // 清除資料表
            $this->db->exec('DROP TABLE IF EXISTS ip_lists');
            $this->db = null;
        }

        // 清理快取
        $this->cache->clear();

        // 清理 Mockery
        Mockery::close();
    }
}
