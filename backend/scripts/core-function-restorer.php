<?php

declare(strict_types=1);

/**
 * 核心功能恢復工具
 * 恢復關鍵類別的基本功能
 */
class CoreFunctionRestorer
{
    public function __construct()
    {
        echo "🔧 啟動核心功能恢復工具...\n\n";
    }

    public function restoreAll(): void
    {
        $this->restoreValidationResult();
        $this->restoreValidator();

        echo "\n✅ 核心功能恢復完成！\n";
    }

    private function restoreValidationResult(): void
    {
        echo "🔧 恢復 ValidationResult...\n";

        $filePath = 'app/Shared/Validation/ValidationResult.php';
        $content = file_get_contents($filePath);

        // 修復建構函式簽名
        $content = preg_replace(
            '/public function __construct\([^)]*\)/',
            'public function __construct(bool $isValid, array $errors = [], array $validatedData = [], array $failedRules = [])',
            $content
        );

        // 修復 failure 方法簽名
        $content = preg_replace(
            '/public static function failure\([^)]*\): self\s*\{[^}]*\}/',
            'public static function failure(array $errors, array $failedRules = []): self
    {
        return new self(false, $errors, [], $failedRules);
    }',
            $content
        );

        file_put_contents($filePath, $content);
        echo "  ✅ ValidationResult 恢復完成\n";
    }

    private function restoreValidator(): void
    {
        echo "🔧 恢復 Validator 核心方法...\n";

        $filePath = 'app/Shared/Validation/Validator.php';
        $content = file_get_contents($filePath);

        // 修復所有驗證方法的參數
        $validationMethods = [
            'validateRequired', 'validateString', 'validateInteger', 'validateNumeric',
            'validateBoolean', 'validateArray', 'validateEmail', 'validateUrl',
            'validateIp', 'validateDate', 'validateDateTime', 'validateFile',
            'validateImage', 'validateMimes', 'validateSize', 'validateMinLength',
            'validateMaxLength', 'validateLength', 'validateMin', 'validateMax',
            'validateBetween', 'validateIn', 'validateNotIn', 'validateRegex',
            'validateAlpha', 'validateAlphaNum', 'validateAlphaDash'
        ];

        foreach ($validationMethods as $method) {
            $content = preg_replace(
                "/private function {$method}\([^)]*\): bool/",
                "private function {$method}(mixed \$value, array \$parameters = []): bool",
                $content
            );
        }

        // 修復特殊方法
        $content = preg_replace(
            '/private function validateConfirmed\([^)]*\): bool/',
            'private function validateConfirmed(mixed $value, array $parameters, array $allData, string $currentField): bool',
            $content
        );

        $content = preg_replace(
            '/private function validateDifferent\([^)]*\): bool/',
            'private function validateDifferent(mixed $value, array $parameters, array $allData): bool',
            $content
        );

        $content = preg_replace(
            '/private function validateSame\([^)]*\): bool/',
            'private function validateSame(mixed $value, array $parameters, array $allData): bool',
            $content
        );

        file_put_contents($filePath, $content);
        echo "  ✅ Validator 方法簽名恢復完成\n";
    }
}

// 執行恢復
try {
    $restorer = new CoreFunctionRestorer();
    $restorer->restoreAll();
} catch (Exception $e) {
    echo "❌ 恢復過程中發生錯誤: " . $e->getMessage() . "\n";
    exit(1);
}
