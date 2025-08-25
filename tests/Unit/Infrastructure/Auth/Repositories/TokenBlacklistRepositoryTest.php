<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Auth\Repositories;

use AlleyNote\Domains\Auth\ValueObjects\TokenBlacklistEntry;
use AlleyNote\Infrastructure\Auth\Repositories\TokenBlacklistRepository;
use DateTime;
use DateTimeImmutable;
use PDO;
use PDOException;
use PDOStatement;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

/**
 * TokenBlacklistRepository 單元測試.
 *
 * 測試 TokenBlacklistRepository 的所有公開方法，確保功能正確性。
 * 使用 Mock PDO 來模擬資料庫操作，避免依賴實際資料庫。
 *
 * @author GitHub Copilot
 * @since 1.0.0
 */
class TokenBlacklistRepositoryTest extends TestCase
{
    private TokenBlacklistRepository $repository;

    private PDO&MockObject $mockPdo;

    private PDOStatement&MockObject $mockStatement;

    protected function setUp(): void
    {
        $this->mockPdo = $this->createMock(PDO::class);
        $this->mockStatement = $this->createMock(PDOStatement::class);
        $this->repository = new TokenBlacklistRepository($this->mockPdo);
    }

    public function testAddToBlacklistSuccess(): void
    {
        $entry = $this->createSampleEntry();

        $this->mockPdo
            ->expects($this->once())
            ->method('prepare')
            ->willReturn($this->mockStatement);

        $this->mockStatement
            ->expects($this->once())
            ->method('execute')
            ->willReturn(true);

        $result = $this->repository->addToBlacklist($entry);

        $this->assertTrue($result);
    }

    public function testAddToBlacklistFailure(): void
    {
        $entry = $this->createSampleEntry();

        $this->mockPdo
            ->expects($this->once())
            ->method('prepare')
            ->willReturn($this->mockStatement);

        $this->mockStatement
            ->expects($this->once())
            ->method('execute')
            ->willReturn(false);

        $result = $this->repository->addToBlacklist($entry);

        $this->assertFalse($result);
    }

    public function testAddToBlacklistDuplicateKey(): void
    {
        $entry = $this->createSampleEntry();

        $this->mockPdo
            ->expects($this->once())
            ->method('prepare')
            ->willReturn($this->mockStatement);

        $pdoException = new PDOException('UNIQUE constraint failed: token_blacklist.jti');
        // 設定正確的錯誤碼
        $reflectionProperty = new ReflectionProperty(PDOException::class, 'code');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($pdoException, '23000');

        $this->mockStatement
            ->expects($this->once())
            ->method('execute')
            ->willThrowException($pdoException);

        $result = $this->repository->addToBlacklist($entry);

        $this->assertFalse($result);
    }

    public function testAddToBlacklistOtherException(): void
    {
        $entry = $this->createSampleEntry();

        $this->mockPdo
            ->expects($this->once())
            ->method('prepare')
            ->willReturn($this->mockStatement);

        $pdoException = new PDOException('Some other error');

        $this->mockStatement
            ->expects($this->once())
            ->method('execute')
            ->willThrowException($pdoException);

        $this->expectException(PDOException::class);
        $this->expectExceptionMessage('Some other error');

        $this->repository->addToBlacklist($entry);
    }

    public function testIsBlacklistedTrue(): void
    {
        $jti = 'test-jti';

        $this->mockPdo
            ->expects($this->once())
            ->method('prepare')
            ->willReturn($this->mockStatement);

        $this->mockStatement
            ->expects($this->once())
            ->method('execute')
            ->with(['jti' => $jti]);

        $this->mockStatement
            ->expects($this->once())
            ->method('fetchColumn')
            ->willReturn('1');

        $result = $this->repository->isBlacklisted($jti);

        $this->assertTrue($result);
    }

