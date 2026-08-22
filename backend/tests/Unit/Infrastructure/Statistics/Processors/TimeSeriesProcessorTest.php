<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Statistics\Processors;

use App\Infrastructure\Statistics\Processors\TimeSeriesProcessor;
use DateTimeImmutable;
use InvalidArgumentException;
use Tests\Support\UnitTestCase;

/**
 * 時間序列處理器測試.
 */
final class TimeSeriesProcessorTest extends UnitTestCase
{
    private TimeSeriesProcessor $processor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->processor = new TimeSeriesProcessor();
    }

    public function testProcessTimeSeriesDataSuccess(): void
    {
        $rawData = [
            ['timestamp' => '2025-01-01', 'value' => 100],
            ['date' => '2025-01-02', 'value' => 200],
        ];

        $chart = $this->processor->processTimeSeriesData($rawData, 'Page Views', 'day');

        $this->assertSame(['2025-01-01', '2025-01-02'], $chart->labels);
        $this->assertCount(1, $chart->datasets);
        $this->assertSame('Page Views', $chart->datasets[0]->label);
        $this->assertSame([100.0, 200.0], $chart->datasets[0]->data);
    }

    public function testProcessTimeSeriesDataWithUnsupportedGranularityThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('不支援的時間粒度: invalid');

        $this->processor->processTimeSeriesData([], 'Metric', 'invalid');
    }

    public function testProcessMultiSeriesDataSuccess(): void
    {
        $allData = [
            'Series 1' => [
                ['timestamp' => '2025-01-01', 'value' => 10],
                ['timestamp' => '2025-01-02', 'value' => 20],
            ],
            'Series 2' => [
                ['timestamp' => '2025-01-01', 'value' => 30],
                ['timestamp' => '2025-01-02', 'value' => 40],
            ],
        ];

        $chart = $this->processor->processMultiSeriesData(
            $allData,
            'Comparison',
            'day',
            new DateTimeImmutable('2025-01-01'),
            new DateTimeImmutable('2025-01-02'),
        );

        $this->assertSame(['Series 1', 'Series 2'], $chart->labels);
        $this->assertCount(2, $chart->datasets);
        $this->assertSame('Series 1', $chart->datasets[0]->label);
        $this->assertSame([10.0, 20.0], $chart->datasets[0]->data);
        $this->assertSame('Series 2', $chart->datasets[1]->label);
        $this->assertSame([30.0, 40.0], $chart->datasets[1]->data);
    }

    public function testProcessEngagementData(): void
    {
        $rawData = [
            ['timestamp' => '2025-01-01', 'value' => 75],
        ];

        $chart = $this->processor->processEngagementData($rawData);
        $this->assertSame(['2025-01-01'], $chart->labels);
        $this->assertSame('engagement', $chart->datasets[0]->label);
    }

    public function testProcessMultiMetricData(): void
    {
        $allData = [
            'Metric A' => [['value' => 5]],
        ];

        $chart = $this->processor->processMultiMetricData($allData, 'Title', 'day');
        $this->assertSame(['Metric A'], $chart->labels);
    }

    public function testGetDefaultColors(): void
    {
        $colors = $this->processor->getDefaultColors();
        $this->assertIsArray($colors);
        $this->assertContains('#FF6384', $colors);
    }
}
