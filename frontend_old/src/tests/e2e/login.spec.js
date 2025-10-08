import { test, expect } from '@playwright/test';

test.describe('登入頁面', () => {
  test.beforeEach(async ({ page }) => {
    await page.goto('/login');
  });

  test('應該顯示登入表單', async ({ page }) => {
    // 檢查標題
    await expect(page.locator('h1')).toContainText('登入');

    // 檢查表單欄位
    await expect(page.locator('input[name="email"]')).toBeVisible();
    await expect(page.locator('input[name="password"]')).toBeVisible();
    await expect(page.locator('button[type="submit"]')).toBeVisible();
  });

  test('應該驗證必填欄位', async ({ page }) => {
    // 點擊登入按鈕但不填寫任何欄位
    await page.locator('button[type="submit"]').click();

    // 檢查是否顯示錯誤訊息（HTML5 驗證）
    const emailInput = page.locator('input[name="email"]');
    const isInvalid = await emailInput.evaluate((el) => !el.validity.valid);
    expect(isInvalid).toBe(true);
  });

  test('應該驗證電子郵件格式', async ({ page }) => {
    // 輸入無效的電子郵件
    await page.locator('input[name="email"]').fill('invalid-email');
    await page.locator('input[name="password"]').fill('password123');
    await page.locator('button[type="submit"]').click();

    // 檢查是否顯示錯誤訊息
    const emailInput = page.locator('input[name="email"]');
    const isInvalid = await emailInput.evaluate((el) => !el.validity.valid);
    expect(isInvalid).toBe(true);
  });

  test('應該處理登入失敗', async ({ page }) => {
    // Mock API 回應
    await page.route('**/api/auth/login', (route) => {
      route.fulfill({
        status: 401,
        contentType: 'application/json',
        body: JSON.stringify({
          success: false,
          message: '帳號或密碼錯誤',
        }),
      });
    });

    // 填寫表單
    await page.locator('input[name="email"]').fill('test@example.com');
    await page.locator('input[name="password"]').fill('wrongpassword');
    await page.locator('button[type="submit"]').click();

    // 等待並檢查錯誤訊息
    await expect(page.locator('.toast-error')).toBeVisible({ timeout: 3000 });
    await expect(page.locator('.toast-error')).toContainText('帳號或密碼錯誤');
  });

  test('應該成功登入並導向後台', async ({ page }) => {
    // Mock API 回應
    await page.route('**/api/auth/login', (route) => {
      route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({
          success: true,
          data: {
            token: 'fake-jwt-token',
            expires_in: 3600,
            user: {
              id: 1,
              username: 'admin',
              email: 'admin@example.com',
              role: 'admin',
            },
          },
        }),
      });
    });

    // Mock CSRF token API
    await page.route('**/api/csrf-token', (route) => {
      route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({
          success: true,
          data: {
            csrf_token: 'fake-csrf-token',
          },
        }),
      });
    });

    // 填寫表單
    await page.locator('input[name="email"]').fill('admin@example.com');
    await page.locator('input[name="password"]').fill('password123');
    await page.locator('button[type="submit"]').click();

    // 等待導向到後台
    await page.waitForURL('**/admin/**', { timeout: 5000 });
    
    // 檢查是否在後台頁面
    expect(page.url()).toContain('/admin');
  });

  test('應該在手機版正常顯示', async ({ page, viewport }) => {
    // 設定手機視窗大小
    await page.setViewportSize({ width: 375, height: 667 });

    // 檢查表單是否正常顯示
    await expect(page.locator('input[name="email"]')).toBeVisible();
    await expect(page.locator('input[name="password"]')).toBeVisible();
    await expect(page.locator('button[type="submit"]')).toBeVisible();

    // 檢查輸入框是否有足夠的大小（觸控友善）
    const submitButton = page.locator('button[type="submit"]');
    const box = await submitButton.boundingBox();
    expect(box?.height).toBeGreaterThanOrEqual(44); // 最小觸控目標
  });
});