    public function testIsBlacklistedFalse(): void
    {
        $jti = 'test-jti';

        $this->mockPdo
            ->expects($this->once())
            ->method('prepare')
            ->willReturn($this->mockStatement);

        $this->mockStatement
            ->expects($this->once())
            ->method('execute')
            ->with(['jti' => $jti]);

        $this->mockStatement
            ->expects($this->once())
            ->method('fetchColumn')
            ->willReturn('0');

        $result = $this->repository->isBlacklisted($jti);

        $this->assertFalse($result);
    }

    public function testIsBlacklistedWithException(): void
    {
        $jti = 'test-jti';

        $this->mockPdo
            ->expects($this->once())
            ->method('prepare')
            ->willThrowException(new PDOException('Database error'));

        $result = $this->repository->isBlacklisted($jti);

        $this->assertFalse($result);
    }

    public function testIsTokenHashBlacklistedTrue(): void
    {
        $tokenHash = 'test-hash';

        $this->mockPdo
            ->expects($this->once())
            ->method('prepare')
            ->willReturn($this->mockStatement);

        $this->mockStatement
            ->expects($this->once())
            ->method('execute')
            ->with(['token_hash' => $tokenHash]);

        $this->mockStatement
            ->expects($this->once())
            ->method('fetchColumn')
            ->willReturn('1');

        $result = $this->repository->isTokenHashBlacklisted($tokenHash);

        $this->assertTrue($result);
    }

    public function testRemoveFromBlacklistSuccess(): void
    {
        $jti = 'test-jti';

        $this->mockPdo
            ->expects($this->once())
            ->method('prepare')
            ->willReturn($this->mockStatement);

        $this->mockStatement
            ->expects($this->once())
            ->method('execute')
            ->with(['jti' => $jti]);

        $this->mockStatement
            ->expects($this->once())
            ->method('rowCount')
            ->willReturn(1);

        $result = $this->repository->removeFromBlacklist($jti);

        $this->assertTrue($result);
    }

    public function testRemoveFromBlacklistNotFound(): void
    {
        $jti = 'test-jti';

        $this->mockPdo
            ->expects($this->once())
            ->method('prepare')
            ->willReturn($this->mockStatement);

        $this->mockStatement
            ->expects($this->once())
            ->method('execute')
            ->with(['jti' => $jti]);

        $this->mockStatement
            ->expects($this->once())
            ->method('rowCount')
            ->willReturn(0);

        $result = $this->repository->removeFromBlacklist($jti);

        $this->assertFalse($result);
    }

    public function testFindByJtiFound(): void
    {
        $jti = 'test-jti';
        $row = [
            'jti' => $jti,
            'token_type' => 'access',
            'user_id' => 1,
            'expires_at' => '2024-12-31 23:59:59',
            'blacklisted_at' => new DateTimeImmutable()->format('Y-m-d H:i:s'),
            'reason' => TokenBlacklistEntry::REASON_LOGOUT,
            'device_id' => 'device-123',
            'metadata' => '{"test": "value"}',
        ];

        $this->mockPdo
            ->expects($this->once())
            ->method('prepare')
            ->willReturn($this->mockStatement);

        $this->mockStatement
            ->expects($this->once())
            ->method('execute')
            ->with(['jti' => $jti]);

        $this->mockStatement
            ->expects($this->once())
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn($row);

        $result = $this->repository->findByJti($jti);

        $this->assertInstanceOf(TokenBlacklistEntry::class, $result);
        $this->assertEquals($jti, $result->getJti());
        $this->assertEquals('access', $result->getTokenType());
    }

    public function testFindByJtiNotFound(): void
    {
        $jti = 'test-jti';

        $this->mockPdo
            ->expects($this->once())
            ->method('prepare')
            ->willReturn($this->mockStatement);

        $this->mockStatement
            ->expects($this->once())
            ->method('execute')
            ->with(['jti' => $jti]);

        $this->mockStatement
            ->expects($this->once())
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn(false);

        $result = $this->repository->findByJti($jti);

        $this->assertNull($result);
    }

