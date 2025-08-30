<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Domains\Auth\Services\TokenBlacklistService;
use App\Domains\Auth\ValueObjects\TokenBlacklistEntry;
use App\Infrastructure\Auth\Repositories\TokenBlacklistRepository;
use DateTimeImmutable;
use PDO;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

/**
 * JWT Token Blacklist 整合測試.
 *
 * 測試 TokenBlacklistRepository 和 TokenBlacklistService 的整合協作
 * 驗證黑名單功能的端到端流程
 */
#[Group('integration')]
#[Group('auth')]
class JwtTokenBlacklistIntegrationTest extends TestCase
{
    private TokenBlacklistRepository $tokenBlacklistRepository;

    private TokenBlacklistService $tokenBlacklistService;

    protected function setUp(): void
    {
        parent::setUp();

        // 建立測試資料庫連線
        $this->db = new PDO('sqlite::memory:');
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // 建立測試資料表
        $this->createTokenBlacklistTable();

        // 初始化 Repository 和 Service
        $this->tokenBlacklistRepository = new TokenBlacklistRepository($this->db);
        $this->tokenBlacklistService = new TokenBlacklistService($this->tokenBlacklistRepository);
    }

    /**
     * 測試基本的黑名單功能整合.
     */
    public function testBasicBlacklistIntegration(): void
    {
        // 1. 建立黑名單項目
        $entry = new TokenBlacklistEntry(
            jti: 'test-token-123',
            tokenType: 'access',
            expiresAt: new DateTimeImmutable('+1 hour'),
            blacklistedAt: new DateTimeImmutable(),
            reason: TokenBlacklistEntry::REASON_LOGOUT,
            userId: 1,
        );

        // 2. 透過 Repository 直接新增
        $success = $this->tokenBlacklistRepository->addToBlacklist($entry);
        $this->assertTrue($success);

        // 3. 透過 Service 檢查黑名單狀態
        $isBlacklisted = $this->tokenBlacklistService->isTokenBlacklisted('test-token-123');
        $this->assertTrue($isBlacklisted);

        // 4. 透過 Repository 直接查詢
        $storedEntry = $this->tokenBlacklistRepository->findByJti('test-token-123');
        $this->assertNotNull($storedEntry);
        $this->assertEquals('test-token-123', $storedEntry->getJti());
        $this->assertEquals(1, $storedEntry->getUserId());
    }

    /**
     * 測試透過 Service 的黑名單操作.
     */
    public function testServiceBlacklistOperations(): void
    {
        // 1. 透過 Service 將 token 加入黑名單
        $success = $this->tokenBlacklistService->blacklistToken(
            jti: 'service-token-456',
            tokenType: 'refresh',
            userId: 2,
            expiresAt: new DateTimeImmutable('+30 days'),
            reason: TokenBlacklistEntry::REASON_SECURITY_BREACH,
            deviceId: 'device-001',
        );

        $this->assertTrue($success);

        // 2. 驗證透過 Repository 可以查到
        $isBlacklisted = $this->tokenBlacklistRepository->isBlacklisted('service-token-456');
        $this->assertTrue($isBlacklisted);

        // 3. 透過 Service 查詢詳細資訊
        $entry = $this->tokenBlacklistRepository->findByJti('service-token-456');
        $this->assertNotNull($entry);
        $this->assertEquals('refresh', $entry->getTokenType());
        $this->assertEquals(2, $entry->getUserId());
        $this->assertEquals(TokenBlacklistEntry::REASON_SECURITY_BREACH, $entry->getReason());
    }

    /**
     * 測試批次操作整合.
     */
    public function testBatchOperationsIntegration(): void
    {
        // 1. 準備批次資料
        $entries = [];
        for ($i = 1; $i <= 5; $i++) {
            $entries[] = new TokenBlacklistEntry(
                jti: "batch-token-{$i}",
                tokenType: 'access',
                expiresAt: new DateTimeImmutable('+2 hours'),
                blacklistedAt: new DateTimeImmutable(),
                reason: TokenBlacklistEntry::REASON_LOGOUT,
                userId: $i,
            );
        }

        // 2. 透過 Repository 批次新增
        $addedCount = $this->tokenBlacklistRepository->batchAddToBlacklist($entries);
        $this->assertEquals(5, $addedCount);

        // 3. 透過 Service 批次檢查
        $jtis = ['batch-token-1', 'batch-token-3', 'batch-token-5', 'non-existent'];
        $checkResults = $this->tokenBlacklistService->batchCheckBlacklist($jtis);

        $this->assertCount(4, $checkResults);
        $this->assertTrue($checkResults['batch-token-1']);
        $this->assertTrue($checkResults['batch-token-3']);
        $this->assertTrue($checkResults['batch-token-5']);
        $this->assertFalse($checkResults['non-existent']);

        // 4. 透過 Repository 批次移除
        $removedCount = $this->tokenBlacklistRepository->batchRemoveFromBlacklist([
            'batch-token-2',
            'batch-token-4',
        ]);
        $this->assertEquals(2, $removedCount);

        // 5. 驗證移除結果
        $this->assertFalse($this->tokenBlacklistRepository->isBlacklisted('batch-token-2'));
        $this->assertFalse($this->tokenBlacklistRepository->isBlacklisted('batch-token-4'));
        $this->assertTrue($this->tokenBlacklistRepository->isBlacklisted('batch-token-1'));
    }

