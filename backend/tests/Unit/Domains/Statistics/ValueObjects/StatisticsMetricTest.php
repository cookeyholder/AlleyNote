<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Statistics\ValueObjects;

use App\Domains\Statistics\ValueObjects\StatisticsMetric;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * StatisticsMetric 值物件單元測試.
 */
final class StatisticsMetricTest extends TestCase
{
    #[Test]
    public function it_can_be_created_with_valid_parameters(): void
    {
        // Arrange
        $name = '文章總數';
        $value = 150;
        $unit = '個';
        $calculationMethod = '計數統計';

        // Act
        $metric = new StatisticsMetric($name, $value, $unit, $calculationMethod);

        // Assert
        $this->assertSame($name, $metric->name);
        $this->assertSame($value, $metric->value);
        $this->assertSame($unit, $metric->unit);
        $this->assertSame($calculationMethod, $metric->calculationMethod);
    }

    #[Test]
    public function it_throws_exception_with_empty_name(): void
    {
        // Assert
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Metric name cannot be empty');

        // Act
        new StatisticsMetric('', 100);
    }

    #[Test]
    public function it_throws_exception_with_negative_value(): void
    {
        // Assert
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Metric value must be non-negative');

        // Act
        new StatisticsMetric('測試指標', -10);
    }

    #[Test]
    public function it_throws_exception_with_invalid_percentage_value(): void
    {
        // Assert - 超過100%
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Percentage value must be between 0 and 100');

        // Act
        new StatisticsMetric('成功率', 150, '%');
    }

    #[Test]
    public function it_throws_exception_with_negative_percentage_value(): void
    {
        // Assert - 負百分比
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Percentage value must be between 0 and 100');

        // Act
        new StatisticsMetric('成功率', -10, '%');
    }

    #[Test]
    public function it_throws_exception_with_meaningless_unit(): void
    {
        // Assert
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unit cannot be meaningless string');

        // Act
        new StatisticsMetric('測試指標', 100, 'N/A');
    }

    #[Test]
    public function it_throws_exception_with_meaningless_calculation_method(): void
    {
        // Assert
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Calculation method cannot be meaningless string');

        // Act
        new StatisticsMetric('測試指標', 100, '個', 'null');
    }

    #[Test]
    public function it_can_be_created_from_array(): void
    {
        // Arrange
        $data = [
            'name' => '用戶總數',
            'value' => 250,
            'unit' => '人',
            'calculation_method' => '去重計數',
        ];

        // Act
        $metric = StatisticsMetric::fromArray($data);

        // Assert
        $this->assertSame('用戶總數', $metric->name);
        $this->assertSame(250, $metric->value);
        $this->assertSame('人', $metric->unit);
        $this->assertSame('去重計數', $metric->calculationMethod);
    }

    #[Test]
    public function it_throws_exception_when_creating_from_incomplete_array(): void
    {
        // Arrange
        /** @var array{name: string} $data */
        $data = [
            'name' => '測試指標',
            // 缺少 value
        ];

        // Assert
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing required fields: name, value');

        // Act
        /** @phpstan-ignore-next-line */
        StatisticsMetric::fromArray($data);
    }

    #[Test]
    public function it_can_create_count_metric(): void
    {
        // Act
        $metric = StatisticsMetric::createCount('文章數量', 100);

        // Assert
        $this->assertSame('文章數量', $metric->name);
        $this->assertSame(100, $metric->value);
        $this->assertSame('個', $metric->unit);
        $this->assertSame('計數統計', $metric->calculationMethod);
    }

    #[Test]
    public function it_can_create_percentage_metric(): void
    {
        // Act
        $metric = StatisticsMetric::createPercentage('完成率', 85.5);

        // Assert
        $this->assertSame('完成率', $metric->name);
        $this->assertSame(85.5, $metric->value);
        $this->assertSame('%', $metric->unit);
        $this->assertSame('百分比計算', $metric->calculationMethod);
    }

    #[Test]
    public function it_can_create_average_metric(): void
    {
        // Act
        $metric = StatisticsMetric::createAverage('平均分數', 78.9);

        // Assert
        $this->assertSame('平均分數', $metric->name);
        $this->assertSame(78.9, $metric->value);
        $this->assertSame('平均', $metric->unit);
        $this->assertSame('平均值計算', $metric->calculationMethod);
    }

    #[Test]
    public function it_can_create_rate_metric(): void
    {
        // Act
        $metric = StatisticsMetric::createRate('轉換率', 0.125);

        // Assert
        $this->assertSame('轉換率', $metric->name);
        $this->assertSame(0.125, $metric->value);
        $this->assertSame('比率', $metric->unit);
        $this->assertSame('比率計算', $metric->calculationMethod);
    }

