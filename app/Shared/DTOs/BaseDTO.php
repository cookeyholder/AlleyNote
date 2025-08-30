<?php

declare(strict_types=1);

namespace App\Shared\DTOs;

use App\Shared\Contracts\ValidatorInterface;
use App\Shared\Exceptions\ValidationException;
use JsonSerializable;

/**
 * 基礎 DTO 抽象類別.
 *
 * 提供資料傳輸物件的基本功能，確保型別安全且防止巨量賦值攻擊
 */
abstract class BaseDTO implements JsonSerializable
{
    protected ValidatorInterface $validator;

    /**
     * 建構函式.
     *
     * @param ValidatorInterface $validator 驗證器實例
     */
    public function __construct(ValidatorInterface $validator)
    {
        $this->validator = $validator;
    }

    /**
     * 將 DTO 轉換為陣列.
     */
    abstract public function toArray(): array;

    /**
     * 取得驗證規則.
     */
    abstract protected function getValidationRules(): array;

    /**
     * 實作 JsonSerializable 介面.
     */
    public function jsonSerialize(): mixed
    {
        return $this->toArray();
    }

    /**
     * 驗證資料.
     *
     * @param array $data 輸入資料
     * @throws ValidationException 當驗證失敗時
     * @return array<mixed> 驗證通過的資料
     */
    protected function validate(array $data): mixed
    {
        return $this->validator->validateOrFail($data, $this->getValidationRules());
    }

    /**
     * 安全地取得值
     */
    protected function getValue(array $data, string $key, mixed $default = null): mixed
    {
        return $data[$key] ?? $default;
    }

    /**
     * 安全地取得字串值
     */
    protected function getString(array $data, string $key, ?string $default = null): ?string
    {
        $value = $this->getValue($data, $key, $default);

        return $value !== null ? trim((string) $value) : null;
    }

    /**
     * 安全地取得整數值
     */
    protected function getInt(array $data, string $key, ?int $default = null): ?int
    {
        $value = $this->getValue($data, $key, $default);

        return $value !== null ? (int) $value : null;
    }

    /**
     * 安全地取得布林值
     */
    protected function getBool(array $data, string $key, ?bool $default = null): ?bool
    {
        $value = $this->getValue($data, $key, $default);
        if (value === null) {
            return null;
        }

        return is_bool($value) ? $value : in_array($value, [1, '1', 'true', 'on', 'yes'], true);
    }
}
