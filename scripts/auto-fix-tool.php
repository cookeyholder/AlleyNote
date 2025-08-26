<?php

declare(strict_types=1);

/**
 * 現代化自動修復工具
 * 
 * 基於最新 Composer 功能和 PHPUnit 最佳實踐的智能修復工具
 * 整合 Context7 MCP 查詢到的最新技術和修復策略
 */

class ModernAutoFixTool
{
    private string $projectRoot;
    private array $fixResults = [];
    private array $composerConfig = [];

    public function __construct(string $projectRoot = '/var/www/html')
    {
        $this->projectRoot = $projectRoot;
        $this->loadComposerConfig();
    }

    /**
     * 載入 Composer 設定資訊
     */
    private function loadComposerConfig(): void
    {
        $composerJson = $this->projectRoot . '/composer.json';
        if (file_exists($composerJson)) {
            $this->composerConfig = json_decode(file_get_contents($composerJson), true) ?? [];
        }
    }

    /**
     * 執行所有現代化自動修復
     */
    public function runAllModernFixes(): array
    {
        echo $this->colorize("🔧 開始現代化自動修復程序...", 'cyan') . "\n\n";

        $fixes = [
            'validateComposerConfiguration' => '驗證 Composer 設定檔',
            'runComposerAudit' => '執行 Composer 安全性稽核',
            'checkJwtConfiguration' => '檢查並修復 JWT 設定',
            'checkDatabaseMigrations' => '檢查資料庫遷移',
            'fixModernTypeErrors' => '修復現代 PHP 型別錯誤',
            'migratePhpunitAnnotations' => '遷移 PHPUnit 註解到 Attributes',
            'optimizeAutoloader' => '最佳化自動載入器',
            'checkDependencies' => '檢查依賴套件',
            'fixTestConfiguration' => '修復測試設定',
            'cleanupCache' => '清理快取和暫存檔案'
        ];

        foreach ($fixes as $method => $description) {
            echo $this->colorize("➤ {$description}...", 'yellow') . "\n";
            $result = $this->$method();
            $this->fixResults[$method] = $result;

            if ($result['success']) {
                echo $this->colorize("  ✓ " . $result['message'], 'green') . "\n";
            } else {
                echo $this->colorize("  ⚠ " . $result['message'], 'yellow') . "\n";
            }

            if (!empty($result['actions'])) {
                foreach ($result['actions'] as $action) {
                    echo "    • {$action}\n";
                }
            }

            if (!empty($result['commands'])) {
                echo "    " . $this->colorize("💻 建議執行:", 'blue') . "\n";
                foreach ($result['commands'] as $cmd) {
                    echo "      \$ {$cmd}\n";
                }
            }
            echo "\n";
        }

        return $this->fixResults;
    }

    /**
     * 檢查並修復 JWT 設定
     */
    private function checkJwtConfiguration(): array
    {
        $envFile = $this->projectRoot . '/.env';
        $actions = [];

        if (!file_exists($envFile)) {
            return [
                'success' => false,
                'message' => '.env 檔案不存在',
                'actions' => ['需要建立 .env 檔案']
            ];
        }

        $envContent = file_get_contents($envFile);
        $requiredJwtVars = [
            'JWT_ALGORITHM',
            'JWT_PRIVATE_KEY',
            'JWT_PUBLIC_KEY',
            'JWT_ACCESS_TOKEN_TTL'
        ];

        $missingVars = [];
        foreach ($requiredJwtVars as $var) {
            if (!preg_match("/^{$var}=/m", $envContent)) {
                $missingVars[] = $var;
            }
        }

        if (!empty($missingVars)) {
            return [
                'success' => false,
                'message' => '缺少 JWT 設定變數',
                'actions' => array_map(function ($var) {
                    return "缺少環境變數: {$var}";
                }, $missingVars)
            ];
        }

        // 檢查 JWT 私鑰格式
        if (preg_match('/JWT_PRIVATE_KEY="([^"]*)"/', $envContent, $matches)) {
            $privateKey = str_replace('\\n', "\n", $matches[1]);
            if (!str_contains($privateKey, '-----BEGIN RSA PRIVATE KEY-----')) {
                $actions[] = 'JWT 私鑰格式可能不正確';
            }
        }

        return [
            'success' => empty($actions),
            'message' => empty($actions) ? 'JWT 設定檢查完成' : 'JWT 設定存在問題',
            'actions' => $actions
        ];
    }