    public function testFindByUserId(): void
    {
        $userId = 123;
        $limit = 10;

        $this->mockPdo
            ->expects($this->once())
            ->method('prepare')
            ->willReturn($this->mockStatement);

        $this->mockStatement
            ->expects($this->exactly(2))
            ->method('bindValue')
            ->willReturnMap([
                ['user_id', $userId, PDO::PARAM_INT, true],
                ['limit', $limit, PDO::PARAM_INT, true],
            ]);

        $this->mockStatement
            ->expects($this->once())
            ->method('execute');

        $this->mockStatement
            ->expects($this->exactly(2))
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturnOnConsecutiveCalls(
                [
                    'jti' => 'jti1',
                    'token_type' => 'access',
                    'user_id' => $userId,
                    'expires_at' => '2024-12-31 23:59:59',
                    'blacklisted_at' => new DateTimeImmutable()->format('Y-m-d H:i:s'),
                    'reason' => TokenBlacklistEntry::REASON_LOGOUT,
                    'device_id' => null,
                    'metadata' => null,
                ],
                false,
            );

        $result = $this->repository->findByUserId($userId, $limit);

        $this->assertCount(1, $result);
        $this->assertInstanceOf(TokenBlacklistEntry::class, $result[0]);
        $this->assertEquals('jti1', $result[0]->getJti());
    }

    public function testFindByUserIdWithException(): void
    {
        $userId = 123;

        $this->mockPdo
            ->expects($this->once())
            ->method('prepare')
            ->willThrowException(new PDOException('Database error'));

        $result = $this->repository->findByUserId($userId);

        $this->assertEquals([], $result);
    }

    public function testFindByDeviceId(): void
    {
        $deviceId = 'device-123';
        $limit = 5;

        $this->mockPdo
            ->expects($this->once())
            ->method('prepare')
            ->willReturn($this->mockStatement);

        $this->mockStatement
            ->expects($this->exactly(2))
            ->method('bindValue')
            ->willReturnMap([
                ['device_id', $deviceId, PDO::PARAM_STR, true],
                ['limit', $limit, PDO::PARAM_INT, true],
            ]);

        $this->mockStatement
            ->expects($this->once())
            ->method('execute');

        $this->mockStatement
            ->expects($this->once())
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn(false);

        $result = $this->repository->findByDeviceId($deviceId, $limit);

        $this->assertEquals([], $result);
    }

    public function testFindByReason(): void
    {
        $reason = 'logout';

        $this->mockPdo
            ->expects($this->once())
            ->method('prepare')
            ->willReturn($this->mockStatement);

        $this->mockStatement
            ->expects($this->once())
            ->method('bindValue')
            ->with('reason', $reason, PDO::PARAM_STR);

        $this->mockStatement
            ->expects($this->once())
            ->method('execute');

        $this->mockStatement
            ->expects($this->once())
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn(false);

        $result = $this->repository->findByReason($reason);

        $this->assertEquals([], $result);
    }

    public function testBatchAddToBlacklistSuccess(): void
    {
        $entries = [
            $this->createSampleEntry('jti1'),
            $this->createSampleEntry('jti2'),
        ];

        $this->mockPdo
            ->expects($this->once())
            ->method('beginTransaction');

        $this->mockPdo
            ->expects($this->once())
            ->method('prepare')
            ->willReturn($this->mockStatement);

        $this->mockStatement
            ->expects($this->exactly(2))
            ->method('execute')
            ->willReturn(true);

        $this->mockPdo
            ->expects($this->once())
            ->method('commit');

        $result = $this->repository->batchAddToBlacklist($entries);

        $this->assertEquals(2, $result);
    }

    public function testBatchAddToBlacklistEmpty(): void
    {
        $result = $this->repository->batchAddToBlacklist([]);
        $this->assertEquals(0, $result);
    }

    public function testBatchAddToBlacklistWithFailures(): void
    {
        $entries = [
            $this->createSampleEntry('jti1'),
            $this->createSampleEntry('jti2'),
        ];

        $this->mockPdo
            ->expects($this->once())
            ->method('beginTransaction');

        $this->mockPdo
            ->expects($this->once())
            ->method('prepare')
            ->willReturn($this->mockStatement);

        $this->mockStatement
            ->expects($this->exactly(2))
            ->method('execute')
            ->willReturnOnConsecutiveCalls(true, false);

        $this->mockPdo
            ->expects($this->once())
            ->method('commit');

        $result = $this->repository->batchAddToBlacklist($entries);

        $this->assertEquals(1, $result);
    }

