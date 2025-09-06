<?php

declare(strict_types=1);

/**
 * 🎯 專業級 PHPStan Level 10 修復工具
 *
 * 基於實際錯誤分析，專門處理：
 * 1. missingType.iterableValue - Array 泛型型別問題
 * 2. return.type - 返回型別問題
 * 3. argument.type - 參數型別問題
 */
class ProfessionalPhpstanFixer
{
    private array $processedFiles = [];
    private array $successfulFixes = [];
    private bool $dryRun = false;

    public function __construct(bool $dryRun = false)
    {
        $this->dryRun = $dryRun;
    }

    public function run(): void
    {
        echo "🎯 專業級 PHPStan Level 10 修復工具\n";
        echo "模式：" . ($this->dryRun ? '預覽模式' : '修復模式') . "\n\n";

        // 先從 ValidationResult 開始，這是我們最了解的檔案
        $this->fixValidationResult();

        echo "\n";
        $this->printSummary();
    }

    private function fixValidationResult(): void
    {
        echo "🔧 修復 ValidationResult.php (核心驗證類別)\n";

        $filepath = '/var/www/html/app/Shared/Validation/ValidationResult.php';
        if (!file_exists($filepath)) {
            echo "  ❌ 檔案不存在: $filepath\n";
            return;
        }

        $content = file_get_contents($filepath);
        if (!$content) {
            echo "  ❌ 無法讀取檔案\n";
            return;
        }

        $originalContent = $content;

        // 修復屬性的 Array 泛型型別
        $content = $this->fixArrayProperties($content);

        // 修復方法返回型別的 Array 泛型
        $content = $this->fixMethodReturnTypes($content);

        // 修復型別轉換問題
        $content = $this->fixTypeCasting($content);

        if ($content !== $originalContent) {
            if (!$this->dryRun) {
                file_put_contents($filepath, $content);
                echo "  ✅ 成功修復並保存\n";
            } else {
                echo "  ✅ 預覽：發現可修復的問題\n";
            }
            $this->successfulFixes[] = 'ValidationResult.php';
        } else {
            echo "  ℹ️  沒有需要修復的內容\n";
        }
    }

    private function fixArrayProperties(string $content): string
    {
        // 修復 $errors 屬性 - 應該是 array<string, array<string>>
        $content = str_replace(
            'private array $errors;',
            '/** @var array<string, array<string>> */
    private array $errors;',
            $content
        );

        // 修復 $validatedData 屬性 - 應該是 array<string, mixed>
        $content = str_replace(
            'private array $validatedData;',
            '/** @var array<string, mixed> */
    private array $validatedData;',
            $content
        );

        // 修復 $failedRules 屬性 - 應該是 array<string, array<string>>
        $content = str_replace(
            'private array $failedRules;',
            '/** @var array<string, array<string>> */
    private array $failedRules;',
            $content
        );

        return $content;
    }

    private function fixMethodReturnTypes(string $content): string
    {
        // 修復 getErrors 方法返回型別
        $content = str_replace(
            'public function getErrors(): array',
            '/**
     * @return array<string, array<string>>
     */
    public function getErrors(): array',
            $content
        );

        // 修復 getFieldErrors 方法返回型別
        $content = str_replace(
            'public function getFieldErrors(string $field): array',
            '/**
     * @return array<string>
     */
    public function getFieldErrors(string $field): array',
            $content
        );

        // 修復 getAllErrors 方法返回型別
        $content = str_replace(
            'public function getAllErrors(): array',
            '/**
     * @return array<string>
     */
    public function getAllErrors(): array',
            $content
        );

        // 修復 getValidatedData 方法返回型別
        $content = str_replace(
            'public function getValidatedData(): array',
            '/**
     * @return array<string, mixed>
     */
    public function getValidatedData(): array',
            $content
        );

        // 修復 getFailedRules 方法返回型別
        $content = str_replace(
            'public function getFailedRules(): array',
            '/**
     * @return array<string, array<string>>
     */
    public function getFailedRules(): array',
            $content
        );

        // 修復 getFieldFailedRules 方法返回型別
        $content = str_replace(
            'public function getFieldFailedRules(string $field): array',
            '/**
     * @return array<string>
     */
    public function getFieldFailedRules(string $field): array',
            $content
        );

        // 修復 toArray 方法返回型別
        $content = str_replace(
            'public function toArray(): array',
            '/**
     * @return array<string, mixed>
     */
    public function toArray(): array',
            $content
        );

        // 修復 jsonSerialize 方法返回型別
        $content = str_replace(
            'public function jsonSerialize(): array',
            '/**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array',
            $content
        );

        return $content;
    }

