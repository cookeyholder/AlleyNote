<?php

declare(strict_types=1);

namespace Tests\Integration;

use AlleyNote\Domains\Auth\Entities\RefreshToken;
use AlleyNote\Domains\Auth\Services\TokenBlacklistService;
use AlleyNote\Domains\Auth\ValueObjects\TokenBlacklistEntry;
use AlleyNote\Infrastructure\Auth\Repositories\RefreshTokenRepository;
use AlleyNote\Infrastructure\Auth\Repositories\TokenBlacklistRepository;
use DateTime;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

/**
 * JWT 認證系統整合測試 - 簡化版本
 * 專注於測試各 Repository 和 Service 間的協作.
 */
#[Group('integration')]
class JwtAuthenticationIntegrationTest extends TestCase
{
    private RefreshTokenRepository $refreshTokenRepository;

    private TokenBlacklistRepository $tokenBlacklistRepository;

    private TokenBlacklistService $tokenBlacklistService;

    protected function setUp(): void
    {
        parent::setUp();

        // 建立真實的 Repository 和 Service 實例
        $this->refreshTokenRepository = new RefreshTokenRepository($this->db);
        $this->tokenBlacklistRepository = new TokenBlacklistRepository($this->db);
        $this->tokenBlacklistService = new TokenBlacklistService($this->tokenBlacklistRepository);

        // 建立測試使用者
        $this->createTestUser();
    }

    /**
     * 測試 RefreshToken Repository 基本功能.
     */
    public function canManageRefreshTokens(): void
    {
        // 檢查初始狀態
        $initialTokens = $this->refreshTokenRepository->findByUserId(1);
        $this->assertEmpty($initialTokens);

        // 建立 Refresh Token
        $refreshToken = new RefreshToken(
            jti: 'test-refresh-jti',
            userId: 1,
            expiresAt: new DateTimeImmutable('+30 days'),
            deviceId: 'test-device-123',
            deviceName: 'Test Device',
        );

        $success = $this->refreshTokenRepository->create($refreshToken);
        $this->assertTrue($success);

        // 驗證 Token 已建立
        $tokens = $this->refreshTokenRepository->findByUserId(1);
        $this->assertCount(1, $tokens);
        $this->assertEquals('test-refresh-jti', $tokens[0]->getJti());

        // 測試查詢功能
        $existsResult = $this->refreshTokenRepository->existsByJti('test-refresh-jti');
        $this->assertTrue($existsResult);

        // 測試刪除功能
        $deleteSuccess = $this->refreshTokenRepository->deleteByJti('test-refresh-jti');
        $this->assertTrue($deleteSuccess);

        // 驗證已刪除
        $finalTokens = $this->refreshTokenRepository->findByUserId(1);
        $this->assertEmpty($finalTokens);
    }

    /**
     * 測試 TokenBlacklist Repository 基本功能.
     */
    public function canManageTokenBlacklist(): void
    {
        // 建立黑名單項目
        $blacklistEntry = new TokenBlacklistEntry(
            jti: 'test-blacklist-jti',
            tokenType: 'access',
            expiresAt: new DateTimeImmutable('+1 hour'),
            blacklistedAt: new DateTimeImmutable(),
            reason: TokenBlacklistEntry::REASON_LOGOUT,
            userId: 1,
        );

        $success = $this->tokenBlacklistRepository->addToBlacklist($blacklistEntry);
        $this->assertTrue($success);

        // 驗證黑名單項目已建立
        $isBlacklisted = $this->tokenBlacklistRepository->isBlacklisted('test-blacklist-jti');
        $this->assertTrue($isBlacklisted);

        // 測試查詢功能
        $entry = $this->tokenBlacklistRepository->findByJti('test-blacklist-jti');
        $this->assertNotNull($entry);
        $this->assertEquals('test-blacklist-jti', $entry->getJti());
        $this->assertEquals(1, $entry->getUserId());

        // 測試統計功能
        $stats = $this->tokenBlacklistRepository->getUserBlacklistStats(1);
        $this->assertArrayHasKey('total_blacklisted', $stats);
        $this->assertEquals(1, $stats['total_blacklisted']);

        // 測試刪除功能
        $removeSuccess = $this->tokenBlacklistRepository->removeFromBlacklist('test-blacklist-jti');
        $this->assertTrue($removeSuccess);

        // 驗證已刪除
        $isStillBlacklisted = $this->tokenBlacklistRepository->isBlacklisted('test-blacklist-jti');
        $this->assertFalse($isStillBlacklisted);
    }