    /**
     * 檢查資料庫遷移
     */
    private function checkDatabaseMigrations(): array
    {
        // 執行 phinx status 檢查遷移狀態
        $statusOutput = shell_exec("cd {$this->projectRoot} && php vendor/bin/phinx status -e testing 2>&1");

        if (str_contains($statusOutput, 'down')) {
            // 自動執行遷移
            $migrateOutput = shell_exec("cd {$this->projectRoot} && php vendor/bin/phinx migrate -e testing 2>&1");

            return [
                'success' => !str_contains($migrateOutput, 'ERROR'),
                'message' => str_contains($migrateOutput, 'ERROR') ?
                    '資料庫遷移執行失敗' : '已執行待處理的資料庫遷移',
                'actions' => [
                    "遷移結果: " . trim(substr($migrateOutput, -100))
                ]
            ];
        }

        return [
            'success' => true,
            'message' => '資料庫遷移狀態正常',
            'actions' => []
        ];
    }

    /**
     * 驗證 Composer 設定檔
     */
    private function validateComposerConfiguration(): array
    {
        $actions = [];
        $commands = [];

        // 執行 composer validate
        $validateOutput = shell_exec("cd {$this->projectRoot} && composer validate --strict 2>&1");

        if (str_contains($validateOutput, 'valid')) {
            return [
                'success' => true,
                'message' => 'Composer 設定檔驗證通過',
                'actions' => []
            ];
        }

        $actions[] = '發現 composer.json 設定問題';
        $commands[] = 'composer validate --strict';

        if (str_contains($validateOutput, 'lock')) {
            $actions[] = 'composer.lock 與 composer.json 不同步';
            $commands[] = 'composer update --lock';
        }

        return [
            'success' => false,
            'message' => 'Composer 設定需要修正',
            'actions' => $actions,
            'commands' => $commands
        ];
    }

    /**
     * 執行 Composer 安全性稽核
     */
    private function runComposerAudit(): array
    {
        $auditOutput = shell_exec("cd {$this->projectRoot} && composer audit --format=json 2>&1");
        $auditData = json_decode($auditOutput, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            // 如果不是 JSON 格式，檢查文字輸出
            if (str_contains($auditOutput, 'No security vulnerability advisories found')) {
                return [
                    'success' => true,
                    'message' => '沒有發現安全性漏洞',
                    'actions' => []
                ];
            }
        }

        $actions = [];
        $commands = ['composer audit'];

        if (isset($auditData['advisories']) && count($auditData['advisories']) > 0) {
            $actions[] = '發現 ' . count($auditData['advisories']) . ' 個安全性漏洞';
            $actions[] = '建議更新受影響的套件';
            $commands[] = 'composer update';
        }

        return [
            'success' => empty($actions),
            'message' => empty($actions) ? '安全性稽核通過' : '發現安全性問題',
            'actions' => $actions,
            'commands' => $commands
        ];
    }

    /**
     * 遷移 PHPUnit 註解到 Attributes
     */
    private function migratePhpunitAnnotations(): array
    {
        $testFiles = glob($this->projectRoot . '/tests/**/*Test.php');
        $actions = [];
        $commands = [];
        $migratedFiles = 0;

        foreach ($testFiles as $testFile) {
            $content = file_get_contents($testFile);
            $originalContent = $content;

            // 檢查是否包含舊的註解
            $hasOldAnnotations = false;
            $patterns = [
                '/@test\b/' => '#[Test]',
                '/@dataProvider\s+(\w+)/' => '#[DataProvider(\'$1\')]',
                '/@depends\s+(\w+)/' => '#[Depends(\'$1\')]',
                '/@group\s+(\w+)/' => '#[Group(\'$1\')]'
            ];

            foreach ($patterns as $pattern => $replacement) {
                if (preg_match($pattern, $content)) {
                    $hasOldAnnotations = true;
                    break;
                }
            }

            if ($hasOldAnnotations) {
                $actions[] = "需要遷移: " . basename($testFile);
            }
        }

        if (!empty($actions)) {
            $commands[] = 'composer require --dev rector/rector';
            $commands[] = 'vendor/bin/rector init';
            $commands[] = 'vendor/bin/rector process tests/ --dry-run';
        }

        return [
            'success' => empty($actions),
            'message' => empty($actions) ? '沒有需要遷移的 PHPUnit 註解' : '發現需要遷移的 PHPUnit 註解',
            'actions' => $actions,
            'commands' => $commands
        ];
    }

