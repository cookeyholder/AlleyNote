// @ts-check
const { test: base, expect } = require('@playwright/test');
const { SecureBasePage } = require('./secure-ui-base');

/**
 * 測試用的使用者認證資訊
 */
const TEST_USER = {
  email: 'admin@example.com',
  password: 'password',
};

/**
 * 擴展 Playwright test，加入自訂 fixtures
 */
const test = base.extend({
  /**
   * 基本的安全頁面物件
   */
  securePage: async ({ page }, use) => {
    const secureBase = new SecureBasePage(page);
    await use(secureBase);
  },

  /**
   * 已登入的管理員頁面 fixture
   */
  adminPage: async ({ page }, use) => {
    // 執行登入流程
    const loginPage = new LoginPage(page);
    await loginPage.goto();
    await loginPage.login(TEST_USER.email, TEST_USER.password);
    
    // 等待登入完成
    await page.waitForURL('**/admin/dashboard', { timeout: 10000 });
    
    // 提供頁面給測試使用
    await use(page);
  },
});

/**
 * 頁面物件 - 登入頁面
 */
class LoginPage extends SecureBasePage {
  constructor(page) {
    super(page);
    this.emailInput = page.locator('input[name="email"]');
    this.passwordInput = page.locator('input[name="password"]');
    this.submitButton = page.locator('button[type="submit"]');
    this.rememberCheckbox = page.locator('input[type="checkbox"]');
  }

  async goto() {
    await this.page.goto('/login');
  }

  async login(email, password, remember = false) {
    await this.emailInput.fill(email);
    await this.passwordInput.fill(password);
    if (remember) {
      await this.rememberCheckbox.check();
    }
    await this.submitButton.click();
  }
}

/**
 * 頁面物件 - 文章詳情 (Public)
 */
class PublicPostPage extends SecureBasePage {
  constructor(page) {
    super(page);
    this.title = page.locator('.post-title');
    this.content = page.locator('.post-content');
  }

  async goto(postId) {
    await this.page.goto(`/posts/${postId}`);
  }

  async assertSafeContent(expectedText, expectedTags = []) {
    await this.assertRichTextRendered('.post-content', expectedText, expectedTags);
  }
}

/**
 * 頁面物件 - Dashboard
 */
class DashboardPage extends SecureBasePage {
  constructor(page) {
    super(page);
    this.heading = page.locator('main h1:has-text("儀表板")');
    this.totalPostsCard = page.locator('text=總文章數').locator('..');
  }

  async goto() {
    await this.page.goto('/admin/dashboard');
  }
}

/**
 * 頁面物件 - 文章管理
 */
class PostsManagementPage extends SecureBasePage {
  constructor(page) {
    super(page);
    this.postRows = page.locator('tbody tr');
  }

  async goto() {
    await this.page.goto('/admin/posts');
  }
}

module.exports = {
  test,
  expect,
  TEST_USER,
  LoginPage,
  PublicPostPage,
  DashboardPage,
  PostsManagementPage,
};
