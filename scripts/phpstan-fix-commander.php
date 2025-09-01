#!/usr/bin/env php
<?php
/**
 * PHPStan Level 8 修復指揮中心
 * 統合執行所有修復工具並提供詳細的修復報告
 * 
 * 使用方式:
 * php scripts/phpstan-fix-commander.php [--dry-run] [--stage=1|2|3|all]
 */

class PhpstanFixCommander
{
    private bool $dryRun = false;
    private string $stage = 'all';
    private array<mixed> $totalStats = [
        'errors_before' => 0,
        'errors_after' => 0,
        'fixes_applied' => 0,
        'files_processed' => 0
    ];

    public function __construct(array<mixed> $args)
    {
        $this->parseArguments($args);
    }

    private function parseArguments(array<mixed> $args): void
    {
        foreach ($args as $arg) {
            if ($arg === '--dry-run') {
                $this->dryRun = true;
            } elseif (str_starts_with($arg, '--stage=')) {
                $this->stage = substr($arg, 8);
            }
        }
    }

    public function run(): void
    {
        echo "🚀 PHPStan Level 8 修復指揮中心\n";
        echo "執行階段: {$this->stage}\n";
        echo "執行模式: " . ($this->dryRun ? "預覽模式" : "修復模式") . "\n";
        echo str_repeat("=", 60) . "\n\n";

        // 先檢查當前錯誤數量
        $this->totalStats['errors_before'] = $this->getPhpstanErrorCount();
        echo "📊 修復前錯誤數量: {$this->totalStats['errors_before']}\n\n";

        // 根據階段執行修復
        switch ($this->stage) {
            case '1':
                $this->runStage1();
                break;
            case '2':
                $this->runStage2();
                break;
            case '3':
                $this->runStage3();
                break;
            case 'all':
            default:
                $this->runAllStages();
                break;
        }

        // 檢查修復後的錯誤數量
        if (!$this->dryRun) {
            echo "\n🔍 正在重新分析錯誤...\n";
            $this->totalStats['errors_after'] = $this->getPhpstanErrorCount();
            $this->printFinalReport();
        }
    }

    private function runAllStages(): void
    {
        $this->runStage1();
        echo "\n" . str_repeat("-", 40) . "\n";
        $this->runStage2();
        echo "\n" . str_repeat("-", 40) . "\n";
        $this->runStage3();
    }

    private function runStage1(): void
    {
        echo "🔥 第一階段: 核心問題修復\n";
        echo "修復 ResponseInterface 匿名類別和 StreamInterface 問題\n\n";

        // 執行匿名類別修復
        $this->runTool('anonymous-class-fixer.php', '匿名類別修復');

        // 執行 StreamInterface write 問題修復
        $this->runTool('advanced-phpstan-fixer.php --type=stream', 'StreamInterface 類型修復');
    }

    private function runStage2(): void
    {
        echo "⚠️ 第二階段: 批量類型修復\n";
        echo "修復陣列類型規範和移除不必要的 null coalescing\n\n";

        // 執行陣列類型修復
        $this->runTool('advanced-phpstan-fixer.php --type=array<mixed>-types', '陣列類型規範修復');

        // 執行 null coalescing 最佳化
        $this->runTool('advanced-phpstan-fixer.php --type=null-coalescing', 'Null Coalescing 最佳化');

        // 執行傳統的類型修復工具
        $this->runTool('phpstan-type-fixer.php', '傳統類型修復');
    }

    private function runStage3(): void
    {
        echo "📝 第三階段: 細節修復和清理\n";
        echo "處理剩餘的特殊案例和邊緣問題\n\n";

        // 執行增強版修復工具
        $this->runTool('enhanced-phpstan-fixer.php', '增強版修復');

        // 執行測試修復工具
        $this->runTool('test-fixer.php', '測試相關修復');

        // 執行 PHPUnit 棄用修復
        $this->runTool('fix-phpunit-deprecations.php', 'PHPUnit 棄用修復');
    }

    private function runTool(string $toolName, string $description): void
    {
        echo "🔧 執行: $description\n";

        $dryRunFlag = $this->dryRun ? ' --dry-run' : '';
        $command = "php scripts/$toolName$dryRunFlag";

        echo "指令: $command\n";

        // 執行工具
        $output = [];
        $returnVar = 0;
        exec($command . ' 2>&1', $output, $returnVar);

        // 顯示輸出
        foreach ($output as $line) {
            echo "  $line\n";
        }

        if ($returnVar !== 0) {
            echo "⚠️ 工具執行可能遇到問題 (返回碼: $returnVar)\n";
        }

        echo "\n";
    }

    private function getPhpstanErrorCount(): int
    {
        // 使用 Docker Compose 執行 PHPStan 分析
        $command = 'sudo docker compose exec -T web ./vendor/bin/phpstan analyse --level=8 --error-format=raw 2>/dev/null | wc -l';

        $output = [];
        exec($command, $output, $returnVar);

        if ($returnVar === 0 && !empty($output[0])) {
            return (int) trim($output[0]);
        }

        // 如果無法獲取錯誤數量，回傳 -1 表示未知
        return -1;
    }

    private function printFinalReport(): void
    {
        echo "\n" . str_repeat("=", 60) . "\n";
        echo "📊 最終修復報告\n";
        echo str_repeat("=", 60) . "\n";

        echo "修復前錯誤數量: {$this->totalStats['errors_before']}\n";
        echo "修復後錯誤數量: {$this->totalStats['errors_after']}\n";

        if ($this->totalStats['errors_before'] > 0 && $this->totalStats['errors_after'] >= 0) {
            $reduced = $this->totalStats['errors_before'] - $this->totalStats['errors_after'];
            $percentage = round(($reduced / $this->totalStats['errors_before']) * 100, 2);

            echo "減少錯誤數量: $reduced\n";
            echo "修復進度: $percentage%\n";

            if ($reduced > 0) {
                echo "✅ 修復成功！\n";
            } elseif ($reduced === 0) {
                echo "⚠️ 沒有減少錯誤數量，可能需要手動修復\n";
            } else {
                echo "❌ 錯誤數量增加，可能修復過程中引入了新問題\n";
            }
        }

        echo "\n💡 建議下一步:\n";

        if ($this->totalStats['errors_after'] > 1000) {
            echo "- 錯誤數量仍然很高，建議檢查核心架構問題\n";
            echo "- 重點關注 app/Application.php 的匿名類別實作\n";
        } elseif ($this->totalStats['errors_after'] > 500) {
            echo "- 進度良好，建議繼續執行自動化修復\n";
            echo "- 可以考慮手動修復一些複雜的類型問題\n";
        } elseif ($this->totalStats['errors_after'] > 100) {
            echo "- 即將完成！建議手動處理剩餘的特殊案例\n";
            echo "- 檢查測試檔案的類型註解\n";
        } elseif ($this->totalStats['errors_after'] > 0) {
            echo "- 最後階段！手動修復剩餘問題\n";
            echo "- 執行完整的測試確保功能正常\n";
        } else {
            echo "- 🎉 恭喜！所有 PHPStan Level 8 錯誤已修復\n";
            echo "- 記得執行完整的測試套件確保功能正常\n";
        }

        echo "\n執行 'sudo docker compose exec web ./vendor/bin/phpstan analyse --level=8' 查看詳細錯誤\n";
    }
}

// 執行腳本
if (php_sapi_name() === 'cli') {
    $commander = new PhpstanFixCommander($argv);
    $commander->run();
}
