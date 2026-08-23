<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Statistics\Formatters;

use App\Infrastructure\Statistics\Formatters\PDFStatisticsFormatter;
use Tests\Support\UnitTestCase;

/**
 * PDF 統計格式化器測試.
 */
final class PDFStatisticsFormatterTest extends UnitTestCase
{
    private PDFStatisticsFormatter $formatter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->formatter = new PDFStatisticsFormatter();
    }

    public function testMetadataAndCapabilities(): void
    {
        $this->assertSame('pdf', $this->formatter->getFormat());
        $this->assertSame('pdf', $this->formatter->getFileExtension());
        $this->assertSame('application/pdf', $this->formatter->getMimeType());
        $this->assertFalse($this->formatter->supportsLargeData());

        $filename = $this->formatter->getRecommendedFilename('users', ['filename_suffix' => '_report']);
        $this->assertStringStartsWith('statistics_users_report_', $filename);
        $this->assertStringEndsWith('.pdf', $filename);
    }

    public function testFormatWithComplexData(): void
    {
        $data = [
            'table_section' => [
                ['col1' => 'val1', 'col2' => ['nested' => 'val2']],
                ['col1' => 'val3', 'col2' => 'val4'],
            ],
            'summary_section' => [
                'total_users' => 150,
                'details'     => ['active' => 100],
            ],
            'string_section' => 'Just some text description',
        ];

        $pdf = $this->formatter->format($data, [
            'title'  => '綜合統計報告',
            'author' => '測試人員',
        ]);

        $this->assertStringStartsWith("%PDF-1.4\n", $pdf);
        $this->assertStringContainsString('綜合統計報告', $pdf);
        $this->assertStringEndsWith("%%EOF\n", $pdf);
    }

    public function testFormatWithEmptyTable(): void
    {
        $data = [
            'empty_section' => [],
        ];

        $pdf = $this->formatter->format($data);
        $this->assertStringContainsString('%PDF-1.4', $pdf);
    }

    public function testFormatWithNonArrayTableRowsFallsBackToSummary(): void
    {
        // 順序陣列中包含非陣列元素，不視為表格資料
        $data = [
            'mixed_section' => ['純文字一', '純文字二'],
        ];

        $pdf = $this->formatter->format($data);

        $this->assertStringContainsString('%PDF-1.4', $pdf);
        $this->assertStringEndsWith("%%EOF\n", $pdf);
    }

    public function testFormatWithNonStringKeysRowsFallsBackToSummary(): void
    {
        // 資料列使用數字鍵，不符合表格資料結構
        $data = [
            'list_section' => [['第一項'], ['第二項']],
        ];

        $pdf = $this->formatter->format($data);

        $this->assertStringContainsString('%PDF-1.4', $pdf);
    }
}
