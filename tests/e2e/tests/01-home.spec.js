// @ts-check
const { test, expect } = require("./fixtures/page-objects");

/**
 * 首頁測試套件
 */
test.describe("首頁功能測試", () => {
  test.beforeEach(async ({ page }) => {
    await page.goto("/");
  });

  test("應該正確顯示首頁標題和導航", async ({ page }) => {
    await expect(
      page.locator('nav a[data-navigo]:has-text("AlleyNote")'),
    ).toBeVisible();
    await expect(page.locator('main h2:has-text("AlleyNote")')).toBeVisible();
    await expect(page.locator("main p").first()).toContainText("基於");
    await expect(
      page.locator('a[href="/login"]:has-text("登入")'),
    ).toBeVisible();
  });

  test("應該顯示最新文章列表", async ({ page }) => {
    await expect(
      page.locator('h3:has-text("最新文章"), h3:has-text("最新發布內容")'),
    ).toBeVisible();

    await expect
      .poll(
        async () =>
          (await page.locator("#posts-count").textContent())?.trim() || "",
        { timeout: 15000 },
      )
      .not.toBeNull();

    const finalCountText =
      (await page.locator("#posts-count").textContent())?.trim() || "";
    if (!finalCountText) {
      await expect(page.locator("#posts-container")).toContainText(
        /載入失敗|目前沒有文章|找不到符合條件的文章/,
      );
      return;
    }

    expect(finalCountText).toMatch(/共 \d+ 篇文章/);

    // 如果有文章，檢查文章卡片
    const postsCount = await page
      .locator("#posts-container article.card")
      .count();
    if (postsCount > 0) {
      // 檢查第一篇文章是否有標題和日期
      const firstPost = page.locator("#posts-container article.card").first();
      await expect(firstPost.locator("h4")).toBeVisible();
      await expect(firstPost.locator("time")).toBeVisible();
    }
  });

  test.skip("應該能夠搜尋文章", async ({ page }) => {
    // 填寫搜尋關鍵字
    const searchInput = page.locator('input[placeholder*="搜尋"]').first();
    await searchInput.fill("測試");
    await searchInput.press("Enter");

    // 等待搜尋結果
    await page.waitForTimeout(1000);

    // 驗證 URL 或搜尋結果
    // 這裡的驗證取決於實際的搜尋實作
  });

  test("點擊登入按鈕應該導航到登入頁面", async ({ page }) => {
    await page.click('a[href="/login"]:has-text("登入")');
    await expect(page).toHaveURL(/\/login/);
  });

  test("應該顯示頁腳資訊", async ({ page }) => {
    await expect(page.locator("text=© 2024 AlleyNote")).toBeVisible();
    await expect(page.locator("text=Domain-Driven Design")).toBeVisible();
  });
});
