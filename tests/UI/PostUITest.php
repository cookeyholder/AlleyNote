<?php

namespace Tests\UI;

class PostUITest extends UITestCase
{
    /** @test */
    public function shouldDisplayPostList(): void
    {
        // 使用 browser_action 工具來測試文章列表頁面
        $this->browser_action('launch', 'http://localhost:8080/posts');
        
        // 檢查頁面標題
        $this->assertTextPresent('文章列表');
        
        // 檢查文章列表元素
        $this->assertElementVisible('.post-list');
        
        // 檢查分頁元素
        $this->assertElementVisible('.pagination');
        
        // 截圖保存
        $this->captureScreenshot('post-list');
        
        // 關閉瀏覽器
        $this->browser_action('close');
    }

    /** @test */
    public function shouldCreateNewPost(): void
    {
        // 登入系統
        $this->login();
        
        // 導航到新增文章頁面
        $this->browser_action('launch', 'http://localhost:8080/posts/create');
        
        // 填寫文章表單
        $this->browser_action('click', '#title');
        $this->browser_action('type', '測試文章標題');
        
        $this->browser_action('click', '#content');
        $this->browser_action('type', '這是一篇測試文章的內容');
        
        // 選擇標籤
        $this->browser_action('click', '.tag-selector');
        $this->browser_action('click', '[data-tag="公告"]');
        
        // 上傳附件
        $this->browser_action('click', '#attachment-upload');
        // 這裡需要處理檔案上傳，將在實際執行時實作
        
        // 預覽文章
        $this->browser_action('click', '#preview-button');
        $this->assertElementVisible('.preview-content');
        
        // 發布文章
        $this->browser_action('click', '#publish-button');
        
        // 驗證成功訊息
        $this->assertTextPresent('文章發布成功');
        
        // 截圖保存
        $this->captureScreenshot('post-create-success');
        
        // 關閉瀏覽器
        $this->browser_action('close');
    }

    /** @test */
    public function shouldEditExistingPost(): void
    {
        // 登入系統
        $this->login();
        
        // 導航到編輯頁面
        $this->browser_action('launch', 'http://localhost:8080/posts/1/edit');
        
        // 修改文章內容
        $this->browser_action('click', '#title');
        $this->browser_action('type', '已更新的文章標題');
        
        $this->browser_action('click', '#content');
        $this->browser_action('type', '這是更新後的文章內容');
        
        // 更新附件
        $this->browser_action('click', '#attachment-update');
        // 這裡需要處理檔案上傳，將在實際執行時實作
        
        // 儲存更新
        $this->browser_action('click', '#save-button');
        
        // 驗證成功訊息
        $this->assertTextPresent('文章更新成功');
        
        // 截圖保存
        $this->captureScreenshot('post-edit-success');
        
        // 關閉瀏覽器
        $this->browser_action('close');
    }

    /** @test */
    public function shouldDeletePost(): void
    {
        // 登入系統
        $this->login();
        
        // 導航到文章列表
        $this->browser_action('launch', 'http://localhost:8080/posts');
        
        // 點擊刪除按鈕
        $this->browser_action('click', '#delete-post-1');
        
        // 確認刪除
        $this->browser_action('click', '#confirm-delete');
        
        // 驗證成功訊息
        $this->assertTextPresent('文章已刪除');
        
        // 驗證文章已不存在
        $this->assertElementNotVisible('#post-1');
        
        // 截圖保存
        $this->captureScreenshot('post-delete-success');
        
        // 關閉瀏覽器
        $this->browser_action('close');
    }

    /** @test */
    public function shouldHandleResponsiveLayout(): void
    {
        // 測試不同螢幕尺寸下的版面配置
        $this->browser_action('launch', 'http://localhost:8080/posts');
        
        // 桌面版面配置測試
        $this->assertElementVisible('.desktop-navigation');
        
        // 平板版面配置測試
        // 這裡將在實際執行時實作螢幕尺寸調整
        
        // 手機版面配置測試
        // 這裡將在實際執行時實作螢幕尺寸調整
        
        // 截圖保存
        $this->captureScreenshot('responsive-layout');
        
        // 關閉瀏覽器
        $this->browser_action('close');
    }

    /** @test */
    public function shouldSupportDarkMode(): void
    {
        // 測試深色模式切換
        $this->browser_action('launch', 'http://localhost:8080/posts');
        
        // 切換到深色模式
        $this->browser_action('click', '#dark-mode-toggle');
        
        // 驗證深色模式樣式
        $this->assertElementVisible('body.dark-mode');
        
        // 截圖保存
        $this->captureScreenshot('dark-mode');
        
        // 關閉瀏覽器
        $this->browser_action('close');
    }

    private function login(): void
    {
        $this->browser_action('launch', 'http://localhost:8080/login');
        
        // 填寫登入表單
        $this->browser_action('click', '#email');
        $this->browser_action('type', 'admin@example.com');
        
        $this->browser_action('click', '#password');
        $this->browser_action('type', 'password123');
        
        // 提交登入
        $this->browser_action('click', '#login-button');
        
        // 驗證登入成功
        $this->assertTextPresent('登入成功');
    }

    private function browser_action(string $action, string $value = ''): void
    {
        // 這個方法將在實際執行時實作，用於包裝 browser_action 工具的呼叫
    }
}
