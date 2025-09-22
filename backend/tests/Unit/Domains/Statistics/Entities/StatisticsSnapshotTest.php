<?php

declare(strict_types=1);

// phpcs:ignore -- PHPStan ignore for test array types
/** @phpstan-ignore-file */

namespace Tests\Unit\Domains\Statistics\Entities;

use App\Domains\Statistics\Entities\StatisticsSnapshot;
use App\Domains\Statistics\ValueObjects\StatisticsPeriod;
use DateTime;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Domains\Statistics\Entities\StatisticsSnapshot
 */
class StatisticsSnapshotTest extends TestCase
{
    private array $validData;

    private array $validStatisticsData;

    private array $validMetadata;

    protected function setUp(): void
    {
        $this->validStatisticsData = [
            'total_posts' => 1250,
            'by_status' => [
                'published' => 1100,
                'draft' => 120,
                'archived' => 30,
            ],
            'by_source' => [
                'web' => 800,
                'api' => 300,
                'import' => 100,
                'migration' => 50,
            ],
            'trends' => [
                'vs_previous_period' => '+12.5%',
                'growth_rate' => 0.125,
            ],
        ];

        $this->validMetadata = [
            'version' => '1.0',
            'calculation_params' => ['include_drafts' => false],
            'calculated_at' => '2025-09-21T10:30:00Z',
        ];

        $this->validData = [
            'id' => 1,
            'uuid' => 'test-uuid-12345',
            'snapshot_type' => StatisticsSnapshot::TYPE_OVERVIEW,
            'period_type' => 'daily',
            'period_start' => '2025-09-21 00:00:00',
            'period_end' => '2025-09-21 23:59:59',
            'statistics_data' => json_encode($this->validStatisticsData),
            'metadata' => json_encode($this->validMetadata),
            'expires_at' => '2025-09-22 00:00:00',
            'created_at' => '2025-09-21 10:30:00',
            'updated_at' => '2025-09-21 10:30:00',
        ];
    }

    public function testCanCreateStatisticsSnapshot(): void
    {
        /** @var array<string, mixed> $validData */
        $validData = $this->validData;
        $snapshot = new StatisticsSnapshot($validData);

        $this->assertSame(1, $snapshot->getId());
        $this->assertSame('test-uuid-12345', $snapshot->getUuid());
        $this->assertSame(StatisticsSnapshot::TYPE_OVERVIEW, $snapshot->getSnapshotType());
        $this->assertInstanceOf(StatisticsPeriod::class, $snapshot->getPeriod());
        $this->assertSame($this->validStatisticsData, $snapshot->getStatisticsData());
        $this->assertSame($this->validMetadata, $snapshot->getMetadata());
        $this->assertInstanceOf(DateTime::class, $snapshot->getExpiresAt());
        $this->assertInstanceOf(DateTime::class, $snapshot->getCreatedAt());
        $this->assertInstanceOf(DateTime::class, $snapshot->getUpdatedAt());
    }

    public function testCreateStaticMethod(): void
    {
        $period = StatisticsPeriod::fromArray([
            'type' => 'daily',
            'start_time' => '2025-09-21 00:00:00',
            'end_time' => '2025-09-21 23:59:59',
        ]);
        $expiresAt = new DateTime('2025-09-22 00:00:00');

        $snapshot = StatisticsSnapshot::create(
            StatisticsSnapshot::TYPE_POSTS,
            $period,
            $this->validStatisticsData,
            $this->validMetadata,
            $expiresAt,
        );

        $this->assertSame(StatisticsSnapshot::TYPE_POSTS, $snapshot->getSnapshotType());
        $this->assertSame($this->validStatisticsData, $snapshot->getStatisticsData());
        $this->assertSame($this->validMetadata, $snapshot->getMetadata());
        $this->assertEquals($expiresAt, $snapshot->getExpiresAt());
    }

    public function testFromArrayStaticMethod(): void
    {
        $snapshot = StatisticsSnapshot::fromArray($this->validData);

        $this->assertInstanceOf(StatisticsSnapshot::class, $snapshot);
        $this->assertSame(1, $snapshot->getId());
        $this->assertSame('test-uuid-12345', $snapshot->getUuid());
    }

    /**
     * @dataProvider invalidSnapshotTypeProvider
     */
    public function testConstructorThrowsExceptionWithInvalidSnapshotType(string $invalidType): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('無效的快照類型');

        $data = $this->validData;
        $data['snapshot_type'] = $invalidType;

