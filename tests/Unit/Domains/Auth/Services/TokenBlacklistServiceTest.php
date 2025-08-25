<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Auth\Services;

use AlleyNote\Domains\Auth\Contracts\TokenBlacklistRepositoryInterface;
use AlleyNote\Domains\Auth\Services\TokenBlacklistService;
use AlleyNote\Domains\Auth\ValueObjects\TokenBlacklistEntry;
use DateTime;
use DateTimeImmutable;
use InvalidArgumentException;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * TokenBlacklistService 單元測試.
 */
final class TokenBlacklistServiceTest extends TestCase
{
    private TokenBlacklistRepositoryInterface&MockInterface $repository;

    private LoggerInterface&MockInterface $logger;

    private TokenBlacklistService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = Mockery::mock(TokenBlacklistRepositoryInterface::class);
        $this->logger = Mockery::mock(LoggerInterface::class);
        $this->service = new TokenBlacklistService($this->repository, $this->logger);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function testBlacklistTokenSuccessfully(): void
    {
        // Arrange
        $jti = 'test-jti-123';
        $tokenType = TokenBlacklistEntry::TOKEN_TYPE_ACCESS;
        $userId = 1;
        $expiresAt = new DateTime('+1 hour');
        $reason = TokenBlacklistEntry::REASON_LOGOUT;
        $deviceId = 'device-123';
        $metadata = ['ip' => '192.168.1.1'];

        $this->repository
            ->shouldReceive('addToBlacklist')
            ->once()
            ->with(Mockery::type(TokenBlacklistEntry::class))
            ->andReturn(true);

        $this->logger
            ->shouldReceive('info')
            ->once()
            ->with('Token blacklist add', Mockery::type('array'));

        // Act
        $result = $this->service->blacklistToken(
            $jti,
            $tokenType,
            $userId,
            $expiresAt,
            $reason,
            $deviceId,
            $metadata,
        );

        // Assert
        $this->assertTrue($result);
    }

    public function testBlacklistTokenWithHighPriorityReason(): void
    {
        // Arrange
        $jti = 'test-jti-123';
        $tokenType = TokenBlacklistEntry::TOKEN_TYPE_ACCESS;
        $userId = 1;
        $expiresAt = new DateTime('+1 hour');
        $reason = TokenBlacklistEntry::REASON_SECURITY_BREACH;

        $this->repository
            ->shouldReceive('addToBlacklist')
            ->once()
            ->andReturn(true);

        $this->logger
            ->shouldReceive('info')
            ->once();

        $this->logger
            ->shouldReceive('warning')
            ->once()
            ->with('High priority token blacklisted', Mockery::type('array'));

        // Act
        $result = $this->service->blacklistToken(
            $jti,
            $tokenType,
            $userId,
            $expiresAt,
            $reason,
        );

        // Assert
        $this->assertTrue($result);
    }

    public function testBlacklistTokenWithInvalidTokenType(): void
    {
        // Arrange
        $jti = 'test-jti-123';
        $tokenType = 'invalid-type';
        $userId = 1;
        $expiresAt = new DateTime('+1 hour');
        $reason = TokenBlacklistEntry::REASON_LOGOUT;

        // Expect
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid token type: invalid-type');

        // Act
        $this->service->blacklistToken($jti, $tokenType, $userId, $expiresAt, $reason);
    }

    public function testBlacklistTokenWithInvalidReason(): void
    {
        // Arrange
        $jti = 'test-jti-123';
        $tokenType = TokenBlacklistEntry::TOKEN_TYPE_ACCESS;
        $userId = 1;
        $expiresAt = new DateTime('+1 hour');
        $reason = 'invalid-reason';

        // Expect
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid blacklist reason: invalid-reason');

        // Act
        $this->service->blacklistToken($jti, $tokenType, $userId, $expiresAt, $reason);
    }

    public function testBlacklistTokenRepositoryFailure(): void
    {
        // Arrange
        $jti = 'test-jti-123';
        $tokenType = TokenBlacklistEntry::TOKEN_TYPE_ACCESS;
        $userId = 1;
        $expiresAt = new DateTime('+1 hour');
        $reason = TokenBlacklistEntry::REASON_LOGOUT;

        $this->repository
            ->shouldReceive('addToBlacklist')
            ->once()
            ->andReturn(false);

        $this->logger
            ->shouldNotReceive('info');

        // Act
        $result = $this->service->blacklistToken($jti, $tokenType, $userId, $expiresAt, $reason);

        // Assert
        $this->assertFalse($result);
    }

