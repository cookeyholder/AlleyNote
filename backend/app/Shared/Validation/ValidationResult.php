<?php

declare(strict_types=1);

namespace App\Shared\Validation;

use JsonSerializable;

/**
 * 驗證結果類.
 *
 * 封裝驗證操作的結果，包含驗證狀態、錯誤訊息和清理後的資料
 */
class ValidationResult implements JsonSerializable
{
    private bool $isValid;

    /** @var array<string, array<string>> */
    private array $errors;

    /** @var array<string, mixed> */
    private array $validatedData;

    /** @var array<string, array<string>> */
    private array $failedRules;

    /**
     * @param bool $isValid 是否驗證通過
     * @param array<string, array<string>> $errors 錯誤訊息陣列，格式為 ['field' => ['error1', 'error2']]
     * @param array<string, mixed> $validatedData 驗證通過的資料
     * @param array<string, array<string>> $failedRules 失敗的規則，格式為 ['field' => ['rule1', 'rule2']]
     */
    public function __construct(
        bool $isValid,
        array $errors = [],
        array $validatedData = [],
        array $failedRules = [],
    ) {
        $this->isValid = $isValid;
        $this->errors = $errors;
        $this->validatedData = $validatedData;
        $this->failedRules = $failedRules;
    }

    /**
     * 建立驗證成功的結果.
     *
     * @param array<string, mixed> $validatedData 驗證通過的資料
     */
    public static function success(array $validatedData): self
    {
        return new self(true, [], $validatedData, []);
    }

    /**
     * 建立驗證失敗的結果.
     *
     * @param array<string, array<string>> $errors 錯誤訊息
     * @param array<string, array<string>> $failedRules 失敗的規則
     */
    public static function failure(array $errors, array $failedRules = []): self
    {
        return new self(false, $errors, [], $failedRules);
    }

    /**
     * 檢查驗證是否通過.
     */
    public function isValid(): bool
    {
        return $this->isValid;
    }

    /**
     * 檢查驗證是否失敗.
     */
    public function isInvalid(): bool
    {
        return !$this->isValid;
    }

    /**
     * 取得所有錯誤訊息.
     */
    /**
     * @return array<string, array<string>>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * 取得特定欄位的錯誤訊息.
     *
     * @param string $field 欄位名稱
     */
    /**
     * @return array<string>
     */
    public function getFieldErrors(string $field): array
    {
        return (array) ($this->errors[$field] ?? []);
    }

    /**
     * 檢查特定欄位是否有錯誤.
     *
     * @param string $field 欄位名稱
     */
    public function hasFieldErrors(string $field): bool
    {
        return !empty($this->errors[$field]);
    }

    /**
     * 取得第一個錯誤訊息.
     */
    public function getFirstError(): ?string
    {
        foreach ($this->errors as $fieldErrors) {
            if (!empty($fieldErrors)) {
                return $fieldErrors[0];
            }
        }

        return null;
    }

    /**
     * 取得特定欄位的第一個錯誤訊息.
     *
     * @param string $field 欄位名稱
     */
    public function getFirstFieldError(string $field): ?string
    {
        $fieldErrors = $this->getFieldErrors($field);

        return !empty($fieldErrors) ? $fieldErrors[0] : null;
    }

    /**
     * 取得所有錯誤訊息的扁平陣列.
     */
    /**
     * @return array<string>
     */
    public function getAllErrors(): array
    {
        $allErrors = [];
        foreach ($this->errors as $fieldErrors) {
            $allErrors = array_merge($allErrors, $fieldErrors);
        }

        return $allErrors;
    }

    /**
     * 取得驗證通過的資料.
     */
    /**
     * @return array<string, mixed>
     */
    public function getValidatedData(): array
    {
        return $this->validatedData;
    }

    /**
     * 取得特定欄位的驗證通過資料.
     *
     * @param string $field 欄位名稱
     * @param mixed $default 預設值
     */
    public function getValidatedField(string $field, mixed $default = null): mixed
    {
        return $this->validatedData[$field] ?? $default;
    }

    /**
     * 取得失敗的規則.
     */
    /**
     * @return array<string, array<string>>
     */
    public function getFailedRules(): array
    {
        return $this->failedRules;
    }

    /**
     * 取得特定欄位失敗的規則.
     *
     * @param string $field 欄位名稱
     */
    /**
     * @return array<string>
     */
    public function getFieldFailedRules(string $field): array
    {
        return (array) ($this->failedRules[$field] ?? []);
    }

    /**
     * 新增錯誤訊息.
     *
     * @param string $field 欄位名稱
     * @param string $error 錯誤訊息
     */
    public function addError(string $field, string $error): self
    {
        if (!isset($this->errors[$field])) {
            $this->errors[$field] = [];
        }
        $this->errors[$field][] = $error;
        $this->isValid = false;

        return $this;
    }

    /**
     * 新增失敗的規則.
     *
     * @param string $field 欄位名稱
     * @param string $rule 規則名稱
     */
    public function addFailedRule(string $field, string $rule): self
    {
        if (!isset($this->failedRules[$field])) {
            $this->failedRules[$field] = [];
        }
        $this->failedRules[$field][] = $rule;

        return $this;
    }

    /**
     * 合併另一個驗證結果.
     *
     * @param ValidationResult $other 另一個驗證結果
     */
    public function merge(ValidationResult $other): self
    {
        $this->isValid = $this->isValid && $other->isValid();

        foreach ($other->getErrors() as $field => $errors) {
            foreach ($errors as $error) {
                $this->addError($field, $error);
            }
        }

        foreach ($other->getFailedRules() as $field => $rules) {
            foreach ($rules as $rule) {
                $this->addFailedRule($field, $rule);
            }
        }

        $this->validatedData = array_merge($this->validatedData, $other->getValidatedData());

        return $this;
    }

    /**
     * 取得總錯誤數量.
     */
    public function getErrorCount(): int
    {
        return count($this->getAllErrors());
    }

    /**
     * 取得受影響的欄位數量 (有錯誤的欄位).
     */
    public function getAffectedFieldCount(): int
    {
        return count($this->errors);
    }

    /**
     * 檢查是否有特定規則的驗證失敗.
     *
     * @param string $rule 規則名稱
     */
    public function hasFailedRule(string $rule): bool
    {
        foreach ($this->failedRules as $fieldRules) {
            if (in_array($rule, $fieldRules, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * 檢查特定欄位是否因特定規則失敗.
     *
     * @param string $field 欄位名稱
     * @param string $rule 規則名稱
     */
    public function hasFieldFailedRule(string $field, string $rule): bool
    {
        return isset($this->failedRules[$field]) && in_array($rule, $this->failedRules[$field], true);
    }

    /**
     * 轉換為陣列格式.
     */
    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'is_valid' => $this->isValid,
            'errors' => $this->errors,
            'validated_data' => $this->validatedData,
            'failed_rules' => $this->failedRules,
        ];
    }

    /**
     * 實作 JsonSerializable 介面.
     */
    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * 轉換為字串格式（用於除錯）.
     */
    public function __toString(): string
    {
        if ($this->isValid) {
            return 'Validation passed with ' . count($this->validatedData) . ' fields';
        }

        $errorCount = count($this->getAllErrors());

        return "Validation failed with {$errorCount} errors: " . implode(', ', $this->getAllErrors());
    }
}
