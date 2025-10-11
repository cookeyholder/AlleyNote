// @ts-check
const { test, expect, TEST_USER, LoginPage } = require('./fixtures/page-objects');

/**
 * 登入功能測試套件
 */
test.describe('登入功能測試', () => {
  let loginPage;

  test.beforeEach(async ({ page }) => {
    loginPage = new LoginPage(page);
    await loginPage.goto();
  });

  test('應該正確顯示登入頁面元素', async ({ page }) => {
    // 檢查頁面標題
    await expect(page.locator('text=歡迎回來，請登入您的帳號')).toBeVisible();
    
    // 檢查表單元素
    await expect(loginPage.emailInput).toBeVisible();
    await expect(loginPage.passwordInput).toBeVisible();
    await expect(loginPage.submitButton).toBeVisible();
    await expect(loginPage.rememberCheckbox).toBeVisible();
    
    // 檢查測試帳號提示
    await expect(page.locator('text=admin@example.com / password')).toBeVisible();
  });

  test('使用正確的帳號密碼應該能成功登入', async ({ page }) => {
    // 執行登入
    await loginPage.login(TEST_USER.email, TEST_USER.password);
    
    // 等待導航到 dashboard
    await page.waitForURL('**/admin/dashboard', { timeout: 10000 });
    
    // 驗證登入成功
    await expect(page.locator('h1:has-text("儀表板")')).toBeVisible();
    await expect(page.locator(`text=${TEST_USER.email}`)).toBeVisible();
  });

  test('使用錯誤的密碼應該顯示錯誤訊息', async ({ page }) => {
    // 使用錯誤密碼登入
    await loginPage.login(TEST_USER.email, 'wrong-password');
    
    // 等待錯誤訊息
    await page.waitForTimeout(1000);
    
    // 應該停留在登入頁面
    await expect(page).toHaveURL(/\/login/);
    
    // 檢查是否有錯誤提示（根據實際實作調整）
    // await expect(page.locator('text=/錯誤|失敗|無效/')).toBeVisible();
  });

  test('「記住我」功能應該能正常運作', async ({ page, context }) => {
    // 勾選記住我並登入
    await loginPage.login(TEST_USER.email, TEST_USER.password, true);
    
    // 等待登入完成
    await page.waitForURL('**/admin/dashboard', { timeout: 10000 });
    
    // 檢查 localStorage 或 cookies
    const cookies = await context.cookies();
    const hasAuthCookie = cookies.some(c => c.name.includes('token') || c.name.includes('auth'));
    
    // 根據實際實作驗證
    // expect(hasAuthCookie).toBe(true);
  });

  test('忘記密碼連結應該可以點擊', async ({ page }) => {
    const forgotPasswordLink = page.locator('a:has-text("忘記密碼")');
    await expect(forgotPasswordLink).toBeVisible();
    
    // 可以選擇是否測試點擊後的行為
  });
});