    public function testBlacklistTokenRepositoryException(): void
    {
        // Arrange
        $jti = 'test-jti-123';
        $tokenType = TokenBlacklistEntry::TOKEN_TYPE_ACCESS;
        $userId = 1;
        $expiresAt = new DateTime('+1 hour');
        $reason = TokenBlacklistEntry::REASON_LOGOUT;

        $this->repository
            ->shouldReceive('addToBlacklist')
            ->once()
            ->andThrow(new RuntimeException('Database error'));

        $this->logger
            ->shouldReceive('error')
            ->once()
            ->with('Failed to blacklist token', Mockery::type('array'));

        // Act
        $result = $this->service->blacklistToken($jti, $tokenType, $userId, $expiresAt, $reason);

        // Assert
        $this->assertFalse($result);
    }

    public function testIsTokenBlacklistedSuccessfully(): void
    {
        // Arrange
        $jti = 'test-jti-123';

        $this->repository
            ->shouldReceive('isBlacklisted')
            ->once()
            ->with($jti)
            ->andReturn(true);

        // Act
        $result = $this->service->isTokenBlacklisted($jti);

        // Assert
        $this->assertTrue($result);
    }

    public function testIsTokenBlacklistedWithEmptyJti(): void
    {
        // Act
        $result = $this->service->isTokenBlacklisted('');

        // Assert
        $this->assertFalse($result);
    }

    public function testIsTokenBlacklistedWithRepositoryException(): void
    {
        // Arrange
        $jti = 'test-jti-123';

        $this->repository
            ->shouldReceive('isBlacklisted')
            ->once()
            ->with($jti)
            ->andThrow(new RuntimeException('Database error'));

        $this->logger
            ->shouldReceive('error')
            ->once()
            ->with('Failed to check blacklist status', Mockery::type('array'));

        // Act
        $result = $this->service->isTokenBlacklisted($jti);

        // Assert
        $this->assertTrue($result); // 預設為安全起見，認為已列入黑名單
    }

    public function testBatchCheckBlacklistSuccessfully(): void
    {
        // Arrange
        $jtis = ['jti1', 'jti2', 'jti3'];
        $expected = ['jti1' => true, 'jti2' => false, 'jti3' => true];

        $this->repository
            ->shouldReceive('batchIsBlacklisted')
            ->once()
            ->with($jtis)
            ->andReturn($expected);

        // Act
        $result = $this->service->batchCheckBlacklist($jtis);

        // Assert
        $this->assertSame($expected, $result);
    }

    public function testBatchCheckBlacklistWithEmptyArray(): void
    {
        // Act
        $result = $this->service->batchCheckBlacklist([]);

        // Assert
        $this->assertSame([], $result);
    }

    public function testBatchCheckBlacklistWithEmptyJtis(): void
    {
        // Act
        $result = $this->service->batchCheckBlacklist(['', null, false]);

        // Assert
        $this->assertSame([], $result);
    }

    public function testBatchCheckBlacklistWithRepositoryException(): void
    {
        // Arrange
        $jtis = ['jti1', 'jti2'];

        $this->repository
            ->shouldReceive('batchIsBlacklisted')
            ->once()
            ->andThrow(new RuntimeException('Database error'));

        $this->logger
            ->shouldReceive('error')
            ->once()
            ->with('Failed to batch check blacklist status', Mockery::type('array'));

        // Act
        $result = $this->service->batchCheckBlacklist($jtis);

        // Assert
        $this->assertSame(['jti1' => true, 'jti2' => true], $result);
    }

    public function testBlacklistUserTokensSuccessfully(): void
    {
        // Arrange
        $userId = 1;
        $reason = TokenBlacklistEntry::REASON_PASSWORD_CHANGED;
        $excludeJti = 'exclude-jti';

        $this->repository
            ->shouldReceive('blacklistAllUserTokens')
            ->once()
            ->with($userId, $reason, $excludeJti)
            ->andReturn(5);

        $this->logger
            ->shouldReceive('info')
            ->once()
            ->with('Blacklisted user tokens', Mockery::type('array'));

        // Act
        $result = $this->service->blacklistUserTokens($userId, $reason, $excludeJti);

        // Assert
        $this->assertSame(5, $result);
    }

    public function testBlacklistUserTokensWithInvalidUserId(): void
    {
        // Expect
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('User ID must be positive');

        // Act
        $this->service->blacklistUserTokens(0, TokenBlacklistEntry::REASON_LOGOUT);
    }