    public function testBatchAddToBlacklistWithException(): void
    {
        $entries = [$this->createSampleEntry()];

        $this->mockPdo
            ->expects($this->once())
            ->method('beginTransaction');

        $this->mockPdo
            ->expects($this->once())
            ->method('prepare')
            ->willThrowException(new PDOException('Database error'));

        $this->mockPdo
            ->expects($this->once())
            ->method('rollBack');

        $this->expectException(PDOException::class);

        $this->repository->batchAddToBlacklist($entries);
    }

    public function testBatchIsBlacklistedSuccess(): void
    {
        $jtis = ['jti1', 'jti2', 'jti3'];

        $this->mockPdo
            ->expects($this->once())
            ->method('prepare')
            ->willReturn($this->mockStatement);

        $this->mockStatement
            ->expects($this->once())
            ->method('execute')
            ->with($jtis);

        $this->mockStatement
            ->expects($this->once())
            ->method('fetchAll')
            ->with(PDO::FETCH_COLUMN)
            ->willReturn(['jti1', 'jti3']);

        $result = $this->repository->batchIsBlacklisted($jtis);

        $expected = [
            'jti1' => true,
            'jti2' => false,
            'jti3' => true,
        ];

        $this->assertEquals($expected, $result);
    }

    public function testBatchIsBlacklistedEmpty(): void
    {
        $result = $this->repository->batchIsBlacklisted([]);
        $this->assertEquals([], $result);
    }

    public function testBatchIsBlacklistedWithException(): void
    {
        $jtis = ['jti1', 'jti2'];

        $this->mockPdo
            ->expects($this->once())
            ->method('prepare')
            ->willThrowException(new PDOException('Database error'));

        $result = $this->repository->batchIsBlacklisted($jtis);

        $expected = [
            'jti1' => false,
            'jti2' => false,
        ];

        $this->assertEquals($expected, $result);
    }

    public function testBatchRemoveFromBlacklistSuccess(): void
    {
        $jtis = ['jti1', 'jti2'];

        $this->mockPdo
            ->expects($this->once())
            ->method('prepare')
            ->willReturn($this->mockStatement);

        $this->mockStatement
            ->expects($this->once())
            ->method('execute')
            ->with($jtis);

        $this->mockStatement
            ->expects($this->once())
            ->method('rowCount')
            ->willReturn(2);

        $result = $this->repository->batchRemoveFromBlacklist($jtis);

        $this->assertEquals(2, $result);
    }

    public function testBatchRemoveFromBlacklistEmpty(): void
    {
        $result = $this->repository->batchRemoveFromBlacklist([]);
        $this->assertEquals(0, $result);
    }

    public function testBlacklistAllUserTokensSuccess(): void
    {
        $userId = 123;
        $reason = 'security_breach';
        $excludeJti = 'exclude-me';

        // Mock for select statement
        $selectStmt = $this->createMock(PDOStatement::class);
        // Mock for batch insert statements
        $insertStmt = $this->createMock(PDOStatement::class);

        $this->mockPdo
            ->expects($this->exactly(2))
            ->method('prepare')
            ->willReturnOnConsecutiveCalls($selectStmt, $insertStmt);

        // First call for selecting tokens
        $selectStmt
            ->expects($this->once())
            ->method('execute')
            ->with([
                'user_id' => $userId,
                'exclude_jti' => $excludeJti,
            ]);

        $selectStmt
            ->expects($this->once())
            ->method('fetchAll')
            ->with(PDO::FETCH_COLUMN)
            ->willReturn(['jti1', 'jti2']);

        // Mock beginTransaction and commit for batch insert
        $this->mockPdo
            ->expects($this->once())
            ->method('beginTransaction');

        // Mock batch insert calls
        $insertStmt
            ->expects($this->exactly(2))
            ->method('execute')
            ->willReturn(true);

        $this->mockPdo
            ->expects($this->once())
            ->method('commit');

        $result = $this->repository->blacklistAllUserTokens($userId, $reason, $excludeJti);

        $this->assertEquals(2, $result);
    }

