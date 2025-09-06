<?php

/**
 * Context7 指導的精準 PHPStan Level 10 修復腳本
 * 專注於 mixed types、array access 和 PDO 結果處理
 */

class Context7GuidedPreciseFixer
{
    private array $processedFiles = [];
    private array $stats = ['fixes' => 0, 'files' => 0];
    
    public function fix(): void
    {
        echo "開始 Context7 指導的精準 PHPStan Level 10 修復...\n";
        
        // 獲取錯誤最多的檔案進行修復
        $this->fixHighErrorFiles();
        
        echo "\n修復統計：\n";
        echo "- 處理檔案數：{$this->stats['files']}\n";
        echo "- 應用修復數：{$this->stats['fixes']}\n";
        echo "\n處理的檔案：\n";
        foreach ($this->processedFiles as $file) {
            echo "- $file\n";
        }
    }
    
    private function fixHighErrorFiles(): void
    {
        // 基於之前分析的高錯誤檔案
        $files = [
            'tests/Unit/Shared/Cache/Repositories/MemoryTagRepositoryTest.php',
            'tests/Unit/Infrastructure/Repositories/Statistics/StatisticsRepositoryTest.php',
            'app/Infrastructure/Repositories/Statistics/StatisticsRepository.php',
            'app/Infrastructure/Repositories/Statistics/UserStatisticsRepository.php',
            'app/Infrastructure/Repositories/Statistics/SystemStatisticsRepository.php',
            'app/Domains/Statistics/Console/StatisticsCalculationConsole.php'
        ];
        
        foreach ($files as $file) {
            $this->fixFile($file);
        }
    }
    
    private function fixFile(string $file): void
    {
        $fullPath = __DIR__ . '/../' . $file;
        if (!file_exists($fullPath)) {
            return;
        }
        
        $content = file_get_contents($fullPath);
        $originalContent = $content;
        $localFixes = 0;
        
        // 應用不同類型的修復
        [$content, $fixes] = $this->fixArrayAccessSafety($content);
        $localFixes += $fixes;
        
        [$content, $fixes] = $this->fixPdoResultTypes($content);
        $localFixes += $fixes;
        
        [$content, $fixes] = $this->fixMixedTypeAnnotations($content);
        $localFixes += $fixes;
        
        [$content, $fixes] = $this->fixTestMockTypes($content);
        $localFixes += $fixes;
        
        [$content, $fixes] = $this->fixMethodReturnTypes($content);
        $localFixes += $fixes;
        
        if ($content !== $originalContent && $localFixes > 0) {
            file_put_contents($fullPath, $content);
            $this->processedFiles[] = $file;
            $this->stats['files']++;
            $this->stats['fixes'] += $localFixes;
            echo "✓ 修復 $file ($localFixes 項修復)\n";
        }
    }
    
    /**
     * 修復數組訪問安全性 - 基於 Context7 指導
     */
    private function fixArrayAccessSafety(string $content): array
    {
        $fixes = 0;
        
        // 修復可能不存在的數組鍵訪問
        // 將 $array['key'] 替換為 $array['key'] ?? null（僅在可能不安全的情況下）
        $pattern = '/\$(\w+)\[([\'"][\w_]+[\'"])\](?!\s*[=\?\:])/';
        $content = preg_replace_callback($pattern, function ($matches) use (&$fixes) {
            // 檢查是否在賦值語句左側（避免破壞賦值）
            if (strpos($matches[0], '=') !== false) {
                return $matches[0];
            }
            $fixes++;
            return "({$matches[0]} ?? null)";
        }, $content);
        
        // 修復 array 形式參數的型別註解
        $pattern = '/(@param\s+)array(\s+\$\w+)/';
        $replacement = '${1}array<string,mixed>${2}';
        $newContent = preg_replace($pattern, $replacement, $content);
        if ($newContent !== $content) {
            $content = $newContent;
            $fixes++;
        }
        
        return [$content, $fixes];
    }
    
