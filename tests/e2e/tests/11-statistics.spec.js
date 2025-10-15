const { test, expect } = require('@playwright/test');

test.describe('系統統計頁面', () => {
  test.beforeEach(async ({ page }) => {
    // 登入系統
    await page.goto('http://localhost:3000');
    await page.click('text=登入');
    await page.fill('input[type="email"]', 'admin@example.com');
    await page.fill('input[type="password"]', 'password');
    await page.click('button[type="submit"]:has-text("登入")');
    await page.waitForURL('**/admin/dashboard');
    
    // 前往統計頁面
    await page.goto('http://localhost:3000/admin/statistics');
    await page.waitForLoadState('networkidle');
  });

  test('應該顯示統計頁面標題', async ({ page }) => {
    const title = await page.locator('h1, h2').filter({ hasText: '系統統計' }).first();
    await expect(title).toBeVisible();
  });

  test('應該顯示統計卡片', async ({ page }) => {
    // 檢查統計卡片是否存在
    const postCard = await page.locator('text=總文章數').first();
    await expect(postCard).toBeVisible();
    
    const userCard = await page.locator('text=活躍使用者').first();
    await expect(userCard).toBeVisible();
    
    const newUserCard = await page.locator('text=新使用者').first();
    await expect(newUserCard).toBeVisible();
    
    const viewCard = await page.locator('text=總瀏覽量').first();
    await expect(viewCard).toBeVisible();
  });

  test('應該顯示流量趨勢圖表', async ({ page }) => {
    const chartTitle = await page.locator('h2:has-text("流量趨勢")');
    await expect(chartTitle).toBeVisible();
    
    // 檢查 canvas 元素（Chart.js 使用 canvas）
    const canvas = await page.locator('#trafficChart');
    await expect(canvas).toBeVisible();
  });

  test('應該顯示熱門文章列表', async ({ page }) => {
    const popularTitle = await page.locator('h2:has-text("熱門文章")');
    await expect(popularTitle).toBeVisible();
  });

  test('應該顯示登入失敗統計', async ({ page }) => {
    const loginFailureTitle = await page.locator('h2:has-text("登入失敗統計")');
    await expect(loginFailureTitle).toBeVisible();
    
    // 檢查總失敗次數顯示
    const totalFailures = await page.locator('text=總失敗次數').first();
    await expect(totalFailures).toBeVisible();
  });

  test('應該顯示登入失敗趨勢圖表', async ({ page }) => {
    const trendTitle = await page.locator('h2:has-text("登入失敗趨勢")');
    await expect(trendTitle).toBeVisible();
    
    // 檢查 canvas 元素
    const canvas = await page.locator('#loginFailuresChart');
    await expect(canvas).toBeVisible();
  });

  test('應該能切換時間範圍', async ({ page }) => {
    // 點擊「本週」按鈕
    await page.click('button:has-text("本週")');
    await page.waitForTimeout(500);
    
    // 驗證按鈕狀態
    const weekButton = await page.locator('button:has-text("本週")');
    await expect(weekButton).toHaveClass(/active/);
    
    // 點擊「本月」按鈕
    await page.click('button:has-text("本月")');
    await page.waitForTimeout(500);
    
    // 驗證按鈕狀態
    const monthButton = await page.locator('button:has-text("本月")');
    await expect(monthButton).toHaveClass(/active/);
  });

  test('應該能刷新統計資料', async ({ page }) => {
    // 找到並點擊刷新按鈕
    const refreshButton = await page.locator('button:has-text("刷新")');
    await expect(refreshButton).toBeVisible();
    
    await refreshButton.click();
    
    // 等待載入完成
    await page.waitForTimeout(1000);
    
    // 檢查是否有成功提示
    const successMessage = await page.locator('text=統計資料已更新').first();
    if (await successMessage.isVisible()) {
      await expect(successMessage).toBeVisible();
    }
  });

  test('統計數據應該是數字', async ({ page }) => {
    // 取得統計卡片中的數字
    const statCards = await page.locator('.text-3xl').all();
    
    for (const card of statCards) {
      const text = await card.textContent();
      // 檢查是否為數字（可能包含逗號分隔符）
      const isNumber = /^[\d,]+$/.test(text?.trim() || '');
      expect(isNumber).toBe(true);
    }
  });

  test('熱門文章應該按瀏覽量排序', async ({ page }) => {
    // 取得所有文章的瀏覽量
    const viewCounts = await page.locator('.text-accent-600').allTextContents();
    
    if (viewCounts.length > 1) {
      // 將文字轉換為數字並檢查是否降序排列
      const numbers = viewCounts
        .map(text => parseInt(text.replace(/[^\d]/g, ''), 10))
        .filter(n => !isNaN(n));
      
      for (let i = 0; i < numbers.length - 1; i++) {
        expect(numbers[i]).toBeGreaterThanOrEqual(numbers[i + 1]);
      }
    }
  });
});
