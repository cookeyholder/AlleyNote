// @ts-check
const { test, expect } = require('@playwright/test');

/**
 * 時區功能測試套件
 * 驗證發布時間的時區轉換是否正確
 */
test.describe('時區轉換功能測試', () => {
  test.beforeEach(async ({ authenticatedPage }) => {
    await authenticatedPage.goto('/admin/posts');
  });

  test('編輯文章時應該正確顯示網站時區時間', async ({ authenticatedPage: page }) => {
    // 找一篇文章來編輯
    const postsCount = await page.locator('tbody tr').count();
    
    if (postsCount > 0) {
      // 點擊編輯第一篇文章
      await page.locator('tbody tr').first().locator('button:has-text("編輯")').click();
      await page.waitForURL(/\/admin\/posts\/\d+\/edit/);
      
      // 檢查時區提示
      await expect(page.locator('text=時區：Asia/Taipei')).toBeVisible();
      
      // 檢查發布時間輸入框
      const publishDateInput = page.locator('input[name="publish_date"]');
      const publishDateValue = await publishDateInput.inputValue();
      
      if (publishDateValue) {
        // 驗證格式為 YYYY-MM-DDTHH:MM
        expect(publishDateValue).toMatch(/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/);
      }
    }
  });

  test('修改發布時間後應該正確儲存為 UTC', async ({ authenticatedPage: page }) => {
    // 導航到新增文章
    await page.goto('/admin/posts/create');
    
    const testTitle = `時區測試 ${Date.now()}`;
    const publishDate = '2025-10-11T15:30'; // 網站時區 UTC+8 的 15:30
    
    // 填寫文章
    await page.fill('input[name="title"]', testTitle);
    await page.locator('.ck-editor__editable').click();
    await page.locator('.ck-editor__editable').fill('測試內容');
    await page.fill('input[name="publish_date"]', publishDate);
    
    // 提交
    await page.locator('button[type="submit"]').click();
    await page.waitForURL(/\/admin\/posts$/);
    
    // 檢查文章是否建立成功
    await expect(page.locator(`text=${testTitle}`)).toBeVisible({ timeout: 5000 });
    
    // 重新編輯這篇文章來驗證時間
    await page.locator(`tr:has-text("${testTitle}")`).locator('button:has-text("編輯")').click();
    await page.waitForURL(/\/admin\/posts\/\d+\/edit/);
    
    // 檢查發布時間是否正確顯示（應該仍是 15:30）
    const savedPublishDate = await page.locator('input[name="publish_date"]').inputValue();
    expect(savedPublishDate).toBe(publishDate);
  });

  test('系統設定應該顯示當前時區', async ({ authenticatedPage: page }) => {
    await page.goto('/admin/settings');
    
    // 檢查時區設定區塊
    await expect(page.locator('h3:has-text("時區設定")')).toBeVisible();
    
    // 檢查時區選擇器
    const timezoneSelect = page.locator('select').filter({ hasText: 'Asia/Taipei' });
    await expect(timezoneSelect).toBeVisible();
    
    // 檢查當前網站時間顯示
    await expect(page.locator('text=當前網站時間：')).toBeVisible();
  });
});
