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
     * @param array<string, mixed> $data 要驗證的資料
     * @return ValidationResult 驗證結果
     */
    public function validate(array $data, /** @var array<string, mixed> */ array $rules/** @var array<string, mixed> */): ValidationResult;

    /**
     * 快速驗證資料，失敗時拋出異常.
     * @param array<string, mixed> $data 要驗證的資料
     * @throws ValidationException 當驗證失敗時
     * @return array<string, mixed><string, mixed>
     */
    public function validateOrFail(array $data, /** @var array<string, mixed> */ array $rules/** @var array<string, mixed> */): array;

    /**
     * 檢查單一規則.
     * @param mixed $value 要檢查的值
     * @param array<string, mixed> $parameters 規則參數
     * @return bool 是否通過驗證
     */
    public function checkRule(mixed $value, string $rule, /** @var array<string, mixed> */ array $parameters/** @var array<string, mixed> */ = []): bool;

    /**
     * 添加自訂驗證規則.
     * @param string $name 規則名稱
     */
    public function addRule(string $name, callable $callback): void;

    /**
     * 添加自訂錯誤訊息.
     * @param string $rule 規則名稱
     */
    public function addMessage(string $rule, string $message): void;

    /**
     * 設定驗證失敗時是否立即停止.
     * @param bool $stopOnFirstFailure 是否在第一個錯誤時停止
     */
    public function stopOnFirstFailure(bool $stopOnFirstFailure = true): self;
}
