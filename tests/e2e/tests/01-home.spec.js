// @ts-check
const { test, expect } = require('@playwright/test');

/**
 * 首頁測試套件
 */
test.describe('首頁功能測試', () => {
  test.beforeEach(async ({ page }) => {
    await page.goto('/');
  });

  test('應該正確顯示首頁標題和導航', async ({ page }) => {
    // 檢查主標題
    await expect(page.locator('h1:has-text("AlleyNote")')).toBeVisible();
    
    // 檢查副標題
    await expect(page.locator('h2:has-text("現代化公布欄系統")')).toBeVisible();
    
    // 檢查登入按鈕
    await expect(page.locator('button:has-text("登入")')).toBeVisible();
  });

  test('應該顯示最新文章列表', async ({ page }) => {
    // 等待文章載入
    await page.waitForSelector('text=最新文章', { timeout: 5000 });
    
    // 檢查文章數量提示
    const countText = await page.locator('text=/共 \\d+ 篇文章/').textContent();
    expect(countText).toMatch(/共 \d+ 篇文章/);
    
    // 如果有文章，檢查文章卡片
    const postsCount = await page.locator('article.card').count();
    if (postsCount > 0) {
      // 檢查第一篇文章是否有標題和日期
      const firstPost = page.locator('article.card').first();
      await expect(firstPost.locator('h4')).toBeVisible();
      await expect(firstPost.locator('time')).toBeVisible();
    }
  });

  test.skip('應該能夠搜尋文章', async ({ page }) => {
    // 填寫搜尋關鍵字
    const searchInput = page.locator('input[placeholder*="搜尋"]').first();
    await searchInput.fill('測試');
    await searchInput.press('Enter');
    
    // 等待搜尋結果
    await page.waitForTimeout(1000);
    
    // 驗證 URL 或搜尋結果
    // 這裡的驗證取決於實際的搜尋實作
  });

  test('點擊登入按鈕應該導航到登入頁面', async ({ page }) => {
    await page.click('button:has-text("登入")');
    await expect(page).toHaveURL(/\/login/);
  });

  test('應該顯示頁腳資訊', async ({ page }) => {
    await expect(page.locator('text=© 2024 AlleyNote')).toBeVisible();
    await expect(page.locator('text=Domain-Driven Design')).toBeVisible();
  });
});