    public function testBlacklistUserTokensWithInvalidReason(): void
    {
        // Expect
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid blacklist reason: invalid');

        // Act
        $this->service->blacklistUserTokens(1, 'invalid');
    }

    public function testBlacklistUserTokensWithRepositoryException(): void
    {
        // Arrange
        $userId = 1;
        $reason = TokenBlacklistEntry::REASON_LOGOUT;

        $this->repository
            ->shouldReceive('blacklistAllUserTokens')
            ->once()
            ->andThrow(new RuntimeException('Database error'));

        $this->logger
            ->shouldReceive('error')
            ->once()
            ->with('Failed to blacklist user tokens', Mockery::type('array'));

        // Act
        $result = $this->service->blacklistUserTokens($userId, $reason);

        // Assert
        $this->assertSame(0, $result);
    }

    public function testBlacklistDeviceTokensSuccessfully(): void
    {
        // Arrange
        $deviceId = 'device-123';
        $reason = TokenBlacklistEntry::REASON_DEVICE_LOST;

        $this->repository
            ->shouldReceive('blacklistAllDeviceTokens')
            ->once()
            ->with($deviceId, $reason)
            ->andReturn(3);

        $this->logger
            ->shouldReceive('info')
            ->once()
            ->with('Blacklisted device tokens', Mockery::type('array'));

        // Act
        $result = $this->service->blacklistDeviceTokens($deviceId, $reason);

        // Assert
        $this->assertSame(3, $result);
    }

    public function testBlacklistDeviceTokensWithEmptyDeviceId(): void
    {
        // Expect
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Device ID cannot be empty');

        // Act
        $this->service->blacklistDeviceTokens('', TokenBlacklistEntry::REASON_DEVICE_LOST);
    }

    public function testBlacklistDeviceTokensWithRepositoryException(): void
    {
        // Arrange
        $deviceId = 'device-123';
        $reason = TokenBlacklistEntry::REASON_DEVICE_LOST;

        $this->repository
            ->shouldReceive('blacklistAllDeviceTokens')
            ->once()
            ->andThrow(new RuntimeException('Database error'));

        $this->logger
            ->shouldReceive('error')
            ->once()
            ->with('Failed to blacklist device tokens', Mockery::type('array'));

        // Act
        $result = $this->service->blacklistDeviceTokens($deviceId, $reason);

        // Assert
        $this->assertSame(0, $result);
    }

    public function testRemoveFromBlacklistSuccessfully(): void
    {
        // Arrange
        $jti = 'test-jti-123';

        $this->repository
            ->shouldReceive('removeFromBlacklist')
            ->once()
            ->with($jti)
            ->andReturn(true);

        $this->logger
            ->shouldReceive('info')
            ->once()
            ->with('Token removed from blacklist', Mockery::type('array'));

        // Act
        $result = $this->service->removeFromBlacklist($jti);

        // Assert
        $this->assertTrue($result);
    }

    public function testRemoveFromBlacklistWithEmptyJti(): void
    {
        // Act
        $result = $this->service->removeFromBlacklist('');

        // Assert
        $this->assertFalse($result);
    }

    public function testRemoveFromBlacklistWithRepositoryException(): void
    {
        // Arrange
        $jti = 'test-jti-123';

        $this->repository
            ->shouldReceive('removeFromBlacklist')
            ->once()
            ->andThrow(new RuntimeException('Database error'));

        $this->logger
            ->shouldReceive('error')
            ->once()
            ->with('Failed to remove token from blacklist', Mockery::type('array'));

        // Act
        $result = $this->service->removeFromBlacklist($jti);

        // Assert
        $this->assertFalse($result);
    }

    public function testBatchRemoveFromBlacklistSuccessfully(): void
    {
        // Arrange
        $jtis = ['jti1', 'jti2', 'jti3'];

        $this->repository
            ->shouldReceive('batchRemoveFromBlacklist')
            ->once()
            ->with($jtis)
            ->andReturn(2);

        $this->logger
            ->shouldReceive('info')
            ->once()
            ->with('Batch removed tokens from blacklist', Mockery::type('array'));

        // Act
        $result = $this->service->batchRemoveFromBlacklist($jtis);

        // Assert
        $this->assertSame(2, $result);
    }

    public function testBatchRemoveFromBlacklistWithEmptyArray(): void
    {
        // Act
        $result = $this->service->batchRemoveFromBlacklist([]);

        // Assert
        $this->assertSame(0, $result);
    }

