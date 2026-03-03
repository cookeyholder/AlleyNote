// @ts-check
const { test, expect, LoginPage, TEST_USER } = require('./fixtures/page-objects');

test.describe('身分認證安全性測試 (Secure-UI Spec)', () => {
  
  test('管理員應該能成功登入且頁面無洩漏', async ({ page }) => {
    const loginPage = new LoginPage(page);
    await loginPage.goto();
    
    // 登入前檢查
    await loginPage.assertNoSensitiveInfoLeaked();
    
    // 執行登入
    await loginPage.login(TEST_USER.email, TEST_USER.password);
    
    // 驗證導航
    await expect(page).toHaveURL(/.*dashboard/);
    
    // 登入後檢查
    await loginPage.assertNoSensitiveInfoLeaked();
  });

  test('無效登入應顯示錯誤訊息且不洩漏系統細節', async ({ page }) => {
    const loginPage = new LoginPage(page);
    await loginPage.goto();
    
    await loginPage.login('wrong@example.com', 'wrongpassword');
    
    // 檢查錯誤訊息是否友善且不包含敏感資訊
    await expect(loginPage.errorAlert).toBeVisible();
    await loginPage.assertNoSensitiveInfoLeaked();
  });
});