    /**
     * 測試 TokenBlacklist Service 高層功能.
     */
    public function canUseTokenBlacklistService(): void
    {
        // 測試透過 Service 將 token 加入黑名單
        $success = $this->tokenBlacklistService->blacklistToken(
            jti: 'service-test-jti',
            tokenType: 'access',
            userId: 1,
            expiresAt: new DateTimeImmutable('+1 hour'),
            reason: TokenBlacklistEntry::REASON_LOGOUT,
            deviceId: 'test-device',
        );

        $this->assertTrue($success);

        // 驗證 token 已被加入黑名單
        $isBlacklisted = $this->tokenBlacklistService->isTokenBlacklisted('service-test-jti');
        $this->assertTrue($isBlacklisted);

        // 測試批次檢查功能
        $checkResults = $this->tokenBlacklistService->batchCheckBlacklist([
            'service-test-jti',
            'non-existent-jti',
        ]);

        $this->assertCount(2, $checkResults);
        $this->assertTrue($checkResults['service-test-jti']);
        $this->assertFalse($checkResults['non-existent-jti']);

        // 測試統計功能
        $stats = $this->tokenBlacklistService->getStatistics();
        $this->assertArrayHasKey('total_blacklisted', $stats);
        $this->assertGreaterThan(0, $stats['total_blacklisted']);

        // 測試健康檢查
        $healthStatus = $this->tokenBlacklistService->getHealthStatus();
        $this->assertArrayHasKey('totalBlacklisted', $healthStatus);
        $this->assertArrayHasKey('expiredCount', $healthStatus);
        $this->assertArrayHasKey('activeCount', $healthStatus);
    }

    /**
     * 測試自動清理功能.
     */
    public function canAutoCleanupExpiredEntries(): void
    {
        // 建立已過期的項目
        $expiredEntry = new TokenBlacklistEntry(
            jti: 'expired-entry-jti',
            tokenType: 'access',
            expiresAt: new DateTimeImmutable('-1 hour'),
            blacklistedAt: new DateTimeImmutable('-2 hours'),
            reason: TokenBlacklistEntry::REASON_LOGOUT,
            userId: 1,
        );

        $this->tokenBlacklistRepository->addToBlacklist($expiredEntry);

        // 建立未過期的項目
        $activeEntry = new TokenBlacklistEntry(
            jti: 'active-entry-jti',
            tokenType: 'access',
            expiresAt: new DateTimeImmutable('+1 hour'),
            blacklistedAt: new DateTimeImmutable(),
            reason: TokenBlacklistEntry::REASON_LOGOUT,
            userId: 1,
        );

        $this->tokenBlacklistRepository->addToBlacklist($activeEntry);

        // 執行自動清理
        $cleanupResult = $this->tokenBlacklistService->autoCleanup();

        $this->assertArrayHasKey('expired_cleaned', $cleanupResult);
        $this->assertGreaterThan(0, $cleanupResult['expired_cleaned']);

        // 驗證過期項目已被清理，活躍項目仍存在
        $this->assertFalse($this->tokenBlacklistRepository->isBlacklisted('expired-entry-jti'));
        $this->assertTrue($this->tokenBlacklistRepository->isBlacklisted('active-entry-jti'));
    }

    /**
     * 測試批次操作功能.
     */
    public function canPerformBatchOperations(): void
    {
        // 準備多個黑名單項目
        $entries = [];
        for ($i = 1; $i <= 3; $i++) {
            $entries[] = new TokenBlacklistEntry(
                jti: "batch-test-jti-{$i}",
                tokenType: 'access',
                expiresAt: new DateTimeImmutable('+1 hour'),
                blacklistedAt: new DateTimeImmutable(),
                reason: TokenBlacklistEntry::REASON_LOGOUT,
                userId: 1,
            );
        }

        // 測試批次加入黑名單
        $addedCount = $this->tokenBlacklistRepository->batchAddToBlacklist($entries);
        $this->assertEquals(3, $addedCount);

        // 測試批次檢查
        $checkResults = $this->tokenBlacklistRepository->batchIsBlacklisted([
            'batch-test-jti-1',
            'batch-test-jti-2',
            'batch-test-jti-3',
            'non-existent-jti',
        ]);

        $this->assertCount(4, $checkResults);
        $this->assertTrue($checkResults['batch-test-jti-1']);
        $this->assertTrue($checkResults['batch-test-jti-2']);
        $this->assertTrue($checkResults['batch-test-jti-3']);
        $this->assertFalse($checkResults['non-existent-jti']);

        // 測試批次移除
        $removedCount = $this->tokenBlacklistRepository->batchRemoveFromBlacklist([
            'batch-test-jti-1',
            'batch-test-jti-2',
        ]);

        $this->assertEquals(2, $removedCount);

        // 驗證移除結果
        $this->assertFalse($this->tokenBlacklistRepository->isBlacklisted('batch-test-jti-1'));
        $this->assertFalse($this->tokenBlacklistRepository->isBlacklisted('batch-test-jti-2'));
        $this->assertTrue($this->tokenBlacklistRepository->isBlacklisted('batch-test-jti-3'));
    }

    /**
     * 建立測試使用者.
     */
    private function createTestUser(): void
    {
        $now = new DateTime()->format('Y-m-d H:i:s');
        $stmt = $this->db->prepare("
            INSERT INTO users (id, username, email, password, status, created_at, updated_at)
            VALUES (1, 'testuser', 'test@example.com', 'password123', 1, :created_at, :updated_at)
        ");
        $stmt->execute([
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }
}