    public function testBatchRemoveFromBlacklistWithRepositoryException(): void
    {
        // Arrange
        $jtis = ['jti1', 'jti2'];

        $this->repository
            ->shouldReceive('batchRemoveFromBlacklist')
            ->once()
            ->andThrow(new RuntimeException('Database error'));

        $this->logger
            ->shouldReceive('error')
            ->once()
            ->with('Failed to batch remove tokens from blacklist', Mockery::type('array'));

        // Act
        $result = $this->service->batchRemoveFromBlacklist($jtis);

        // Assert
        $this->assertSame(0, $result);
    }

    public function testAutoCleanupSuccessfully(): void
    {
        // Arrange
        $this->repository
            ->shouldReceive('cleanupExpiredEntries')
            ->once()
            ->andReturn(10);

        $this->repository
            ->shouldReceive('cleanupOldEntries')
            ->once()
            ->andReturn(5);

        $this->logger
            ->shouldReceive('info')
            ->once()
            ->with('Auto cleanup completed', Mockery::type('array'));

        // Act
        $result = $this->service->autoCleanup();

        // Assert
        $this->assertTrue($result['success']);
        $this->assertSame(15, $result['total_cleaned']);
        $this->assertSame(10, $result['expired_cleaned']);
        $this->assertSame(5, $result['old_cleaned']);
        $this->assertIsFloat($result['execution_time']);
    }

    public function testAutoCleanupWithRepositoryException(): void
    {
        // Arrange
        $this->repository
            ->shouldReceive('cleanupExpiredEntries')
            ->once()
            ->andThrow(new RuntimeException('Database error'));

        $this->logger
            ->shouldReceive('error')
            ->once()
            ->with('Auto cleanup failed', Mockery::type('array'));

        // Act
        $result = $this->service->autoCleanup();

        // Assert
        $this->assertFalse($result['success']);
        $this->assertSame(0, $result['total_cleaned']);
        $this->assertArrayHasKey('error', $result);
        $this->assertIsFloat($result['execution_time']);
    }

    public function testGetStatisticsSuccessfully(): void
    {
        // Arrange
        $stats = ['total' => 100, 'by_token_type' => ['access' => 60, 'refresh' => 40]];
        $sizeInfo = ['total_entries' => 100, 'active_entries' => 80];

        $this->repository
            ->shouldReceive('getBlacklistStats')
            ->once()
            ->andReturn($stats);

        $this->repository
            ->shouldReceive('getSizeInfo')
            ->once()
            ->andReturn($sizeInfo);

        $this->repository
            ->shouldReceive('isSizeExceeded')
            ->once()
            ->andReturn(false);

        // Act
        $result = $this->service->getStatistics();

        // Assert
        $this->assertSame(100, $result['total']);
        $this->assertSame($sizeInfo, $result['size_info']);
        $this->assertFalse($result['is_size_exceeded']);
        $this->assertInstanceOf(DateTimeImmutable::class, $result['generated_at']);
    }

    public function testGetStatisticsWithRepositoryException(): void
    {
        // Arrange
        $this->repository
            ->shouldReceive('getBlacklistStats')
            ->once()
            ->andThrow(new RuntimeException('Database error'));

        $this->logger
            ->shouldReceive('error')
            ->once()
            ->with('Failed to get blacklist statistics', Mockery::type('array'));

        // Act
        $result = $this->service->getStatistics();

        // Assert
        $this->assertArrayHasKey('error', $result);
        $this->assertInstanceOf(DateTimeImmutable::class, $result['generated_at']);
    }

    public function testGetUserStatisticsSuccessfully(): void
    {
        // Arrange
        $userId = 1;
        $expected = ['total' => 10, 'by_reason' => ['logout' => 8, 'revoked' => 2]];

        $this->repository
            ->shouldReceive('getUserBlacklistStats')
            ->once()
            ->with($userId)
            ->andReturn($expected);

        // Act
        $result = $this->service->getUserStatistics($userId);

        // Assert
        $this->assertSame($expected, $result);
    }

    public function testGetUserStatisticsWithInvalidUserId(): void
    {
        // Expect
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('User ID must be positive');

        // Act
        $this->service->getUserStatistics(0);
    }

