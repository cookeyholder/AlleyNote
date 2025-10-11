<?php

declare(strict_types=1);

namespace App\Shared\Exceptions;

use App\Shared\Validation\ValidationResult;
use Exception;
use Throwable;

// Import ValidationResult

class ValidationException extends Exception
{
    protected ValidationResult $validationResult; // Store the ValidationResult

    public function __construct(ValidationResult $validationResult, string $message = '', int $code = 422, ?Throwable $previous = null)
    {
        // If no message is provided, use the first error from ValidationResult
        if (empty($message)) {
            $message = $validationResult->getFirstError() ?? '驗證失敗';
        }
        parent::__construct($message, $code, $previous); // 使用提供的錯誤碼，預設 422
        $this->validationResult = $validationResult;
    }

    public function getValidationResult(): ValidationResult
    {
        return $this->validationResult;
    }

    // Static factory method for creating from an array of errors
    public static function fromErrors(array $errors, array|string $failedRulesOrMessage = '', string $message = ''): self
    {
        // Handle overloaded parameters
        if (is_array($failedRulesOrMessage)) {
            $failedRules = $failedRulesOrMessage;
            $validationResult = ValidationResult::failure($errors, $failedRules);
        } else {
            $message = $failedRulesOrMessage;
            $validationResult = ValidationResult::failure($errors);
        }

        return new self($validationResult, $message);
    }

    // Static factory method for creating from a single error
    public static function fromSingleError(string $field, string $error, string $rule = '', string $message = ''): self
    {
        $errors = [$field => [$error]];
        $failedRules = $rule ? [$field => [$rule]] : [];

        $validationResult = ValidationResult::failure($errors, $failedRules);

        return new self($validationResult, $message);
    }

    /**
     * 從多個欄位錯誤建立異常.
     *
     * @param array<string, array<string>> $errors 錯誤訊息陣列，格式：['field' => ['error1', 'error2']]
     * @param string $message 自訂錯誤訊息
     */
    public static function fromMultipleErrors(array $errors, string $message = ''): self
    {
        $validationResult = ValidationResult::failure($errors);

        return new self($validationResult, $message);
    }

    // Override getErrors to delegate to ValidationResult
    public function getErrors(): array
    {
        return $this->validationResult->getErrors();
    }

    // Get failed rules from ValidationResult
    public function getFailedRules(): array
    {
        return $this->validationResult->getFailedRules();
    }

    /**
     * 取得第一個錯誤訊息.
     */
    public function getFirstError(): ?string
    {
        return $this->validationResult->getFirstError();
    }

    /**
     * 轉換為 API 回應格式.
     */
    public function toApiResponse(): array
    {
        return [
            'success' => false,
            'message' => $this->getMessage(),
            'errors' => $this->validationResult->getErrors(),
            'failed_rules' => $this->validationResult->getFailedRules(),
        ];
    }

    /**
     * 取得除錯資訊.
     */
    public function toDebugArray(): array
    {
        return [
            'message' => $this->getMessage(),
            'code' => $this->getCode(),
            'file' => $this->getFile(),
            'line' => $this->getLine(),
            'trace' => $this->getTrace(),
            'validation_result' => $this->validationResult->toArray(),
        ];
    }
}
