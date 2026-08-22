<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Statistics\Formatters;

use App\Infrastructure\Statistics\Formatters\CSVStatisticsFormatter;
use Tests\Support\UnitTestCase;

/**
 * CSV 統計格式化器測試.
 */
final class CSVStatisticsFormatterTest extends UnitTestCase
{
    private CSVStatisticsFormatter $formatter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->formatter = new CSVStatisticsFormatter();
    }

    public function testMetadataAndCapabilities(): void
    {
        $this->assertSame('csv', $this->formatter->getFormat());
        $this->assertSame('csv', $this->formatter->getFileExtension());
        $this->assertSame('text/csv', $this->formatter->getMimeType());
        $this->assertTrue($this->formatter->supportsLargeData());

        $filename = $this->formatter->getRecommendedFilename('daily', ['filename_suffix' => '_v2']);
        $this->assertStringStartsWith('statistics_daily_v2_', $filename);
        $this->assertStringEndsWith('.csv', $filename);
    }

    public function testFormatEmptyDataReturnsEmptyString(): void
    {
        $this->assertSame('', $this->formatter->format([]));
    }

    public function testFormatSequentialArrayOfAssociativeArrays(): void
    {
        $data = [
            ['id' => 1, 'name' => 'Alice', 'score' => 90],
            ['id' => 2, 'name' => 'Bob', 'score' => 85],
        ];

        $csv = $this->formatter->format($data);
        $lines = explode("\n", trim($csv));

        $this->assertCount(3, $lines);
        $this->assertSame('id,name,score', $lines[0]);
        $this->assertSame('1,Alice,90', $lines[1]);
        $this->assertSame('2,Bob,85', $lines[2]);
    }

    public function testFormatWithCustomDelimiterAndWithoutHeaders(): void
    {
        $data = [
            ['a' => '10', 'b' => '20'],
        ];

        $csv = $this->formatter->format($data, [
            'delimiter'       => ';',
            'include_headers' => false,
        ]);

        $this->assertSame("10;20\n", $csv);
    }

    public function testFormatAssociativeArrayWithNestedStructures(): void
    {
        $data = [
            'overview' => [
                'total'  => 100,
                'nested' => ['sub_key' => 'sub_val'],
            ],
            'tags'   => ['tech', 'news'],
            'scalar' => 'test_val',
        ];

        $csv = $this->formatter->format($data);
        $this->assertNotEmpty($csv);
        $this->assertStringContainsString('overview_total', $csv);
        $this->assertStringContainsString('overview_nested', $csv);
        $this->assertStringContainsString('tags', $csv);
        $this->assertStringContainsString('scalar', $csv);
    }

    public function testFormatWithEncoding(): void
    {
        $data = [
            ['name' => '中文測試'],
        ];

        $csv = $this->formatter->format($data, ['encoding' => 'UTF-8']);
        $this->assertStringContainsString('中文測試', $csv);
    }
}
