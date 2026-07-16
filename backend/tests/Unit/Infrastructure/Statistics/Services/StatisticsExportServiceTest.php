<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Statistics\Services;

use App\Application\Services\Statistics\DTOs\PaginatedStatisticsDTO;
use App\Application\Services\Statistics\StatisticsApplicationService;
use App\Domains\Statistics\Contracts\BatchExportResult;
use App\Domains\Statistics\Contracts\ExportResult;
use App\Domains\Statistics\Contracts\StatisticsFormatterInterface;
use App\Domains\Statistics\DTOs\StatisticsOverviewDTO;
use App\Infrastructure\Statistics\Services\StatisticsExportService;
use Mockery;
use DateTime;
use InvalidArgumentException;
use RuntimeException;
use Tests\Support\UnitTestCase;

/**
 * 統計匯出服務單元測試.
 *
 * 測試統計資料匯出功能的各種情境。
 */
final class StatisticsExportServiceTest extends UnitTestCase
{
    private StatisticsExportService $exportService;

    /** @var array<string, StatisticsFormatterInterface> */
    private array $formatters;

    private StatisticsApplicationService $queryService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->queryService = $this->createTestQueryService();
        $this->formatters = $this->createTestFormatters();
        $this->exportService = new StatisticsExportService(
            $this->queryService,
            $this->formatters,
        );
    }

    public function test應該能匯出統計概覽資料(): void
    {
        // Act
        $result = $this->exportService->exportOverview([
            'format'       => 'json',
            'period_start' => new DateTime('2025-09-01'),
            'period_end'   => new DateTime('2025-09-30'),
        ]);

        // Assert
        $this->assertInstanceOf(ExportResult::class, $result);
        $this->assertEquals('json', $result->format);
        $this->assertStringContainsString('overview', $result->filename);
        $this->assertGreaterThan(0, $result->recordCount);
        $this->assertGreaterThan(0, $result->fileSize);
        $this->assertGreaterThan(0, $result->executionTime);
    }

    public function test應該能匯出文章統計資料(): void
    {
        // Act
        $result = $this->exportService->exportPostStatistics([
            'format' => 'csv',
            'limit'  => 100,
            'offset' => 0,
        ]);

        // Assert
        $this->assertInstanceOf(ExportResult::class, $result);
        $this->assertEquals('csv', $result->format);
        $this->assertStringContainsString('posts', $result->filename);
        $this->assertStringContainsString('.csv', $result->filename);
    }

    public function test應該能匯出來源分布統計(): void
    {
        // Act
        $result = $this->exportService->exportSourceDistribution([
            'format'          => 'json',
            'group_by_detail' => true,
        ]);

        // Assert
        $this->assertInstanceOf(ExportResult::class, $result);
        $this->assertEquals('json', $result->format);
        $this->assertStringContainsString('sources', $result->filename);
    }

    public function test應該能匯出使用者統計資料(): void
    {
        // Act
        $result = $this->exportService->exportUserStatistics([
            'format'           => 'csv',
            'include_inactive' => false,
        ]);

        // Assert
        $this->assertInstanceOf(ExportResult::class, $result);
        $this->assertEquals('csv', $result->format);
        $this->assertStringContainsString('users', $result->filename);
    }

    public function test應該能匯出熱門內容統計(): void
    {
        // Act
        $result = $this->exportService->exportPopularContent([
            'format' => 'json',
            'limit'  => 50,
        ]);

        // Assert
        $this->assertInstanceOf(ExportResult::class, $result);
        $this->assertEquals('json', $result->format);
        $this->assertStringContainsString('popular', $result->filename);
    }

    public function test應該能進行批次匯出(): void
    {
        // Arrange
        $types = ['overview', 'posts', 'sources'];

        // Act
        $result = $this->exportService->exportBatch($types, [
            'format'       => 'json',
            'period_start' => new DateTime('2025-09-01'),
            'period_end'   => new DateTime('2025-09-30'),
        ]);

        // Assert
        $this->assertInstanceOf(BatchExportResult::class, $result);
        $this->assertEquals(3, $result->successCount);
        $this->assertEquals(0, $result->failureCount);
        $this->assertTrue($result->isAllSuccessful());
        $this->assertCount(3, $result->results);
        $this->assertArrayHasKey('overview', $result->results);
        $this->assertArrayHasKey('posts', $result->results);
        $this->assertArrayHasKey('sources', $result->results);
    }

    public function test批次匯出部分失敗時應該正確處理(): void
    {
        // Arrange - 建立會失敗的查詢服務
        $failingQueryService = Mockery::mock(StatisticsApplicationService::class);
        $failingQueryService->shouldReceive('getOverview')
            ->andThrow(new RuntimeException('Query failed'));
        $failingQueryService->shouldReceive('getPostStatistics')
            ->andReturn(new PaginatedStatisticsDTO(
                data: [['id' => 1, 'title' => 'Test']],
                totalCount: 1,
                currentPage: 1,
                perPage: 20,
            ));
        $failingQueryService->shouldReceive('getSourceDistribution')
            ->andReturn(['sources' => [['source' => 'web', 'count' => 10]]]);
        $failingQueryService->shouldReceive('getUserStatistics')
            ->andReturn(new PaginatedStatisticsDTO(
                data: [],
                totalCount: 0,
                currentPage: 1,
                perPage: 20,
            ));
        $failingQueryService->shouldReceive('getPopularContent')
            ->andReturn([]);

        $exportService = new StatisticsExportService($failingQueryService, $this->formatters);
        $types = ['overview', 'posts', 'sources'];

        // Act
        $result = $exportService->exportBatch($types, ['format' => 'json']);

        // Assert
        $this->assertInstanceOf(BatchExportResult::class, $result);
        $this->assertEquals(2, $result->successCount);
        $this->assertEquals(1, $result->failureCount);
        $this->assertFalse($result->isAllSuccessful());
        $this->assertTrue($result->hasFailures());
        $this->assertCount(2, $result->results);
        $this->assertCount(1, $result->errors);
        $this->assertArrayHasKey('overview', $result->errors);
    }

    public function test應該能取得支援的格式列表(): void
    {
        // Act
        $formats = $this->exportService->getSupportedFormats();

        // Assert
        $this->assertIsArray($formats);
        $this->assertContains('json', $formats);
        $this->assertContains('csv', $formats);
        $this->assertContains('pdf', $formats);
    }

    public function test應該能取得支援的統計類型列表(): void
    {
        // Act
        $types = $this->exportService->getSupportedTypes();

        // Assert
        $this->assertIsArray($types);
        $this->assertContains('overview', $types);
        $this->assertContains('posts', $types);
        $this->assertContains('sources', $types);
        $this->assertContains('users', $types);
        $this->assertContains('popular', $types);
    }

    public function test使用不支援格式應該拋出例外(): void
    {
        // Assert
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('不支援的匯出格式: xml');

        // Act
        $this->exportService->exportOverview(['format' => 'xml']);
    }

    public function test匯出結果應該包含正確的元資料(): void
    {
        // Act
        $result = $this->exportService->exportOverview([
            'format'          => 'json',
            'include_details' => true,
        ]);

        // Assert
        $this->assertArrayHasKey('export_time', $result->metadata);
        $this->assertArrayHasKey('format_options', $result->metadata);
        $this->assertTrue($result->metadata['include_details']);
    }

    /**
     * 建立測試用的查詢服務.
     */
    private function createTestQueryService(): StatisticsApplicationService
    {
        $mock = Mockery::mock(StatisticsApplicationService::class);

        $mock->shouldReceive('getOverview')
            ->andReturn(new StatisticsOverviewDTO(
                totalPosts: 100,
                activeUsers: 50,
                newUsers: 10,
                postActivity: ['total_posts' => 100, 'published_posts' => 80, 'draft_posts' => 20],
                userActivity: ['total_users' => 50, 'active_users' => 30, 'new_users' => 10],
                engagementMetrics: ['posts_per_active_user' => 2.0, 'user_growth_rate' => 25.0],
                periodSummary: ['type' => 'monthly', 'duration_days' => 30],
            ));

        $mock->shouldReceive('getPostStatistics')
            ->andReturn(new PaginatedStatisticsDTO(
                data: [
                    ['id' => 1, 'title' => 'Test Post', 'views' => 100],
                ],
                totalCount: 100,
                currentPage: 1,
                perPage: 20,
            ));

        $mock->shouldReceive('getSourceDistribution')
            ->andReturn([
                'sources' => [
                    ['source' => 'web', 'count' => 60, 'percentage' => 60.0],
                    ['source' => 'mobile', 'count' => 30, 'percentage' => 30.0],
                    ['source' => 'api', 'count' => 10, 'percentage' => 10.0],
                ],
            ]);

        $mock->shouldReceive('getUserStatistics')
            ->andReturn(new PaginatedStatisticsDTO(
                data: [
                    ['id' => 1, 'username' => 'user1', 'post_count' => 10],
                ],
                totalCount: 200,
                currentPage: 1,
                perPage: 20,
            ));

        $mock->shouldReceive('getPopularContent')
            ->andReturn([
                ['id' => 1, 'title' => 'Popular Post', 'views' => 1000],
            ]);

        return $mock;
    }

    /**
     * 建立測試用的格式化器.
     *
     * @return array<string, StatisticsFormatterInterface>
     */
    private function createTestFormatters(): array
    {
        $jsonFormatter = new class implements StatisticsFormatterInterface {
            public function getFormat(): string
            {
                return 'json';
            }

            public function getFileExtension(): string
            {
                return 'json';
            }

            public function getMimeType(): string
            {
                return 'application/json';
            }

            public function format(array $data, array $options = []): string
            {
                return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            }

            public function supportsLargeData(): bool
            {
                return true;
            }

            public function getRecommendedFilename(string $type, array $options = []): string
            {
                $timestamp = date('Y-m-d_H-i-s');

                return "statistics_{$type}_{$timestamp}.json";
            }
        };

        $csvFormatter = new class implements StatisticsFormatterInterface {
            public function getFormat(): string
            {
                return 'csv';
            }

            public function getFileExtension(): string
            {
                return 'csv';
            }

            public function getMimeType(): string
            {
                return 'text/csv';
            }

            public function format(array $data, array $options = []): string
            {
                // 簡化的 CSV 格式化實作
                if (empty($data)) {
                    return '';
                }

                $output = '';
                $firstKey = array_key_first($data);
                if (is_array($data[$firstKey]) && !empty($data[$firstKey])) {
                    $headers = array_map(
                        static fn(mixed $header): string => (string) $header,
                        array_keys($data[$firstKey][0]),
                    );
                    $output .= implode(',', $headers) . "\n";

                    foreach ($data[$firstKey] as $row) {
                        if (!is_array($row)) {
                            continue;
                        }
                        $output .= implode(',', array_map(
                            static fn(mixed $value): string => is_scalar($value) || $value === null
                                ? (string) $value
                                : (json_encode($value, JSON_UNESCAPED_UNICODE) ?: get_debug_type($value)),
                            array_values($row),
                        )) . "\n";
                    }
                }

                return $output;
            }

            public function supportsLargeData(): bool
            {
                return true;
            }

            public function getRecommendedFilename(string $type, array $options = []): string
            {
                $timestamp = date('Y-m-d_H-i-s');

                return "statistics_{$type}_{$timestamp}.csv";
            }
        };

        $pdfFormatter = new class implements StatisticsFormatterInterface {
            public function getFormat(): string
            {
                return 'pdf';
            }

            public function getFileExtension(): string
            {
                return 'pdf';
            }

            public function getMimeType(): string
            {
                return 'application/pdf';
            }

            public function format(array $data, array $options = []): string
            {
                // 模擬 PDF 內容
                return '%PDF-1.4 (mock PDF content for testing)';
            }

            public function supportsLargeData(): bool
            {
                return false;
            }

            public function getRecommendedFilename(string $type, array $options = []): string
            {
                $timestamp = date('Y-m-d_H-i-s');

                return "statistics_{$type}_{$timestamp}.pdf";
            }
        };

        return [
            'json' => $jsonFormatter,
            'csv'  => $csvFormatter,
            'pdf'  => $pdfFormatter,
        ];
    }
}
