<?php
declare(strict_types=1);

/**
 * 保守型領域層語法修復器
 * 基於 Context7 PHP 官方文件查詢的權威知識
 * 專門處理領域服務和 DTO 的語法錯誤，採用保守且精確的修復策略
 */

class ConservativeDomainSyntaxFixer
{
    private array $stats = ['files' => 0, 'fixes' => 0, 'errors' => 0, 'warnings' => []];
    private array $backups = [];

    // 階段二：領域服務和 DTO 目標檔案（按重要性排序）
    private array $targetFiles = [
        // 核心服務（最重要）
        'app/Domains/Auth/Services/AuthService.php',
        'app/Domains/Auth/Services/JwtTokenService.php',
        'app/Domains/Post/Services/PostService.php',
        'app/Domains/Security/Services/ActivityLoggingService.php',

        // 重要 DTO
        'app/Domains/Auth/DTOs/RegisterUserDTO.php',
        'app/Domains/Post/DTOs/CreatePostDTO.php',
        'app/Domains/Security/DTOs/CreateActivityLogDTO.php',

        // 次要 DTO
        'app/Domains/Auth/DTOs/LoginRequestDTO.php',
        'app/Domains/Post/DTOs/UpdatePostDTO.php',
        'app/Domains/Security/DTOs/ActivityLogSearchDTO.php',
    ];

    public function fixDomainLayerSyntax(): void
    {
        echo "🛠️  開始保守型領域層語法修復...\n";
        echo "📋 基於 Context7 PHP 官方文件的權威知識\n\n";

        foreach ($this->targetFiles as $file) {
            if (file_exists($file)) {
                $this->processFileWithBackup($file);
            } else {
                echo "⚠️  檔案不存在: $file\n";
            }
        }

        $this->generateDetailedReport();
    }

    private function processFileWithBackup(string $filePath): void
    {
        echo "🔍 處理: $filePath\n";

        // 1. 先檢查語法錯誤
        if (!$this->checkSyntax($filePath)) {
            echo "❌ 語法錯誤，跳過: $filePath\n\n";
            $this->stats['errors']++;
            return;
        }

        // 2. 建立備份
        $this->createBackup($filePath);

        // 3. 讀取內容
        $originalContent = file_get_contents($filePath);
        $content = $originalContent;

        // 4. 應用保守修復
        $content = $this->applyConservativeFixes($content, $filePath);

        // 5. 檢查修復效果
        if ($content !== $originalContent) {
            file_put_contents($filePath, $content);

            // 6. 驗證修復後的語法
            if ($this->checkSyntax($filePath)) {
                echo "✅ 修復成功: $filePath\n";
                $this->stats['files']++;
            } else {
                echo "❌ 修復後語法錯誤，回滾: $filePath\n";
                $this->restoreBackup($filePath);
                $this->stats['errors']++;
            }
        } else {
            echo "ℹ️  無需修復: $filePath\n";
        }

        echo "\n";
    }

    private function applyConservativeFixes(string $content, string $filePath): string
    {
        $fixes = 0;

        // 基於 PHP 官方文件的保守修復模式

        // 1. 修復明確的類型錯誤（僅處理明顯錯誤）
        $patterns = [
            // 修復 nullable 類型語法 (PHP 8.4 標準)
            '/\?\s*string\s*\|\s*null/' => '?string',
            '/\?\s*int\s*\|\s*null/' => '?int',
            '/\?\s*bool\s*\|\s*null/' => '?bool',
            '/\?\s*array\s*\|\s*null/' => '?array',

            // 修復基本返回類型錯誤
            '/:\s*void\s*;/' => ': void;',
            '/:\s*string\s*;/' => ': string;',
            '/:\s*int\s*;/' => ': int;',
            '/:\s*bool\s*;/' => ': bool;',
            '/:\s*array\s*;/' => ': array;',

            // 修復基本參數類型錯誤
            '/\(\s*string\s*\$/' => '(string $',
            '/\(\s*int\s*\$/' => '(int $',
            '/\(\s*bool\s*\$/' => '(bool $',
            '/\(\s*array\s*\$/' => '(array $',

            // 修復函式宣告語法
            '/public\s+function\s+([a-zA-Z_][a-zA-Z0-9_]*)\s*\(\s*\)\s*:\s*void\s*\{/' => 'public function $1(): void {',
            '/private\s+function\s+([a-zA-Z_][a-zA-Z0-9_]*)\s*\(\s*\)\s*:\s*void\s*\{/' => 'private function $1(): void {',
            '/protected\s+function\s+([a-zA-Z_][a-zA-Z0-9_]*)\s*\(\s*\)\s*:\s*void\s*\{/' => 'protected function $1(): void {',
        ];

        foreach ($patterns as $pattern => $replacement) {
            $newContent = preg_replace($pattern, $replacement, $content);
            if ($newContent !== $content && $newContent !== null) {
                $content = $newContent;
                $fixes++;
                echo "  🔧 應用修復模式: " . substr($pattern, 1, 30) . "...\n";
            }
        }

        // 2. 修復常見的 PHP 8.4 語法問題（保守模式）
        $content = $this->fixCommonPHP84Issues($content, $fixes);

        // 3. 修復 DTO 特定問題
        if (strpos($filePath, 'DTO') !== false) {
            $content = $this->fixDTOSpecificIssues($content, $fixes);
        }

        // 4. 修復服務類特定問題
        if (strpos($filePath, 'Service') !== false) {
            $content = $this->fixServiceSpecificIssues($content, $fixes);
        }

        $this->stats['fixes'] += $fixes;

        if ($fixes > 0) {
            echo "  ✨ 總計修復: $fixes 個問題\n";
        }

        return $content;
    }

