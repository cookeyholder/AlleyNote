<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Statistics\Adapters;

use App\Domains\Statistics\Contracts\StatisticsRepositoryInterface;
use App\Infrastructure\Statistics\Adapters\StatisticsDatabaseAdapterFactory;
use App\Infrastructure\Statistics\Adapters\StatisticsRepositoryCacheAdapter;
use App\Infrastructure\Statistics\Adapters\StatisticsRepositoryLoggingAdapter;
use App\Infrastructure\Statistics\Adapters\StatisticsRepositoryTransactionAdapter;
use App\Shared\Contracts\CacheServiceInterface;
use InvalidArgumentException;
use PDO;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

#[CoversClass(StatisticsDatabaseAdapterFactory::class)]
final class StatisticsDatabaseAdapterFactoryTest extends TestCase
{
    private StatisticsRepositoryInterface&MockObject $mockRepository;

    private CacheServiceInterface&MockObject $mockCache;

    private LoggerInterface&MockObject $mockLogger;

    private PDO&MockObject $mockDb;

    protected function setUp(): void
    {
        $this->mockRepository = $this->createMock(StatisticsRepositoryInterface::class);
        $this->mockCache = $this->createMock(CacheServiceInterface::class);
        $this->mockLogger = $this->createMock(LoggerInterface::class);
        $this->mockDb = $this->createMock(PDO::class);
    }

    public function testCreateBaseReturnsBaseRepository(): void
    {
        $factory = new StatisticsDatabaseAdapterFactory($this->mockRepository);

        $adapter = $factory->createBase();

        $this->assertSame($this->mockRepository, $adapter);
    }

    public function testCreateWithCacheReturnsCacheAdapter(): void
    {
        $factory = new StatisticsDatabaseAdapterFactory(
            $this->mockRepository,
            $this->mockCache,
        );

        $adapter = $factory->createWithCache();

        $this->assertInstanceOf(StatisticsRepositoryCacheAdapter::class, $adapter);
    }

    public function testCreateWithCacheThrowsExceptionWhenCacheNotProvided(): void
    {
        $factory = new StatisticsDatabaseAdapterFactory($this->mockRepository);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cache service is required for cache adapter');

        $factory->createWithCache();
    }

    public function testCreateWithLoggingReturnsLoggingAdapter(): void
    {
        $factory = new StatisticsDatabaseAdapterFactory(
            $this->mockRepository,
            null,
            $this->mockLogger,
        );

        $adapter = $factory->createWithLogging();

        $this->assertInstanceOf(StatisticsRepositoryLoggingAdapter::class, $adapter);
    }

    public function testCreateWithLoggingThrowsExceptionWhenLoggerNotProvided(): void
    {
        $factory = new StatisticsDatabaseAdapterFactory($this->mockRepository);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Logger is required for logging adapter');

        $factory->createWithLogging();
    }

    public function testCreateWithTransactionReturnsTransactionAdapter(): void
    {
        $factory = new StatisticsDatabaseAdapterFactory(
            $this->mockRepository,
            null,
            null,
            $this->mockDb,
        );

        $adapter = $factory->createWithTransaction();

        $this->assertInstanceOf(StatisticsRepositoryTransactionAdapter::class, $adapter);
    }

    public function testCreateWithTransactionThrowsExceptionWhenDbNotProvided(): void
    {
        $factory = new StatisticsDatabaseAdapterFactory($this->mockRepository);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('PDO connection is required for transaction adapter');

        $factory->createWithTransaction();
    }

    public function testCreateCachedWithLoggingReturnsCombinedAdapter(): void
    {
        $factory = new StatisticsDatabaseAdapterFactory(
            $this->mockRepository,
            $this->mockCache,
            $this->mockLogger,
        );

        $adapter = $factory->createCachedWithLogging();

        $this->assertInstanceOf(StatisticsRepositoryLoggingAdapter::class, $adapter);
    }

