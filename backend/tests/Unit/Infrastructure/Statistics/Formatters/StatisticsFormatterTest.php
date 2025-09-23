<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Statistics\Formatters;

use App\Infrastructure\Statistics\Formatters\CSVStatisticsFormatter;
use App\Infrastructure\Statistics\Formatters\JSONStatisticsFormatter;
use App\Infrastructure\Statistics\Formatters\PDFStatisticsFormatter;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * 統計格式化器單元測試.
 *
 * 測試各種統計資料格式化器的功能。
 */
final class StatisticsFormatterTest extends TestCase
{
    public function testJSON格式化器應該正確格式化資料(): void
    {
        // Arrange
        $formatter = new JSONStatisticsFormatter();
        $data = [
            'overview' => [
                'total_posts' => 100,
                'total_views' => 5000,
            ],
            'posts' => [
                ['id' => 1, 'title' => '測試文章', 'views' => 150],
                ['id' => 2, 'title' => '另一篇文章', 'views' => 200],
            ],
        ];

        // Act
        $result = $formatter->format($data);

        // Assert
        $this->assertJson($result);
        $decoded = json_decode($result, true);
        $this->assertArrayHasKey('metadata', $decoded);
        $this->assertArrayHasKey('data', $decoded);
        $this->assertEquals('json', $decoded['metadata']['format']);
        $this->assertEquals($data, $decoded['data']);
    }

    public function testJSON格式化器應該支援緊湊模式(): void
    {
        // Arrange
        $formatter = new JSONStatisticsFormatter();
        $data = ['test' => 'value'];

        // Act
        $compact = $formatter->format($data, ['compact' => true]);
        $pretty = $formatter->format($data, ['compact' => false]);

        // Assert
        $this->assertStringNotContainsString("\n", $compact);
        $this->assertStringContainsString("\n", $pretty);
    }

    public function testCSV格式化器應該正確格式化表格資料(): void
    {
        // Arrange
        $formatter = new CSVStatisticsFormatter();
        $data = [
            'posts' => [
                ['id' => 1, 'title' => '測試文章', 'views' => 150],
                ['id' => 2, 'title' => '另一篇文章', 'views' => 200],
            ],
        ];

        // Act
        $result = $formatter->format($data);

        // Assert
        $lines = explode("\n", trim($result));
        $this->assertCount(3, $lines); // 標題行 + 2 資料行
        $this->assertEquals('id,title,views', $lines[0]); // 標題行
        $this->assertStringContainsString('1,測試文章,150', $lines[1]);
        $this->assertStringContainsString('2,另一篇文章,200', $lines[2]);
    }

    public function testCSV格式化器應該支援自訂分隔符號(): void
    {
        // Arrange
        $formatter = new CSVStatisticsFormatter();
        $data = [
            'test' => [
                ['a' => 1, 'b' => 2],
            ],
        ];

        // Act
        $result = $formatter->format($data, ['delimiter' => ';']);

        // Assert
        $this->assertStringContainsString('a;b', $result);
        $this->assertStringContainsString('1;2', $result);
    }

    public function testCSV格式化器應該處理空資料(): void
    {
        // Arrange
        $formatter = new CSVStatisticsFormatter();

        // Act
        $result = $formatter->format([]);

        // Assert
        $this->assertEquals('', $result);
    }

    public function testPDF格式化器應該產生PDF標記(): void
    {
        // Arrange
        $formatter = new PDFStatisticsFormatter();
        $data = [
            'overview' => [
                'total_posts' => 100,
                'total_views' => 5000,
            ],
        ];

        // Act
        $result = $formatter->format($data);

        // Assert
        $this->assertStringStartsWith('%PDF-1.4', $result);
        $this->assertStringContainsString('%%EOF', $result);
    }

    public function testPDF格式化器應該支援自訂標題(): void
    {
        // Arrange
        $formatter = new PDFStatisticsFormatter();
        $data = ['test' => 'value'];
        $options = ['title' => '自訂報告標題'];

        // Act
        $result = $formatter->format($data, $options);

        // Assert
        $this->assertStringContainsString('自訂報告標題', $result);
    }

    public function test各格式化器應該返回正確的基本資訊(): void
    {
        $formatters = [
            new JSONStatisticsFormatter(),
            new CSVStatisticsFormatter(),
            new PDFStatisticsFormatter(),
        ];

        foreach ($formatters as $formatter) {
            // Assert
            $this->assertIsString($formatter->getFormat());
            $this->assertIsString($formatter->getFileExtension());
            $this->assertIsString($formatter->getMimeType());
            $this->assertIsBool($formatter->supportsLargeData());

            $filename = $formatter->getRecommendedFilename('test');
            $this->assertStringContainsString($formatter->getFileExtension(), $filename);
            $this->assertStringContainsString('test', $filename);
        }
    }

    public function test檔案名稱應該包含時間戳記(): void
    {
        // Arrange
        $formatter = new JSONStatisticsFormatter();

        // Act
        $filename1 = $formatter->getRecommendedFilename('test');
        sleep(1); // 確保時間戳記不同（使用更長的延遲）
        $filename2 = $formatter->getRecommendedFilename('test');

        // Assert
        $this->assertNotEquals($filename1, $filename2);
        $this->assertStringContainsString('test', $filename1);
        $this->assertStringContainsString('test', $filename2);
    }

    public function testJSON格式化器應該處理錯誤資料(): void
    {
        // Arrange
        $formatter = new JSONStatisticsFormatter();
        $invalidData = [
            'invalid' => "\xB1\x31", // 無效的 UTF-8 字符
        ];

        // Act & Assert
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('JSON 編碼失敗');
        $formatter->format($invalidData);
    }

    public function testCSV格式化器應該處理巢狀資料結構(): void
    {
        // Arrange
        $formatter = new CSVStatisticsFormatter();
        $data = [
            'complex' => [
                'nested' => ['key' => 'value'],
                'array' => [1, 2, 3],
            ],
        ];

        // Act
        $result = $formatter->format($data);

        // Assert
        $this->assertNotEmpty($result);
        $this->assertStringContainsString('complex_nested', $result);
        $this->assertStringContainsString('complex_array', $result);
    }

    public function test格式化器應該支援不同的檔案名稱後綴(): void
    {
        // Arrange
        $formatter = new JSONStatisticsFormatter();
        $suffix = '_custom';

        // Act
        $filename = $formatter->getRecommendedFilename('test', ['filename_suffix' => $suffix]);

        // Assert
        $this->assertStringContainsString($suffix, $filename);
    }
}
