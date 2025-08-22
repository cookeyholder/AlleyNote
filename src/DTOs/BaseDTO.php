<?php

declare(strict_types=1);

namespace App\DTOs;

use JsonSerializable;

/**
 * 基礎 DTO 抽象類別
 * 
 * 提供資料傳輸物件的基本功能，確保型別安全且防止巨量賦值攻擊
 */
abstract class BaseDTO implements JsonSerializable
{
    /**
     * 將 DTO 轉換為陣列
     * 
     * @return array
     */
    abstract public function toArray(): array;

    /**
     * 實作 JsonSerializable 介面
     * 
     * @return array
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * 驗證必填欄位
     * 
     * @param array $required 必填欄位清單
     * @param array $data 輸入資料
     * @throws \InvalidArgumentException
     */
    protected function validateRequired(array $required, array $data): void
    {
        $missing = [];
        foreach ($required as $field) {
            if (!array_key_exists($field, $data) || empty($data[$field])) {
                $missing[] = $field;
            }
        }

        if (!empty($missing)) {
            throw new \InvalidArgumentException(
                sprintf('缺少必填欄位: %s', implode(', ', $missing))
            );
        }
    }

    /**
     * 安全地取得字串值
     * 
     * @param array $data
     * @param string $key
     * @param string|null $default
     * @return string|null
     */
    protected function getString(array $data, string $key, ?string $default = null): ?string
    {
        if (!array_key_exists($key, $data)) {
            return $default;
        }

        $value = $data[$key];
        if ($value === null) {
            return null;
        }

        return trim((string) $value);
    }

    /**
     * 安全地取得整數值
     * 
     * @param array $data
     * @param string $key
     * @param int|null $default
     * @return int|null
     */
    protected function getInt(array $data, string $key, ?int $default = null): ?int
    {
        if (!array_key_exists($key, $data)) {
            return $default;
        }

        $value = $data[$key];
        if ($value === null) {
            return null;
        }

        return (int) $value;
    }

    /**
     * 安全地取得布林值
     * 
     * @param array $data
     * @param string $key
     * @param bool|null $default
     * @return bool|null
     */
    protected function getBool(array $data, string $key, ?bool $default = null): ?bool
    {
        if (!array_key_exists($key, $data)) {
            return $default;
        }

        $value = $data[$key];
        if ($value === null) {
            return null;
        }

        return (bool) $value;
    }
}
