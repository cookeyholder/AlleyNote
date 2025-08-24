<?php

declare(strict_types=1);

namespace App\Shared\Validation;

use Exception;
use Throwable;

/**
 * 驗證異常類
 *
 * 當驗證失敗時拋出的異常，包含詳細的錯誤資訊
 */
class ValidationException extends Exception
{
    private ValidationResult $validationResult;
    private array $errors;
    private array $failedRules;

    /**
     * @param ValidationResult $validationResult 驗證結果
     * @param string $message 異常訊息
     * @param int $code 異常代碼
     * @param Throwable|null $previous 前一個異常
     */
    public function __construct(
        ValidationResult $validationResult,
        string $message = '',
        int $code = 422,
        ?Throwable $previous = null
    ) {
        $this->validationResult = $validationResult;
        $this->errors = $validationResult->getErrors();
        $this->failedRules = $validationResult->getFailedRules();

        // 如果沒有提供訊息，使用第一個錯誤作為訊息
        if (empty($message)) {
            $message = $validationResult->getFirstError() ?? '驗證失敗';
        }

        parent::__construct($message, $code, $previous);
    }

    /**
     * 從錯誤陣列建立異常
     *
     * @param array $errors 錯誤陣列，格式為 ['field' => ['error1', 'error2']]
     * @param array $failedRules 失敗的規則
     * @param string $message 異常訊息
     * @return self
     */
    public static function fromErrors(
        array $errors,
        array $failedRules = [],
        string $message = ''
    ): self {
        $validationResult = ValidationResult::failure($errors, $failedRules);
        return new self($validationResult, $message);
    }

    /**
     * 從單一錯誤建立異常
     *
     * @param string $field 欄位名稱
     * @param string $error 錯誤訊息
     * @param string $rule 失敗的規則
     * @return self
     */
    public static function fromSingleError(string $field, string $error, string $rule = ''): self
    {
        $errors = [$field => [$error]];
        $failedRules = $rule ? [$field => [$rule]] : [];
        return self::fromErrors($errors, $failedRules, $error);
    }

    /**
     * 取得驗證結果
     *
     * @return ValidationResult
     */
    public function getValidationResult(): ValidationResult
    {
        return $this->validationResult;
    }

    /**
     * 取得所有錯誤訊息
     *
     * @return array 格式為 ['field' => ['error1', 'error2']]
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * 取得特定欄位的錯誤訊息
     *
     * @param string $field 欄位名稱
     * @return array
     */
    public function getFieldErrors(string $field): array
    {
        return $this->errors[$field] ?? [];
    }

    /**
     * 檢查特定欄位是否有錯誤
     *
     * @param string $field 欄位名稱
     * @return bool
     */
    public function hasFieldErrors(string $field): bool
    {
        return !empty($this->errors[$field]);
    }

    /**
     * 取得所有錯誤訊息的扁平陣列
     *
     * @return array
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
     * 取得第一個錯誤訊息
     *
     * @return string|null
     */
    public function getFirstError(): ?string
    {
        return $this->validationResult->getFirstError();
    }

    /**
     * 取得特定欄位的第一個錯誤訊息
     *
     * @param string $field 欄位名稱
     * @return string|null
     */
    public function getFirstFieldError(string $field): ?string
    {
        return $this->validationResult->getFirstFieldError($field);
    }

    /**
     * 取得失敗的規則
     *
     * @return array 格式為 ['field' => ['rule1', 'rule2']]
     */
    public function getFailedRules(): array
    {
        return $this->failedRules;
    }

    /**
     * 取得特定欄位失敗的規則
     *
     * @param string $field 欄位名稱
     * @return array
     */
    public function getFieldFailedRules(string $field): array
    {
        return $this->failedRules[$field] ?? [];
    }

    /**
     * 轉換為適用於 API 回應的陣列格式
     *
     * @return array
     */
    public function toApiResponse(): array
    {
        return [
            'success' => false,
            'message' => $this->getMessage(),
            'errors' => $this->errors,
            'failed_rules' => $this->failedRules,
        ];
    }

    /**
     * 轉換為適用於除錯的陣列格式
     *
     * @return array
     */
    public function toDebugArray(): array
    {
        return [
            'exception' => static::class,
            'message' => $this->getMessage(),
            'code' => $this->getCode(),
            'file' => $this->getFile(),
            'line' => $this->getLine(),
            'validation_result' => $this->validationResult->toArray(),
        ];
    }

    /**
     * 取得錯誤數量
     *
     * @return int
     */
    public function getErrorCount(): int
    {
        return count($this->getAllErrors());
    }

    /**
     * 取得受影響的欄位數量
     *
     * @return int
     */
    public function getAffectedFieldCount(): int
    {
        return count($this->errors);
    }

    /**
     * 檢查是否為特定規則的驗證失敗
     *
     * @param string $rule 規則名稱
     * @return bool
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
     * 檢查特定欄位是否因特定規則失敗
     *
     * @param string $field 欄位名稱
     * @param string $rule 規則名稱
     * @return bool
     */
    public function hasFieldFailedRule(string $field, string $rule): bool
    {
        return in_array($rule, $this->getFieldFailedRules($field), true);
    }
}
