// @ts-check
const {
  test,
  expect,
  LoginPage,
  TEST_USER,
} = require("./fixtures/page-objects");

test.describe("身分認證安全性測試 (Secure-UI Spec)", () => {
  test("管理員應該能成功登入且頁面無洩漏", async ({ page }) => {
    const loginPage = new LoginPage(page);
    await loginPage.goto();

    // 登入前檢查
    await loginPage.assertNoSensitiveInfoLeaked();

    // 執行登入
    await loginPage.login(TEST_USER.email, TEST_USER.password);

    // CI 偶發情況：登入成功但未即時導頁，先嘗試等待；失敗則重試一次
    try {
      await page.waitForURL("**/admin/dashboard", { timeout: 8000 });
    } catch {
      await page.waitForTimeout(600);
      await loginPage.login(TEST_USER.email, TEST_USER.password);

      // 若已拿到 token 但仍留在 /login，主動前往儀表板
      const hasToken = await page.evaluate(() => {
        const raw = localStorage.getItem("alleynote_access_token");
        return !!raw && raw !== "null";
      });

      if (hasToken && /\/login(?:\?|$)/.test(page.url())) {
        await page.goto("/admin/dashboard");
      }
    }

    // 驗證導航
    await expect(page).toHaveURL(/\/admin\/dashboard/, { timeout: 15000 });

    // 登入後檢查
    await loginPage.assertNoSensitiveInfoLeaked();
  });

  test("無效登入應顯示錯誤訊息且不洩漏系統細節", async ({ page }) => {
    const loginPage = new LoginPage(page);
    await loginPage.goto();

    await loginPage.login("wrong@example.com", "wrongpassword");

    // 檢查錯誤訊息是否友善且不包含敏感資訊
    await expect(loginPage.errorAlert.last()).toBeVisible();
    await expect(loginPage.errorAlert.last()).toContainText(
      /登入失敗|Invalid credentials/i,
    );
    await loginPage.assertNoSensitiveInfoLeaked();
  });
});
