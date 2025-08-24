<?php

declare(strict_types=1);

namespace Tests\Factory\Abstracts;

abstract class AbstractFactory
{
    protected static array $sequence = [];

    /**
     * 產生一筆資料.
     * @param array $attributes 自訂屬性
     */
    abstract public static function make(array $attributes = []): array;

    /**
     * 產生多筆資料.
     * @param int $count 數量
     * @param array $attributes 自訂屬性
     */
    public static function makeMany(int $count, array $attributes = []): array
    {
        return array_map(
            fn() => static::make($attributes),
            range(1, $count),
        );
    }

    /**
     * 取得序列值
     * @param string $key 序列鍵值
     */
    protected static function sequence(string $key): int
    {
        if (!isset(static::$sequence[$key])) {
            static::$sequence[$key] = 0;
        }

        return ++static::$sequence[$key];
    }
}