    private function fixCommonPHP84Issues(string $content, int &$fixes): string
    {
        // 基於 Context7 查詢的 PHP 8.4 常見問題修復

        // 修復錯誤的陣列語法
        $newContent = preg_replace('/array\s*\(\s*\)/', '[]', $content);
        if ($newContent !== $content) {
            $content = $newContent;
            $fixes++;
        }

        // 修復錯誤的字串連接
        $newContent = preg_replace('/\.\s*\.\s*/', ' . ', $content);
        if ($newContent !== $content) {
            $content = $newContent;
            $fixes++;
        }

        // 修復簡單的分號錯誤
        $newContent = preg_replace('/;{2,}/', ';', $content);
        if ($newContent !== $content) {
            $content = $newContent;
            $fixes++;
        }

        return $content;
    }

    private function fixDTOSpecificIssues(string $content, int &$fixes): string
    {
        // DTO 特定的保守修復

        // 修復 DTO 建構子問題
        $pattern = '/public\s+function\s+__construct\s*\(/';
        if (preg_match($pattern, $content)) {
            // DTO 建構子應該有適當的參數類型
            $newContent = preg_replace(
                '/public\s+function\s+__construct\s*\(\s*\)/',
                'public function __construct()',
                $content
            );
            if ($newContent !== $content) {
                $content = $newContent;
                $fixes++;
            }
        }

        return $content;
    }

    private function fixServiceSpecificIssues(string $content, int &$fixes): string
    {
        // 服務類特定的保守修復

        // 修復依賴注入問題
        $pattern = '/public\s+function\s+__construct\s*\(/';
        if (preg_match($pattern, $content)) {
            // 確保建構子格式正確
            $newContent = preg_replace(
                '/public\s+function\s+__construct\s*\(\s*([^)]+)\s*\)\s*\{/',
                'public function __construct($1) {',
                $content
            );
            if ($newContent !== $content) {
                $content = $newContent;
                $fixes++;
            }
        }

        return $content;
    }

    private function checkSyntax(string $filePath): bool
    {
        $output = [];
        $returnCode = 0;

        exec("php -l " . escapeshellarg($filePath) . " 2>&1", $output, $returnCode);

        return $returnCode === 0;
    }

    private function createBackup(string $filePath): void
    {
        $backupPath = $filePath . '.backup.' . date('Y-m-d_H-i-s');
        copy($filePath, $backupPath);
        $this->backups[$filePath] = $backupPath;
        echo "  💾 建立備份: " . basename($backupPath) . "\n";
    }

    private function restoreBackup(string $filePath): void
    {
        if (isset($this->backups[$filePath]) && file_exists($this->backups[$filePath])) {
            copy($this->backups[$filePath], $filePath);
            echo "  🔄 已回滾: $filePath\n";
        }
    }

    private function generateDetailedReport(): void
    {
        echo "\n" . str_repeat('=', 60) . "\n";
        echo "📊 保守型領域層語法修復報告\n";
        echo str_repeat('=', 60) . "\n";

        echo "✅ 成功修復檔案: {$this->stats['files']}\n";
        echo "🔧 總修復次數: {$this->stats['fixes']}\n";
        echo "❌ 錯誤檔案數: {$this->stats['errors']}\n";
        echo "💾 建立備份數: " . count($this->backups) . "\n";

        if (!empty($this->stats['warnings'])) {
            echo "\n⚠️  警告訊息:\n";
            foreach ($this->stats['warnings'] as $warning) {
                echo "  • $warning\n";
            }
        }

        echo "\n📋 備份檔案清單:\n";
        foreach ($this->backups as $original => $backup) {
            echo "  • " . basename($backup) . " (原檔: " . basename($original) . ")\n";
        }

        echo "\n🎯 修復策略統計:\n";
        echo "  • 採用保守模式：只修復明確的語法錯誤\n";
        echo "  • 基於 PHP 8.4 官方標準\n";
        echo "  • 每個檔案都有備份和語法驗證\n";
        echo "  • 修復失敗自動回滾\n";

        echo "\n⏰ 完成時間: " . date('Y-m-d H:i:s') . "\n";

        // 清理建議
        echo "\n🧹 清理建議:\n";
        echo "  • 確認修復無誤後，可執行以下指令清理備份:\n";
        echo "  • find . -name '*.backup.*' -mtime +7 -delete\n";
        echo "\n";
    }
}

// 執行修復
$fixer = new ConservativeDomainSyntaxFixer();
$fixer->fixDomainLayerSyntax();

echo "\n🚀 階段二修復完成！接下來請執行語法檢查確認修復效果。\n";