    public function testCreateCachedWithLoggingThrowsExceptionWhenCacheNotProvided(): void
    {
        $factory = new StatisticsDatabaseAdapterFactory(
            $this->mockRepository,
            null,
            $this->mockLogger,
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cache service is required for cache adapter');

        $factory->createCachedWithLogging();
    }

    public function testCreateTransactionalWithLoggingReturnsCombinedAdapter(): void
    {
        $factory = new StatisticsDatabaseAdapterFactory(
            $this->mockRepository,
            null,
            $this->mockLogger,
            $this->mockDb,
        );

        $adapter = $factory->createTransactionalWithLogging();

        $this->assertInstanceOf(StatisticsRepositoryLoggingAdapter::class, $adapter);
    }

    public function testCreateFullReturnsFullyFeaturedAdapter(): void
    {
        $factory = new StatisticsDatabaseAdapterFactory(
            $this->mockRepository,
            $this->mockCache,
            $this->mockLogger,
            $this->mockDb,
        );

        $adapter = $factory->createFull();

        $this->assertInstanceOf(StatisticsRepositoryLoggingAdapter::class, $adapter);
    }

    public function testCreateFullThrowsExceptionWhenDependenciesMissing(): void
    {
        $factory = new StatisticsDatabaseAdapterFactory($this->mockRepository);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cache service is required for full adapter');

        $factory->createFull();
    }

    public function testCreateByConfigWithNoFeaturesReturnsBase(): void
    {
        $factory = new StatisticsDatabaseAdapterFactory($this->mockRepository);

        $adapter = $factory->createByConfig([]);

        $this->assertSame($this->mockRepository, $adapter);
    }

    public function testCreateByConfigWithCacheEnabled(): void
    {
        $factory = new StatisticsDatabaseAdapterFactory(
            $this->mockRepository,
            $this->mockCache,
        );

        $adapter = $factory->createByConfig(['cache' => true]);

        $this->assertInstanceOf(StatisticsRepositoryCacheAdapter::class, $adapter);
    }

    public function testCreateByConfigWithMultipleFeaturesEnabled(): void
    {
        $factory = new StatisticsDatabaseAdapterFactory(
            $this->mockRepository,
            $this->mockCache,
            $this->mockLogger,
            $this->mockDb,
        );

        $adapter = $factory->createByConfig([
            'cache' => true,
            'logging' => true,
            'transaction' => true,
        ]);

        $this->assertInstanceOf(StatisticsRepositoryLoggingAdapter::class, $adapter);
    }

    public function testCanCreateReturnsTrueForAvailableTypes(): void
    {
        $factory = new StatisticsDatabaseAdapterFactory(
            $this->mockRepository,
            $this->mockCache,
            $this->mockLogger,
            $this->mockDb,
        );

        $this->assertTrue($factory->canCreate('base'));
        $this->assertTrue($factory->canCreate('cache'));
        $this->assertTrue($factory->canCreate('logging'));
        $this->assertTrue($factory->canCreate('transaction'));
        $this->assertTrue($factory->canCreate('full'));
    }

    public function testCanCreateReturnsFalseForUnavailableTypes(): void
    {
        $factory = new StatisticsDatabaseAdapterFactory($this->mockRepository);

        $this->assertTrue($factory->canCreate('base'));
        $this->assertFalse($factory->canCreate('cache'));
        $this->assertFalse($factory->canCreate('logging'));
        $this->assertFalse($factory->canCreate('transaction'));
        $this->assertFalse($factory->canCreate('invalid_type'));
    }

    public function testGetAvailableTypesReturnsCorrectTypes(): void
    {
        $factory = new StatisticsDatabaseAdapterFactory(
            $this->mockRepository,
            $this->mockCache,
            $this->mockLogger,
        );

        $types = $factory->getAvailableTypes();

        $this->assertContains('base', $types);
        $this->assertContains('cache', $types);
        $this->assertContains('logging', $types);
        $this->assertContains('cached_logging', $types);
        $this->assertNotContains('transaction', $types);
        $this->assertNotContains('full', $types);
    }

    public function testGetAvailableTypesWithMinimalDependencies(): void
    {
        $factory = new StatisticsDatabaseAdapterFactory($this->mockRepository);

        $types = $factory->getAvailableTypes();

        $this->assertEquals(['base'], $types);
    }
}
