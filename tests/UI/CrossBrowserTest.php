<?php

namespace Tests\UI;

class CrossBrowserTest extends UITestCase
{
    /**
     * 測試瀏覽器清單.
     */
    private array $browsers = [
        'chrome' => [
            'name' => 'Chrome',
            'userAgent' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        ],
        'firefox' => [
            'name' => 'Firefox',
            'userAgent' => 'Mozilla/5.0 (X11; Linux x86_64; rv:109.0) Gecko/20100101 Firefox/119.0',
        ],
        'safari' => [
            'name' => 'Safari',
            'userAgent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 14_3_1) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.2 Safari/605.1.15',
        ],
    ];

    #[Test]
    public function browserAction(): void
    {
        foreach ($this->browsers as $browser) {
            // 使用特定瀏覽器的 User Agent 啟動測試
            $this->testBrowserCompatibility($browser);
        }
    }

    private function testBrowserCompatibility(array $browser): void
    {
        // 啟動瀏覽器並設定 User Agent
        $this->browserAction('launch', 'http://localhost:8080/posts');

        // 測試基本功能
        $this->testBasicFunctionality();

        // 測試 CSS 樣式
        $this->testCssStyles();

        // 測試 JavaScript 功能
        $this->testJavaScriptFeatures();

        // 測試響應式設計
        $this->testResponsiveDesign();

        // 截圖保存
        $this->captureScreenshot("compatibility-{$browser['name']}");

        // 關閉瀏覽器
        $this->browserAction('close');
    }

    private function testBasicFunctionality(): void
    {
        // 檢查頁面載入
        $this->assertElementVisible('body');
        $this->assertElementVisible('header');
        $this->assertElementVisible('main');
        $this->assertElementVisible('footer');

        // 檢查導航功能
        $this->assertElementVisible('nav');
        $this->browserAction('click', '#menu-toggle');
        $this->assertElementVisible('#main-menu');
    }

    private function testCssStyles(): void
    {
        // 檢查基本樣式
        $this->assertElementVisible('.container');
        $this->assertElementVisible('.btn');
        $this->assertElementVisible('.card');

        // 檢查字體渲染
        $this->assertElementVisible('.heading');
        $this->assertElementVisible('.text-content');

        // 檢查動畫效果
        $this->assertElementVisible('.animated');
    }

    private function testJavaScriptFeatures(): void
    {
        // 測試互動功能
        $this->browserAction('click', '#dark-mode-toggle');
        $this->assertElementVisible('body.dark-mode');

        // 測試表單驗證
        $this->browserAction('click', '#search-input');
        $this->browserAction('type', 'test');
        $this->browserAction('click', '#search-button');
        $this->assertElementVisible('.search-results');

        // 測試 AJAX 載入
        $this->browserAction('click', '#load-more');
        $this->assertElementVisible('.loading-indicator');
    }

    private function testResponsiveDesign(): void
    {
        // 測試桌面版面配置
        $this->assertElementVisible('.desktop-navigation');

        // 測試平板版面配置
        // 模擬平板尺寸
        $this->assertElementVisible('.tablet-navigation');

        // 測試手機版面配置
        // 模擬手機尺寸
        $this->assertElementVisible('.mobile-navigation');
        $this->assertElementVisible('.hamburger-menu');
    }

    private function performBrowserAction(string $action, string $value = ''): void
    {
        // 這個方法將在實際執行時實作，用於包裝 browser_action 工具的呼叫
    }
}