    public function testBlacklistAllUserTokensNoTokens(): void
    {
        $userId = 123;
        $reason = 'logout';

        $this->mockPdo
            ->expects($this->once())
            ->method('prepare')
            ->willReturn($this->mockStatement);

        $this->mockStatement
            ->expects($this->once())
            ->method('execute');

        $this->mockStatement
            ->expects($this->once())
            ->method('fetchAll')
            ->with(PDO::FETCH_COLUMN)
            ->willReturn([]);

        $result = $this->repository->blacklistAllUserTokens($userId, $reason);

        $this->assertEquals(0, $result);
    }

    public function testBlacklistAllDeviceTokensSuccess(): void
    {
        $deviceId = 'device-123';
        $reason = 'device_lost';

        // Mock for select statement
        $selectStmt = $this->createMock(PDOStatement::class);
        // Mock for batch insert statements
        $insertStmt = $this->createMock(PDOStatement::class);

        $this->mockPdo
            ->expects($this->exactly(2))
            ->method('prepare')
            ->willReturnOnConsecutiveCalls($selectStmt, $insertStmt);

        $selectStmt
            ->expects($this->once())
            ->method('execute')
            ->with(['device_id' => $deviceId]);

        $selectStmt
            ->expects($this->once())
            ->method('fetchAll')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn([
                ['jti' => 'jti1', 'user_id' => 1],
                ['jti' => 'jti2', 'user_id' => 2],
            ]);

        $this->mockPdo
            ->expects($this->once())
            ->method('beginTransaction');

        // Mock batch insert calls
        $insertStmt
            ->expects($this->exactly(2))
            ->method('execute')
            ->willReturn(true);

        $this->mockPdo
            ->expects($this->once())
            ->method('commit');

        $result = $this->repository->blacklistAllDeviceTokens($deviceId, $reason);

        $this->assertEquals(2, $result);
    }

    public function testCleanupSuccess(): void
    {
        $this->mockPdo
            ->expects($this->once())
            ->method('prepare')
            ->willReturn($this->mockStatement);

        $this->mockStatement
            ->expects($this->once())
            ->method('execute');

        $this->mockStatement
            ->expects($this->once())
            ->method('rowCount')
            ->willReturn(5);

        $result = $this->repository->cleanup();

        $this->assertEquals(5, $result);
    }

    public function testCleanupWithBeforeDate(): void
    {
        $beforeDate = new DateTime('2024-01-01 00:00:00');

        $this->mockPdo
            ->expects($this->once())
            ->method('prepare')
            ->willReturn($this->mockStatement);

        $this->mockStatement
            ->expects($this->once())
            ->method('execute')
            ->with(['before_date' => '2024-01-01 00:00:00']);

        $this->mockStatement
            ->expects($this->once())
            ->method('rowCount')
            ->willReturn(3);

        $result = $this->repository->cleanup($beforeDate);

        $this->assertEquals(3, $result);
    }

    public function testCleanupExpiredEntries(): void
    {
        $this->mockPdo
            ->expects($this->once())
            ->method('prepare')
            ->willReturn($this->mockStatement);

        $this->mockStatement
            ->expects($this->once())
            ->method('execute');

        $this->mockStatement
            ->expects($this->once())
            ->method('rowCount')
            ->willReturn(2);

        $result = $this->repository->cleanupExpiredEntries();

        $this->assertEquals(2, $result);
    }

    public function testCleanupOldEntries(): void
    {
        $days = 30;

        $this->mockPdo
            ->expects($this->once())
            ->method('prepare')
            ->willReturn($this->mockStatement);

        $this->mockStatement
            ->expects($this->once())
            ->method('execute')
            ->with(['days' => $days]);

        $this->mockStatement
            ->expects($this->once())
            ->method('rowCount')
            ->willReturn(10);

        $result = $this->repository->cleanupOldEntries($days);

        $this->assertEquals(10, $result);
    }

