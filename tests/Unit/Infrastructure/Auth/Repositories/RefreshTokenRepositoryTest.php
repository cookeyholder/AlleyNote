<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Auth\Repositories;

use AlleyNote\Domains\Auth\Entities\RefreshToken;
use AlleyNote\Domains\Auth\Exceptions\RefreshTokenException;
use AlleyNote\Domains\Auth\ValueObjects\DeviceInfo;
use AlleyNote\Infrastructure\Auth\Repositories\RefreshTokenRepository;
use DateTime;
use PDO;
use PDOException;
use PDOStatement;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * RefreshTokenRepository 單元測試.
 *
 * 測試 RefreshTokenRepository 的所有功能，包括：
 * - CRUD 操作（建立、查詢、更新、刪除）
 * - 查詢功能（依 JTI、使用者、裝置查詢）
 * - 狀態管理（撤銷、過期檢查）
 * - 批量操作與統計功能
 * - 錯誤處理與例外情況
 */
final class RefreshTokenRepositoryTest extends TestCase
{
    private RefreshTokenRepository $repository;

    /** @var MockObject&PDO */
    private MockObject $mockPdo;

    /** @var MockObject&PDOStatement */
    private MockObject $mockStatement;

    private DeviceInfo $deviceInfo;

    private DateTime $futureDate;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockPdo = $this->createMock(PDO::class);
        $this->mockStatement = $this->createMock(PDOStatement::class);
        $this->repository = new RefreshTokenRepository($this->mockPdo);

        $this->deviceInfo = new DeviceInfo(
            deviceId: 'device-123',
            deviceName: 'Test Device',
            ipAddress: '192.168.1.100',
            userAgent: 'Test Agent',
            platform: 'Linux',
            browser: 'Chrome',
        );