    /**
     * 測試統計功能整合.
     */
    public function testStatisticsIntegration(): void
    {
        // 1. 建立測試資料：不同使用者、不同狀態的 token
        $testData = [
            ['user' => 1, 'jti' => 'active-token-1', 'expires' => '+1 hour'],
            ['user' => 1, 'jti' => 'active-token-2', 'expires' => '+2 hours'],
            ['user' => 2, 'jti' => 'active-token-3', 'expires' => '+3 hours'],
            ['user' => 2, 'jti' => 'expired-token-1', 'expires' => '-1 hour'],
            ['user' => 3, 'jti' => 'expired-token-2', 'expires' => '-2 hours'],
        ];

        foreach ($testData as $data) {
            $entry = new TokenBlacklistEntry(
                jti: $data['jti'],
                tokenType: 'access',
                expiresAt: new DateTimeImmutable($data['expires']),
                blacklistedAt: new DateTimeImmutable(),
                reason: TokenBlacklistEntry::REASON_LOGOUT,
                userId: $data['user'],
            );
            $this->tokenBlacklistRepository->addToBlacklist($entry);
        }

        // 2. 透過 Service 取得整體統計
        $stats = $this->tokenBlacklistService->getStatistics();
        $this->assertArrayHasKey('total', $stats);
        $this->assertArrayHasKey('size_info', $stats);
        $this->assertEquals(5, $stats['total']);

        // 3. 透過 Repository 取得使用者統計
        $userStats = $this->tokenBlacklistRepository->getUserBlacklistStats(1);
        $this->assertArrayHasKey('total_blacklisted', $userStats);
        $this->assertEquals(2, $userStats['total_blacklisted']);

        // 4. 透過 Service 取得健康狀態
        $health = $this->tokenBlacklistService->getHealthStatus();
        $this->assertArrayHasKey('total_entries', $health);
        $this->assertArrayHasKey('expired_entries', $health);
        $this->assertArrayHasKey('active_entries', $health);
    }

    /**
     * 測試自動清理功能整合.
     */
    public function testAutoCleanupIntegration(): void
    {
        // 1. 建立過期和活躍的黑名單項目
        $expiredEntry = new TokenBlacklistEntry(
            jti: 'expired-cleanup-token',
            tokenType: 'access',
            expiresAt: new DateTimeImmutable('-1 hour'),
            blacklistedAt: new DateTimeImmutable('-2 hours'),
            reason: TokenBlacklistEntry::REASON_LOGOUT,
            userId: 1,
        );

        $activeEntry = new TokenBlacklistEntry(
            jti: 'active-cleanup-token',
            tokenType: 'access',
            expiresAt: new DateTimeImmutable('+1 hour'),
            blacklistedAt: new DateTimeImmutable(),
            reason: TokenBlacklistEntry::REASON_LOGOUT,
            userId: 1,
        );

        $this->tokenBlacklistRepository->addToBlacklist($expiredEntry);
        $this->tokenBlacklistRepository->addToBlacklist($activeEntry);

        // 2. 執行自動清理
        $cleanupResult = $this->tokenBlacklistService->autoCleanup();

        // 3. 驗證清理結果
        $this->assertArrayHasKey('expired_cleaned', $cleanupResult);
        $this->assertGreaterThan(0, $cleanupResult['expired_cleaned']);

        // 4. 驗證過期項目已被清理，活躍項目仍存在
        $this->assertFalse($this->tokenBlacklistRepository->isBlacklisted('expired-cleanup-token'));
        $this->assertTrue($this->tokenBlacklistRepository->isBlacklisted('active-cleanup-token'));
    }

