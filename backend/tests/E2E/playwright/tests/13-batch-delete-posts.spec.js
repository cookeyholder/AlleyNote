// @ts-check
const { test, expect } = require('@playwright/test');

/**
 * 批次刪除文章功能測試
 */
test.describe('批次刪除文章功能', () => {
  test.beforeEach(async ({ page }) => {
    // 登入
    await page.goto('/login');
    await page.fill('input[name="email"]', 'admin@example.com');
    await page.fill('input[name="password"]', 'password');
    await page.click('button[type="submit"]');
    await page.waitForURL('**/admin/dashboard', { timeout: 10000 });
    
    // 訪問文章管理頁面
    await page.goto('/admin/posts');
    await page.waitForLoadState('networkidle', { timeout: 15000 });
    await page.waitForTimeout(1000);
  });

  test('應該顯示批次刪除按鈕', async ({ page }) => {
    const batchBtn = page.locator('#batch-delete-btn');
    await expect(batchBtn).toBeVisible();
    await expect(batchBtn).toHaveText(/批次刪除/);
  });

  test('點擊批次刪除按鈕應該進入批次模式', async ({ page }) => {
    // 點擊批次刪除按鈕
    await page.click('#batch-delete-btn');
    await page.waitForTimeout(500);
    
    // 檢查批次工具列是否顯示
    const toolbar = page.locator('#batch-toolbar');
    await expect(toolbar).toBeVisible();
    
    // 檢查表格是否有 checkbox 欄
    const checkboxes = page.locator('.post-checkbox');
    const count = await checkboxes.count();
    expect(count).toBeGreaterThan(0);
    
    // 檢查按鈕文字是否改變
    const batchBtn = page.locator('#batch-delete-btn');
    await expect(batchBtn).toHaveText(/取消批次/);
  });

  test('應該能選擇單個文章', async ({ page }) => {
    // 進入批次模式
    await page.click('#batch-delete-btn');
    await page.waitForTimeout(500);
    
    // 選擇第一篇文章
    const firstCheckbox = page.locator('.post-checkbox').first();
    await firstCheckbox.check();
    await page.waitForTimeout(300);
    
    // 檢查計數器
    const counter = page.locator('#selected-count');
    await expect(counter).toHaveText('1');
    
    // 檢查該行是否高亮
    const firstRow = page.locator('tbody tr').first();
    await expect(firstRow).toHaveClass(/bg-accent-50/);
  });

  test('應該能使用全選功能', async ({ page }) => {
    // 進入批次模式
    await page.click('#batch-delete-btn');
    await page.waitForTimeout(500);
    
    // 點擊全選按鈕
    await page.click('#select-all-btn');
    await page.waitForTimeout(500);
    
    // 檢查所有 checkbox 是否被選中
    const checkboxes = page.locator('.post-checkbox');
    const count = await checkboxes.count();
    
    for (let i = 0; i < count; i++) {
      await expect(checkboxes.nth(i)).toBeChecked();
    }
    
    // 檢查計數器
    const counter = page.locator('#selected-count');
    const counterText = await counter.textContent();
    expect(parseInt(counterText)).toBe(count);
  });

  test('應該能取消全選', async ({ page }) => {
    // 進入批次模式並全選
    await page.click('#batch-delete-btn');
    await page.waitForTimeout(500);
    await page.click('#select-all-btn');
    await page.waitForTimeout(500);
    
    // 取消全選
    await page.click('#deselect-all-btn');
    await page.waitForTimeout(500);
    
    // 檢查計數器
    const counter = page.locator('#selected-count');
    await expect(counter).toHaveText('0');
    
    // 檢查 checkbox 是否都未選中
    const checkboxes = page.locator('.post-checkbox');
    const count = await checkboxes.count();
    
    for (let i = 0; i < count; i++) {
      await expect(checkboxes.nth(i)).not.toBeChecked();
    }
  });

  test('應該能退出批次模式', async ({ page }) => {
    // 進入批次模式
    await page.click('#batch-delete-btn');
    await page.waitForTimeout(500);
    
    // 點擊取消按鈕
    await page.click('#cancel-batch-btn');
    await page.waitForTimeout(500);
    
    // 檢查批次工具列是否隱藏
    const toolbar = page.locator('#batch-toolbar');
    await expect(toolbar).toBeHidden();
    
    // 檢查 checkbox 欄是否消失
    const checkboxes = page.locator('.post-checkbox');
    await expect(checkboxes.first()).not.toBeVisible();
    
    // 檢查按鈕文字是否恢復
    const batchBtn = page.locator('#batch-delete-btn');
    await expect(batchBtn).toHaveText(/批次刪除/);
  });

  test('未選擇文章時點擊刪除應該顯示錯誤', async ({ page }) => {
    // 進入批次模式
    await page.click('#batch-delete-btn');
    await page.waitForTimeout(500);
    
    // 不選擇任何文章，直接點擊刪除
    await page.click('#confirm-batch-delete-btn');
    await page.waitForTimeout(1000);
    
    // 應該顯示錯誤訊息 - 檢查頁面內容是否包含錯誤訊息
    const pageContent = await page.textContent('body');
    expect(pageContent).toContain('請至少選擇一篇文章');
  });

  test.skip('應該能成功批次刪除文章', async ({ page }) => {
    // 先建立測試文章
    await page.goto('/admin/posts/create');
    await page.waitForLoadState('networkidle');
    
    const testTitle1 = `測試批次刪除 ${Date.now()}-1`;
    const testTitle2 = `測試批次刪除 ${Date.now()}-2`;
    
    // 建立第一篇
    await page.fill('#title-input', testTitle1);
    await page.fill('.editor-content', '測試內容1');
    await page.click('button:has-text("發布")');
    await page.waitForTimeout(1000);
    
    // 建立第二篇
    await page.goto('/admin/posts/create');
    await page.waitForLoadState('networkidle');
    await page.fill('#title-input', testTitle2);
    await page.fill('.editor-content', '測試內容2');
    await page.click('button:has-text("發布")');
    await page.waitForTimeout(1000);
    
    // 回到文章列表
    await page.goto('/admin/posts');
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(1000);
    
    // 進入批次模式
    await page.click('#batch-delete-btn');
    await page.waitForTimeout(500);
    
    // 選擇測試文章
    const rows = page.locator('tbody tr');
    const rowCount = await rows.count();
    
    for (let i = 0; i < rowCount; i++) {
      const row = rows.nth(i);
      const title = await row.locator('td').first().textContent();
      if (title?.includes('測試批次刪除')) {
        const checkbox = row.locator('.post-checkbox');
        await checkbox.check();
        await page.waitForTimeout(200);
      }
    }
    
    // 點擊刪除按鈕
    await page.click('#confirm-batch-delete-btn');
    
    // 確認對話框
    await page.waitForTimeout(500);
    const confirmBtn = page.locator('button:has-text("確定"), button:has-text("刪除")');
    if (await confirmBtn.isVisible()) {
      await confirmBtn.click();
    }
    
    // 等待刪除完成
    await page.waitForTimeout(2000);
    
    // 檢查成功訊息
    const successToast = page.locator('text=/成功刪除.*篇文章/i');
    await expect(successToast).toBeVisible({ timeout: 5000 });
    
    // 驗證文章已被刪除
    await page.waitForLoadState('networkidle');
    const pageContent = await page.content();
    expect(pageContent).not.toContain(testTitle1);
    expect(pageContent).not.toContain(testTitle2);
  });

  test('批次模式下不應該顯示操作按鈕', async ({ page }) => {
    // 進入批次模式
    await page.click('#batch-delete-btn');
    await page.waitForTimeout(500);
    
    // 檢查第一行是否有操作欄
    const firstRow = page.locator('tbody tr').first();
    const cells = firstRow.locator('td');
    const cellCount = await cells.count();
    
    // 批次模式下應該沒有操作欄（只有checkbox, 標題, 狀態, 作者, 發布時間）
    expect(cellCount).toBe(5); // checkbox + 4個資料欄
    
    // 確認表格內沒有編輯/刪除按鈕（不包括工具列）
    const editBtnInTable = await firstRow.locator('button:has-text("編輯")').count();
    const deleteBtnInTable = await firstRow.locator('button:has-text("刪除")').count();
    
    expect(editBtnInTable).toBe(0);
    expect(deleteBtnInTable).toBe(0);
  });

  test('一般模式下應該顯示操作按鈕', async ({ page }) => {
    // 確保不在批次模式
    const toolbar = page.locator('#batch-toolbar');
    const isVisible = await toolbar.isVisible();
    
    if (isVisible) {
      await page.click('#cancel-batch-btn');
      await page.waitForTimeout(500);
    }
    
    // 檢查操作按鈕是否顯示
    const firstRow = page.locator('tbody tr').first();
    await expect(firstRow.locator('button:has-text("編輯")')).toBeVisible();
    await expect(firstRow.locator('button:has-text("刪除")')).toBeVisible();
  });
});
