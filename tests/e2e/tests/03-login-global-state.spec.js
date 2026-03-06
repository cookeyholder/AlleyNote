// @ts-check
const {
  test,
  expect,
  LoginPage,
  TEST_USER,
} = require("./fixtures/page-objects");

test.describe("登入全域狀態回歸測試", () => {
  test("登入成功後應寫入使用者狀態並離開 /login", async ({ page }) => {
    const loginPage = new LoginPage(page);
    await loginPage.goto();

    await loginPage.login(TEST_USER.email, TEST_USER.password);

    await expect
      .poll(
        async () =>
          await page.evaluate(() => {
            const rawUser = localStorage.getItem("alleynote_user");
            if (!rawUser) {
              return null;
            }

            try {
              const parsed = JSON.parse(rawUser);
              return parsed?.email || parsed?.username || "present";
            } catch {
              return "invalid-json";
            }
          }),
        { timeout: 15000 },
      )
      .toBeTruthy();

    await expect(page).toHaveURL(/\/admin\/dashboard/, { timeout: 15000 });
  });
});