        new StatisticsSnapshot($data);
    }

    /**
     * @return array<string, array<string>>
     */
    public static function invalidSnapshotTypeProvider(): array
    {
        return [
            'empty type' => [''],
            'invalid type' => ['invalid_type'],
            'numeric type' => ['123'],
            'special chars' => ['@#$%'],
        ];
    }

    public function testConstructorThrowsExceptionWithMissingPeriodData(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('統計週期資料不完整');

        $data = $this->validData;
        unset($data['period_start']);

        new StatisticsSnapshot($data);
    }

    public function testConstructorThrowsExceptionWithInvalidJson(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('無效的 JSON 資料');

        $data = $this->validData;
        $data['statistics_data'] = '{invalid json}';

        new StatisticsSnapshot($data);
    }

    public function testConstructorHandlesEmptyStatisticsData(): void
    {
        $data = $this->validData;
        $data['statistics_data'] = '{}';
        $data['metadata'] = '';

        $snapshot = new StatisticsSnapshot($data);

        $this->assertSame([], $snapshot->getStatisticsData());
        $this->assertSame([], $snapshot->getMetadata());
    }

    public function testIsExpiredWithExpiredSnapshot(): void
    {
        $data = $this->validData;
        $data['expires_at'] = '2020-01-01 00:00:00'; // 過去的時間

        $snapshot = new StatisticsSnapshot($data);

        $this->assertTrue($snapshot->isExpired());
    }

    public function testIsExpiredWithNonExpiredSnapshot(): void
    {
        $data = $this->validData;
        $data['expires_at'] = new DateTime('+1 day')->format('Y-m-d H:i:s');

        $snapshot = new StatisticsSnapshot($data);

        $this->assertFalse($snapshot->isExpired());
    }

    public function testIsExpiredWithNullExpiresAt(): void
    {
        $data = $this->validData;
        $data['expires_at'] = null;

        $snapshot = new StatisticsSnapshot($data);

        $this->assertFalse($snapshot->isExpired());
    }

    public function testIsTypeMethod(): void
    {
        $snapshot = new StatisticsSnapshot($this->validData);

        $this->assertTrue($snapshot->isType(StatisticsSnapshot::TYPE_OVERVIEW));
        $this->assertFalse($snapshot->isType(StatisticsSnapshot::TYPE_POSTS));
    }

    public function testGetStatisticMethod(): void
    {
        $snapshot = new StatisticsSnapshot($this->validData);

        $this->assertSame(1250, $snapshot->getStatistic('total_posts'));
        $this->assertSame('default', $snapshot->getStatistic('non_existent_key', 'default'));
        $this->assertNull($snapshot->getStatistic('non_existent_key'));
    }

    public function testHasStatisticMethod(): void
    {
        $snapshot = new StatisticsSnapshot($this->validData);

        $this->assertTrue($snapshot->hasStatistic('total_posts'));
        $this->assertFalse($snapshot->hasStatistic('non_existent_key'));
    }

    public function testGetTotalCount(): void
    {
        $data = $this->validData;
        $statisticsData = json_decode($data['statistics_data'], true);
        $statisticsData['total_count'] = 500;
        $data['statistics_data'] = json_encode($statisticsData);

        $snapshot = new StatisticsSnapshot($data);

        $this->assertSame(500, $snapshot->getTotalCount());
    }

    public function testGetTotalCountWithoutData(): void
    {
        $snapshot = new StatisticsSnapshot($this->validData);

        $this->assertSame(0, $snapshot->getTotalCount());
    }

    public function testGetGrowthRate(): void
    {
        $snapshot = new StatisticsSnapshot($this->validData);

        $this->assertSame(0.125, $snapshot->getGrowthRate());
    }

    public function testGetGrowthRateWithoutData(): void
    {
        $data = $this->validData;
        $statisticsData = ['total_posts' => 100]; // 沒有 trends 資料
        $data['statistics_data'] = json_encode($statisticsData);

        $snapshot = new StatisticsSnapshot($data);

        $this->assertNull($snapshot->getGrowthRate());
    }

    public function testUpdateStatistics(): void
    {
        $snapshot = new StatisticsSnapshot($this->validData);
        $originalUpdateTime = $snapshot->getUpdatedAt();

        sleep(1); // 確保時間有差異

        $newData = ['new_metric' => 'test_value'];
        $snapshot->updateStatistics($newData);

        $this->assertTrue($snapshot->hasStatistic('new_metric'));
        $this->assertSame('test_value', $snapshot->getStatistic('new_metric'));
        $this->assertGreaterThan($originalUpdateTime, $snapshot->getUpdatedAt());
    }

    public function testUpdateMetadata(): void
    {
        $snapshot = new StatisticsSnapshot($this->validData);
        $originalUpdateTime = $snapshot->getUpdatedAt();

        sleep(1); // 確保時間有差異

        $newMetadata = ['new_meta' => 'test_meta'];
        $snapshot->updateMetadata($newMetadata);

        $updatedMetadata = $snapshot->getMetadata();
        $this->assertArrayHasKey('new_meta', $updatedMetadata);
        $this->assertSame('test_meta', $updatedMetadata['new_meta']);
        $this->assertGreaterThan($originalUpdateTime, $snapshot->getUpdatedAt());
    }

    public function testSetExpiresAt(): void
    {
        $snapshot = new StatisticsSnapshot($this->validData);
        $newExpiresAt = new DateTime('+2 days');

        $snapshot->setExpiresAt($newExpiresAt);

        $this->assertEquals($newExpiresAt, $snapshot->getExpiresAt());
    }

    public function testSetExpiresAtWithPastDate(): void
    {
        $snapshot = new StatisticsSnapshot($this->validData);
        // 使用早於測試資料中 created_at (2025-09-21 10:30:00) 的時間
        $pastDate = new DateTime('2025-09-21 10:00:00'); // 比 created_at 早 30 分鐘

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('過期時間必須晚於建立時間');

        $snapshot->setExpiresAt($pastDate);
    }

    public function testSetExpiresAtWithNull(): void
    {
        $snapshot = new StatisticsSnapshot($this->validData);

        $snapshot->setExpiresAt(null);

        $this->assertNull($snapshot->getExpiresAt());
    }

    /**
     * @dataProvider dataIntegrityProvider
     */
    public function testValidateDataIntegrity(string $snapshotType, array $statisticsData, bool $expected): void
    {
        $data = $this->validData;
        $data['snapshot_type'] = $snapshotType;
        $data['statistics_data'] = json_encode($statisticsData);

        $snapshot = new StatisticsSnapshot($data);

        $this->assertSame($expected, $snapshot->validateDataIntegrity());
    }

    /**
     * @return array<string, array{string, array<string, mixed>, bool}>
     */
    public static function dataIntegrityProvider(): array
    {
        return [
            'valid overview' => [
                StatisticsSnapshot::TYPE_OVERVIEW,
                ['total_posts' => 100],
                true,
            ],
            'invalid overview' => [
                StatisticsSnapshot::TYPE_OVERVIEW,
                ['wrong_key' => 100],
                false,
            ],
            'valid posts' => [
                StatisticsSnapshot::TYPE_POSTS,
                ['by_status' => ['published' => 100]],
                true,
            ],
            'invalid posts' => [
                StatisticsSnapshot::TYPE_POSTS,
                ['wrong_key' => 100],
                false,
            ],
            'valid sources' => [
                StatisticsSnapshot::TYPE_SOURCES,
                ['by_source' => ['web' => 100]],
                true,
            ],
            'valid users' => [
                StatisticsSnapshot::TYPE_USERS,
                ['active_users' => 50],
                true,
            ],
            'valid popular' => [
                StatisticsSnapshot::TYPE_POPULAR,
                ['top_posts' => [1, 2, 3]],
                true,
            ],
            'empty data' => [
                StatisticsSnapshot::TYPE_OVERVIEW,
                [],
                false,
            ],
        ];
    }

    public function testToArray(): void
    {
        $snapshot = new StatisticsSnapshot($this->validData);
        $array = $snapshot->toArray();

        $this->assertIsArray($array);
        $this->assertArrayHasKey('id', $array);
        $this->assertArrayHasKey('uuid', $array);
        $this->assertArrayHasKey('snapshot_type', $array);
        $this->assertArrayHasKey('period_type', $array);
        $this->assertArrayHasKey('period_start', $array);
        $this->assertArrayHasKey('period_end', $array);
        $this->assertArrayHasKey('statistics_data', $array);
        $this->assertArrayHasKey('metadata', $array);
        $this->assertArrayHasKey('expires_at', $array);
        $this->assertArrayHasKey('created_at', $array);
        $this->assertArrayHasKey('updated_at', $array);

        $this->assertSame(1, $array['id']);
        $this->assertSame('test-uuid-12345', $array['uuid']);
        $this->assertSame(StatisticsSnapshot::TYPE_OVERVIEW, $array['snapshot_type']);
    }

    public function testJsonSerialize(): void
    {
        $snapshot = new StatisticsSnapshot($this->validData);
        $json = json_encode($snapshot);

        $this->assertIsString($json);

        /** @var array<string, mixed> $decoded */
        $decoded = json_decode($json, true);
        $this->assertIsArray($decoded);
        $this->assertArrayHasKey('id', $decoded);
        $this->assertSame(1, $decoded['id']);
    }

    public function testSupportedTypesConstants(): void
    {
        $this->assertSame('overview', StatisticsSnapshot::TYPE_OVERVIEW);
        $this->assertSame('posts', StatisticsSnapshot::TYPE_POSTS);
        $this->assertSame('sources', StatisticsSnapshot::TYPE_SOURCES);
        $this->assertSame('users', StatisticsSnapshot::TYPE_USERS);
        $this->assertSame('popular', StatisticsSnapshot::TYPE_POPULAR);
    }

    public function testConstructorWithMinimalData(): void
    {
        $minimalData = [
            'snapshot_type' => StatisticsSnapshot::TYPE_OVERVIEW,
            'period_type' => 'daily',
            'period_start' => '2025-09-21 00:00:00',
            'period_end' => '2025-09-21 23:59:59',
        ];

        $snapshot = new StatisticsSnapshot($minimalData);

        $this->assertSame(0, $snapshot->getId());
        $this->assertNotEmpty($snapshot->getUuid());
        $this->assertSame(StatisticsSnapshot::TYPE_OVERVIEW, $snapshot->getSnapshotType());
        $this->assertSame([], $snapshot->getStatisticsData());
        $this->assertSame([], $snapshot->getMetadata());
        $this->assertNull($snapshot->getExpiresAt());
        $this->assertInstanceOf(DateTime::class, $snapshot->getCreatedAt());
        $this->assertInstanceOf(DateTime::class, $snapshot->getUpdatedAt());
    }
}