    public function testGetUserStatisticsWithRepositoryException(): void
    {
        // Arrange
        $userId = 1;

        $this->repository
            ->shouldReceive('getUserBlacklistStats')
            ->once()
            ->andThrow(new RuntimeException('Database error'));

        $this->logger
            ->shouldReceive('error')
            ->once()
            ->with('Failed to get user blacklist statistics', Mockery::type('array'));

        // Act
        $result = $this->service->getUserStatistics($userId);

        // Assert
        $this->assertSame(1, $result['user_id']);
        $this->assertArrayHasKey('error', $result);
    }

    public function testSearchBlacklistEntriesSuccessfully(): void
    {
        // Arrange
        $criteria = [
            'user_id' => 123,
            'token_type' => TokenBlacklistEntry::TOKEN_TYPE_ACCESS,
            'reason' => TokenBlacklistEntry::REASON_LOGOUT,
        ];
        $limit = 50;
        $offset = 0;

        $entry1 = new TokenBlacklistEntry(
            jti: 'test-jti-1',
            tokenType: TokenBlacklistEntry::TOKEN_TYPE_ACCESS,
            expiresAt: new DateTimeImmutable('+1 hour'),
            blacklistedAt: new DateTimeImmutable(),
            reason: TokenBlacklistEntry::REASON_LOGOUT,
            userId: 123,
        );
        $entry2 = new TokenBlacklistEntry(
            jti: 'test-jti-2',
            tokenType: TokenBlacklistEntry::TOKEN_TYPE_ACCESS,
            expiresAt: new DateTimeImmutable('+2 hours'),
            blacklistedAt: new DateTimeImmutable(),
            reason: TokenBlacklistEntry::REASON_LOGOUT,
            userId: 123,
        );
        $expectedEntries = [$entry1, $entry2];

        $this->repository->shouldReceive('search')
            ->once()
            ->with($criteria, $limit, $offset)
            ->andReturn($expectedEntries);

        $this->repository->shouldReceive('countSearch')
            ->once()
            ->with($criteria)
            ->andReturn(2);

        // Act
        $result = $this->service->searchBlacklistEntries($criteria, $limit, $offset);

        // Assert
        $this->assertSame($expectedEntries, $result['entries']);
        $this->assertSame(2, $result['total']);
        $this->assertSame($limit, $result['limit']);
        $this->assertSame($offset, $result['offset']);
        $this->assertSame(false, $result['has_more']);
    }

    public function testSearchBlacklistEntriesWithNegativeOffset(): void
    {
        // Expect
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Offset must be non-negative');

        // Act
        $this->service->searchBlacklistEntries([], null, -1);
    }

    public function testSearchBlacklistEntriesWithZeroLimit(): void
    {
        // Expect
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Limit must be positive');

        // Act
        $this->service->searchBlacklistEntries([], 0);
    }

    public function testSearchBlacklistEntriesWithRepositoryException(): void
    {
        // Arrange
        $criteria = ['user_id' => 1];

        $this->repository
            ->shouldReceive('search')
            ->once()
            ->andThrow(new RuntimeException('Database error'));

        $this->logger
            ->shouldReceive('error')
            ->once()
            ->with('Failed to search blacklist entries', Mockery::type('array'));

        // Act
        $result = $this->service->searchBlacklistEntries($criteria);

        // Assert
        $this->assertSame([], $result['entries']);
        $this->assertSame(0, $result['total']);
        $this->assertArrayHasKey('error', $result);
    }

    public function testGetRecentHighPriorityEntriesSuccessfully(): void
    {
        // Arrange
        $limit = 25;
        $entry = new TokenBlacklistEntry(
            jti: 'high-priority-jti',
            tokenType: TokenBlacklistEntry::TOKEN_TYPE_ACCESS,
            expiresAt: new DateTimeImmutable('-1 hour'), // 已過期，高優先級
            blacklistedAt: new DateTimeImmutable(),
            reason: TokenBlacklistEntry::REASON_SECURITY_BREACH,
            userId: 456,
        );
        $entries = [$entry];

        $this->repository
            ->shouldReceive('getHighPriorityEntries')
            ->once()
            ->with($limit)
            ->andReturn($entries);

        // Act
        $result = $this->service->getRecentHighPriorityEntries($limit);

        // Assert
        $this->assertSame($entries, $result);
    }

    public function testGetRecentHighPriorityEntriesWithRepositoryException(): void
    {
        // Arrange
        $this->repository
            ->shouldReceive('getHighPriorityEntries')
            ->once()
            ->andThrow(new RuntimeException('Database error'));

        $this->logger
            ->shouldReceive('error')
            ->once()
            ->with('Failed to get high priority entries', Mockery::type('array'));

        // Act
        $result = $this->service->getRecentHighPriorityEntries();

        // Assert
        $this->assertSame([], $result);
    }

