<?php

declare(strict_types=1);

namespace App\Shared\Validation;

use App\Shared\Contracts\ValidatorInterface;

/**
 * 主要驗證器類
 *
 * 提供完整的資料驗證功能，支援多種驗證規則和自訂規則
 */
class Validator implements ValidatorInterface
{
    private array $customRules = [];
    private array $customMessages = [];
    private bool $stopOnFirstFailure = false;
    private array $defaultMessages = [
        'required' => '欄位 :field 為必填項目',
        'required_if' => '當 :other 為 :value 時，欄位 :field 為必填項目',
        'string' => '欄位 :field 必須是字串',
        'integer' => '欄位 :field 必須是整數',
        'numeric' => '欄位 :field 必須是數字',
        'boolean' => '欄位 :field 必須是布林值',
        'array' => '欄位 :field 必須是陣列',
        'email' => '欄位 :field 必須是有效的電子郵件地址',
        'url' => '欄位 :field 必須是有效的 URL',
        'ip' => '欄位 :field 必須是有效的 IP 地址',
        'date' => '欄位 :field 必須是有效的日期',
        'datetime' => '欄位 :field 必須是有效的日期時間',
        'min' => '欄位 :field 不能少於 :min',
        'max' => '欄位 :field 不能超過 :max',
        'min_length' => '欄位 :field 長度不能少於 :min 個字元',
        'max_length' => '欄位 :field 長度不能超過 :max 個字元',
        'length' => '欄位 :field 長度必須是 :length 個字元',
        'between' => '欄位 :field 必須介於 :min 和 :max 之間',
        'in' => '欄位 :field 必須是以下值之一：:values',
        'not_in' => '欄位 :field 不能是以下值之一：:values',
        'regex' => '欄位 :field 格式不正確',
        'alpha' => '欄位 :field 只能包含字母',
        'alpha_num' => '欄位 :field 只能包含字母和數字',
        'alpha_dash' => '欄位 :field 只能包含字母、數字、破折號和底線',
        'unique' => '欄位 :field 的值已存在',
        'exists' => '欄位 :field 的值不存在',
        'confirmed' => '欄位 :field 確認不匹配',
        'different' => '欄位 :field 必須與 :other 不同',
        'same' => '欄位 :field 必須與 :other 相同',
        'file' => '欄位 :field 必須是檔案',
        'image' => '欄位 :field 必須是圖片檔案',
        'mimes' => '欄位 :field 必須是以下類型之一：:types',
        'size' => '欄位 :field 大小必須為 :size',
        'max_file_size' => '欄位 :field 檔案大小不能超過 :max KB',
    ];

    public function validate(array $data, array $rules): ValidationResult
    {
        $errors = [];
        $failedRules = [];
        $validatedData = [];

        foreach ($rules as $field => $ruleSet) {
            if (is_string($ruleSet)) {
                $ruleSet = explode('|', $ruleSet);
            }

            $fieldValue = $data[$field] ?? null;
            $fieldErrors = [];
            $fieldFailedRules = [];

            foreach ($ruleSet as $rule) {
                $ruleName = $rule;
                $parameters = [];

                // 解析帶參數的規則 (例如: min:5, between:1,10)
                if (strpos($rule, ':') !== false) {
                    [$ruleName, $paramString] = explode(':', $rule, 2);
                    $parameters = explode(',', $paramString);
                }

                // 檢查規則
                if (!$this->checkRule($fieldValue, $ruleName, $parameters, $data, $field)) {
                    $errorMessage = $this->getErrorMessage($field, $ruleName, $parameters, $fieldValue);
                    $fieldErrors[] = $errorMessage;
                    $fieldFailedRules[] = $ruleName;

                    if ($this->stopOnFirstFailure) {
                        break;
                    }
                }
            }

            if (empty($fieldErrors)) {
                $validatedData[$field] = $fieldValue;
            } else {
                $errors[$field] = $fieldErrors;
                $failedRules[$field] = $fieldFailedRules;
            }
        }

        $isValid = empty($errors);

        return new ValidationResult($isValid, $errors, $validatedData, $failedRules);
    }

    public function validateOrFail(array $data, array $rules): array
    {
        $result = $this->validate($data, $rules);

        if ($result->isInvalid()) {
            throw new ValidationException($result);
        }

        return $result->getValidatedData();
    }

