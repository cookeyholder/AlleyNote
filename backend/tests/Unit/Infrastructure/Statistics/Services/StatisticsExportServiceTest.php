<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Statistics\Services;

use App\Domains\Statistics\Contracts\BatchExportResult;
use App\Domains\Statistics\Contracts\ExportResult;
use App\Domains\Statistics\Contracts\StatisticsFormatterInterface;
use App\Domains\Statistics\Contracts\StatisticsQueryServiceInterface;
use App\Infrastructure\Statistics\Services\StatisticsExportService;
use DateTime;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * 統計匯出服務單元測試.
 *
 * 測試統計資料匯出功能的各種情境。
 */
final class StatisticsExportServiceTest extends TestCase
{
    private StatisticsExportService $exportService;

    /** @var array<string, StatisticsFormatterInterface> */
    private array $formatters;

    private StatisticsQueryServiceInterface $queryService;

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
            'format' => 'json',
            'period_start' => new DateTime('2025-09-01'),
            'period_end' => new DateTime('2025-09-30'),
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
            'limit' => 100,
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
            'format' => 'json',
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
            'format' => 'csv',
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
            'limit' => 50,
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
            'format' => 'json',
            'period_start' => new DateTime('2025-09-01'),
            'period_end' => new DateTime('2025-09-30'),
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
        $failingQueryService = new class implements StatisticsQueryServiceInterface {
            public function getOverview(array $options = []): array
            {
                throw new RuntimeException('Query failed');
            }

            public function getPostStatistics(array $options = []): array
            {
                return ['posts' => [['id' => 1, 'title' => 'Test']]];
            }

            public function getSourceDistribution(array $options = []): array
            {
                return ['sources' => [['source' => 'web', 'count' => 10]]];
            }

            public function getUserStatistics(array $options = []): array
            {
                return [];
            }

            public function getPopularContent(array $options = []): array
            {
                return [];
            }
        };

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
            'format' => 'json',
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
    private function createTestQueryService(): StatisticsQueryServiceInterface
    {
        return new class implements StatisticsQueryServiceInterface {
            public function getOverview(array $options = []): array
            {
                return [
                    'total_posts' => 100,
                    'total_views' => 5000,
                    'total_users' => 50,
                    'period_start' => '2025-09-01',
                    'period_end' => '2025-09-30',
                ];
            }

            public function getPostStatistics(array $options = []): array
            {
                $posts = [];
                $limit = $options['limit'] ?? 10;

                for ($i = 1; $i <= $limit; $i++) {
                    $posts[] = [
                        'id' => $i,
                        'title' => "測試文章 {$i}",
                        'views' => rand(10, 1000),
                        'created_at' => '2025-09-' . str_pad((string) rand(1, 30), 2, '0', STR_PAD_LEFT),
                    ];
                }

                return ['posts' => $posts];
            }

            public function getSourceDistribution(array $options = []): array
            {
                return [
                    'sources' => [
                        ['source' => 'web', 'count' => 60, 'percentage' => 60.0],
                        ['source' => 'mobile', 'count' => 30, 'percentage' => 30.0],
                        ['source' => 'api', 'count' => 10, 'percentage' => 10.0],
                    ],
                ];
            }

            public function getUserStatistics(array $options = []): array
            {
                return [
                    'users' => [
                        ['id' => 1, 'username' => 'user1', 'post_count' => 10, 'last_active' => '2025-09-23'],
                        ['id' => 2, 'username' => 'user2', 'post_count' => 15, 'last_active' => '2025-09-22'],
                    ],
                ];
            }

            public function getPopularContent(array $options = []): array
            {
                $limit = $options['limit'] ?? 10;
                $popular = [];

                for ($i = 1; $i <= $limit; $i++) {
                    $popular[] = [
                        'id' => $i,
                        'title' => "熱門文章 {$i}",
                        'views' => 1000 - $i * 50,
                        'rank' => $i,
                    ];
                }

                return ['popular_posts' => $popular];
            }
        };
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
                    $headers = array_keys($data[$firstKey][0]);
                    $output .= implode(',', $headers) . "\n";

                    foreach ($data[$firstKey] as $row) {
                        $output .= implode(',', array_values($row)) . "\n";
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
            'csv' => $csvFormatter,
            'pdf' => $pdfFormatter,
        ];
    }
}
