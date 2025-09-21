<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Statistics\Models;

use App\Domains\Statistics\Models\StatisticsSnapshot;
use PHPUnit\Framework\TestCase;

/**
 * StatisticsSnapshot 模型測試.
 */
class StatisticsSnapshotTest extends TestCase
{
    /**
     * @return array<string, array{0: array<string, mixed>}>
     */
    public static function validDataProvider(): array
    {
        return [
            'basic_data' => [
                [
                    'id' => 1,
                    'uuid' => 'test-uuid-123',
                    'snapshot_type' => 'posts',
                    'period_type' => 'daily',
                    'period_start' => '2025-09-21 00:00:00',
                    'period_end' => '2025-09-21 23:59:59',
                    'statistics_data' => ['total_posts' => 100, 'active_users' => 25],
                    'total_views' => 500,
                    'total_unique_viewers' => 150,
                    'created_at' => '2025-09-21 10:00:00',
                    'updated_at' => '2025-09-21 11:00:00',
                ],
            ],
            'json_string_data' => [
                [
                    'id' => 2,
                    'uuid' => 'test-uuid-456',
                    'snapshot_type' => 'users',
                    'period_type' => 'weekly',
                    'period_start' => '2025-09-15 00:00:00',
                    'period_end' => '2025-09-21 23:59:59',
                    'statistics_data' => '{"total_users": 200, "new_users": 30}',
                    'total_views' => 1000,
                    'total_unique_viewers' => 300,
                    'created_at' => '2025-09-21 10:00:00',
                    'updated_at' => null,
                ],
            ],
        ];
    }

    /**
     * 測試模型建構函式.
     *
     * @dataProvider validDataProvider
     * @param array<string, mixed> $data
     */
    public function testConstruct(array $data): void
    {
        $snapshot = new StatisticsSnapshot($data);

        $this->assertEquals($data['id'], $snapshot->getId());
        $this->assertEquals($data['uuid'], $snapshot->getUuid());
        $this->assertEquals($data['snapshot_type'], $snapshot->getSnapshotType());
        $this->assertEquals($data['period_type'], $snapshot->getPeriodType());
        $this->assertEquals($data['period_start'], $snapshot->getPeriodStart());
        $this->assertEquals($data['period_end'], $snapshot->getPeriodEnd());
        $this->assertEquals($data['total_views'], $snapshot->getTotalViews());
        $this->assertEquals($data['total_unique_viewers'], $snapshot->getTotalUniqueViewers());
        $this->assertEquals($data['created_at'], $snapshot->getCreatedAt());
        $this->assertEquals($data['updated_at'], $snapshot->getUpdatedAt());

        // 檢驗統計資料
        if (is_string($data['statistics_data'])) {
            $expectedData = json_decode($data['statistics_data'], true);
            $this->assertEquals($expectedData, $snapshot->getStatisticsData());
        } else {
            $this->assertEquals($data['statistics_data'], $snapshot->getStatisticsData());
        }
    }

    public function testConstructWithDefaults(): void
    {
        $snapshot = new StatisticsSnapshot([]);

        $this->assertEquals(0, $snapshot->getId());
        $this->assertEquals('', $snapshot->getUuid());
        $this->assertEquals('', $snapshot->getSnapshotType());
        $this->assertEquals('', $snapshot->getPeriodType());
        $this->assertEquals('', $snapshot->getPeriodStart());
        $this->assertEquals('', $snapshot->getPeriodEnd());
        $this->assertEquals([], $snapshot->getStatisticsData());
        $this->assertEquals(0, $snapshot->getTotalViews());
        $this->assertEquals(0, $snapshot->getTotalUniqueViewers());
        $this->assertEquals('', $snapshot->getCreatedAt());
        $this->assertNull($snapshot->getUpdatedAt());
    }

    public function testConstructWithInvalidJsonString(): void
    {
        $data = [
            'statistics_data' => 'invalid json string',
        ];

        $snapshot = new StatisticsSnapshot($data);
        $this->assertEquals([], $snapshot->getStatisticsData());
    }

    public function testToArray(): void
    {
        $data = [
            'id' => 1,
            'uuid' => 'test-uuid-123',
            'snapshot_type' => 'posts',
            'period_type' => 'daily',
            'period_start' => '2025-09-21 00:00:00',
            'period_end' => '2025-09-21 23:59:59',
            'statistics_data' => ['total_posts' => 100],
            'total_views' => 500,
            'total_unique_viewers' => 150,
            'created_at' => '2025-09-21 10:00:00',
            'updated_at' => '2025-09-21 11:00:00',
        ];

        $snapshot = new StatisticsSnapshot($data);
        $result = $snapshot->toArray();

        $this->assertEquals($data, $result);
    }

    public function testJsonSerialize(): void
    {
        $data = [
            'id' => 1,
            'uuid' => 'test-uuid-123',
            'snapshot_type' => 'posts',
            'period_type' => 'daily',
            'period_start' => '2025-09-21 00:00:00',
            'period_end' => '2025-09-21 23:59:59',
            'statistics_data' => ['total_posts' => 100],
            'total_views' => 500,
            'total_unique_viewers' => 150,
            'created_at' => '2025-09-21 10:00:00',
            'updated_at' => '2025-09-21 11:00:00',
        ];

        $snapshot = new StatisticsSnapshot($data);
        $json = json_encode($snapshot);

        $this->assertIsString($json);

        $decoded = json_decode($json, true);
        $this->assertEquals($data, $decoded);
    }

    public function testFromArray(): void
    {
        $data = [
            'id' => 1,
            'uuid' => 'test-uuid-123',
            'snapshot_type' => 'posts',
            'period_type' => 'daily',
            'period_start' => '2025-09-21 00:00:00',
            'period_end' => '2025-09-21 23:59:59',
            'statistics_data' => ['total_posts' => 100],
            'total_views' => 500,
            'total_unique_viewers' => 150,
            'created_at' => '2025-09-21 10:00:00',
            'updated_at' => '2025-09-21 11:00:00',
        ];

        $snapshot = StatisticsSnapshot::fromArray($data);

        $this->assertInstanceOf(StatisticsSnapshot::class, $snapshot);
        $this->assertEquals($data['id'], $snapshot->getId());
        $this->assertEquals($data['uuid'], $snapshot->getUuid());
    }

    public function testConstructWithMixedTypes(): void
    {
        $data = [
            'id' => '123',  // string instead of int
            'total_views' => '456',  // string instead of int
            'total_unique_viewers' => '789',  // string instead of int
        ];

        $snapshot = new StatisticsSnapshot($data);

        $this->assertEquals(123, $snapshot->getId());
        $this->assertEquals(456, $snapshot->getTotalViews());
        $this->assertEquals(789, $snapshot->getTotalUniqueViewers());
    }
}