    public function testGetBlacklistStatsSuccess(): void
    {
        // Mock multiple prepared statements
        $totalStmt = $this->createMock(PDOStatement::class);
        $typeStmt = $this->createMock(PDOStatement::class);
        $reasonStmt = $this->createMock(PDOStatement::class);
        $securityStmt = $this->createMock(PDOStatement::class);
        $userStmt = $this->createMock(PDOStatement::class);

        $this->mockPdo
            ->expects($this->exactly(5))
            ->method('prepare')
            ->willReturnOnConsecutiveCalls($totalStmt, $typeStmt, $reasonStmt, $securityStmt, $userStmt);

        $totalStmt->expects($this->once())->method('execute');
        $totalStmt->expects($this->once())->method('fetchColumn')->willReturn('100');

        $typeStmt->expects($this->once())->method('execute');
        $typeStmt->expects($this->once())->method('fetchAll')->with(PDO::FETCH_KEY_PAIR)
            ->willReturn(['access' => 60, 'refresh' => 40]);

        $reasonStmt->expects($this->once())->method('execute');
        $reasonStmt->expects($this->once())->method('fetchAll')->with(PDO::FETCH_KEY_PAIR)
            ->willReturn(['logout' => 50, 'security_breach' => 25, 'expired' => 25]);

        $securityStmt->expects($this->once())->method('execute');
        $securityStmt->expects($this->once())->method('fetchColumn')->willReturn('30');

        $userStmt->expects($this->once())->method('execute');
        $userStmt->expects($this->once())->method('fetchColumn')->willReturn('70');

        $result = $this->repository->getBlacklistStats();

        $expected = [
            'total' => 100,
            'by_token_type' => ['access' => 60, 'refresh' => 40],
            'by_reason' => ['logout' => 50, 'security_breach' => 25, 'expired' => 25],
            'security_related' => 30,
            'user_initiated' => 70,
            'system_initiated' => 30,
        ];

        $this->assertEquals($expected, $result);
    }

    public function testGetUserBlacklistStatsSuccess(): void
    {
        $userId = 123;

        $this->mockPdo
            ->expects($this->once())
            ->method('prepare')
            ->willReturn($this->mockStatement);

        $this->mockStatement
            ->expects($this->once())
            ->method('execute')
            ->with(['user_id' => $userId]);

        $this->mockStatement
            ->expects($this->once())
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn([
                'total' => 10,
                'access_tokens' => 6,
                'refresh_tokens' => 4,
                'security_related' => 2,
                'last_blacklisted' => '2024-01-01 12:00:00',
            ]);

        $result = $this->repository->getUserBlacklistStats($userId);

        $expected = [
            'total' => 10,
            'access_tokens' => 6,
            'refresh_tokens' => 4,
            'security_related' => 2,
            'last_blacklisted' => '2024-01-01 12:00:00',
        ];

        $this->assertEquals($expected, $result);
    }

    public function testGetRecentBlacklistEntriesSuccess(): void
    {
        $limit = 50;
        $since = new DateTime('2024-01-01 00:00:00');

        $this->mockPdo
            ->expects($this->once())
            ->method('prepare')
            ->willReturn($this->mockStatement);

        $this->mockStatement
            ->expects($this->exactly(2))
            ->method('bindValue')
            ->willReturnMap([
                ['since', '2024-01-01 00:00:00', PDO::PARAM_STR, true],
                ['limit', $limit, PDO::PARAM_INT, true],
            ]);

        $this->mockStatement
            ->expects($this->once())
            ->method('execute');

        $this->mockStatement
            ->expects($this->once())
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn(false);

        $result = $this->repository->getRecentBlacklistEntries($limit, $since);

        $this->assertEquals([], $result);
    }