    public function testOptimizeSuccessfully(): void
    {
        // Arrange
        $expected = [
            'cleaned_entries' => 50,
            'compacted_size' => 1024000,
            'execution_time' => 2.5,
        ];

        $this->repository
            ->shouldReceive('optimize')
            ->once()
            ->andReturn($expected);

        $this->logger
            ->shouldReceive('info')
            ->once()
            ->with('Blacklist optimization completed', $expected);

        // Act
        $result = $this->service->optimize();

        // Assert
        $this->assertSame($expected, $result);
    }

    public function testOptimizeWithRepositoryException(): void
    {
        // Arrange
        $this->repository
            ->shouldReceive('optimize')
            ->once()
            ->andThrow(new RuntimeException('Database error'));

        $this->logger
            ->shouldReceive('error')
            ->once()
            ->with('Blacklist optimization failed', Mockery::type('array'));

        // Act
        $result = $this->service->optimize();

        // Assert
        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
    }

    public function testGetHealthStatusHealthy(): void
    {
        // Arrange
        $sizeInfo = [
            'total_entries' => 1000,
            'active_entries' => 800,
            'expired_entries' => 100,
            'cleanable_entries' => 50,
        ];
        $stats = ['security_related' => 10];

        $this->repository
            ->shouldReceive('getSizeInfo')
            ->once()
            ->andReturn($sizeInfo);

        $this->repository
            ->shouldReceive('isSizeExceeded')
            ->once()
            ->andReturn(false);

        $this->repository
            ->shouldReceive('getBlacklistStats')
            ->once()
            ->andReturn($stats);

        // Act
        $result = $this->service->getHealthStatus();

        // Assert
        $this->assertTrue($result['healthy']);
        $this->assertFalse($result['size_exceeded']);
        $this->assertFalse($result['too_large']);
        $this->assertSame(100000, $result['max_recommended_size']);
        $this->assertSame(1000, $result['total_entries']);
        $this->assertIsArray($result['recommendations']);
    }

    public function testGetHealthStatusUnhealthyWithRecommendations(): void
    {
        // Arrange
        $sizeInfo = [
            'total_entries' => 200000,
            'active_entries' => 150000,
            'expired_entries' => 50000,
            'cleanable_entries' => 1000,
        ];
        $stats = ['security_related' => 500];

        $this->repository
            ->shouldReceive('getSizeInfo')
            ->once()
            ->andReturn($sizeInfo);

        $this->repository
            ->shouldReceive('isSizeExceeded')
            ->once()
            ->andReturn(true);

        $this->repository
            ->shouldReceive('getBlacklistStats')
            ->once()
            ->andReturn($stats);

        // Act
        $result = $this->service->getHealthStatus();

        // Assert
        $this->assertFalse($result['healthy']);
        $this->assertTrue($result['size_exceeded']);
        $this->assertTrue($result['too_large']);
        $this->assertSame(100000, $result['max_recommended_size']);
        $this->assertContains('Run cleanup to reduce blacklist size', $result['recommendations']);
        $this->assertContains('Blacklist size exceeds recommended limit, consider cleanup', $result['recommendations']);
        $this->assertContains('High number of expired entries, consider cleanup', $result['recommendations']);
        $this->assertContains('High security-related blacklist entries detected', $result['recommendations']);
    }

    public function testGetHealthStatusWithRepositoryException(): void
    {
        // Arrange
        $this->repository
            ->shouldReceive('getSizeInfo')
            ->once()
            ->andThrow(new RuntimeException('Database error'));

        $this->logger
            ->shouldReceive('error')
            ->once()
            ->with('Failed to get blacklist health status', Mockery::type('array'));

        // Act
        $result = $this->service->getHealthStatus();

        // Assert
        $this->assertFalse($result['healthy']);
        $this->assertArrayHasKey('error', $result);
    }

    public function testServiceWithoutLogger(): void
    {
        // Arrange
        $service = new TokenBlacklistService($this->repository);
        $jti = 'test-jti-123';

        $this->repository
            ->shouldReceive('isBlacklisted')
            ->once()
            ->with($jti)
            ->andReturn(false);

        // Act
        $result = $service->isTokenBlacklisted($jti);

        // Assert
        $this->assertFalse($result);
    }
}