    private function fixTypeCasting(string $content): string
    {
        // 修復 getFieldErrors 方法的返回語句
        $content = preg_replace(
            '/return \$this->errors\[\$field\] \?\? \[\];/',
            'return (array)($this->errors[$field] ?? []);',
            $content
        );

        // 修復 getFirstError 方法
        $content = preg_replace(
            '/return \$this->getAllErrors\(\)\[0\] \?\? null;/',
            '$allErrors = $this->getAllErrors();
        return $allErrors[0] ?? null;',
            $content
        );

        // 修復 getFirstFieldError 方法
        $content = preg_replace(
            '/return \$this->getFieldErrors\(\$field\)\[0\] \?\? null;/',
            '$fieldErrors = $this->getFieldErrors($field);
        return $fieldErrors[0] ?? null;',
            $content
        );

        // 修復 getFieldFailedRules 方法的返回語句
        $content = preg_replace(
            '/return \$this->failedRules\[\$field\] \?\? \[\];/',
            'return (array)($this->failedRules[$field] ?? []);',
            $content
        );

        // 修復 array_merge 參數
        $content = preg_replace(
            '/return array_merge\(\.\.\.\$this->errors\);/',
            'return array_merge(...array_values($this->errors));',
            $content
        );

        // 修復 mergeWith 方法中的 foreach 迴圈
        $content = preg_replace(
            '/foreach \(\$other->getErrors\(\) as \$field => \$errors\) \{/',
            'foreach ($other->getErrors() as $field => $errors) {
            if (!is_array($errors)) continue;',
            $content
        );

        $content = preg_replace(
            '/foreach \(\$other->getFailedRules\(\) as \$field => \$rules\) \{/',
            'foreach ($other->getFailedRules() as $field => $rules) {
            if (!is_array($rules)) continue;',
            $content
        );

        // 修復 in_array 調用
        $content = preg_replace(
            '/in_array\(\$field, \$this->errors\[\$field\]\)/',
            'in_array($field, (array)($this->errors[$field] ?? []))',
            $content
        );

        $content = preg_replace(
            '/in_array\(\$rule, \$this->failedRules\[\$field\]\)/',
            'in_array($rule, (array)($this->failedRules[$field] ?? []))',
            $content
        );

        return $content;
    }

    private function printSummary(): void
    {
        echo "======================================================================\n";
        echo "🎯 專業級修復完成報告\n";
        echo "======================================================================\n";
        echo "處理模式：" . ($this->dryRun ? '預覽模式' : '修復模式') . "\n";
        echo "成功修復的檔案數：" . count($this->successfulFixes) . "\n\n";

        if (!empty($this->successfulFixes)) {
            echo "📁 成功修復的檔案：\n";
            foreach ($this->successfulFixes as $file) {
                echo "  ✅ $file\n";
            }
        }

        echo "\n🎯 建議下一步：\n";
        echo "1. 驗證核心功能：docker compose exec -T web ./vendor/bin/phpunit tests/Unit/Validation/ValidationResultTest.php\n";
        echo "2. 檢查修復效果：docker compose exec -T web ./vendor/bin/phpstan analyse --memory-limit=1G app/Shared/Validation/ValidationResult.php\n";
        echo "3. 檢查整體錯誤數：docker compose exec -T web ./vendor/bin/phpstan analyse --memory-limit=1G --error-format=table 2>&1 | tail -5\n";
        echo "======================================================================\n";
    }
}

// 主程式
$dryRun = in_array('--dry-run', $argv);
$fixer = new ProfessionalPhpstanFixer($dryRun);
$fixer->run();