    public function testGetHighPriorityEntriesSuccess(): void
    {
        $limit = 25;

        $this->mockPdo
            ->expects($this->once())
            ->method('prepare')
            ->willReturn($this->mockStatement);

        $this->mockStatement
            ->expects($this->once())
            ->method('bindValue')
            ->with('limit', $limit, PDO::PARAM_INT);

        $this->mockStatement
            ->expects($this->once())
            ->method('execute');

        $this->mockStatement
            ->expects($this->once())
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn(false);

        $result = $this->repository->getHighPriorityEntries($limit);

        $this->assertEquals([], $result);
    }

    public function testSearchSuccess(): void
    {
        $criteria = [
            'user_id' => 123,
            'device_id' => 'device-123',
            'token_type' => 'access',
            'reason' => 'logout',
        ];
        $limit = 10;
        $offset = 5;

        $this->mockPdo
            ->expects($this->once())
            ->method('prepare')
            ->willReturn($this->mockStatement);

        $this->mockStatement
            ->expects($this->exactly(6))
            ->method('bindValue')
            ->willReturnMap([
                ['user_id', 123, PDO::PARAM_INT, true],
                ['device_id', 'device-123', PDO::PARAM_STR, true],
                ['token_type', 'access', PDO::PARAM_STR, true],
                ['reason', 'logout', PDO::PARAM_STR, true],
                ['limit', 10, PDO::PARAM_INT, true],
                ['offset', 5, PDO::PARAM_INT, true],
            ]);

        $this->mockStatement
            ->expects($this->once())
            ->method('execute');

        $this->mockStatement
            ->expects($this->once())
            ->method('fetch')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn(false);

        $result = $this->repository->search($criteria, $limit, $offset);

        $this->assertEquals([], $result);
    }

    public function testCountSearchSuccess(): void
    {
        $criteria = ['user_id' => 123];

        $this->mockPdo
            ->expects($this->once())
            ->method('prepare')
            ->willReturn($this->mockStatement);

        $this->mockStatement
            ->expects($this->once())
            ->method('bindValue')
            ->with('user_id', 123, PDO::PARAM_INT);

        $this->mockStatement
            ->expects($this->once())
            ->method('execute');

        $this->mockStatement
            ->expects($this->once())
            ->method('fetchColumn')
            ->willReturn('42');

        $result = $this->repository->countSearch($criteria);

        $this->assertEquals(42, $result);
    }

    public function testIsSizeExceededTrue(): void
    {
        $maxSize = 1000;

        $this->mockPdo
            ->expects($this->once())
            ->method('prepare')
            ->willReturn($this->mockStatement);

        $this->mockStatement
            ->expects($this->once())
            ->method('execute');

        $this->mockStatement
            ->expects($this->once())
            ->method('fetchColumn')
            ->willReturn('1500');

        $result = $this->repository->isSizeExceeded($maxSize);

        $this->assertTrue($result);
    }

    public function testIsSizeExceededFalse(): void
    {
        $maxSize = 1000;

        $this->mockPdo
            ->expects($this->once())
            ->method('prepare')
            ->willReturn($this->mockStatement);

        $this->mockStatement
            ->expects($this->once())
            ->method('execute');

        $this->mockStatement
            ->expects($this->once())
            ->method('fetchColumn')
            ->willReturn('500');

        $result = $this->repository->isSizeExceeded($maxSize);

        $this->assertFalse($result);
    }

    public function testGetSizeInfoSuccess(): void
    {
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
            ->willReturn([
                'total_entries' => '1000',
                'active_entries' => '800',
                'expired_entries' => '200',
            ]);

        $result = $this->repository->getSizeInfo();

        $expected = [
            'total_entries' => 1000,
            'active_entries' => 800,
            'expired_entries' => 200,
            'cleanable_entries' => 200,
            'estimated_size_mb' => 0.19, // (1000 * 200) / (1024 * 1024)
        ];

        $this->assertEquals($expected, $result);
    }

