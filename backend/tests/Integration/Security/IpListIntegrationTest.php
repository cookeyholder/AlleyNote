<?php

declare(strict_types=1);

namespace Tests\Integration\Security;

use App\Application\Controllers\Api\V1\IpController;
use App\Domains\Security\Repositories\ActivityLogRepository;
use App\Domains\Security\Repositories\IpRepository;
use App\Domains\Security\Services\ActivityLoggingService;
use App\Domains\Security\Services\IpService;
use App\Shared\Contracts\CacheServiceInterface;
use App\Shared\Contracts\OutputSanitizerInterface;
use App\Shared\Contracts\ValidatorInterface;
use InvalidArgumentException;
use JsonException;
use Mockery;
use PDO;
use PHPUnit\Framework\Attributes\Group;
use Psr\Log\LoggerInterface;
use Tests\Support\AuthApiIntegrationTestCase;

/**
 * IP 黑白名單整合測試.
 *
 * 以真實 SQLite 資料庫與容器服務驗證 ip_lists 相關元件：
 * - IpRepository 的黑／白名單查詢與 CIDR 網段比對
 * - IpService 的存取判斷（白名單優先、預設放行、無效 IP 拒絕）
 * - IpController 的建立規則、依類型查詢與存取檢查端點行為
 * - 建立 IP 規則時同步寫入安全審計事件（ip_blocked／ip_unblocked）
 */
#[Group('integration')]
#[Group('security')]
#[Group('ip-list')]
final class IpListIntegrationTest extends AuthApiIntegrationTestCase
{
    private const BLOCKED_IP = '198.51.100.77';

    private const WHITELISTED_IP = '198.51.100.88';

    private IpController $ipController;

    private IpRepository $ipRepository;

    private IpService $ipService;

    protected function setUp(): void
    {
        parent::setUp();

        $container = $this->app->getContainer();
        $cache = $container->get(CacheServiceInterface::class);
        $this->assertInstanceOf(CacheServiceInterface::class, $cache);
        $validator = $container->get(ValidatorInterface::class);
        $this->assertInstanceOf(ValidatorInterface::class, $validator);
        $sanitizer = $container->get(OutputSanitizerInterface::class);
        $this->assertInstanceOf(OutputSanitizerInterface::class, $sanitizer);

        $logger = $this->createSilentLogger();
        $this->ipRepository = new IpRepository($this->db, $cache);
        $this->ipService = new IpService(
            $this->ipRepository,
            new ActivityLoggingService(new ActivityLogRepository($this->db), $logger),
        );
        $this->ipController = new IpController($this->ipService, $validator, $sanitizer);
    }

    /**
     * 測試建立黑名單規則會封鎖該 IP 並寫入安全審計事件.
     */
    public function testCreateBlacklistRuleBlocksIpAddressAndAuditsEvent(): void
    {
        $result = $this->ipController->create([
            'ip_address' => self::BLOCKED_IP,
            'action'     => 'block',
            'reason'     => '惡意掃描',
            'created_by' => 1,
        ]);

        $this->assertSame(201, $this->getIntValue($result, 'status'), '建立黑名單規則應回傳 201');
        $data = $this->getArrayValue($result, 'data');
        $this->assertSame(self::BLOCKED_IP, $this->getStringValue($data, 'ip_address'));
        $this->assertSame(0, $this->getIntValue($data, 'type'), '黑名單規則的 type 應為 0');

        $this->assertTrue($this->ipRepository->isBlacklisted(self::BLOCKED_IP));
        $this->assertFalse($this->ipRepository->isWhitelisted(self::BLOCKED_IP));
        $this->assertFalse($this->ipService->isIpAllowed(self::BLOCKED_IP), '黑名單內的 IP 不應被允許');

        $row = $this->fetchLatestActivityLog('ip_blocked');
        $this->assertNotNull($row, '封鎖 IP 應寫入 ip_blocked 安全事件');
        $this->assertSame('blocked', $this->getStringValue($row, 'status'));
        $metadata = $this->decodeMetadata($row);
        $this->assertSame(self::BLOCKED_IP, $this->getStringValue($metadata, 'ip_address'));
        $this->assertSame('blacklist', $this->getStringValue($metadata, 'rule_type'));
    }