    public function checkRule(mixed $value, string $rule, array $parameters = [], array $allData = [], string $currentField = ''): bool
    {
        // 檢查自訂規則
        if (isset($this->customRules[$rule])) {
            return call_user_func($this->customRules[$rule], $value, $parameters);
        }

        // 內建規則
        return match ($rule) {
            'required' => $this->validateRequired($value),
            'required_if' => $this->validateRequiredIf($value, $parameters),
            'string' => $this->validateString($value),
            'integer', 'int' => $this->validateInteger($value),
            'numeric' => $this->validateNumeric($value),
            'boolean', 'bool' => $this->validateBoolean($value),
            'array' => $this->validateArray($value),
            'email' => $this->validateEmail($value),
            'url' => $this->validateUrl($value),
            'ip' => $this->validateIp($value),
            'date' => $this->validateDate($value),
            'datetime' => $this->validateDateTime($value),
            'min' => $this->validateMin($value, $parameters),
            'max' => $this->validateMax($value, $parameters),
            'min_length' => $this->validateMinLength($value, $parameters),
            'max_length' => $this->validateMaxLength($value, $parameters),
            'length' => $this->validateLength($value, $parameters),
            'between' => $this->validateBetween($value, $parameters),
            'in' => $this->validateIn($value, $parameters),
            'not_in' => $this->validateNotIn($value, $parameters),
            'regex' => $this->validateRegex($value, $parameters),
            'alpha' => $this->validateAlpha($value),
            'alpha_num' => $this->validateAlphaNum($value),
            'alpha_dash' => $this->validateAlphaDash($value),
            'confirmed' => $this->validateConfirmed($value, $parameters, $allData, $currentField),
            'different' => $this->validateDifferent($value, $parameters, $allData),
            'same' => $this->validateSame($value, $parameters, $allData),
            default => true, // 未知規則預設為通過
        };
    }

    public function addRule(string $name, callable $callback): void
    {
        $this->customRules[$name] = $callback;
    }

    public function addMessage(string $rule, string $message): void
    {
        $this->customMessages[$rule] = $message;
    }

    public function stopOnFirstFailure(bool $stopOnFirstFailure = true): self
    {
        $this->stopOnFirstFailure = $stopOnFirstFailure;
        return $this;
    }

    // 驗證規則實作

    private function validateRequired(mixed $value): bool
    {
        if ($value === null) {
            return false;
        }

        if (is_string($value) && trim($value) === '') {
            return false;
        }

        if (is_array($value) && empty($value)) {
            return false;
        }

        return true;
    }

    private function validateRequiredIf(mixed $value, array $parameters): bool
    {
        if (count($parameters) < 2) {
            return true;
        }

        // TODO: 實作條件式必填驗證
        // 需要訪問整個資料陣列才能實作
        return true;
    }

    private function validateString(mixed $value): bool
    {
        return is_string($value);
    }

