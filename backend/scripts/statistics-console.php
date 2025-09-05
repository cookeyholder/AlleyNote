#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * 統計計算控制台執行腳本。
 *
 * 提供命令行介面來執行統計計算任務
 */

require_once __DIR__ . '/vendor/autoload.php';

use App\Domains\Statistics\Console\StatisticsCalculationConsole;
use App\Domains\Statistics\Commands\StatisticsCalculationCommand;
use App\Application\Services\Statistics\StatisticsApplicationService;
use App\Domains\Statistics\Services\StatisticsCacheService;
use Psr\Log\LoggerInterface;

// 這裡應該透過 DI 容器來取得服務實例
// 由於這是範例腳本，暫時使用簡化的方式

try {
    // 假設有 bootstrap 程序來初始化服務
    // $container = require __DIR__ . '/config/container.php';
    
    // 如果沒有 DI 容器，需要手動建立服務
    // 這部分在實際專案中應該透過 DI 容器來管理
    
    echo "統計計算控制台\n";
    echo "=============\n";
    echo "注意: 此腳本需要透過完整的應用程式 bootstrap 來執行\n";
    echo "請確保已正確設定資料庫連線和服務依賴\n\n";
    
    // 如果 DI 容器可用，取消以下註解：
    /*
    $calculationCommand = $container->get(StatisticsCalculationCommand::class);
    $logger = $container->get(LoggerInterface::class);
    
    $console = new StatisticsCalculationConsole($calculationCommand, $logger);
    
    // 移除腳本名稱，只保留參數
    $arguments = array_slice($argv, 1);
    
    exit($console->run($arguments));
    */
    
    echo "範例使用方式:\n";
    echo "  php statistics-console.php calculate\n";
    echo "  php statistics-console.php calculate --periods daily,weekly\n";
    echo "  php statistics-console.php calculate --force --skip-cache\n";
    echo "  php statistics-console.php status\n";
    echo "  php statistics-console.php cleanup\n";
    echo "  php statistics-console.php help\n";
    
} catch (\Exception $e) {
    fwrite(STDERR, "錯誤: {$e->getMessage()}\n");
    exit(1);
}