    /**
     * 測試建立白名單規則會允許該 IP 並寫入安全審計事件.
     */
    public function testCreateWhitelistRuleAllowsIpAddressAndAuditsEvent(): void
    {
        $result = $this->ipController->create([
            'ip_address' => self::WHITELISTED_IP,
            'action'     => 'allow',
            'created_by' => 1,
        ]);

        $this->assertSame(201, $this->getIntValue($result, 'status'));
        $data = $this->getArrayValue($result, 'data');
        $this->assertSame(1, $this->getIntValue($data, 'type'), '白名單規則的 type 應為 1');

        $this->assertTrue($this->ipRepository->isWhitelisted(self::WHITELISTED_IP));
        $this->assertFalse($this->ipRepository->isBlacklisted(self::WHITELISTED_IP));
        $this->assertTrue($this->ipService->isIpAllowed(self::WHITELISTED_IP));

        $row = $this->fetchLatestActivityLog('ip_unblocked');
        $this->assertNotNull($row, '加入白名單應寫入 ip_unblocked 安全事件');
        $metadata = $this->decodeMetadata($row);
        $this->assertSame('whitelist', $this->getStringValue($metadata, 'rule_type'));
    }

    /**
     * 測試同一 IP 同時存在黑白名單規則時以白名單優先.
     */
    public function testWhitelistTakesPrecedenceOverBlacklist(): void
    {
        $sharedIp = '203.0.114.50';
        foreach (['block', 'allow'] as $action) {
            $result = $this->ipController->create([
                'ip_address' => $sharedIp,
                'action'     => $action,
                'created_by' => 1,
            ]);
            $this->assertSame(201, $this->getIntValue($result, 'status'));
        }

        $this->assertTrue($this->ipRepository->isWhitelisted($sharedIp));
        $this->assertTrue($this->ipRepository->isBlacklisted($sharedIp));
        $this->assertTrue(
            $this->ipService->isIpAllowed($sharedIp),
            '白名單優先於黑名單，同時存在時應被允許',
        );
    }

    /**
     * 測試 CIDR 網段黑名單可涵蓋子網內 IP 且不影響網段外 IP.
     */
    public function testCidrRangeRuleMatchesSubnetTraffic(): void
    {
        $result = $this->ipController->create([
            'ip_address' => '203.0.115.0/24',
            'action'     => 'block',
            'reason'     => '封鎖攻擊網段',
            'created_by' => 1,
        ]);

        $this->assertSame(201, $this->getIntValue($result, 'status'));
        $this->assertTrue($this->ipRepository->isBlacklisted('203.0.115.66'), '網段內的 IP 應被封鎖');
        $this->assertFalse($this->ipRepository->isBlacklisted('203.0.116.66'), '網段外的 IP 不應被封鎖');
        $this->assertFalse($this->ipService->isIpAllowed('203.0.115.66'));
        $this->assertTrue($this->ipService->isIpAllowed('203.0.116.66'));
    }

    /**
     * 測試未列入任何名單的 IP 預設允許存取.
     */
    public function testUnknownIpAddressIsAllowedByDefault(): void
    {
        $this->assertFalse($this->ipRepository->isBlacklisted('203.0.116.99'));
        $this->assertFalse($this->ipRepository->isWhitelisted('203.0.116.99'));
        $this->assertTrue($this->ipService->isIpAllowed('203.0.116.99'));
    }

    /**
     * 測試無效 IP 位址會被驗證拒絕且不寫入資料庫.
     */
    public function testInvalidIpAddressIsRejectedByValidation(): void
    {
        $result = $this->ipController->create([
            'ip_address' => '999.999.999.1',
            'action'     => 'block',
            'created_by' => 1,
        ]);

        $this->assertSame(400, $this->getIntValue($result, 'status'), '無效 IP 應回傳 400');
        $this->assertNotSame('', $this->getStringValue($result, 'error'), '應回傳錯誤說明');

        $stmt = $this->db->prepare('SELECT COUNT(*) FROM ip_lists');
        $stmt->execute();
        $count = $stmt->fetchColumn();
        $this->assertSame(0, is_numeric($count) ? (int) $count : -1, '驗證失敗不應留下資料');

        $this->expectException(InvalidArgumentException::class);
        $this->ipService->isIpAllowed('not-an-ip-address');
    }

