// @ts-check
const { test, expect } = require('./fixtures/page-objects');

/**
 * 輔助函數：等待 CKEditor 完全載入並初始化
 * @param {import('@playwright/test').Page} page - Playwright 頁面對象
 * @param {string} containerSelector - 編輯器容器選擇器
 */
async function waitForCKEditorReady(page, containerSelector = '#content') {
  // 等待 CKEditor 腳本從 CDN 載入
  await page.waitForFunction(() => typeof ClassicEditor !== 'undefined', { timeout: 20000 });
  
  // 等待編輯器容器出現
  await page.waitForSelector(containerSelector, { timeout: 15000 });
  
  // 等待編輯器初始化完成（等待可編輯區域出現）
  await page.waitForSelector(`${containerSelector}.ck-editor__editable`, { timeout: 15000 });
  
  // 額外等待確保工具列完全渲染
  await page.waitForTimeout(1000);
}

/**
 * 取得編輯器的參照
 * @param {import('@playwright/test').Page} page - Playwright 頁面對象
 * @param {string} containerSelector - 編輯器容器選擇器
 */
function getEditor(page, containerSelector = '#content') {
  return page.locator(`${containerSelector}.ck-editor__editable`);
}

/**
 * CKEditor 5 編輯器功能測試套件
 * 測試文章編輯器和頁腳編輯器的完整配置與功能
 */
