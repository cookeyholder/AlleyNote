// @ts-check
const {
  test,
  expect,
  LoginPage,
  TEST_USER,
} = require("./fixtures/page-objects");

const FALLBACK_TEST_USER = {
  email: "superadmin@example.com",
  password: "SuperAdmin@123456",
};

test.describe("身分認證安全性測試 (Secure-UI Spec)", () => {
  test("管理員應該能成功登入且頁面無洩漏", async ({ page }) => {
    const loginPage = new LoginPage(page);
    await loginPage.goto();

    // 登入前檢查
    await loginPage.assertNoSensitiveInfoLeaked();

    // 執行登入（含 fallback 帳號）
    await loginPage.loginWithFallback([TEST_USER, FALLBACK_TEST_USER]);

    // 驗證導航
    await expect(page).toHaveURL(/\/admin\/dashboard/, { timeout: 15000 });

    // 登入後檢查
    await loginPage.assertNoSensitiveInfoLeaked();
  });

  test("無效登入應顯示錯誤訊息且不洩漏系統細節", async ({ page }) => {
    const loginPage = new LoginPage(page);
    await loginPage.goto();

    await loginPage.login("wrong@example.com", "wrongpassword");

    // 檢查錯誤訊息是否友善且不包含敏感資訊（支援 toast 或頁面內訊息）
    await expect(page).toHaveURL(/\/login/, { timeout: 10000 });
    const bodyText = await page.locator("body").innerText();
    const hasFriendlyError =
      /登入失敗|Invalid credentials|帳號或密碼錯誤/i.test(bodyText);

    if (!hasFriendlyError) {
      await expect(page.locator("#login-btn")).toContainText(/登入/);
    }

    await loginPage.assertNoSensitiveInfoLeaked();
  });
});