    public function testOptimizeSuccess(): void
    {
        // Mock multiple prepare calls for optimization
        $cleanupStmt = $this->createMock(PDOStatement::class);
        $totalCountStmt = $this->createMock(PDOStatement::class);
        $cleanupOldStmt = $this->createMock(PDOStatement::class);
        $newSizeStmt = $this->createMock(PDOStatement::class);

        $this->mockPdo
            ->expects($this->once())
            ->method('beginTransaction');

        $this->mockPdo
            ->expects($this->exactly(4))
            ->method('prepare')
            ->willReturnOnConsecutiveCalls($cleanupStmt, $totalCountStmt, $cleanupOldStmt, $newSizeStmt);

        // Cleanup expired entries call
        $cleanupStmt->expects($this->once())->method('execute');
        $cleanupStmt->expects($this->once())->method('rowCount')->willReturn(100);

        // Total count call - 檢查是否需要額外清理
        $totalCountStmt->expects($this->once())->method('execute');
        $totalCountStmt->expects($this->once())->method('fetchColumn')->willReturn('60000');

        // Cleanup old entries call (因為總數量 > 50000)
        $cleanupOldStmt->expects($this->once())->method('execute')->with(['days' => 30]);
        $cleanupOldStmt->expects($this->once())->method('rowCount')->willReturn(20);

        // New size info call
        $newSizeStmt->expects($this->once())->method('execute');
        $newSizeStmt->expects($this->once())->method('fetch')->with(PDO::FETCH_ASSOC)
            ->willReturn(['total_entries' => '30000', 'active_entries' => '25000', 'expired_entries' => '5000']);

        // Mock getAttribute for SQLite check
        $this->mockPdo
            ->expects($this->once())
            ->method('getAttribute')
            ->with(PDO::ATTR_DRIVER_NAME)
            ->willReturn('sqlite');

        // Mock VACUUM execution
        $this->mockPdo
            ->expects($this->once())
            ->method('exec')
            ->with('VACUUM');

        $this->mockPdo
            ->expects($this->once())
            ->method('commit');

        $result = $this->repository->optimize();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('cleaned_entries', $result);
        $this->assertArrayHasKey('compacted_size', $result);
        $this->assertArrayHasKey('execution_time', $result);
        $this->assertArrayHasKey('total_entries_after', $result);
        $this->assertEquals(120, $result['cleaned_entries']); // 100 + 20
        $this->assertEquals(5.72, $result['compacted_size']); // (30000 * 200) / (1024 * 1024)
    }

    public function testOptimizeWithException(): void
    {
        // 建立新的 Mock PDO 實例避免干擾其他測試
        $failingPdo = $this->createMock(PDO::class);
        $repository = new TokenBlacklistRepository($failingPdo);

        $failingPdo
            ->expects($this->once())
            ->method('beginTransaction');

        $failingPdo
            ->expects($this->atMost(2))  // 允許最多 2 次呼叫，因為可能會呼叫 getSizeInfo
            ->method('prepare')
            ->willThrowException(new PDOException('Database error'));

        $failingPdo
            ->expects($this->once())
            ->method('rollBack');

        $result = $repository->optimize();

        // 檢查基本結果結構
        $this->assertEquals(0, $result['cleaned_entries']);
        $this->assertEquals(0, $result['compacted_size']);
        $this->assertArrayHasKey('error', $result);
        $this->assertIsString($result['error']);
        $this->assertNotEmpty($result['error']);
        $this->assertIsFloat($result['execution_time']);
    }

    /**
     * 建立範例黑名單項目.
     *
     * @param string $jti JTI，預設為 'test-jti'
     * @return TokenBlacklistEntry 範例項目
     */
    private function createSampleEntry(string $jti = 'test-jti'): TokenBlacklistEntry
    {
        return new TokenBlacklistEntry(
            jti: $jti,
            tokenType: TokenBlacklistEntry::TOKEN_TYPE_ACCESS,
            expiresAt: new DateTimeImmutable('+1 hour'),
            blacklistedAt: new DateTimeImmutable(),
            reason: TokenBlacklistEntry::REASON_LOGOUT,
            userId: 123,
            deviceId: 'device-123',
            metadata: ['test' => 'value'],
        );
    }
}
