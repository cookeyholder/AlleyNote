<?php

declare(strict_types=1);

namespace App\Domains\Statistics\ValueObjects;

use App\Domains\Statistics\Exceptions\InvalidStatisticsMetricException;

/**
 * 統計指標值物件
 * 表示統計資料的數值和相關資訊.
 */
readonly class StatisticsMetric
{
    /**
     * @param int|float $value 數值
     * @param string $description 描述
     */
    private function __construct(
        public int|float $value,
        public string $unit,
        public string $description,
        public int $precision,
    ) {}

    /**
     * 建立統計指標.
     */
    public static function create(
        int|float $value,
        string $unit = '',
        string $description = '',
        int $precision = 0,
    ): self {
        if ($value < 0) {
            throw new InvalidStatisticsMetricException(
                '統計指標數值不能為負數',
            );
        }

        if ($precision < 0 || $precision > 10) {
            throw new InvalidStatisticsMetricException(
                '精確度必須在 0-10 之間',
            );
        }

        $unit = trim($unit);
        $description = trim($description);

        return new self($value, $unit, $description, $precision);
    }

    /**
     * 建立計數指標.
     */
    public static function count(int $value, string $description = ''): self
    {
        if ($value < 0) {
            throw new InvalidStatisticsMetricException(
                '計數值不能為負數',
            );
        }

        return new self($value, '個', $description, 0);
    }

    /**
     * 建立百分比指標.
     */
    public static function percentage(float $value, string $description = ''): self
    {
        if ($value < 0 || $value > 100) {
            throw new InvalidStatisticsMetricException(
                '百分比必須在 0-100 之間',
            );
        }

        return new self($value, '%', $description, 2);
    }

    /**
     * 建立比率指標.
     */
    public static function ratio(float $value, string $description = ''): self
    {
        if ($value < 0) {
            throw new InvalidStatisticsMetricException(
                '比率不能為負數',
            );
        }

        return new self($value, ':1', $description, 3);
    }

    /**
     * 建立時間指標（秒）.
     */
    public static function timeInSeconds(int|float $value, string $description = ''): self
    {
        if ($value < 0) {
            throw new InvalidStatisticsMetricException(
                '時間不能為負數',
            );
        }

        return new self($value, '秒', $description, 2);
    }

    /**
     * 建立大小指標（位元組）.
     */
    public static function sizeInBytes(int $value, string $description = ''): self
    {
        if ($value < 0) {
            throw new InvalidStatisticsMetricException(
                '大小不能為負數',
            );
        }

        return new self($value, 'bytes', $description, 0);
    }

    /**
     * 建立零值指標.
     */
    public static function zero(string $unit = '', string $description = ''): self
    {
        return new self(0, $unit, $description, 0);
    }

    /**
     * 取得格式化後的數值.
     */
    public function getFormattedValue(): string
    {
        if ($this->precision === 0) {
            return number_format((float) $this->value, 0, '.', ',');
        }

        return number_format((float) $this->value, $this->precision, '.', ',');
    }

    /**
     * 取得帶單位的格式化字串.
     */
    public function getFormattedValueWithUnit(): string
    {
        $formatted = $this->getFormattedValue();

        return empty($this->unit) ? $formatted : "{$formatted} {$this->unit}";
    }

    /**
     * 取得適合顯示的完整字串.
     */
    public function getDisplayString(): string
    {
        $formatted = $this->getFormattedValueWithUnit();

        if (empty($this->description)) {
            return $formatted;
        }

        return "{$formatted} ({$this->description})";
    }

    /**
     * 判斷是否為零值.
     */
    public function isZero(): bool
    {
        return $this->value == 0;
    }

    /**
     * 判斷是否為正值.
     */
    public function isPositive(): bool
    {
        return $this->value > 0;
    }

    /**
     * 判斷是否為整數.
     */
    public function isInteger(): bool
    {
        return is_int($this->value);
    }

    /**
     * 判斷是否為浮點數.
     */
    public function isFloat(): bool
    {
        return is_float($this->value);
    }

    /**
     * 判斷是否為百分比類型.
     */
    public function isPercentage(): bool
    {
        return $this->unit === '%';
    }

    /**
     * 判斷是否為計數類型.
     */
    public function isCount(): bool
    {
        return $this->unit === '個';
    }

    /**
     * 判斷是否為時間類型.
     */
    public function isTime(): bool
    {
        return in_array($this->unit, ['秒', '分鐘', '小時', '天'], true);
    }

    /**
     * 判斷是否為大小類型.
     */
    public function isSize(): bool
    {
        return in_array($this->unit, ['bytes', 'KB', 'MB', 'GB', 'TB'], true);
    }

    /**
     * 加法運算.
     */
    public function add(StatisticsMetric $other): self
    {
        $this->validateSameUnit($other);

        $newValue = $this->value + $other->value;
        $maxPrecision = max($this->precision, $other->precision);

        return new self($newValue, $this->unit, $this->description, $maxPrecision);
    }

    /**
     * 減法運算.
     */
    public function subtract(StatisticsMetric $other): self
    {
        $this->validateSameUnit($other);

        $newValue = $this->value - $other->value;
        if ($newValue < 0) {
            throw new InvalidStatisticsMetricException(
                '減法結果不能為負數',
            );
        }

        $maxPrecision = max($this->precision, $other->precision);

        return new self($newValue, $this->unit, $this->description, $maxPrecision);
    }

    /**
     * 乘法運算.
     */
    public function multiply(int|float $multiplier): self
    {
        if ($multiplier < 0) {
            throw new InvalidStatisticsMetricException(
                '乘數不能為負數',
            );
        }

        $newValue = $this->value * $multiplier;

        return new self($newValue, $this->unit, $this->description, $this->precision);
    }

    /**
     * 除法運算.
     */
    public function divide(int|float $divisor): self
    {
        if ($divisor <= 0) {
            throw new InvalidStatisticsMetricException(
                '除數必須大於零',
            );
        }

        $newValue = $this->value / $divisor;
        $precision = max($this->precision, 2); // 除法結果至少保留2位小數

        return new self($newValue, $this->unit, $this->description, $precision);
    }

    /**
     * 計算百分比變化.
     */
    public function calculatePercentageChangeTo(StatisticsMetric $other): self
    {
        $this->validateSameUnit($other);

        if ($this->value == 0) {
            throw new InvalidStatisticsMetricException(
                '基數為零時無法計算百分比變化',
            );
        }

        $change = (($other->value - $this->value) / $this->value) * 100;

        return self::percentage(abs($change), '變化百分比');
    }

    /**
     * 比較兩個指標.
     */
    public function compareTo(StatisticsMetric $other): int
    {
        $this->validateSameUnit($other);

        return $this->value <=> $other->value;
    }

    /**
     * 判斷是否相等.
     */
    public function equals(StatisticsMetric $other): bool
    {
        return $this->value === $other->value
            && $this->unit === $other->unit
            && $this->precision === $other->precision;
    }

    /**
     * 判斶是否大於另一個指標.
     */
    public function greaterThan(StatisticsMetric $other): bool
    {
        return $this->compareTo($other) > 0;
    }

    /**
     * 判斷是否小於另一個指標.
     */
    public function lessThan(StatisticsMetric $other): bool
    {
        return $this->compareTo($other) < 0;
    }

    /**
     * 轉換為陣列.
     * @return array{
     *     value: int|float,
     *     unit: string,
     *     description: string,
     *     precision: int,
     *     formatted: string,
     *     formatted_with_unit: string,
     *     display_string: string
     * }
     */
    public function toArray(): array
    {
        return [
            'value' => $this->value,
            'unit' => $this->unit,
            'description' => $this->description,
            'precision' => $this->precision,
            'formatted' => $this->getFormattedValue(),
            'formatted_with_unit' => $this->getFormattedValueWithUnit(),
            'display_string' => $this->getDisplayString(),
        ];
    }

    /**
     * 轉換為字串.
     */
    public function __toString(): string
    {
        return $this->getDisplayString();
    }

    /**
     * 取得數值.
     */
    public function getValue(): int|float
    {
        return $this->value;
    }

    /**
     * 取得單位.
     */
    public function getUnit(): string
    {
        return $this->unit;
    }

    /**
     * 取得描述.
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * 驗證兩個指標是否具有相同單位.
     */
    private function validateSameUnit(StatisticsMetric $other): void
    {
        if ($this->unit !== $other->unit) {
            throw new InvalidStatisticsMetricException(
                sprintf(
                    '指標單位不匹配：%s vs %s',
                    $this->unit,
                    $other->unit,
                ),
            );
        }
    }
}
