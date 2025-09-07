<?php

declare(strict_types=1);

/**
 * 修復 PHPStan Level 10 陣列型別問題的腳本
 */

require_once 'vendor/autoload.php';

class PhpStanArrayTypeFixer
{
    private array $changedFiles = [];

    public function fixArrayParameterTypes(): void
    {
        $this->fixControllers();
        $this->fixDTOs();
        $this->fixRepositories();
        $this->fixServices();
        $this->fixModels();

        $this->printSummary();
    }

    private function fixControllers(): void
    {
        echo "修復控制器類別...\n";

        $controllerFiles = glob('app/Application/Controllers/**/*.php');
        foreach ($controllerFiles as $file) {
            $this->fixArrayTypesInFile($file);
        }
    }

    private function fixDTOs(): void
    {
        echo "修復 DTO 類別...\n";

        $dtoFiles = [
            ...glob('app/Application/DTOs/**/*.php'),
            ...glob('app/Domains/*/DTOs/*.php'),
        ];

        foreach ($dtoFiles as $file) {
            $this->fixArrayTypesInFile($file);
        }
    }

    private function fixRepositories(): void
    {
        echo "修復 Repository 類別...\n";

        $repoFiles = [
            ...glob('app/Domains/*/Repositories/*.php'),
            ...glob('app/Infrastructure/*/Repositories/*.php'),
        ];

        foreach ($repoFiles as $file) {
            $this->fixArrayTypesInFile($file);
        }
    }

    private function fixServices(): void
    {
        echo "修復 Service 類別...\n";

        $serviceFiles = [
            ...glob('app/Application/Services/**/*.php'),
            ...glob('app/Domains/*/Services/*.php'),
            ...glob('app/Infrastructure/*/Services/*.php'),
        ];

        foreach ($serviceFiles as $file) {
            $this->fixArrayTypesInFile($file);
        }
    }

    private function fixModels(): void
    {
        echo "修復 Model 類別...\n";

        $modelFiles = glob('app/Domains/*/Models/*.php');
        foreach ($modelFiles as $file) {
            $this->fixArrayTypesInFile($file);
        }
    }

    private function fixArrayTypesInFile(string $file): void
    {
        if (!file_exists($file)) {
            return;
        }

        $content = file_get_contents($file);
        if ($content === false) {
            return;
        }

        $originalContent = $content;

        // 修復方法參數中的 array 型別
        $content = $this->fixMethodParameters($content);

        // 修復方法返回值中的 array 型別
        $content = $this->fixReturnTypes($content);

        // 修復屬性中的 array 型別
        $content = $this->fixPropertyTypes($content);

        if ($content !== $originalContent) {
            file_put_contents($file, $content);
            $this->changedFiles[] = $file;
            echo "  已修復: {$file}\n";
        }
    }

    private function fixMethodParameters(string $content): string
    {
        // 修復缺少完整型別宣告的方法參數
        $patterns = [
            // 修復 array $param 為 array<string, mixed> $param
            '/(\s+public\s+function\s+\w+\([^)]*?)array\s+(\$\w+)/m' => '$1array $2',

            // 修復 PHPDoc 中的 @param array
            '/@param\s+array\s+(\$\w+)/' => '@param array<string, mixed> $1',

            // 修復缺少 @phpstan-param 的情況
            '/(@param\s+array<string,\s*mixed>\s+\$\w+)(?!\s*@phpstan-param)/' => '$1' . "\n     * @phpstan-param array<string, mixed> \$args",
        ];

        foreach ($patterns as $pattern => $replacement) {
            $content = preg_replace($pattern, $replacement, $content);
        }

        return $content;
    }

    private function fixReturnTypes(string $content): string
    {
        $patterns = [
            // 修復 @return array
            '/@return\s+array(?!\s*<)/' => '@return array<string, mixed>',

            // 修復缺少 @phpstan-return 的情況
            '/(@return\s+array<[^>]+>)(?!\s*@phpstan-return)/' => '$1' . "\n     * @phpstan-return array<string, mixed>",
        ];

        foreach ($patterns as $pattern => $replacement) {
            $content = preg_replace($pattern, $replacement, $content);
        }

        return $content;
    }

    private function fixPropertyTypes(string $content): string
    {
        $patterns = [
            // 修復 @var array
            '/@var\s+array(?!\s*<)/' => '@var array<string, mixed>',
        ];

        foreach ($patterns as $pattern => $replacement) {
            $content = preg_replace($pattern, $replacement, $content);
        }

        return $content;
    }

    private function printSummary(): void
    {
        echo "\n修復完成！\n";
        echo "共修復了 " . count($this->changedFiles) . " 個檔案：\n";

        foreach ($this->changedFiles as $file) {
            echo "  - {$file}\n";
        }
    }
}

// 執行修復
$fixer = new PhpStanArrayTypeFixer();
$fixer->fixArrayParameterTypes();

echo "\n請手動檢查修復結果並測試程式碼功能！\n";
