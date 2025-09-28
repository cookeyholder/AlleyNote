<?php

declare(strict_types=1);

namespace App\Domains\Statistics\ValueObjects;

use InvalidArgumentException;
use JsonSerializable;

/**
 * 統計指標值物件.
 *
 * 表示一個具體的統計測量值，如文章總數、平均瀏覽量等。
 * 此值物件是 immutable 的，一旦建立就不能修改。
 *
 * @psalm-immutable
 */
final readonly class StatisticsMetric implements JsonSerializable
{
    /**
     * @param string $name 指標名稱
     * @param int|float $value 指標數值（必須非負）
     * @param string $unit 指標單位
     * @param string $calculationMethod 計算方式描述
     */
    public function __construct(
        public string $name,
        public int|float $value,
        public string $unit = '',
        public string $calculationMethod = '',
    ) {
        $this->validate();
    }

    /**
     * 從陣列建立統計指標物件.
     *
     * @param array{name: string, value: int|float, unit?: string, calculation_method?: string} $data
     * @throws InvalidArgumentException
     */
    public static function fromArray(array $data): self
    {
        if (!isset($data['name'], $data['value'])) {
            throw new InvalidArgumentException('Missing required fields: name, value');
        }

        return new self(
            name: $data['name'],
            value: $data['value'],
            unit: $data['unit'] ?? '',
            calculationMethod: $data['calculation_method'] ?? '',
        );
    }

    /**
     * 建立計數類型指標.
     */
    public static function createCount(string $name, int $count, string $calculationMethod = ''): self
    {
        return new self($name, $count, '個', $calculationMethod ?: '計數統計');
    }

    /**
     * 建立百分比類型指標.
     */
    public static function createPercentage(string $name, float $percentage, string $calculationMethod = ''): self
    {
        return new self($name, $percentage, '%', $calculationMethod ?: '百分比計算');
    }

    /**
     * 建立平均值類型指標.
     */
    public static function createAverage(string $name, float $average, string $calculationMethod = ''): self
    {
        return new self($name, $average, '平均', $calculationMethod ?: '平均值計算');
    }

    /**
     * 建立比率類型指標.
     */
    public static function createRate(string $name, float $rate, string $calculationMethod = ''): self
    {
        return new self($name, $rate, '比率', $calculationMethod ?: '比率計算');
    }

    /**
     * 檢查是否為百分比指標.
     */
    public function isPercentage(): bool
    {
        return $this->unit === '%';
    }

    /**
     * 檢查是否為計數指標.
     */
    public function isCount(): bool
    {
        return $this->unit === '個' || (is_int($this->value) && $this->unit === '');
    }

    /**
     * 格式化指標值為可讀字串.
     */
    public function formatValue(int $precision = 2): string
    {
        $formatted = is_int($this->value)
            ? (string) $this->value
            : number_format($this->value, $precision);

        return $this->unit ? "{$formatted} {$this->unit}" : $formatted;
    }

    /**
     * 轉換為陣列.
     *
     * @return array{name: string, value: int|float, unit: string, calculation_method: string, formatted_value: string}
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'value' => $this->value,
            'unit' => $this->unit,
            'calculation_method' => $this->calculationMethod,
            'formatted_value' => $this->formatValue(),
        ];
    }

    /**
     * JSON 序列化.
     *
     * @return array{name: string, value: int|float, unit: string, calculation_method: string, formatted_value: string}
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * 檢查兩個統計指標是否相等.
     */
    public function equals(StatisticsMetric $other): bool
    {
        return $this->name === $other->name
            && $this->value === $other->value
            && $this->unit === $other->unit
            && $this->calculationMethod === $other->calculationMethod;
    }

    /**
     * 轉換為字串表示.
     */
    public function __toString(): string
    {
        return "{$this->name}: {$this->formatValue()}";
    }

    /**
     * 驗證統計指標的有效性.
     *
     * @throws InvalidArgumentException
     */
    private function validate(): void
    {
        // 檢查指標名稱不能為空
        if (trim($this->name) === '') {
            throw new InvalidArgumentException('Metric name cannot be empty');
        }

        // 檢查百分比值必須在 0-100 範圍內（優先於一般非負檢查）
        if ($this->isPercentage() && ($this->value < 0 || $this->value > 100)) {
            throw new InvalidArgumentException('Percentage value must be between 0 and 100');
        }

        // 檢查指標值必須非負（對於非百分比）
        if (!$this->isPercentage() && $this->value < 0) {
            throw new InvalidArgumentException('Metric value must be non-negative');
        }

        // 檢查單位和計算方式不能是無意義字串
        if ($this->unit === 'N/A' || $this->unit === 'null') {
            throw new InvalidArgumentException('Unit cannot be meaningless string');
        }

        if ($this->calculationMethod === 'N/A' || $this->calculationMethod === 'null') {
            throw new InvalidArgumentException('Calculation method cannot be meaningless string');
        }
    }
}
