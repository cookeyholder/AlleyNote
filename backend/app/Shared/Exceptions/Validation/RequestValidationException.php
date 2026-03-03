<?php

declare(strict_types=1);

namespace App\Shared\Exceptions\Validation;

use App\Shared\Exceptions\ValidationException;

class RequestValidationException extends ValidationException
{
    /**
     * @param array<string, mixed> $errors
     */
    public function __construct(string $message = '', array $errors = [])
    {
        if (empty($message) && !empty($errors)) {
            $message = '請求資料驗證失敗';
        }

        // 呼叫 ValidationException::fromErrors 來建立 ValidationResult
        $exception = self::fromErrors($errors, $message);
        parent::__construct($exception->getValidationResult(), $exception->getMessage());
    }

    public static function invalidJson(): self
    {
        return new self('請求資料格式錯誤，必須為有效的 JSON 格式');
    }

    /**
     * @param array<int|string, mixed> $fields
     */
    public static function missingRequiredFields(array $fields): self
    {
        $errors = [];
        foreach ($fields as $field) {
            if (is_string($field)) {
                $errors[$field] = "欄位 '{$field}' 為必填項目";
            }
        }

        return new self('缺少必要欄位', $errors);
    }

    public static function invalidFieldType(string $field, string $expectedType, mixed $actualValue): self
    {
        $actualType = gettype($actualValue);
        $errors = [$field => "欄位 '{$field}' 應為 {$expectedType} 類型，實際為 {$actualType}"];

        return new self('欄位類型錯誤', $errors);
    }

    public static function fieldTooLong(string $field, int $maxLength, int $actualLength): self
    {
        $errors = [$field => "欄位 '{$field}' 長度不能超過 {$maxLength} 個字元，目前為 {$actualLength} 個字元"];

        return new self('欄位長度超出限制', $errors);
    }

    public static function fieldTooShort(string $field, int $minLength, int $actualLength): self
    {
        $errors = [$field => "欄位 '{$field}' 長度不能少於 {$minLength} 個字元，目前為 {$actualLength} 個字元"];

        return new self('欄位長度不足', $errors);
    }

    public static function invalidEmail(string $field, string $email): self
    {
        $errors = [$field => "'{$email}' 不是有效的電子郵件格式"];

        return new self('電子郵件格式錯誤', $errors);
    }

    public static function invalidUrl(string $field, string $url): self
    {
        $errors = [$field => "'{$url}' 不是有效的 URL 格式"];

        return new self('URL 格式錯誤', $errors);
    }

    public static function invalidDate(string $field, string $date): self
    {
        $errors = [$field => "'{$date}' 不是有效的日期格式"];

        return new self('日期格式錯誤', $errors);
    }

    public static function valueNotInList(string $field, mixed $value, array $allowedValues): self
    {
        $valueStr = is_scalar($value) ? (string) $value : gettype($value);
        $allowedList = implode(', ', $allowedValues);
        $errors = [$field => "'{$valueStr}' 不在允許的值清單中：{$allowedList}"];

        return new self('欄位值不在允許範圍內', $errors);
    }

    public static function numericRangeError(string $field, mixed $value, mixed $min = null, mixed $max = null): self
    {
        $valueStr = is_numeric($value) ? (string) $value : gettype($value);
        $minStr = $min !== null && is_numeric($min) ? (string) $min : '';
        $maxStr = $max !== null && is_numeric($max) ? (string) $max : '';

        $message = "欄位 '{$field}' 的值 '{$valueStr}' 超出允許範圍";

        if ($minStr !== '' && $maxStr !== '') {
            $message .= "（範圍：{$minStr} - {$maxStr}）";
        } elseif ($minStr !== '') {
            $message .= "（最小值：{$minStr}）";
        } elseif ($maxStr !== '') {
            $message .= "（最大值：{$maxStr}）";
        }

        $errors = [$field => $message];

        return new self('數值範圍錯誤', $errors);
    }

    public static function duplicateValue(string $field, mixed $value): self
    {
        $valueStr = is_scalar($value) ? (string) $value : gettype($value);
        $errors = [$field => "值 '{$valueStr}' 已存在，不能重複"];

        return new self('值重複', $errors);
    }

    public static function invalidFileType(string $field, string $actualType, array $allowedTypes): self
    {
        $allowedList = implode(', ', $allowedTypes);
        $errors = [$field => "檔案類型 '{$actualType}' 不被支援，允許的類型：{$allowedList}"];

        return new self('檔案類型不支援', $errors);
    }

    public static function fileTooLarge(string $field, int $actualSize, int $maxSize): self
    {
        $actualSizeMB = round($actualSize / 1024 / 1024, 2);
        $maxSizeMB = round($maxSize / 1024 / 1024, 2);
        $errors = [$field => "檔案大小 {$actualSizeMB}MB 超過限制 {$maxSizeMB}MB"];

        return new self('檔案大小超出限制', $errors);
    }

    /**
     * @param array<string, mixed> $errors
     */
    public static function customValidation(array $errors): self
    {
        return new self('自定義驗證失敗', $errors);
    }
}
