<?php

declare(strict_types=1);

/**
 * Validator 類型註解修復腳本
 */

$filePath = __DIR__ . '/../app/Shared/Validation/Validator.php';
$content = file_get_contents($filePath);

// 修復模式
$patterns = [
    // validateMinLength
    [
        'search' => '/(\s+)\/\*\*\\n\s+\*\s+@param array<string, mixed> \$parameters\s+\*\/\s+private function validateMinLength\(mixed \$value, array \$parameters\): bool/',
        'replace' => '$1/**
     * 驗證最小長度
     * @param mixed $value 要驗證的值
     * @param array<string, mixed> $parameters 驗證參數
     * @return bool
     */
    private function validateMinLength(mixed $value, array $parameters): bool'
    ],

    // validateMaxLength
    [
        'search' => '/(\s+)\/\*\*\\n\s+\*\s+@param array<string, mixed> \$parameters\s+\*\/\s+private function validateMaxLength\(mixed \$value, array \$parameters\): bool/',
        'replace' => '$1/**
     * 驗證最大長度
     * @param mixed $value 要驗證的值
     * @param array<string, mixed> $parameters 驗證參數
     * @return bool
     */
    private function validateMaxLength(mixed $value, array $parameters): bool'
    ],

    // validateLength
    [
        'search' => '/(\s+)\/\*\*\\n\s+\*\s+@param array<string, mixed> \$parameters\s+\*\/\s+private function validateLength\(mixed \$value, array \$parameters\): bool/',
        'replace' => '$1/**
     * 驗證指定長度
     * @param mixed $value 要驗證的值
     * @param array<string, mixed> $parameters 驗證參數
     * @return bool
     */
    private function validateLength(mixed $value, array $parameters): bool'
    ],

    // validateBetween
    [
        'search' => '/(\s+)\/\*\*\\n\s+\*\s+@param array<string, mixed> \$parameters\s+\*\/\s+private function validateBetween\(mixed \$value, array \$parameters\): bool/',
        'replace' => '$1/**
     * 驗證數值範圍
     * @param mixed $value 要驗證的值
     * @param array<string, mixed> $parameters 驗證參數
     * @return bool
     */
    private function validateBetween(mixed $value, array $parameters): bool'
    ],

    // validateIn
    [
        'search' => '/(\s+)\/\*\*\\n\s+\*\s+@param array<string, mixed> \$parameters\s+\*\/\s+private function validateIn\(mixed \$value, array \$parameters\): bool/',
        'replace' => '$1/**
     * 驗證值在指定列表中
     * @param mixed $value 要驗證的值
     * @param array<string, mixed> $parameters 驗證參數
     * @return bool
     */
    private function validateIn(mixed $value, array $parameters): bool'
    ],

    // validateNotIn
    [
        'search' => '/(\s+)\/\*\*\\n\s+\*\s+@param array<string, mixed> \$parameters\s+\*\/\s+private function validateNotIn\(mixed \$value, array \$parameters\): bool/',
        'replace' => '$1/**
     * 驗證值不在指定列表中
     * @param mixed $value 要驗證的值
     * @param array<string, mixed> $parameters 驗證參數
     * @return bool
     */
    private function validateNotIn(mixed $value, array $parameters): bool'
    ],

    // validateRegex
    [
        'search' => '/(\s+)\/\*\*\\n\s+\*\s+@param array<string, mixed> \$parameters\s+\*\/\s+private function validateRegex\(mixed \$value, array \$parameters\): bool/',
        'replace' => '$1/**
     * 驗證正規表示式
     * @param mixed $value 要驗證的值
     * @param array<string, mixed> $parameters 驗證參數
     * @return bool
     */
    private function validateRegex(mixed $value, array $parameters): bool'
    ]
];

$changes = 0;
foreach ($patterns as $pattern) {
    $newContent = preg_replace($pattern['search'], $pattern['replace'], $content);
    if ($newContent && $newContent !== $content) {
        $content = $newContent;
        $changes++;
    }
}

// 同時修復一些簡單的缺少類型註解的方法
$simplePatterns = [
    // 修復沒有 PHPDoc 的方法
    '/(\s+)private function (validateConfirmed|validateDifferent|validateSame)\(mixed \$value, array \$allData, array \$parameters\): bool/' => '$1/**
     * 驗證欄位確認
     * @param mixed $value 要驗證的值
     * @param array<string, mixed> $allData 完整資料
     * @param array<string, mixed> $parameters 驗證參數
     * @return bool
     */
    private function $2(mixed $value, array $allData, array $parameters): bool',

    '/(\s+)private function getErrorMessage\(string \$field, string \$rule, array \$parameters = \[\]\): string/' => '$1/**
     * 取得錯誤訊息
     * @param string $field 欄位名稱
     * @param string $rule 規則名稱
     * @param array<string, mixed> $parameters 參數
     * @return string
     */
    private function getErrorMessage(string $field, string $rule, array $parameters = []): string',

    '/(\s+)private function replacePlaceholders\(string \$message, array \$parameters\): string/' => '$1/**
     * 替換訊息中的佔位符
     * @param string $message 訊息模板
     * @param array<string, mixed> $parameters 參數
     * @return string
     */
    private function replacePlaceholders(string $message, array $parameters): string'
];

foreach ($simplePatterns as $search => $replace) {
    $newContent = preg_replace($search, $replace, $content);
    if ($newContent && $newContent !== $content) {
        $content = $newContent;
        $changes++;
    }
}

file_put_contents($filePath, $content);

echo "✅ Validator.php 類型註解修復完成，共變更 $changes 項目\n";
