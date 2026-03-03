// @ts-check
const { test, expect, DashboardPage } = require('./fixtures/page-objects');

/**
 * Dashboard 功能測試套件
 * 需要先登入才能訪問
 */
test.describe('Dashboard 功能測試', () => {
  let dashboardPage;

  test.beforeEach(async ({ authenticatedPage }) => {
    dashboardPage = new DashboardPage(authenticatedPage);
    await dashboardPage.goto();
  });

  test('應該正確顯示儀表板標題', async ({ authenticatedPage: page }) => {
    await expect(dashboardPage.heading).toBeVisible();
  });

  test('應該顯示統計卡片', async ({ authenticatedPage: page }) => {
    // 檢查統計卡片
    await expect(page.locator('text=總文章數').first()).toBeVisible();
    await expect(page.locator('text=總瀏覽量').first()).toBeVisible();
    await expect(page.locator('text=草稿數').first()).toBeVisible();
    await expect(page.locator('h3:has-text("已發布")').first()).toBeVisible();
  });

  test('應該顯示最近發布的文章', async ({ authenticatedPage: page }) => {
    const recentPostsHeading = page.locator('h2:has-text("最近發布的文章")');
    await expect(recentPostsHeading).toBeVisible();
    
    // 如果有文章，檢查文章列表
    const postsCount = await page.locator('text=最近發布的文章').locator('..').locator('h3').count();
    if (postsCount > 0) {
      // 檢查第一篇文章有標題和狀態
      const firstPost = page.locator('text=最近發布的文章').locator('..').locator('h3').first();
      await expect(firstPost).toBeVisible();
    }
  });

  test('應該顯示快速操作區塊', async ({ authenticatedPage: page }) => {
    await expect(page.locator('h2:has-text("快速操作")')).toBeVisible();
    
    // 檢查快速操作連結
    await expect(page.locator('text=新增文章')).toBeVisible();
    await expect(page.locator('text=管理文章')).toBeVisible();
    await expect(page.locator('text=使用者管理')).toBeVisible();
  });

  test('點擊新增文章應該導航到編輯頁面', async ({ authenticatedPage: page }) => {
    await page.click('text=新增文章');
    await expect(page).toHaveURL(/\/admin\/posts\/create/);
  });

  test('點擊管理文章應該導航到文章列表', async ({ authenticatedPage: page }) => {
    await page.click('text=管理文章');
    await expect(page).toHaveURL(/\/admin\/posts/);
  });

  test('側邊欄應該正確顯示', async ({ authenticatedPage: page }) => {
    // 檢查側邊欄選單項目
    await expect(page.locator('a:has-text("儀表板")')).toBeVisible();
    await expect(page.locator('a:has-text("文章管理")')).toBeVisible();
    await expect(page.locator('a:has-text("使用者管理")')).toBeVisible();
    await expect(page.locator('a:has-text("角色管理")')).toBeVisible();
    await expect(page.locator('a:has-text("標籤管理")')).toBeVisible();
    await expect(page.locator('a:has-text("系統設定")')).toBeVisible();
  });

  test('應該顯示使用者資訊', async ({ authenticatedPage: page }) => {
    // 檢查使用者郵箱顯示
    await expect(page.locator('text=admin@example.com')).toBeVisible();
  });
});
