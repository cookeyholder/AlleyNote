<?php

declare(strict_types=1);

namespace App\Domains\Statistics\Exceptions;

/**
 * 統計計算例外.
 *
 * 當統計計算過程中發生錯誤時拋出的例外，
 * 例如：資料不足、計算溢位、無效參數等。
 */
final class StatisticsCalculationException extends StatisticsException

{
    public static function insufficientData(string $operation, int $required, int $actual): self
    {
        return new self(
            "執行 '{$operation}' 需要至少 {$required} 筆資料，但只有 {$actual} 筆",
        );
    }

    public static function invalidParameter(string $parameter, mixed $value): self
    {
        $valueStr = is_scalar($value) ? (string) $value : gettype($value);

        return new self("參數 '{$parameter}' 的值無效: {$valueStr}");
    }

    public static function divisionByZero(string $operation): self
    {
        return new self("執行 '{$operation}' 時發生除零錯誤");
    }

    public static function calculationOverflow(string $operation): self
    {
        return new self("執行 '{$operation}' 時發生數值溢位");
    }

    public static function inconsistentDataLength(int $expected, int $actual): self
    {
        return new self("資料長度不一致：期望 {$expected}，實際 {$actual}");
    }
}