    private function validateInteger(mixed $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_INT) !== false;
    }

    private function validateNumeric(mixed $value): bool
    {
        return is_numeric($value);
    }

    private function validateBoolean(mixed $value): bool
    {
        return is_bool($value) || in_array($value, [0, 1, '0', '1', 'true', 'false', 'on', 'yes'], true);
    }

    private function validateArray(mixed $value): bool
    {
        return is_array($value);
    }

    private function validateEmail(mixed $value): bool
    {
        if (!is_string($value)) {
            return false;
        }

        return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
    }

    private function validateUrl(mixed $value): bool
    {
        if (!is_string($value)) {
            return false;
        }

        return filter_var($value, FILTER_VALIDATE_URL) !== false;
    }

    private function validateIp(mixed $value): bool
    {
        if (!is_string($value)) {
            return false;
        }

        return filter_var($value, FILTER_VALIDATE_IP) !== false;
    }

    private function validateDate(mixed $value): bool
    {
        if (!is_string($value)) {
            return false;
        }

        $date = \DateTime::createFromFormat('Y-m-d', $value);
        return $date && $date->format('Y-m-d') === $value;
    }

    private function validateDateTime(mixed $value): bool
    {
        if (!is_string($value)) {
            return false;
        }

        // 支援多種日期時間格式
        $formats = [
            'Y-m-d H:i:s',
            'Y-m-d\TH:i:s',
            'Y-m-d\TH:i:s\Z',
            \DateTime::RFC3339,
            \DateTime::ISO8601,
        ];

        foreach ($formats as $format) {
            $date = \DateTime::createFromFormat($format, $value);
            if ($date && $date->format($format) === $value) {
                return true;
            }
        }

        return false;
    }

    private function validateMin(mixed $value, array $parameters): bool
    {
        if (empty($parameters)) {
            return true;
        }

        $min = (float) $parameters[0];

        if (is_numeric($value)) {
            return (float) $value >= $min;
        }

        if (is_string($value)) {
            return mb_strlen($value) >= $min;
        }

        if (is_array($value)) {
            return count($value) >= $min;
        }

        return true;
    }

    private function validateMax(mixed $value, array $parameters): bool
    {
        if (empty($parameters)) {
            return true;
        }

        $max = (float) $parameters[0];

        if (is_numeric($value)) {
            return (float) $value <= $max;
        }

        if (is_string($value)) {
            return mb_strlen($value) <= $max;
        }

        if (is_array($value)) {
            return count($value) <= $max;
        }

        return true;
    }

    private function validateMinLength(mixed $value, array $parameters): bool
    {
        if (empty($parameters) || !is_string($value)) {
            return true;
        }

        $minLength = (int) $parameters[0];
        return mb_strlen($value) >= $minLength;
    }

    private function validateMaxLength(mixed $value, array $parameters): bool
    {
        if (empty($parameters) || !is_string($value)) {
            return true;
        }

        $maxLength = (int) $parameters[0];
        return mb_strlen($value) <= $maxLength;
    }

    private function validateLength(mixed $value, array $parameters): bool
    {
        if (empty($parameters) || !is_string($value)) {
            return true;
        }

        $length = (int) $parameters[0];
        return mb_strlen($value) === $length;
    }

    private function validateBetween(mixed $value, array $parameters): bool
    {
        if (count($parameters) < 2) {
            return true;
        }

        $min = (float) $parameters[0];
        $max = (float) $parameters[1];

        if (is_numeric($value)) {
            $numValue = (float) $value;
            return $numValue >= $min && $numValue <= $max;
        }

        if (is_string($value)) {
            $length = mb_strlen($value);
            return $length >= $min && $length <= $max;
        }

        if (is_array($value)) {
            $count = count($value);
            return $count >= $min && $count <= $max;
        }

        return true;
    }

    private function validateIn(mixed $value, array $parameters): bool
    {
        return in_array($value, $parameters, true);
    }

    private function validateNotIn(mixed $value, array $parameters): bool
    {
        return !in_array($value, $parameters, true);
    }

    private function validateRegex(mixed $value, array $parameters): bool
    {
        if (empty($parameters) || !is_string($value)) {
            return true;
        }

        $pattern = $parameters[0];
        return preg_match($pattern, $value) === 1;
    }

    private function validateAlpha(mixed $value): bool
    {
        if (!is_string($value)) {
            return false;
        }

        return preg_match('/^[a-zA-Z\p{L}]+$/u', $value) === 1;
    }

    private function validateAlphaNum(mixed $value): bool
    {
        if (!is_string($value)) {
            return false;
        }

        return preg_match('/^[a-zA-Z0-9\p{L}\p{N}]+$/u', $value) === 1;
    }

    private function validateAlphaDash(mixed $value): bool
    {
        if (!is_string($value)) {
            return false;
        }

        return preg_match('/^[a-zA-Z0-9\p{L}\p{N}_-]+$/u', $value) === 1;
    }

    private function validateConfirmed(mixed $value, array $parameters, array $allData = [], string $currentField = ''): bool
    {
        // 預設確認欄位名稱為 field_confirmation
        $confirmationField = $currentField . '_confirmation';

        // 如果有提供參數，使用參數作為確認欄位名稱
        if (!empty($parameters)) {
            $confirmationField = $parameters[0];
        }

        // 檢查確認欄位是否存在且值相等
        if (!isset($allData[$confirmationField])) {
            return false;
        }

        return $value === $allData[$confirmationField];
    }

    private function validateDifferent(mixed $value, array $parameters, array $allData = []): bool
    {
        if (empty($parameters)) {
            return true;
        }

        $otherField = $parameters[0];
        if (!isset($allData[$otherField])) {
            return true;
        }

        return $value !== $allData[$otherField];
    }

    private function validateSame(mixed $value, array $parameters, array $allData = []): bool
    {
        if (empty($parameters)) {
            return true;
        }

        $otherField = $parameters[0];
        if (!isset($allData[$otherField])) {
            return false;
        }

        return $value === $allData[$otherField];
    }

    // 錯誤訊息處理

    private function getErrorMessage(string $field, string $rule, array $parameters, mixed $value): string
    {
        // 檢查自訂訊息
        $customKey = "{$field}.{$rule}";
        if (isset($this->customMessages[$customKey])) {
            return $this->replacePlaceholders($this->customMessages[$customKey], $field, $parameters, $value);
        }

        if (isset($this->customMessages[$rule])) {
            return $this->replacePlaceholders($this->customMessages[$rule], $field, $parameters, $value);
        }

        // 使用預設訊息
        $message = $this->defaultMessages[$rule] ?? "欄位 {$field} 驗證失敗";

        return $this->replacePlaceholders($message, $field, $parameters, $value);
    }

    private function replacePlaceholders(string $message, string $field, array $parameters, mixed $value): string
    {
        $replacements = [
            ':field' => $field,
            ':value' => is_scalar($value) ? (string) $value : gettype($value),
        ];

        // 添加參數替換
        if (!empty($parameters)) {
            $replacements[':min'] = $parameters[0] ?? '';
            $replacements[':max'] = $parameters[1] ?? $parameters[0] ?? '';
            $replacements[':length'] = $parameters[0] ?? '';
            $replacements[':values'] = implode(', ', $parameters);
            $replacements[':types'] = implode(', ', $parameters);
            $replacements[':size'] = $parameters[0] ?? '';
            $replacements[':other'] = $parameters[0] ?? '';
        }

        return str_replace(array_keys($replacements), array_values($replacements), $message);
    }
}
