<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Statistics\Formatters;

use App\Infrastructure\Statistics\Formatters\JSONStatisticsFormatter;
use RuntimeException;
use Tests\Support\UnitTestCase;

/**
 * JSON 統計格式化器測試.
 */
final class JSONStatisticsFormatterTest extends UnitTestCase
{
    private JSONStatisticsFormatter $formatter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->formatter = new JSONStatisticsFormatter();
    }

    public function testMetadataAndCapabilities(): void
    {
        $this->assertSame('json', $this->formatter->getFormat());
        $this->assertSame('json', $this->formatter->getFileExtension());
        $this->assertSame('application/json', $this->formatter->getMimeType());
        $this->assertTrue($this->formatter->supportsLargeData());

        $filename = $this->formatter->getRecommendedFilename('overview', ['filename_suffix' => '_test']);
        $this->assertStringStartsWith('statistics_overview_test_', $filename);
        $this->assertStringEndsWith('.json', $filename);
    }

    public function testFormatPrettyPrint(): void
    {
        $data = ['views' => 100];
        $json = $this->formatter->format($data, ['compact' => false]);

        $this->assertStringContainsString("\n", $json);
        $decoded = json_decode($json, true);
        $this->assertSame('json', $decoded['metadata']['format']);
        $this->assertSame($data, $decoded['data']);
    }

    public function testFormatCompact(): void
    {
        $data = ['views' => 100];
        $json = $this->formatter->format($data, ['compact' => true]);

        $this->assertStringNotContainsString("\n", $json);
        $decoded = json_decode($json, true);
        $this->assertSame($data, $decoded['data']);
    }

    public function testFormatThrowsOnInvalidUtf8(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('JSON 編碼失敗');

        $this->formatter->format(["\xB1\x31" => 'invalid']);
    }
}
