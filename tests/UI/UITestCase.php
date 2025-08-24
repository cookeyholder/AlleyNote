<?php

namespace Tests\UI;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;


abstract class UITestCase extends TestCase
{
    protected static ?Process $serverProcess = null;

    protected static string $baseUrl = 'http://localhost:8080';

    public static function setUpBeforeClass(): void
    {
        // 啟動開發伺服器
        static::$serverProcess = new Process(['php', '-S', 'localhost:8080', '-t', 'public']);
        static::$serverProcess->start();

        // 等待伺服器啟動
        usleep(500000); // 等待 0.5 秒
    }

    public static function tearDownAfterClass(): void
    {
        // 關閉開發伺服器
        if (static::$serverProcess !== null) {
            static::$serverProcess->stop();
        }
    }

    protected function captureScreenshot(string $name): void
    {
        $screenshotDir = __DIR__ . '/screenshots';
        if (!file_exists($screenshotDir)) {
            mkdir($screenshotDir, 0777, true);
        }

        // 使用當前時間戳記作為檔名前綴
        $timestamp = date('Y-m-d_H-i-s');
        $filename = sprintf('%s/%s_%s.png', $screenshotDir, $timestamp, $name);

        // 實際的截圖操作將由具體的測試類別實作
    }

    protected function assertElementVisible(string $selector): void
    {
        // 將由具體的測試類別實作
    }

    protected function assertElementNotVisible(string $selector): void
    {
        // 將由具體的測試類別實作
    }

    protected function assertTextPresent(string $text): void
    {
        // 將由具體的測試類別實作
    }

    protected function assertTextNotPresent(string $text): void
    {
        // 將由具體的測試類別實作
    }
}