    /**
     * 修復 PDO 結果類型 - 基於 Context7 PDO 指導
     */
    private function fixPdoResultTypes(string $content): array
    {
        $fixes = 0;
        
        // 修復 PDOStatement::fetch() 結果
        $pattern = '/(\$\w+\s*=\s*\$\w+\s*->\s*fetch\s*\(\s*\)\s*;)/';
        $content = preg_replace_callback($pattern, function ($matches) use (&$fixes) {
            $fixes++;
            return "/** @var array<string,mixed>|false */\n        " . $matches[1];
        }, $content);
        
        // 修復 PDOStatement::fetchAll() 結果
        $pattern = '/(\$\w+\s*=\s*\$\w+\s*->\s*fetchAll\s*\(\s*\)\s*;)/';
        $content = preg_replace_callback($pattern, function ($matches) use (&$fixes) {
            $fixes++;
            return "/** @var array<int,array<string,mixed>> */\n        " . $matches[1];
        }, $content);
        
        // 修復 PDO::query() 結果
        $pattern = '/(\$\w+\s*=\s*\$\w+\s*->\s*query\s*\([^)]+\)\s*;)/';
        $content = preg_replace_callback($pattern, function ($matches) use (&$fixes) {
            $fixes++;
            return "/** @var \\PDOStatement|false */\n        " . $matches[1];
        }, $content);
        
        return [$content, $fixes];
    }
    
    /**
     * 修復 mixed 類型註解
     */
    private function fixMixedTypeAnnotations(string $content): array
    {
        $fixes = 0;
        
        // 為缺少類型的參數添加 mixed 類型
        $pattern = '/function\s+\w+\s*\(\s*\$(\w+)\s*\)(?!\s*:\s*\w+)/';
        $content = preg_replace_callback($pattern, function ($matches) use (&$fixes) {
            $fixes++;
            return str_replace('$' . $matches[1], 'mixed $' . $matches[1], $matches[0]);
        }, $content);
        
        // 為缺少返回類型的方法添加 mixed 返回類型
        $pattern = '/public\s+function\s+(\w+)\s*\([^)]*\)(?!\s*:\s*\w+)\s*\{/';
        $content = preg_replace_callback($pattern, function ($matches) use (&$fixes) {
            // 跳過建構函式和測試方法
            if ($matches[1] === '__construct' || strpos($matches[1], 'test') === 0) {
                return $matches[0];
            }
            $fixes++;
            return str_replace(') {', '): mixed {', $matches[0]);
        }, $content);
        
        return [$content, $fixes];
    }
    
    /**
     * 修復測試 Mock 類型
     */
    private function fixTestMockTypes(string $content): array
    {
        $fixes = 0;
        
        // 修復 createMock 返回類型註解
        $pattern = '/(\$\w+\s*=\s*\$this\s*->\s*createMock\s*\(\s*([^)]+)\s*\)\s*;)/';
        $content = preg_replace_callback($pattern, function ($matches) use (&$fixes) {
            $fixes++;
            $varName = trim(explode('=', $matches[1])[0]);
            return "/** @var \\PHPUnit\\Framework\\MockObject\\MockObject&{$matches[2]} $varName */\n        " . $matches[1];
        }, $content);
        
        // 修復測試方法返回類型
        $pattern = '/public\s+function\s+(test\w+)\s*\(\s*\)(?!\s*:\s*void)\s*\{/';
        $content = preg_replace_callback($pattern, function ($matches) use (&$fixes) {
            $fixes++;
            return str_replace(') {', '): void {', $matches[0]);
        }, $content);
        
        // 修復 setUp 和 tearDown 方法
        $pattern = '/public\s+function\s+(setUp|tearDown)\s*\(\s*\)(?!\s*:\s*void)\s*\{/';
        $content = preg_replace_callback($pattern, function ($matches) use (&$fixes) {
            $fixes++;
            return str_replace(') {', '): void {', $matches[0]);
        }, $content);
        
        return [$content, $fixes];
    }
    
    /**
     * 修復方法返回類型
     */
    private function fixMethodReturnTypes(string $content): array
    {
        $fixes = 0;
        
        // 修復缺少返回類型的 private/protected 方法
        $pattern = '/(private|protected)\s+function\s+(\w+)\s*\([^)]*\)(?!\s*:\s*\w+)\s*\{/';
        $content = preg_replace_callback($pattern, function ($matches) use (&$fixes) {
            // 跳過建構函式
            if ($matches[2] === '__construct') {
                return $matches[0];
            }
            $fixes++;
            return str_replace(') {', '): mixed {', $matches[0]);
        }, $content);
        
        return [$content, $fixes];
    }
}

// 執行修復
$fixer = new Context7GuidedPreciseFixer();
$fixer->fix();
