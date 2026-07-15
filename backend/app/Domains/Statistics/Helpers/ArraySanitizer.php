<?php

declare(strict_types=1);

namespace App\Domains\Statistics\Helpers;

/**
 * 陣列型別安全輔助工具.
 */
final class ArraySanitizer
{
    /**
     * 確保回傳 array<string, mixed> 型別.
     *
     * @param mixed $data
     *
     * @return array<string, mixed>
     */
    public static function ensureStringMixedArray($data): array
    {
        if (!is_array($data)) {
            return [];
        }
        $result = [];
        foreach ($data as $key => $value) {
            if (is_string($key)) {
                $result[$key] = $value;
            }
        }

        return $result;
    }

    /**
     * 確保回傳 array<string, int> 型別.
     *
     * @param mixed $data
     *
     * @return array<string, int>
     */
    public static function ensureStringIntArray($data): array
    {
        if (!is_array($data)) {
            return [];
        }
        $result = [];
        foreach ($data as $key => $value) {
            if (is_string($key) && is_numeric($value)) {
                $result[$key] = (int) $value;
            }
        }

        return $result;
    }

    /**
     * 確保回傳 array<int, array<string, mixed>> 型別.
     *
     * @param mixed $data
     *
     * @return array<int, array<string, mixed>>
     */
    public static function ensureIntArrayStringMixedArray($data): array
    {
        if (!is_array($data)) {
            return [];
        }
        $result = [];
        foreach ($data as $item) {
            if (is_array($item)) {
                $filteredItem = [];
                foreach ($item as $key => $value) {
                    if (is_string($key)) {
                        $filteredItem[$key] = $value;
                    }
                }
                $result[] = $filteredItem;
            }
        }

        return $result;
    }

    /**
     * 確保回傳 array<string, int|float> 型別.
     *
     * @param mixed $data
     *
     * @return array<string, int|float>
     */
    public static function ensureStringNumberArray($data): array
    {
        if (!is_array($data)) {
            return [];
        }
        $result = [];
        foreach ($data as $key => $value) {
            if (is_string($key) && (is_int($value) || is_float($value))) {
                $result[$key] = $value;
            }
        }

        return $result;
    }

    /**
     * 確保回傳 array<string, int> 型別，且值 >= 0.
     *
     * @param mixed $data
     *
     * @return array<string, int>
     */
    public static function ensureStringNonNegativeIntArray($data): array
    {
        if (!is_array($data)) {
            return [];
        }
        $filtered = [];
        foreach ($data as $key => $value) {
            if (is_string($key) && is_numeric($value) && $value >= 0) {
                $filtered[$key] = (int) $value;
            }
        }

        return $filtered;
    }
}