    /**
     * 測試依類型查詢只回傳對應名單的規則.
     */
    public function testGetRulesByTypeReturnsFilteredList(): void
    {
        $this->createRuleViaController(self::BLOCKED_IP, 'block');
        $this->createRuleViaController(self::WHITELISTED_IP, 'allow');

        $blacklist = $this->ipService->getRulesByType(0);
        $whitelist = $this->ipService->getRulesByType(1);

        $this->assertCount(1, $blacklist, '黑名單應只有一筆規則');
        $this->assertCount(1, $whitelist, '白名單應只有一筆規則');
        $this->assertSame(self::BLOCKED_IP, $blacklist[0]->getIpAddress());
        $this->assertSame(self::WHITELISTED_IP, $whitelist[0]->getIpAddress());

        $invalidResult = $this->ipController->getByType(['type' => 99]);
        $this->assertSame(400, $this->getIntValue($invalidResult, 'status'), '無效的名單類型應回傳 400');
    }

    /**
     * 測試 checkAccess 端點能正確回報存取決策.
     */
    public function testCheckAccessReportsAllowAndDenyDecisions(): void
    {
        $this->createRuleViaController(self::BLOCKED_IP, 'block');

        $denied = $this->ipController->checkAccess(['ip' => self::BLOCKED_IP]);
        $this->assertSame(200, $this->getIntValue($denied, 'status'));
        $deniedData = $this->getArrayValue($denied, 'data');
        $this->assertSame(self::BLOCKED_IP, $this->getStringValue($deniedData, 'ip'));
        $this->assertFalse((bool) ($deniedData['allowed'] ?? true), '黑名單 IP 的 checkAccess 應回報不允許');

        $allowed = $this->ipController->checkAccess(['ip' => '203.0.117.5']);
        $allowedData = $this->getArrayValue($allowed, 'data');
        $this->assertTrue((bool) ($allowedData['allowed'] ?? false), '未列入名單的 IP 應回報允許');

        $missingIp = $this->ipController->checkAccess([]);
        $this->assertSame(400, $this->getIntValue($missingIp, 'status'), '缺少 ip 參數應回傳 400');
    }

    /**
     * 透過控制器建立 IP 規則並斷言成功.
     *
     * @param string $ipAddress IP 位址或 CIDR
     * @param string $action 動作（block 或 allow）
     */
    private function createRuleViaController(string $ipAddress, string $action): void
    {
        $result = $this->ipController->create([
            'ip_address' => $ipAddress,
            'action'     => $action,
            'created_by' => 1,
        ]);
        $this->assertSame(201, $this->getIntValue($result, 'status'), "建立 {$action} 規則應成功");
    }

    /**
     * 建立不輸出任何訊息的測試用記錄器.
     */
    private function createSilentLogger(): LoggerInterface
    {
        $logger = Mockery::mock(LoggerInterface::class);
        $logger->shouldReceive('emergency')->andReturn(null);
        $logger->shouldReceive('alert')->andReturn(null);
        $logger->shouldReceive('critical')->andReturn(null);
        $logger->shouldReceive('error')->andReturn(null);
        $logger->shouldReceive('warning')->andReturn(null);
        $logger->shouldReceive('notice')->andReturn(null);
        $logger->shouldReceive('info')->andReturn(null);
        $logger->shouldReceive('debug')->andReturn(null);
        $logger->shouldReceive('log')->andReturn(null);

        return $logger;
    }

    /**
     * 取得最新一筆指定類型的活動記錄.
     *
     * @return array<string, mixed>|null 無記錄時回傳 null
     */
    private function fetchLatestActivityLog(string $actionType): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM user_activity_logs WHERE action_type = :action_type ORDER BY id DESC LIMIT 1',
        );
        $stmt->execute(['action_type' => $actionType]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        /** @var array<string, mixed>|false $row */

        return is_array($row) ? $row : null;
    }

    /**
     * 解析活動記錄的 metadata JSON 欄位.
     *
     * @param array<string, mixed> $row 活動記錄資料列
     *
     * @return array<string, mixed> 解析失敗時回傳空陣列
     */
    private function decodeMetadata(array $row): array
    {
        $raw = $row['metadata'] ?? null;
        if (!is_string($raw) || $raw === '') {
            return [];
        }

        try {
            $decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return [];
        }

        /** @var array<string, mixed> $decoded */

        return is_array($decoded) ? $decoded : [];
    }
}
