// @ts-check
const { test, expect } = require('./fixtures/page-objects');

/**
 * CKEditor 編輯器可用性測試
 * 確保編輯器能夠正常初始化並使用
 */
test.describe('CKEditor 編輯器可用性測試', () => {
  
  test('新增文章頁面的 CKEditor 應該正常工作', async ({ authenticatedPage: page }) => {
    // 前往新增文章頁面
    await page.goto('/admin/posts/create');
    await page.waitForLoadState('networkidle');
    
    // 等待 CKEditor 初始化
    await page.waitForSelector('.ck-editor__editable', { timeout: 10000 });
    
    // 檢查工具列是否顯示
    const toolbar = page.locator('.ck-toolbar').first();
    await expect(toolbar).toBeVisible();
    
    // 檢查是否有基本的編輯按鈕（Bold, Italic 等）
    const boldButton = toolbar.locator('button').filter({ hasText: /Bold/i }).first();
    await expect(boldButton).toBeVisible();
    
    const italicButton = toolbar.locator('button').filter({ hasText: /Italic/i }).first();
    await expect(italicButton).toBeVisible();
    
    // 測試編輯器是否可以輸入內容
    const editor = page.locator('.ck-editor__editable').first();
    await editor.click();
    await editor.fill('測試內容');
    
    // 驗證內容已輸入
    await expect(editor).toContainText('測試內容');
  });

  test('編輯文章頁面的 CKEditor 應該正常工作', async ({ authenticatedPage: page }) => {
    // 先建立一篇測試文章
    const timestamp = Date.now();
    const testTitle = `測試文章 ${timestamp}`;
    const testContent = `測試內容 ${timestamp}`;
    
    // 前往新增文章頁面
    await page.goto('/admin/posts/create');
    await page.waitForLoadState('networkidle');
    
    // 等待 CKEditor 初始化
    await page.waitForSelector('.ck-editor__editable', { timeout: 10000 });
    
    // 填寫文章
    await page.fill('#title', testTitle);
    const editor = page.locator('.ck-editor__editable').first();
    await editor.click();
    await editor.fill(testContent);
    
    // 發布文章
    await page.click('button:has-text("發布文章")');
    await page.waitForTimeout(2000);
    
    // 回到文章列表
    await page.goto('/admin/posts');
    await page.waitForLoadState('networkidle');
    
    // 找到並點擊編輯按鈕
    const editButton = page.locator(`tr:has-text("${testTitle}") button:has-text("編輯")`).first();
    await editButton.click();
    await page.waitForLoadState('networkidle');
    
    // 等待 CKEditor 初始化
    await page.waitForSelector('.ck-editor__editable', { timeout: 10000 });
    
    // 檢查工具列是否顯示
    const toolbar = page.locator('.ck-toolbar').first();
    await expect(toolbar).toBeVisible();
    
    // 檢查編輯器是否載入了文章內容
    const editorInEdit = page.locator('.ck-editor__editable').first();
    await expect(editorInEdit).toContainText(testContent);
    
    // 測試編輯功能
    await editorInEdit.click();
    await page.keyboard.type(' - 已編輯');
    
    // 驗證內容已更新
    await expect(editorInEdit).toContainText('已編輯');
    
    // 清理：刪除測試文章
    await page.goto('/admin/posts');
    await page.waitForLoadState('networkidle');
    const deleteButton = page.locator(`tr:has-text("${testTitle}") button:has-text("刪除")`).first();
    await deleteButton.click();
    
    // 確認刪除
    await page.waitForTimeout(500);
    const confirmButton = page.locator('button:has-text("確定")').first();
    if (await confirmButton.isVisible()) {
      await confirmButton.click();
    }
    await page.waitForTimeout(1000);
  });

  test('CKEditor 不應該顯示 toolbarview-item-unavailable 警告', async ({ authenticatedPage: page }) => {
    // 設置 console 監聽
    const warnings = [];
    page.on('console', msg => {
      if (msg.type() === 'warning' && msg.text().includes('toolbarview-item-unavailable')) {
        warnings.push(msg.text());
      }
    });
    
    // 前往新增文章頁面
    await page.goto('/admin/posts/create');
    await page.waitForLoadState('networkidle');
    
    // 等待 CKEditor 初始化
    await page.waitForSelector('.ck-editor__editable', { timeout: 10000 });
    await page.waitForTimeout(2000);
    
    // 確認沒有 toolbarview-item-unavailable 警告
    expect(warnings.length).toBe(0);
  });
});
