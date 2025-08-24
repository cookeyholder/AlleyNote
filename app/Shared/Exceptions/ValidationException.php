<?php

declare(strict_types=1);

namespace App\Shared\Exceptions;

use Exception;
use App\Shared\Validation\ValidationResult; // Import ValidationResult

class ValidationException extends Exception
{
    protected ValidationResult $validationResult; // Store the ValidationResult

    public function __construct(ValidationResult $validationResult, string $message = '')
    {
        // If no message is provided, use the first error from ValidationResult
        if (empty($message)) {
            $message = $validationResult->getFirstError() ?? 'Validation failed.';
        }
        parent::__construct($message);
        $this->validationResult = $validationResult;
    }

    public function getValidationResult(): ValidationResult
    {
        return $this->validationResult;
    }

    // Static factory method for creating from an array of errors
    public static function fromErrors(array $errors, string $message = ''): self
    {
        $validationResult = ValidationResult::failure($errors);
        return new self($validationResult, $message);
    }

    // Static factory method for creating from a single error
    public static function fromSingleError(string $field, string $error, string $message = ''): self
    {
        $validationResult = ValidationResult::failure([$field => [$error]]);
        return new self($validationResult, $message);
    }

    // Override getErrors to delegate to ValidationResult
    public function getErrors(): array
    {
        return $this->validationResult->getErrors();
    }

    /**
     * 轉換為 API 回應格式
     *
     * @return array
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
     * 取得除錯資訊
     *
     * @return array
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