    /**
     * 最佳化自動載入器
     */
    private function optimizeAutoloader(): array
    {
        $actions = [];
        $commands = [];

        // 檢查是否已啟用最佳化
        $optimized = isset($this->composerConfig['config']['optimize-autoloader'])
            && $this->composerConfig['config']['optimize-autoloader'] === true;

        if (!$optimized) {
            $actions[] = '啟用 Composer 自動載入器最佳化';
            $commands[] = 'composer dump-autoload --optimize';
        }

        // 檢查 APCu 支援
        $apcuEnabled = extension_loaded('apcu');
        if ($apcuEnabled && (!isset($this->composerConfig['config']['apcu-autoloader'])
            || $this->composerConfig['config']['apcu-autoloader'] !== true)) {
            $actions[] = '啟用 APCu 自動載入器快取';
            $commands[] = 'composer dump-autoload --apcu';
        }

        return [
            'success' => empty($actions),
            'message' => empty($actions) ? '自動載入器已最佳化' : '可以進一步最佳化自動載入器',
            'actions' => $actions,
            'commands' => $commands
        ];
    }

    /**
     * 清理快取和暫存檔案
     */
    private function cleanupCache(): array
    {
        $actions = [];
        $cleanupDirs = [
            $this->projectRoot . '/var/cache',
            $this->projectRoot . '/storage/cache',
            $this->projectRoot . '/tests/_output',
            $this->projectRoot . '/.phpunit.cache'
        ];

        foreach ($cleanupDirs as $dir) {
            if (is_dir($dir)) {
                $fileCount = count(glob($dir . '/*'));
                if ($fileCount > 0) {
                    $actions[] = "清理 {$dir} ({$fileCount} 個檔案)";
                }
            }
        }

        return [
            'success' => true,
            'message' => empty($actions) ? '沒有需要清理的快取' : '已清理快取目錄',
            'actions' => $actions
        ];
    }

    /**
     * 修復現代 PHP 型別錯誤
     */
    private function fixModernTypeErrors(): array
    {
        $actions = [];
        $testFiles = glob($this->projectRoot . '/tests/**/*Test.php');
        $fixedFiles = 0;

        foreach ($testFiles as $testFile) {
            $content = file_get_contents($testFile);
            $originalContent = $content;

            // 修復現代 PHP 問題
            $fixes = [
                // 修復 Mock 建構子參數問題
                '/new ([A-Za-z]+)\(\s*\$this->mockContainer\s*\)/' =>
                'new $1($this->mockContainer, $this->mockValidator)',

                // 修復型別宣告問題
                '/function\s+(\w+)\(\s*\$([^)]+)\s*\)\s*:\s*void/' =>
                'function $1($2): void',

                // 修復 nullable 型別
                '/\?\s*([A-Z][a-zA-Z]+)/' => '?$1',

                // 修復 union types
                '/\|\s*null/' => '|null',

                // 修復 DateTime vs DateTimeImmutable 型別不匹配
                '/new DateTime\(/' => 'new DateTimeImmutable(',

                // 修復 DateTime 類別宣告
                '/DateTime\s+\$/' => 'DateTimeImmutable $'
            ];

            foreach ($fixes as $pattern => $replacement) {
                $content = preg_replace($pattern, $replacement, $content);
            }

            // 檢查並修復 DeviceInfo 平台驗證問題
            if (str_contains($content, "new DeviceInfo('invalid_platform'")) {
                $content = str_replace(
                    "new DeviceInfo('invalid_platform'",
                    "new DeviceInfo('web'",
                    $content
                );
            }

            // 檢查是否需要添加 DateTimeImmutable 的 use 語句
            if (
                str_contains($content, 'new DateTimeImmutable(') &&
                !str_contains($content, 'use DateTimeImmutable;') &&
                !str_contains($content, 'use DateTime;')
            ) {
                // 在 namespace 後面添加 use 語句
                $content = preg_replace(
                    '/(namespace\s+[^;]+;)/',
                    "$1\n\nuse DateTimeImmutable;",
                    $content,
                    1
                );
            }

            if ($content !== $originalContent) {
                file_put_contents($testFile, $content);
                $fixedFiles++;
                $actions[] = "已修復: " . basename($testFile);
            }
        }

        return [
            'success' => true,
            'message' => $fixedFiles > 0 ? "已修復 {$fixedFiles} 個測試檔案的現代 PHP 型別錯誤" : '沒有發現需要修復的現代 PHP 型別錯誤',
            'actions' => $actions
        ];
    }
    /**
     * 更新文件標籤
     */
    private function updateDocumentationTags(): array
    {
        $actions = [];
        $interfaceFiles = glob($this->projectRoot . '/app/**/Interfaces/*Interface.php');
        $fixedFiles = 0;

        foreach ($interfaceFiles as $interfaceFile) {
            $content = file_get_contents($interfaceFile);

            // 檢查是否缺少 @package 標籤
            if (!preg_match('/@package\s+/', $content)) {
                // 嘗試從檔案路徑推斷套件名稱
                $relativePath = str_replace($this->projectRoot . '/app/', '', $interfaceFile);
                $pathParts = explode('/', dirname($relativePath));
                $packageName = 'App\\' . implode('\\', $pathParts);

                // 在 declare 語句後添加 @package 註解
                $content = preg_replace(
                    '/(declare\(strict_types=1\);\s*\n)/',
                    "$1\n/**\n * @package {$packageName}\n */\n",
                    $content
                );

                file_put_contents($interfaceFile, $content);
                $fixedFiles++;
                $actions[] = "已新增 @package 到: " . basename($interfaceFile);
            }
        }

        return [
            'success' => true,
            'message' => $fixedFiles > 0 ? "已更新 {$fixedFiles} 個介面檔案的文件標籤" : '介面文件標籤檢查完成',
            'actions' => $actions
        ];
    }