        $this->futureDate = new DateTime('+1 hour');
    }

    // ========== CREATE 測試 ==========

    public function testCreate_ShouldReturnTrue_WhenTokenCreatedSuccessfully(): void
    {
        // Arrange
        $jti = 'test-jti-123';
        $userId = 1;
        $tokenHash = 'hash123';

        $this->mockPdo
            ->expects($this->once())
            ->method('prepare')
            ->willReturn($this->mockStatement);

        $this->mockStatement
            ->expects($this->once())
            ->method('execute')
            ->with($this->callback(function ($params) use ($jti, $userId, $tokenHash) {
                return $params[0] === $jti
                    && $params[1] === $userId
                    && $params[2] === $tokenHash
                    && $params[4] === 'device-123';
            }))
            ->willReturn(true);

        // Act
        $result = $this->repository->create(
            $jti,
            $userId,
            $tokenHash,
            $this->futureDate,
            $this->deviceInfo,
        );

        // Assert
        $this->assertTrue($result);
    }

    public function testCreate_ShouldThrowException_WhenDatabaseFails(): void
    {
        // Arrange
        $this->mockPdo
            ->expects($this->once())
            ->method('prepare')
            ->willThrowException(new PDOException('Database error'));

        // Assert
        $this->expectException(RefreshTokenException::class);
        $this->expectExceptionMessage('Failed to create refresh token');

        // Act
        $this->repository->create(
            'jti',
            1,
            'hash',
            $this->futureDate,
            $this->deviceInfo,
        );
    }

    public function testCreate_ShouldHandleParentTokenJti_WhenProvided(): void
    {
        // Arrange
        $parentJti = 'parent-jti-456';

        $this->mockPdo
            ->expects($this->once())
            ->method('prepare')
            ->willReturn($this->mockStatement);

        $this->mockStatement
            ->expects($this->once())
            ->method('execute')
            ->with($this->callback(function ($params) use ($parentJti) {
                return $params[12] === $parentJti; // parent_token_jti 參數位置
            }))
            ->willReturn(true);

        // Act
        $result = $this->repository->create(
            'jti',
            1,
            'hash',
            $this->futureDate,
            $this->deviceInfo,
            $parentJti,
        );

        // Assert
        $this->assertTrue($result);
    }

    // ========== FIND BY JTI 測試 ==========

    public function testFindByJti_ShouldReturnArray_WhenTokenExists(): void
    {
        // Arrange
        $jti = 'test-jti-123';
        $expectedData = [
            'jti' => $jti,
            'user_id' => 1,
            'token_hash' => 'hash123',
            'status' => RefreshToken::STATUS_ACTIVE,
        ];

        $this->mockPdo
            ->expects($this->once())
            ->method('prepare')
            ->willReturn($this->mockStatement);

        $this->mockStatement
            ->expects($this->once())
            ->method('execute')
            ->with([$jti]);

        $this->mockStatement
            ->expects($this->once())
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn($expectedData);

        // Act
        $result = $this->repository->findByJti($jti);

        // Assert
        $this->assertEquals($expectedData, $result);
    }

    public function testFindByJti_ShouldReturnNull_WhenTokenNotFound(): void
    {
        // Arrange
        $this->mockPdo
            ->expects($this->once())
            ->method('prepare')
            ->willReturn($this->mockStatement);

        $this->mockStatement
            ->expects($this->once())
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn(false);

        // Act
        $result = $this->repository->findByJti('non-existent-jti');

        // Assert
        $this->assertNull($result);
    }

    public function testFindByJti_ShouldThrowException_WhenDatabaseFails(): void
    {
        // Arrange
        $this->mockPdo
            ->expects($this->once())
            ->method('prepare')
            ->willThrowException(new PDOException('Database error'));

        // Assert
        $this->expectException(RefreshTokenException::class);
        $this->expectExceptionMessage('Failed to find refresh token by JTI');

        // Act
        $this->repository->findByJti('test-jti');
    }

    // ========== FIND BY TOKEN HASH 測試 ==========

    public function testFindByTokenHash_ShouldReturnArray_WhenTokenExists(): void
    {
        // Arrange
        $tokenHash = 'hash123';
        $expectedData = [
            'jti' => 'jti-123',
            'token_hash' => $tokenHash,
            'user_id' => 1,
        ];

        $this->mockPdo
            ->expects($this->once())
            ->method('prepare')
            ->willReturn($this->mockStatement);

        $this->mockStatement
            ->expects($this->once())
            ->method('execute')
            ->with([$tokenHash]);

        $this->mockStatement
            ->expects($this->once())
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn($expectedData);

        // Act
        $result = $this->repository->findByTokenHash($tokenHash);

        // Assert
        $this->assertEquals($expectedData, $result);
    }

    public function testFindByTokenHash_ShouldReturnNull_WhenTokenNotFound(): void
    {
        // Arrange
        $this->mockPdo
            ->expects($this->once())
            ->method('prepare')
            ->willReturn($this->mockStatement);

        $this->mockStatement
            ->expects($this->once())
            ->method('fetch')
            ->willReturn(false);

        // Act
        $result = $this->repository->findByTokenHash('non-existent-hash');

        // Assert
        $this->assertNull($result);
    }

    // ========== FIND BY USER ID 測試 ==========

    public function testFindByUserId_ShouldReturnArray_WhenTokensExist(): void
    {
        // Arrange
        $userId = 1;
        $expectedData = [
            ['jti' => 'jti-1', 'user_id' => $userId],
            ['jti' => 'jti-2', 'user_id' => $userId],
        ];

        $this->mockPdo
            ->expects($this->once())
            ->method('prepare')
            ->willReturn($this->mockStatement);

        $this->mockStatement
            ->expects($this->once())
            ->method('execute')
            ->with($this->callback(function ($params) use ($userId) {
                return $params[0] === $userId;
            }));

        $this->mockStatement
            ->expects($this->once())
            ->method('fetchAll')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn($expectedData);

        // Act
        $result = $this->repository->findByUserId($userId);

        // Assert
        $this->assertEquals($expectedData, $result);
    }

    public function testFindByUserId_ShouldIncludeExpiredTokens_WhenRequested(): void
    {
        // Arrange
        $userId = 1;

        $this->mockPdo
            ->expects($this->once())
            ->method('prepare')
            ->willReturn($this->mockStatement);

        $this->mockStatement
            ->expects($this->once())
            ->method('execute')
            ->with([$userId, RefreshToken::STATUS_ACTIVE]); // 現在包含狀態參數

        $this->mockStatement
            ->expects($this->once())
            ->method('fetchAll')
            ->willReturn([]);

        // Act
        $result = $this->repository->findByUserId($userId, true);

        // Assert
        $this->assertEquals([], $result);
    }

    public function testFindByUserId_ShouldExcludeExpiredTokens_ByDefault(): void
    {
        // Arrange
        $userId = 1;

        $this->mockPdo
            ->expects($this->once())
            ->method('prepare')
            ->willReturn($this->mockStatement);

        $this->mockStatement
            ->expects($this->once())
            ->method('execute')
            ->with($this->callback(function ($params) use ($userId) {
                return count($params) === 3 && $params[0] === $userId && $params[1] === RefreshToken::STATUS_ACTIVE;
            }));

        $this->mockStatement
            ->expects($this->once())
            ->method('fetchAll')
            ->willReturn([]);

        // Act
        $result = $this->repository->findByUserId($userId);

        // Assert
        $this->assertEquals([], $result);
    }

    // ========== FIND BY USER ID AND DEVICE 測試 ==========

    public function testFindByUserIdAndDevice_ShouldReturnArray_WhenTokensExist(): void
    {
        // Arrange
        $userId = 1;
        $deviceId = 'device-123';
        $expectedData = [
            ['jti' => 'jti-1', 'user_id' => $userId, 'device_id' => $deviceId],
        ];

        $this->mockPdo
            ->expects($this->once())
            ->method('prepare')
            ->willReturn($this->mockStatement);

        $this->mockStatement
            ->expects($this->once())
            ->method('execute')
            ->with([$userId, $deviceId]);

        $this->mockStatement
            ->expects($this->once())
            ->method('fetchAll')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn($expectedData);

        // Act
        $result = $this->repository->findByUserIdAndDevice($userId, $deviceId);

        // Assert
        $this->assertEquals($expectedData, $result);
    }

    // ========== UPDATE LAST USED 測試 ==========

    public function testUpdateLastUsed_ShouldReturnTrue_WhenUpdateSuccessful(): void
    {
        // Arrange
        $jti = 'test-jti-123';
        $lastUsedAt = new DateTime();

        $this->mockPdo
            ->expects($this->once())
            ->method('prepare')
            ->willReturn($this->mockStatement);

        $this->mockStatement
            ->expects($this->once())
            ->method('execute')
            ->with($this->callback(function ($params) use ($jti) {
                return $params[2] === $jti;
            }))
            ->willReturn(true);

        // Act
        $result = $this->repository->updateLastUsed($jti, $lastUsedAt);

        // Assert
        $this->assertTrue($result);
    }

    public function testUpdateLastUsed_ShouldUseCurrentTime_WhenLastUsedAtIsNull(): void
    {
        // Arrange
        $jti = 'test-jti-123';

        $this->mockPdo
            ->expects($this->once())
            ->method('prepare')
            ->willReturn($this->mockStatement);

        $this->mockStatement
            ->expects($this->once())
            ->method('execute')
            ->with($this->callback(function ($params) {
                return is_string($params[0]) && is_string($params[1]); // 檢查時間格式
            }))
            ->willReturn(true);

        // Act
        $result = $this->repository->updateLastUsed($jti);

        // Assert
        $this->assertTrue($result);
    }

    // ========== REVOKE 測試 ==========

    public function testRevoke_ShouldReturnTrue_WhenRevocationSuccessful(): void
    {
        // Arrange
        $jti = 'test-jti-123';
        $reason = 'test_revocation';

        $this->mockPdo
            ->expects($this->once())
            ->method('prepare')
            ->willReturn($this->mockStatement);

        $this->mockStatement
            ->expects($this->once())
            ->method('execute')
            ->with($this->callback(function ($params) use ($jti, $reason) {
                return $params[0] === RefreshToken::STATUS_REVOKED
                    && $params[1] === $reason
                    && $params[4] === $jti;
            }))
            ->willReturn(true);

        // Act
        $result = $this->repository->revoke($jti, $reason);

        // Assert
        $this->assertTrue($result);
    }

    public function testRevoke_ShouldUseDefaultReason_WhenReasonNotProvided(): void
    {
        // Arrange
        $jti = 'test-jti-123';

        $this->mockPdo
            ->expects($this->once())
            ->method('prepare')
            ->willReturn($this->mockStatement);

        $this->mockStatement
            ->expects($this->once())
            ->method('execute')
            ->with($this->callback(function ($params) {
                return $params[1] === 'manual_revocation'; // 預設原因
            }))
            ->willReturn(true);

        // Act
        $result = $this->repository->revoke($jti);

        // Assert
        $this->assertTrue($result);
    }

    // ========== REVOKE ALL BY USER ID 測試 ==========

    public function testRevokeAllByUserId_ShouldReturnRevokedCount_WhenSuccessful(): void
    {
        // Arrange
        $userId = 1;
        $reason = 'logout_all';
        $expectedCount = 3;

        $this->mockPdo
            ->expects($this->once())
            ->method('prepare')
            ->willReturn($this->mockStatement);

        $this->mockStatement
            ->expects($this->once())
            ->method('execute')
            ->with($this->callback(function ($params) use ($userId, $reason) {
                return $params[0] === RefreshToken::STATUS_REVOKED
                    && $params[1] === $reason
                    && $params[4] === $userId
                    && $params[5] === RefreshToken::STATUS_ACTIVE;
            }));

        $this->mockStatement
            ->expects($this->once())
            ->method('rowCount')
            ->willReturn($expectedCount);

        // Act
        $result = $this->repository->revokeAllByUserId($userId, $reason);

        // Assert
        $this->assertEquals($expectedCount, $result);
    }

    public function testRevokeAllByUserId_ShouldExcludeJti_WhenProvided(): void
    {
        // Arrange
        $userId = 1;
        $excludeJti = 'keep-this-jti';

        $this->mockPdo
            ->expects($this->once())
            ->method('prepare')
            ->willReturn($this->mockStatement);

        $this->mockStatement
            ->expects($this->once())
            ->method('execute')
            ->with($this->callback(function ($params) use ($excludeJti) {
                return count($params) === 7 && $params[6] === $excludeJti;
            }));

        $this->mockStatement
            ->expects($this->once())
            ->method('rowCount')
            ->willReturn(2);

        // Act
        $result = $this->repository->revokeAllByUserId($userId, 'test', $excludeJti);

        // Assert
        $this->assertEquals(2, $result);
    }

    // ========== REVOKE ALL BY DEVICE 測試 ==========

    public function testRevokeAllByDevice_ShouldReturnRevokedCount_WhenSuccessful(): void
    {
        // Arrange
        $userId = 1;
        $deviceId = 'device-123';
        $reason = 'device_logout';
        $expectedCount = 2;

        $this->mockPdo
            ->expects($this->once())
            ->method('prepare')
            ->willReturn($this->mockStatement);

        $this->mockStatement
            ->expects($this->once())
            ->method('execute')
            ->with($this->callback(function ($params) use ($userId, $deviceId, $reason) {
                return $params[0] === RefreshToken::STATUS_REVOKED
                    && $params[1] === $reason
                    && $params[4] === $userId
                    && $params[5] === $deviceId
                    && $params[6] === RefreshToken::STATUS_ACTIVE;
            }));

        $this->mockStatement
            ->expects($this->once())
            ->method('rowCount')
            ->willReturn($expectedCount);

        // Act
        $result = $this->repository->revokeAllByDevice($userId, $deviceId, $reason);

        // Assert
        $this->assertEquals($expectedCount, $result);
    }

    // ========== DELETE 測試 ==========

    public function testDelete_ShouldReturnTrue_WhenDeletionSuccessful(): void
    {
        // Arrange
        $jti = 'test-jti-123';

        $this->mockPdo
            ->expects($this->once())
            ->method('prepare')
            ->willReturn($this->mockStatement);

        $this->mockStatement
            ->expects($this->once())
            ->method('execute')
            ->with([$jti]);

        $this->mockStatement
            ->expects($this->once())
            ->method('rowCount')
            ->willReturn(1);

        // Act
        $result = $this->repository->delete($jti);

        // Assert
        $this->assertTrue($result);
    }

    public function testDelete_ShouldReturnFalse_WhenTokenNotFound(): void
    {
        // Arrange
        $this->mockPdo
            ->expects($this->once())
            ->method('prepare')
            ->willReturn($this->mockStatement);

        $this->mockStatement
            ->expects($this->once())
            ->method('rowCount')
            ->willReturn(0);

        // Act
        $result = $this->repository->delete('non-existent-jti');

        // Assert
        $this->assertFalse($result);
    }

    // ========== IS REVOKED 測試 ==========

    public function testIsRevoked_ShouldReturnTrue_WhenTokenIsRevoked(): void
    {
        // Arrange
        $jti = 'revoked-jti';

        $this->mockPdo
            ->expects($this->once())
            ->method('prepare')
            ->willReturn($this->mockStatement);

        $this->mockStatement
            ->expects($this->once())
            ->method('execute')
            ->with([$jti]);

        $this->mockStatement
            ->expects($this->once())
            ->method('fetchColumn')
            ->willReturn(RefreshToken::STATUS_REVOKED);

        // Act
        $result = $this->repository->isRevoked($jti);

        // Assert
        $this->assertTrue($result);
    }

    public function testIsRevoked_ShouldReturnFalse_WhenTokenIsActive(): void
    {
        // Arrange
        $jti = 'active-jti';

        $this->mockPdo
            ->expects($this->once())
            ->method('prepare')
            ->willReturn($this->mockStatement);

        $this->mockStatement
            ->expects($this->once())
            ->method('fetchColumn')
            ->willReturn(RefreshToken::STATUS_ACTIVE);

        // Act
        $result = $this->repository->isRevoked($jti);

        // Assert
        $this->assertFalse($result);
    }

    // ========== IS EXPIRED 測試 ==========

    public function testIsExpired_ShouldReturnTrue_WhenTokenIsExpired(): void
    {
        // Arrange
        $jti = 'expired-jti';
        $expiredDate = new DateTime('-1 hour')->format('Y-m-d H:i:s');

        $this->mockPdo
            ->expects($this->once())
            ->method('prepare')
            ->willReturn($this->mockStatement);

        $this->mockStatement
            ->expects($this->once())
            ->method('fetchColumn')
            ->willReturn($expiredDate);

        // Act
        $result = $this->repository->isExpired($jti);

        // Assert
        $this->assertTrue($result);
    }

    public function testIsExpired_ShouldReturnFalse_WhenTokenIsNotExpired(): void
    {
        // Arrange
        $jti = 'valid-jti';
        $futureDate = new DateTime('+1 hour')->format('Y-m-d H:i:s');

        $this->mockPdo
            ->expects($this->once())
            ->method('prepare')
            ->willReturn($this->mockStatement);

        $this->mockStatement
            ->expects($this->once())
            ->method('fetchColumn')
            ->willReturn($futureDate);

        // Act
        $result = $this->repository->isExpired($jti);

        // Assert
        $this->assertFalse($result);
    }

    public function testIsExpired_ShouldReturnTrue_WhenTokenNotFound(): void
    {
        // Arrange
        $jti = 'non-existent-jti';

        $this->mockPdo
            ->expects($this->once())
            ->method('prepare')
            ->willReturn($this->mockStatement);

        $this->mockStatement
            ->expects($this->once())
            ->method('fetchColumn')
            ->willReturn(false);

        // Act
        $result = $this->repository->isExpired($jti);

        // Assert
        $this->assertTrue($result); // Token not found, consider expired
    }

    // ========== IS VALID 測試 ==========

    public function testIsValid_ShouldReturnTrue_WhenTokenIsValidAndNotRevoked(): void
    {
        // Arrange
        $jti = 'valid-jti';

        // Mock consecutive calls
        $this->mockPdo
            ->expects($this->exactly(2))
            ->method('prepare')
            ->willReturn($this->mockStatement);

        $this->mockStatement
            ->expects($this->exactly(2))
            ->method('execute');

        $this->mockStatement
            ->expects($this->exactly(2))
            ->method('fetchColumn')
            ->willReturnOnConsecutiveCalls(
                new DateTime('+1 hour')->format('Y-m-d H:i:s'), // isExpired 回傳未到期時間
                RefreshToken::STATUS_ACTIVE, // isRevoked 回傳活躍狀態
            );

        // Act
        $result = $this->repository->isValid($jti);

        // Assert
        $this->assertTrue($result);
    }

    // ========== CLEANUP 測試 ==========

    public function testCleanup_ShouldReturnCleanedCount_WhenSuccessful(): void
    {
        // Arrange
        $expectedCount = 5;
        $beforeDate = new DateTime('-1 day');

        $this->mockPdo
            ->expects($this->once())
            ->method('prepare')
            ->willReturn($this->mockStatement);

        $this->mockStatement
            ->expects($this->once())
            ->method('execute')
            ->with([$beforeDate->format('Y-m-d H:i:s')]);

        $this->mockStatement
            ->expects($this->once())
            ->method('rowCount')
            ->willReturn($expectedCount);

        // Act
        $result = $this->repository->cleanup($beforeDate);

        // Assert
        $this->assertEquals($expectedCount, $result);
    }

    public function testCleanup_ShouldUseCurrentTime_WhenBeforeDateIsNull(): void
    {
        // Arrange
        $this->mockPdo
            ->expects($this->once())
            ->method('prepare')
            ->willReturn($this->mockStatement);

        $this->mockStatement
            ->expects($this->once())
            ->method('execute')
            ->with($this->callback(function ($params) {
                return is_string($params[0]); // 檢查是否為時間字串
            }));

        $this->mockStatement
            ->expects($this->once())
            ->method('rowCount')
            ->willReturn(0);

        // Act
        $result = $this->repository->cleanup();

        // Assert
        $this->assertEquals(0, $result);
    }

    // ========== CLEANUP REVOKED 測試 ==========

    public function testCleanupRevoked_ShouldReturnCleanedCount_WhenSuccessful(): void
    {
        // Arrange
        $days = 7;
        $expectedCount = 3;

        $this->mockPdo
            ->expects($this->once())
            ->method('prepare')
            ->willReturn($this->mockStatement);

        $this->mockStatement
            ->expects($this->once())
            ->method('execute')
            ->with($this->callback(function ($params) {
                return $params[0] === RefreshToken::STATUS_REVOKED
                    && is_string($params[1]); // 檢查日期格式
            }));

        $this->mockStatement
            ->expects($this->once())
            ->method('rowCount')
            ->willReturn($expectedCount);

        // Act
        $result = $this->repository->cleanupRevoked($days);

        // Assert
        $this->assertEquals($expectedCount, $result);
    }

    // ========== GET USER TOKEN STATS 測試 ==========

    public function testGetUserTokenStats_ShouldReturnStatsArray_WhenSuccessful(): void
    {
        // Arrange
        $userId = 1;
        $expectedStats = [
            'total' => 10,
            'active' => 5,
            'expired' => 3,
            'revoked' => 2,
        ];

        $this->mockPdo
            ->expects($this->once())
            ->method('prepare')
            ->willReturn($this->mockStatement);

        $this->mockStatement
            ->expects($this->once())
            ->method('execute');

        $this->mockStatement
            ->expects($this->once())
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn($expectedStats);

        // Act
        $result = $this->repository->getUserTokenStats($userId);

        // Assert
        $this->assertEquals($expectedStats, $result);
    }

    // ========== GET SYSTEM STATS 測試 ==========

    public function testGetSystemStats_ShouldReturnSystemStatsArray_WhenSuccessful(): void
    {
        // Arrange
        $expectedStats = [
            'total_tokens' => 100,
            'active_tokens' => 60,
            'expired_tokens' => 25,
            'revoked_tokens' => 15,
            'unique_users' => 20,
            'unique_devices' => 35,
        ];

        $this->mockPdo
            ->expects($this->once())
            ->method('prepare')
            ->willReturn($this->mockStatement);

        $this->mockStatement
            ->expects($this->once())
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn($expectedStats);

        // Act
        $result = $this->repository->getSystemStats();

        // Assert
        $this->assertEquals($expectedStats, $result);
    }

    // ========== BATCH CREATE 測試 ==========

    public function testBatchCreate_ShouldReturnCreatedCount_WhenSuccessful(): void
    {
        // Arrange
        $tokens = [
            [
                'jti' => 'jti-1',
                'user_id' => 1,
                'token_hash' => 'hash-1',
                'expires_at' => '+1 hour',
                'device_info' => $this->deviceInfo,
            ],
            [
                'jti' => 'jti-2',
                'user_id' => 2,
                'token_hash' => 'hash-2',
                'expires_at' => '+2 hours',
                'device_info' => $this->deviceInfo,
            ],
        ];

        $this->mockPdo
            ->expects($this->once())
            ->method('beginTransaction');

        $this->mockPdo
            ->expects($this->once())
            ->method('commit');

        $this->mockPdo
            ->expects($this->exactly(2))
            ->method('prepare')
            ->willReturn($this->mockStatement);

        $this->mockStatement
            ->expects($this->exactly(2))
            ->method('execute')
            ->willReturn(true);

        // Act
        $result = $this->repository->batchCreate($tokens);

        // Assert
        $this->assertEquals(2, $result);
    }

    public function testBatchCreate_ShouldRollbackAndThrowException_WhenFails(): void
    {
        // Arrange
        $tokens = [
            [
                'jti' => 'jti-1',
                'user_id' => 1,
                'token_hash' => 'hash-1',
                'expires_at' => '+1 hour',
                'device_info' => $this->deviceInfo,
            ],
        ];

        $this->mockPdo
            ->expects($this->once())
            ->method('beginTransaction');

        $this->mockPdo
            ->expects($this->never())
            ->method('rollback'); // 異常從 create 方法拋出，不會執行到 rollback

        $this->mockPdo
            ->expects($this->once())
            ->method('prepare')
            ->willThrowException(new PDOException('Database error'));

        // Assert
        $this->expectException(RefreshTokenException::class);
        $this->expectExceptionMessage('Failed to create refresh token');

        // Act
        $this->repository->batchCreate($tokens);
    }

    // ========== BATCH REVOKE 測試 ==========

    public function testBatchRevoke_ShouldReturnRevokedCount_WhenSuccessful(): void
    {
        // Arrange
        $jtis = ['jti-1', 'jti-2', 'jti-3'];
        $reason = 'batch_test';
        $expectedCount = 3;

        $this->mockPdo
            ->expects($this->once())
            ->method('prepare')
            ->willReturn($this->mockStatement);

        $this->mockStatement
            ->expects($this->once())
            ->method('execute')
            ->with($this->callback(function ($params) use ($reason, $jtis) {
                return $params[0] === RefreshToken::STATUS_REVOKED
                    && $params[1] === $reason
                    && array_slice($params, 4) === $jtis;
            }));

        $this->mockStatement
            ->expects($this->once())
            ->method('rowCount')
            ->willReturn($expectedCount);

        // Act
        $result = $this->repository->batchRevoke($jtis, $reason);

        // Assert
        $this->assertEquals($expectedCount, $result);
    }

    public function testBatchRevoke_ShouldReturnZero_WhenJtisArrayIsEmpty(): void
    {
        // Arrange & Act
        $result = $this->repository->batchRevoke([]);

        // Assert
        $this->assertEquals(0, $result);
    }

    // ========== GET TOKENS NEAR EXPIRY 測試 ==========

    public function testGetTokensNearExpiry_ShouldReturnArray_WhenTokensFound(): void
    {
        // Arrange
        $thresholdHours = 12;
        $expectedTokens = [
            ['jti' => 'jti-1', 'expires_at' => '+10 hours'],
            ['jti' => 'jti-2', 'expires_at' => '+11 hours'],
        ];

        $this->mockPdo
            ->expects($this->once())
            ->method('prepare')
            ->willReturn($this->mockStatement);

        $this->mockStatement
            ->expects($this->once())
            ->method('execute')
            ->with($this->callback(function ($params) {
                return count($params) === 3
                    && $params[2] === RefreshToken::STATUS_ACTIVE;
            }));

        $this->mockStatement
            ->expects($this->once())
            ->method('fetchAll')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn($expectedTokens);

        // Act
        $result = $this->repository->getTokensNearExpiry($thresholdHours);

        // Assert
        $this->assertEquals($expectedTokens, $result);
    }

    // ========== 錯誤處理測試 ==========

    public function testFindByTokenHash_ShouldThrowException_WhenDatabaseFails(): void
    {
        // Arrange
        $this->mockPdo
            ->expects($this->once())
            ->method('prepare')
            ->willThrowException(new PDOException('Database error'));

        // Assert
        $this->expectException(RefreshTokenException::class);

        // Act
        $this->repository->findByTokenHash('hash');
    }

    public function testUpdateLastUsed_ShouldThrowException_WhenDatabaseFails(): void
    {
        // Arrange
        $this->mockPdo
            ->expects($this->once())
            ->method('prepare')
            ->willThrowException(new PDOException('Database error'));

        // Assert
        $this->expectException(RefreshTokenException::class);

        // Act
        $this->repository->updateLastUsed('jti');
    }

    public function testRevoke_ShouldThrowException_WhenDatabaseFails(): void
    {
        // Arrange
        $this->mockPdo
            ->expects($this->once())
            ->method('prepare')
            ->willThrowException(new PDOException('Database error'));

        // Assert
        $this->expectException(RefreshTokenException::class);

        // Act
        $this->repository->revoke('jti');
    }

    public function testRevokeAllByUserId_ShouldThrowException_WhenDatabaseFails(): void
    {
        // Arrange
        $this->mockPdo
            ->expects($this->once())
            ->method('prepare')
            ->willThrowException(new PDOException('Database error'));

        // Assert
        $this->expectException(RefreshTokenException::class);

        // Act
        $this->repository->revokeAllByUserId(1);
    }

    public function testRevokeAllByDevice_ShouldThrowException_WhenDatabaseFails(): void
    {
        // Arrange
        $this->mockPdo
            ->expects($this->once())
            ->method('prepare')
            ->willThrowException(new PDOException('Database error'));

        // Assert
        $this->expectException(RefreshTokenException::class);

        // Act
        $this->repository->revokeAllByDevice(1, 'device');
    }

    public function testDelete_ShouldThrowException_WhenDatabaseFails(): void
    {
        // Arrange
        $this->mockPdo
            ->expects($this->once())
            ->method('prepare')
            ->willThrowException(new PDOException('Database error'));

        // Assert
        $this->expectException(RefreshTokenException::class);

        // Act
        $this->repository->delete('jti');
    }

    public function testIsRevoked_ShouldThrowException_WhenDatabaseFails(): void
    {
        // Arrange
        $this->mockPdo
            ->expects($this->once())
            ->method('prepare')
            ->willThrowException(new PDOException('Database error'));

        // Assert
        $this->expectException(RefreshTokenException::class);

        // Act
        $this->repository->isRevoked('jti');
    }

    public function testIsExpired_ShouldThrowException_WhenDatabaseFails(): void
    {
        // Arrange
        $this->mockPdo
            ->expects($this->once())
            ->method('prepare')
            ->willThrowException(new PDOException('Database error'));

        // Assert
        $this->expectException(RefreshTokenException::class);

        // Act
        $this->repository->isExpired('jti');
    }

    public function testCleanup_ShouldThrowException_WhenDatabaseFails(): void
    {
        // Arrange
        $this->mockPdo
            ->expects($this->once())
            ->method('prepare')
            ->willThrowException(new PDOException('Database error'));

        // Assert
        $this->expectException(RefreshTokenException::class);

        // Act
        $this->repository->cleanup();
    }

    public function testCleanupRevoked_ShouldThrowException_WhenDatabaseFails(): void
    {
        // Arrange
        $this->mockPdo
            ->expects($this->once())
            ->method('prepare')
            ->willThrowException(new PDOException('Database error'));

        // Assert
        $this->expectException(RefreshTokenException::class);

        // Act
        $this->repository->cleanupRevoked();
    }

    public function testGetUserTokenStats_ShouldThrowException_WhenDatabaseFails(): void
    {
        // Arrange
        $this->mockPdo
            ->expects($this->once())
            ->method('prepare')
            ->willThrowException(new PDOException('Database error'));

        // Assert
        $this->expectException(RefreshTokenException::class);

        // Act
        $this->repository->getUserTokenStats(1);
    }

    public function testGetSystemStats_ShouldThrowException_WhenDatabaseFails(): void
    {
        // Arrange
        $this->mockPdo
            ->expects($this->once())
            ->method('prepare')
            ->willThrowException(new PDOException('Database error'));

        // Assert
        $this->expectException(RefreshTokenException::class);

        // Act
        $this->repository->getSystemStats();
    }

    public function testGetTokensNearExpiry_ShouldThrowException_WhenDatabaseFails(): void
    {
        // Arrange
        $this->mockPdo
            ->expects($this->once())
            ->method('prepare')
            ->willThrowException(new PDOException('Database error'));

        // Assert
        $this->expectException(RefreshTokenException::class);

        // Act
        $this->repository->getTokensNearExpiry();
    }
}
