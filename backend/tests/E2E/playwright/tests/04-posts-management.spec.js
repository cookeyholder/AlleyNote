// @ts-check
const { test, expect, PostsManagementPage, PostEditorPage } = require('./fixtures/page-objects');

/**
 * 文章管理功能測試套件
 */
test.describe('文章管理功能測試', () => {
  let postsPage;

  test.beforeEach(async ({ authenticatedPage }) => {
    postsPage = new PostsManagementPage(authenticatedPage);
    await postsPage.goto();
    // 等待頁面完全載入
    await authenticatedPage.waitForLoadState('networkidle', { timeout: 10000 });
    await authenticatedPage.waitForTimeout(1000); // 額外等待確保 JS 執行完成
  });

  test.skip('應該正確顯示文章管理頁面', async ({ authenticatedPage: page }) => {
    await expect(postsPage.heading).toBeVisible();
    await expect(postsPage.newPostButton).toBeVisible();
    await expect(postsPage.searchInput).toBeVisible();
  });

  test.skip('應該顯示文章列表', async ({ authenticatedPage: page }) => {
    // 檢查表頭
    await expect(page.locator('text=標題')).toBeVisible();
    await expect(page.locator('text=狀態')).toBeVisible();
    await expect(page.locator('text=作者')).toBeVisible();
    await expect(page.locator('text=發布時間')).toBeVisible();
    await expect(page.locator('text=操作')).toBeVisible();
    
    // 檢查是否有文章
    const postsCount = await postsPage.getPostsCount();
    expect(postsCount).toBeGreaterThanOrEqual(0);
  });

  test.skip('應該能夠搜尋文章', async ({ authenticatedPage: page }) => {
    // 記錄原始文章數
    const originalCount = await postsPage.getPostsCount();
    
    // 搜尋特定關鍵字
    await postsPage.searchPosts('測試');
    
    // 搜尋後的結果數量應該小於等於原始數量
    const searchCount = await postsPage.getPostsCount();
    expect(searchCount).toBeLessThanOrEqual(originalCount);
  });

  test('應該能夠重置搜尋', async ({ authenticatedPage: page }) => {
    // 先搜尋
    await postsPage.searchPosts('測試');
    await page.waitForTimeout(500);
    
    // 點擊重置
    await postsPage.resetButton.click();
    await page.waitForTimeout(500);
    
    // 搜尋框應該被清空
    await expect(postsPage.searchInput).toHaveValue('');
  });

  test.skip('應該能夠篩選文章狀態', async ({ authenticatedPage: page }) => {
    // 選擇只顯示已發布的文章
    const statusSelect = page.locator('select').first();
    await statusSelect.selectOption('已發布');
    await page.waitForTimeout(500);
    
    // 檢查所有顯示的文章都是已發布狀態
    const statusCells = page.locator('tbody tr td:nth-child(2)');
    const count = await statusCells.count();
    
    for (let i = 0; i < count; i++) {
      const text = await statusCells.nth(i).textContent();
      expect(text).toContain('已發布');
    }
  });

  test.skip('點擊新增文章應該導航到編輯器', async ({ authenticatedPage: page }) => {
    await postsPage.clickNewPost();
    await expect(page).toHaveURL(/\/admin\/posts\/create/);
    await expect(page.locator('h1:has-text("新增文章")')).toBeVisible();
  });

  test('每篇文章都應該有操作按鈕', async ({ authenticatedPage: page }) => {
    const postsCount = await postsPage.getPostsCount();
    
    if (postsCount > 0) {
      const firstRow = postsPage.postRows.first();
      
      // 檢查編輯按鈕
      await expect(firstRow.locator('button:has-text("編輯")')).toBeVisible();
      
      // 檢查刪除按鈕
      await expect(firstRow.locator('button:has-text("刪除")')).toBeVisible();
      
      // 檢查發布/轉草稿按鈕（根據狀態）
      const hasPublishButton = await firstRow.locator('button:has-text("發布")').count() > 0;
      const hasUnpublishButton = await firstRow.locator('button:has-text("轉草稿")').count() > 0;
      
      expect(hasPublishButton || hasUnpublishButton).toBe(true);
    }
  });
});
