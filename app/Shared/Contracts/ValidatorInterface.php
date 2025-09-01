<?php

declare(strict_types=1);

namespace App\Shared\Contracts;

use App\Shared\Exceptions\ValidationException;
use App\Shared\Validation\ValidationResult;

/**
 * 驗證器介面.
 *
 * 定義資料驗證的標準介面
 */
interface ValidatorInterface
{
    /**
     * 驗證資料.
     *
     * @param array<string, mixed> $data 要驗證的資料
     * @param array<string, mixed> $rules 驗證規則
     * @return ValidationResult 驗證結果
     */
    public function validate(array $data, array $rules): ValidationResult;

    /**
     * 快速驗證資料，失敗時拋出異常.
     *
     * @param array<string, mixed> $data 要驗證的資料
     * @param array<string, mixed> $rules 驗證規則
     * @throws ValidationException 當驗證失敗時
     * @return array 驗證通過的資料
     */
    public function validateOrFail(array $data, array $rules): array;

    /**
     * 檢查單一規則.
     *
     * @param mixed $value 要檢查的值
     * @param string $rule 驗證規則
     * @param array<string, mixed> $parameters 規則參數
     * @return bool 是否通過驗證
     */
    public function checkRule(mixed $value, string $rule, array $parameters = []): bool;

    /**
     * 添加自訂驗證規則.
     *
     * @param string $name 規則名稱
     * @param callable $callback 驗證回調函式
     */
    public function addRule(string $name, callable $callback): void;

    /**
     * 添加自訂錯誤訊息.
     *
     * @param string $rule 規則名稱
     * @param string $message 錯誤訊息
     */
    public function addMessage(string $rule, string $message): void;

    /**
     * 設定驗證失敗時是否立即停止.
     *
     * @param bool $stopOnFirstFailure 是否在第一個錯誤時停止
     */
    public function stopOnFirstFailure(bool $stopOnFirstFailure = true): self;
}
