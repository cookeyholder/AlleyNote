// @ts-check
const { test, expect, PostEditorPage } = require('./fixtures/page-objects');

/**
 * 文章編輯功能測試套件
 */
test.describe('文章編輯功能測試', () => {
  let editorPage;

  test.beforeEach(async ({ authenticatedPage }) => {
    editorPage = new PostEditorPage(authenticatedPage);
  });

  test('應該正確顯示新增文章頁面', async ({ authenticatedPage: page }) => {
    await editorPage.goto();
    
    await expect(page.locator('h1:has-text("新增文章")')).toBeVisible();
    await expect(editorPage.titleInput).toBeVisible();
    await expect(editorPage.contentEditor).toBeVisible();
    await expect(editorPage.statusSelect).toBeVisible();
    await expect(editorPage.submitButton).toBeVisible();
  });

  test('應該能夠建立新文章', async ({ authenticatedPage: page }) => {
    await editorPage.goto();
    
    const testTitle = `測試文章 ${Date.now()}`;
    const testContent = '這是測試文章的內容';
    
    // 填寫文章資料
    await editorPage.fillPost({
      title: testTitle,
      content: testContent,
      status: 'draft',
    });
    
    // 提交文章
    await editorPage.submitPost();
    
    // 應該導航回文章列表
    await expect(page).toHaveURL(/\/admin\/posts$/);
    
    // 應該能在列表中找到新建立的文章
    await expect(page.locator(`text=${testTitle}`)).toBeVisible({ timeout: 5000 });
  });

  test('應該能夠編輯現有文章', async ({ authenticatedPage: page }) => {
    // 先導航到文章列表找一篇文章來編輯
    await page.goto('/admin/posts');
    
    // 等待文章列表載入
    await page.waitForTimeout(1000);
    
    const postsCount = await page.locator('tbody tr').count();
    
    if (postsCount > 0) {
      // 點擊第一篇文章的編輯按鈕
      await page.locator('tbody tr').first().locator('button:has-text("編輯")').click();
      
      // 等待編輯器載入
      await page.waitForURL(/\/admin\/posts\/\d+\/edit/);
      
      // 檢查編輯器元素
      await expect(page.locator('h1:has-text("編輯文章")')).toBeVisible();
      await expect(editorPage.titleInput).toBeVisible();
      
      // 檢查標題欄位有值
      await expect(editorPage.titleInput).not.toHaveValue('');
    }
  });

  test('應該能夠設定發布時間', async ({ authenticatedPage: page }) => {
    await editorPage.goto();
    
    // 檢查發布時間輸入框
    await expect(editorPage.publishDateInput).toBeVisible();
    
    // 設定發布時間
    const publishDate = '2025-12-31T15:30';
    await editorPage.publishDateInput.fill(publishDate);
    
    // 檢查值是否正確設定
    await expect(editorPage.publishDateInput).toHaveValue(publishDate);
    
    // 檢查時區提示
    await expect(page.locator('text=時區：Asia/Taipei')).toBeVisible();
  });

  test('應該顯示自動儲存提示', async ({ authenticatedPage: page }) => {
    // 導航到編輯現有文章（需要有文章 ID）
    await page.goto('/admin/posts');
    
    const postsCount = await page.locator('tbody tr').count();
    
    if (postsCount > 0) {
      await page.locator('tbody tr').first().locator('button:has-text("編輯")').click();
      await page.waitForURL(/\/admin\/posts\/\d+\/edit/);
      
      // 檢查自動儲存提示
      await expect(page.locator('text=自動儲存已啟用')).toBeVisible();
    }
  });

  test('應該能夠儲存草稿', async ({ authenticatedPage: page }) => {
    await editorPage.goto();
    
    const testTitle = `草稿文章 ${Date.now()}`;
    
    await editorPage.fillPost({
      title: testTitle,
      content: '草稿內容',
      status: 'draft',
    });
    
    // 點擊儲存草稿
    await editorPage.saveDraftButton.click();
    
    // 等待儲存完成（可能有提示訊息）
    await page.waitForTimeout(1000);
  });

  test('取消按鈕應該返回文章列表', async ({ authenticatedPage: page }) => {
    await editorPage.goto();
    
    // 點擊取消（沒有修改的情況下）
    await editorPage.cancelButton.click();
    
    // 應該導航回文章列表
    await expect(page).toHaveURL(/\/admin\/posts$/);
  });

  test('應該驗證必填欄位', async ({ authenticatedPage: page }) => {
    await editorPage.goto();
    
    // 不填寫任何內容，直接提交
    await editorPage.submitButton.click();
    
    // 應該顯示驗證錯誤（標題為必填）
    // 檢查是否仍在編輯頁面
    await expect(page).toHaveURL(/\/admin\/posts\/create/);
  });

  test('應該能夠新增摘要', async ({ authenticatedPage: page }) => {
    await editorPage.goto();
    
    const testExcerpt = '這是文章摘要';
    
    await editorPage.fillPost({
      title: '測試摘要',
      content: '內容',
      excerpt: testExcerpt,
    });
    
    await expect(editorPage.excerptTextarea).toHaveValue(testExcerpt);
  });
});