    #[Test]
    public function it_can_check_if_is_percentage(): void
    {
        // Arrange
        $percentageMetric = StatisticsMetric::createPercentage('完成率', 85);
        $countMetric = StatisticsMetric::createCount('文章數', 100);

        // Act & Assert
        $this->assertTrue($percentageMetric->isPercentage());
        $this->assertFalse($countMetric->isPercentage());
    }

    #[Test]
    public function it_can_check_if_is_count(): void
    {
        // Arrange
        $countMetric = StatisticsMetric::createCount('文章數', 100);
        $percentageMetric = StatisticsMetric::createPercentage('完成率', 85);
        $integerMetric = new StatisticsMetric('數量', 50); // 整數且無單位

        // Act & Assert
        $this->assertTrue($countMetric->isCount());
        $this->assertFalse($percentageMetric->isCount());
        $this->assertTrue($integerMetric->isCount());
    }

    #[Test]
    public function it_can_format_value(): void
    {
        // Arrange
        $countMetric = StatisticsMetric::createCount('文章數', 100);
        $percentageMetric = StatisticsMetric::createPercentage('完成率', 85.567);
        $floatMetric = new StatisticsMetric('平均值', 123.456789);

        // Act & Assert
        $this->assertSame('100 個', $countMetric->formatValue());
        $this->assertSame('85.57 %', $percentageMetric->formatValue());
        $this->assertSame('123.46', $floatMetric->formatValue());
        $this->assertSame('123.4568', $floatMetric->formatValue(4)); // 指定精度
    }

    #[Test]
    public function it_can_convert_to_array(): void
    {
        // Arrange
        $metric = StatisticsMetric::createCount('用戶數', 250);

        // Act
        $array = $metric->toArray();

        // Assert
        /** @phpstan-ignore-next-line method.alreadyNarrowedType */
        $this->assertIsArray($array);
        $this->assertArrayHasKey('name', $array);
        $this->assertArrayHasKey('value', $array);
        $this->assertArrayHasKey('unit', $array);
        $this->assertArrayHasKey('calculation_method', $array);
        $this->assertArrayHasKey('formatted_value', $array);
        $this->assertSame('用戶數', $array['name']);
        $this->assertSame(250, $array['value']);
        $this->assertSame('250 個', $array['formatted_value']);
    }

    #[Test]
    public function it_can_be_json_serialized(): void
    {
        // Arrange
        $metric = StatisticsMetric::createPercentage('成功率', 95.5);

        // Act
        $json = json_encode($metric);
        $this->assertNotFalse($json);
        $decoded = json_decode($json, true);

        // Assert
        /** @phpstan-ignore-next-line method.alreadyNarrowedType */
        $this->assertIsString($json);
        $this->assertIsArray($decoded);
        $this->assertSame('成功率', $decoded['name']);
        $this->assertSame(95.5, $decoded['value']);
        $this->assertSame('%', $decoded['unit']);
    }

    #[Test]
    public function it_can_check_equality(): void
    {
        // Arrange
        $metric1 = StatisticsMetric::createCount('文章數', 100);
        $metric2 = StatisticsMetric::createCount('文章數', 100);
        $metric3 = StatisticsMetric::createCount('文章數', 200);
        $metric4 = StatisticsMetric::createCount('用戶數', 100);

        // Act & Assert
        $this->assertTrue($metric1->equals($metric2));
        $this->assertFalse($metric1->equals($metric3)); // 不同值
        $this->assertFalse($metric1->equals($metric4)); // 不同名稱
    }

    #[Test]
    public function it_can_be_converted_to_string(): void
    {
        // Arrange
        $metric = StatisticsMetric::createCount('文章總數', 150);

        // Act
        $string = (string) $metric;

        // Assert
        $this->assertSame('文章總數: 150 個', $string);
    }

    #[Test]
    public function it_accepts_float_values(): void
    {
        // Act
        $metric = new StatisticsMetric('平均分數', 78.95, '分');

        // Assert
        $this->assertSame(78.95, $metric->value);
        $this->assertSame('78.95 分', $metric->formatValue());
    }

    #[Test]
    public function it_handles_empty_optional_parameters(): void
    {
        // Act
        $metric = new StatisticsMetric('測試指標', 100);

        // Assert
        $this->assertSame('', $metric->unit);
        $this->assertSame('', $metric->calculationMethod);
        $this->assertSame('100', $metric->formatValue());
    }
}