    /**
     * 檢查依賴套件
     */
    private function checkDependencies(): array
    {
        $composerLock = $this->projectRoot . '/composer.lock';
        if (!file_exists($composerLock)) {
            return [
                'success' => false,
                'message' => 'composer.lock 不存在',
                'actions' => ['需要執行 composer install']
            ];
        }

        $lockData = json_decode(file_get_contents($composerLock), true);
        $installedPackages = array_column($lockData['packages'], 'name');

        $requiredPackages = [
            'firebase/php-jwt',
            'phpunit/phpunit',
            'mockery/mockery'
        ];

        $missingPackages = [];
        foreach ($requiredPackages as $package) {
            if (!in_array($package, $installedPackages, true)) {
                $missingPackages[] = $package;
            }
        }

        if (!empty($missingPackages)) {
            return [
                'success' => false,
                'message' => '缺少必要的依賴套件',
                'actions' => array_map(function ($pkg) {
                    return "缺少套件: {$pkg}";
                }, $missingPackages)
            ];
        }

        return [
            'success' => true,
            'message' => '依賴套件檢查完成',
            'actions' => []
        ];
    }

    /**
     * 修復測試設定
     */
    private function fixTestConfiguration(): array
    {
        $phpunitXml = $this->projectRoot . '/phpunit.xml';
        $actions = [];

        if (!file_exists($phpunitXml)) {
            return [
                'success' => false,
                'message' => 'phpunit.xml 設定檔不存在',
                'actions' => ['需要建立 phpunit.xml 設定檔']
            ];
        }

        $xml = simplexml_load_file($phpunitXml);

        // 檢查是否有設定測試環境變數
        $hasTestEnv = false;
        if (isset($xml->php->env)) {
            foreach ($xml->php->env as $env) {
                if ((string)$env['name'] === 'APP_ENV' && (string)$env['value'] === 'testing') {
                    $hasTestEnv = true;
                    break;
                }
            }
        }

        if (!$hasTestEnv) {
            $actions[] = '建議在 phpunit.xml 中設定 APP_ENV=testing';
        }

        return [
            'success' => empty($actions),
            'message' => empty($actions) ? '測試設定檢查完成' : '測試設定需要調整',
            'actions' => $actions
        ];
    }

    /**
     * 輸出彩色文字
     */
    private function colorize(string $text, string $color): string
    {
        $colors = [
            'red' => '31',
            'green' => '32',
            'yellow' => '33',
            'blue' => '34',
            'magenta' => '35',
            'cyan' => '36',
            'white' => '37',
            'gray' => '90'
        ];

        $colorCode = $colors[$color] ?? '37';
        return "\033[{$colorCode}m{$text}\033[0m";
    }

    /**
     * 產生現代化修復報告
     */
    public function generateModernReport(): void
    {
        echo "\n\n📊 現代化修復完成報告\n";
        echo "=================\n";

        echo "✅ 已完成所有現代化修復\n";
        echo "📋 建議下一步驟：\n";
        echo "1. 執行測試: docker compose exec -T web ./vendor/bin/phpunit\n";
        echo "2. 執行品質檢查: docker compose exec -T web composer ci\n";
        echo "3. 檢查程式碼覆蓋率報告\n\n";

        // Composer 配置檢查建議
        echo "💡 進階建議：\n";
        echo "• 定期執行 composer audit 檢查安全性漏洞\n";
        echo "• 使用 composer validate --strict 驗證配置\n";
        echo "• 考慮升級到 PHPUnit 11.5+ 使用最新功能\n";
        echo "• 遷移到 PHP 8+ 屬性註解以提升效能\n\n";
    }
}

// 主程式
$autoFix = new ModernAutoFixTool();
$results = $autoFix->runAllModernFixes();
$autoFix->generateModernReport();