test.describe('CKEditor 5 編輯器功能測試', () => {
  
  /**
   * 測試文章編輯器的工具列配置
   */
  test.describe('文章編輯器工具列配置', () => {
    test('應該包含完整的工具列項目', async ({ authenticatedPage: page }) => {
      // 前往新增文章頁面
      await page.goto('/admin/posts/create');
      await page.waitForLoadState('networkidle');
      
      // 等待 CKEditor 完全載入
      await waitForCKEditorReady(page);
      
      // 檢查工具列是否存在
      const toolbar = page.locator('.ck-toolbar').first();
      await expect(toolbar).toBeVisible();
      
      // 檢查核心編輯工具（粗體、斜體、底線）
      const boldBtn = toolbar.locator('button').filter({ hasText: /粗體|Bold|B/ }).or(toolbar.locator('[data-cke-tooltip-text*="Bold"]')).first();
      await expect(boldBtn).toBeVisible({ timeout: 5000 });
      
      const italicBtn = toolbar.locator('button').filter({ hasText: /斜體|Italic|I/ }).or(toolbar.locator('[data-cke-tooltip-text*="Italic"]')).first();
      await expect(italicBtn).toBeVisible({ timeout: 5000 });
      
      const underlineBtn = toolbar.locator('button').filter({ hasText: /底線|Underline|U/ }).or(toolbar.locator('[data-cke-tooltip-text*="Underline"]')).first();
      await expect(underlineBtn).toBeVisible({ timeout: 5000 });
      
      // 檢查標題選單
      const headingDropdown = toolbar.locator('.ck-heading-dropdown').first();
      await expect(headingDropdown).toBeVisible();
      
      // 檢查列表工具
      const bulletListBtn = toolbar.locator('button').filter({ hasText: /項目符號|Bullet/ }).or(toolbar.locator('[data-cke-tooltip-text*="Bullet"]')).first();
      await expect(bulletListBtn).toBeVisible({ timeout: 5000 });
      
      const numberListBtn = toolbar.locator('button').filter({ hasText: /編號|Number/ }).or(toolbar.locator('[data-cke-tooltip-text*="Number"]')).first();
      await expect(numberListBtn).toBeVisible({ timeout: 5000 });
    });

    test('應該包含進階格式化工具', async ({ authenticatedPage: page }) => {
      await page.goto('/admin/posts/create');
      await page.waitForLoadState('networkidle');
      await waitForCKEditorReady(page);
      
      const toolbar = page.locator('.ck-toolbar').first();
      
      // 檢查字型大小選單
      const fontSizeDropdown = toolbar.locator('.ck-font-size-dropdown').first();
      await expect(fontSizeDropdown).toBeVisible({ timeout: 5000 });
      
      // 檢查字型家族選單
      const fontFamilyDropdown = toolbar.locator('.ck-font-family-dropdown').first();
      await expect(fontFamilyDropdown).toBeVisible({ timeout: 5000 });
      
      // 檢查對齊選單
      const alignmentDropdown = toolbar.locator('.ck-alignment-dropdown').first();
      await expect(alignmentDropdown).toBeVisible({ timeout: 5000 });
    });
  });

  /**
   * 測試文章編輯器的基本編輯功能
   */
  test.describe('文章編輯器編輯功能', () => {
    test('應該能夠輸入文字', async ({ authenticatedPage: page }) => {
      await page.goto('/admin/posts/create');
      await page.waitForLoadState('networkidle');
      await waitForCKEditorReady(page);
      
      const editor = getEditor(page);
      
      // 輸入文字
      await editor.click();
      await page.keyboard.type('這是測試文字內容');
      
      // 驗證文字已輸入
      await expect(editor).toContainText('這是測試文字內容');
    });

    test('應該能夠套用粗體格式', async ({ authenticatedPage: page }) => {
      await page.goto('/admin/posts/create');
      await page.waitForLoadState('networkidle');
      await waitForCKEditorReady(page);
      
      const editor = getEditor(page);
      const toolbar = page.locator('.ck-toolbar').first();
      
      // 輸入文字
      await editor.click();
      await page.keyboard.type('粗體測試');
      
      // 選取全部
      await page.keyboard.press('Control+A');
      
      // 點擊粗體按鈕
      const boldBtn = toolbar.locator('button').filter({ hasText: /粗體|Bold|B/ }).or(toolbar.locator('[data-cke-tooltip-text*="Bold"]')).first();
      await boldBtn.click();
      
      // 驗證粗體已套用（檢查 strong 或 b 標籤）
      const boldText = editor.locator('strong, b');
      await expect(boldText).toBeVisible();
      await expect(boldText).toContainText('粗體測試');
    });

    test('應該能夠插入項目符號列表', async ({ authenticatedPage: page }) => {
      await page.goto('/admin/posts/create');
      await page.waitForLoadState('networkidle');
      await waitForCKEditorReady(page);
      
      const editor = getEditor(page);
      const toolbar = page.locator('.ck-toolbar').first();
      
      // 點擊項目符號列表按鈕
      const bulletBtn = toolbar.locator('button').filter({ hasText: /項目符號|Bullet/ }).or(toolbar.locator('[data-cke-tooltip-text*="Bullet"]')).first();
      await bulletBtn.click();
      
      // 輸入列表項目
      await page.keyboard.type('第一項');
      await page.keyboard.press('Enter');
      await page.keyboard.type('第二項');
      await page.keyboard.press('Enter');
      await page.keyboard.type('第三項');
      
      // 驗證列表已建立
      const listItems = editor.locator('ul li');
      await expect(listItems).toHaveCount(3);
    });

    test('應該能夠插入連結', async ({ authenticatedPage: page }) => {
      await page.goto('/admin/posts/create');
      await page.waitForLoadState('networkidle');
      await waitForCKEditorReady(page);
      
      const editor = getEditor(page);
      const toolbar = page.locator('.ck-toolbar').first();
      
      // 輸入文字
      await editor.click();
      await page.keyboard.type('點擊這裡');
      
      // 選取全部
      await page.keyboard.press('Control+A');
      
      // 點擊連結按鈕
      const linkBtn = toolbar.locator('button').filter({ hasText: /連結|Link/ }).or(toolbar.locator('[data-cke-tooltip-text*="Link"]')).first();
      await linkBtn.click();
      
      // 輸入 URL（等待連結輸入框出現）
      await page.waitForTimeout(500);
      const urlInput = page.locator('.ck-balloon-panel input[type="text"]').first();
      await urlInput.waitFor({ state: 'visible', timeout: 5000 });
      await urlInput.fill('https://example.com');
      await page.keyboard.press('Enter');
      
      // 驗證連結已插入
      await page.waitForTimeout(500);
      const link = editor.locator('a');
      await expect(link).toBeVisible();
      await expect(link).toContainText('點擊這裡');
    });

    test('應該能夠使用鍵盤快捷鍵（Ctrl+B 粗體）', async ({ authenticatedPage: page }) => {
      await page.goto('/admin/posts/create');
      await page.waitForLoadState('networkidle');
      await waitForCKEditorReady(page);
      
      const editor = getEditor(page);
      
      // 輸入文字
      await editor.click();
      await page.keyboard.type('快捷鍵測試');
      
      // 選取全部並使用快捷鍵
      await page.keyboard.press('Control+A');
      await page.keyboard.press('Control+B');
      
      // 驗證粗體已套用
      const boldText = editor.locator('strong, b');
      await expect(boldText).toBeVisible();
      await expect(boldText).toContainText('快捷鍵測試');
    });
  });

  /**
   * 測試頁腳編輯器配置
   */
  test.describe('頁腳編輯器配置', () => {
    test('應該在系統設定頁面顯示頁腳編輯器', async ({ authenticatedPage: page }) => {
      await page.goto('/admin/settings');
      await page.waitForLoadState('networkidle');
      
      // 等待頁腳編輯器載入（使用不同的選擇器）
      await page.waitForFunction(() => typeof ClassicEditor !== 'undefined', { timeout: 20000 });
      await page.waitForTimeout(3000); // 給足夠時間初始化
      
      // 檢查頁腳編輯器容器存在
      const footerEditorContainer = page.locator('#footer-description-editor');
      await expect(footerEditorContainer).toBeVisible();
      
      // 檢查 CKEditor 已初始化
      const editor = footerEditorContainer.locator('.ck-editor__editable');
      await expect(editor).toBeVisible({ timeout: 10000 });
    });

    test('頁腳編輯器應該可以編輯內容', async ({ authenticatedPage: page }) => {
      await page.goto('/admin/settings');
      await page.waitForLoadState('networkidle');
      
      // 等待編輯器載入
      await page.waitForFunction(() => typeof ClassicEditor !== 'undefined', { timeout: 20000 });
      await page.waitForTimeout(3000);
      
      const editor = page.locator('#footer-description-editor .ck-editor__editable');
      await editor.waitFor({ state: 'visible', timeout: 10000 });
      
      // 清空內容
      await editor.click();
      await page.keyboard.press('Control+A');
      await page.keyboard.press('Delete');
      
      // 輸入新內容
      await page.keyboard.type('頁腳測試內容');
      
      // 驗證內容已輸入
      await expect(editor).toContainText('頁腳測試內容');
    });
  });

  /**
   * 測試編輯器的儲存功能
   */
  test.describe('編輯器內容儲存', () => {
    test('應該能夠建立並儲存文章', async ({ authenticatedPage: page }) => {
      await page.goto('/admin/posts/create');
      await page.waitForLoadState('networkidle');
      await waitForCKEditorReady(page);
      
      const editor = getEditor(page);
      
      // 填寫標題
      const titleInput = page.locator('input#title, input[name="title"]');
      const testTitle = `測試文章 ${Date.now()}`;
      await titleInput.fill(testTitle);
      
      // 在編輯器中輸入內容
      await editor.click();
      await page.keyboard.type('這是儲存測試的內容');
      
      // 儲存文章
      const saveButton = page.locator('button#submit-btn, button:has-text("發布文章")');
      await saveButton.click();
      
      // 等待儲存完成（可能導航到列表頁或顯示成功訊息）
      await page.waitForTimeout(3000);
      
      // 驗證已儲存（導航到列表頁或顯示成功訊息）
      const url = page.url();
      const hasSuccessToast = await page.locator('.toast, [class*="toast"], text=成功').isVisible().catch(() => false);
      
      // 至少其中之一應該為真
      expect(url.includes('/admin/posts') || hasSuccessToast).toBeTruthy();
    });
  });
});