    /**
     * 測試查詢功能整合.
     */
    public function testQueryFunctionsIntegration(): void
    {
        // 1. 建立不同使用者的 token
        $user1Tokens = ['user1-token-1', 'user1-token-2'];
        $user2Tokens = ['user2-token-1'];

        foreach ($user1Tokens as $jti) {
            $entry = new TokenBlacklistEntry(
                jti: $jti,
                tokenType: 'access',
                expiresAt: new DateTimeImmutable('+1 hour'),
                blacklistedAt: new DateTimeImmutable(),
                reason: TokenBlacklistEntry::REASON_LOGOUT,
                userId: 1,
            );
            $this->tokenBlacklistRepository->addToBlacklist($entry);
        }

        foreach ($user2Tokens as $jti) {
            $entry = new TokenBlacklistEntry(
                jti: $jti,
                tokenType: 'refresh',
                expiresAt: new DateTimeImmutable('+1 day'),
                blacklistedAt: new DateTimeImmutable(),
                reason: TokenBlacklistEntry::REASON_SECURITY_BREACH,
                userId: 2,
            );
            $this->tokenBlacklistRepository->addToBlacklist($entry);
        }

        // 2. 透過 Repository 查詢使用者的黑名單項目
        $user1Entries = $this->tokenBlacklistRepository->findByUserId(1);
        $this->assertCount(2, $user1Entries);

        $user2Entries = $this->tokenBlacklistRepository->findByUserId(2);
        $this->assertCount(1, $user2Entries);

        // 3. 透過 Repository 按類型查詢
        $accessTokens = $this->tokenBlacklistRepository->findByTokenType('access');
        $this->assertCount(2, $accessTokens);

        $refreshTokens = $this->tokenBlacklistRepository->findByTokenType('refresh');
        $this->assertCount(1, $refreshTokens);

        // 4. 透過 Repository 按原因查詢
        $logoutTokens = $this->tokenBlacklistRepository->findByReason(TokenBlacklistEntry::REASON_LOGOUT);
        $this->assertCount(2, $logoutTokens);

        $securityTokens = $this->tokenBlacklistRepository->findByReason(TokenBlacklistEntry::REASON_SECURITY_BREACH);
        $this->assertCount(1, $securityTokens);
    }

    /**
     * 測試錯誤處理和邊界情況
     */
    public function testErrorHandlingIntegration(): void
    {
        // 1. 測試重複新增同一個 JTI
        $entry1 = new TokenBlacklistEntry(
            jti: 'duplicate-jti-test',
            tokenType: 'access',
            expiresAt: new DateTimeImmutable('+1 hour'),
            blacklistedAt: new DateTimeImmutable(),
            reason: TokenBlacklistEntry::REASON_LOGOUT,
            userId: 1,
        );

        $entry2 = new TokenBlacklistEntry(
            jti: 'duplicate-jti-test', // 同樣的 JTI
            tokenType: 'refresh',
            expiresAt: new DateTimeImmutable('+2 hours'),
            blacklistedAt: new DateTimeImmutable(),
            reason: TokenBlacklistEntry::REASON_SECURITY_BREACH,
            userId: 2,
        );

        // 第一次新增應該成功
        $success1 = $this->tokenBlacklistRepository->addToBlacklist($entry1);
        $this->assertTrue($success1);

        // 第二次新增相同 JTI 應該失敗或被忽略
        $success2 = $this->tokenBlacklistRepository->addToBlacklist($entry2);
        $this->assertFalse($success2);

        // 2. 測試查詢不存在的 token
        $isBlacklisted = $this->tokenBlacklistService->isTokenBlacklisted('non-existent-token');
        $this->assertFalse($isBlacklisted);

        $entry = $this->tokenBlacklistRepository->findByJti('non-existent-token');
        $this->assertNull($entry);

        // 3. 測試空陣列的批次操作
        $batchResults = $this->tokenBlacklistService->batchCheckBlacklist([]);
        $this->assertIsArray($batchResults);
        $this->assertEmpty($batchResults);

        $removedCount = $this->tokenBlacklistRepository->batchRemoveFromBlacklist([]);
        $this->assertEquals(0, $removedCount);
    }

    /**
     * 建立 token_blacklist 資料表.
     */
    private function createTokenBlacklistTable(): void
    {
        $sql = '
            CREATE TABLE token_blacklist (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                jti VARCHAR(255) UNIQUE NOT NULL,
                token_type VARCHAR(50) NOT NULL,
                user_id INTEGER,
                expires_at DATETIME NOT NULL,
                blacklisted_at DATETIME NOT NULL,
                reason VARCHAR(100) NOT NULL,
                device_id VARCHAR(255),
                metadata TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ';

        $this->db->exec($sql);

        // 建立索引
        $this->db->exec('CREATE INDEX idx_token_blacklist_jti ON token_blacklist(jti)');
        $this->db->exec('CREATE INDEX idx_token_blacklist_user_id ON token_blacklist(user_id)');
        $this->db->exec('CREATE INDEX idx_token_blacklist_expires_at ON token_blacklist(expires_at)');
    }
}
