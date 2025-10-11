// @ts-check
const { test: base, expect } = require('@playwright/test');

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
   * 已登入的頁面 fixture
   */
  authenticatedPage: async ({ page }, use) => {
    // 執行登入流程
    await page.goto('/login');
    await page.fill('input[name="email"]', TEST_USER.email);
    await page.fill('input[name="password"]', TEST_USER.password);
    await page.click('button[type="submit"]');
    
    // 等待登入完成（導航到 dashboard）
    await page.waitForURL('**/admin/dashboard', { timeout: 10000 });
    
    // 提供已登入的頁面給測試使用
    await use(page);
  },
});

/**
 * 頁面物件 - 登入頁面
 */
class LoginPage {
  constructor(page) {
    this.page = page;
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
 * 頁面物件 - Dashboard
 */
class DashboardPage {
  constructor(page) {
    this.page = page;
    this.heading = page.locator('h1:has-text("儀表板")');
    this.totalPostsCard = page.locator('text=總文章數').locator('..');
    this.publishedCountCard = page.locator('text=已發布').locator('..');
  }

  async goto() {
    await this.page.goto('/admin/dashboard');
  }

  async getPostsCount() {
    const text = await this.totalPostsCard.locator('text=/\\d+/').first().textContent();
    return parseInt(text || '0', 10);
  }
}

/**
 * 頁面物件 - 文章管理
 */
class PostsManagementPage {
  constructor(page) {
    this.page = page;
    this.heading = page.locator('h1:has-text("文章管理")');
    this.newPostButton = page.locator('button:has-text("新增文章")');
    this.searchInput = page.locator('input[placeholder*="搜尋"]');
    this.searchButton = page.locator('button:has-text("搜尋")');
    this.resetButton = page.locator('button:has-text("重置")');
    this.postRows = page.locator('tbody tr');
  }

  async goto() {
    await this.page.goto('/admin/posts');
  }

  async searchPosts(keyword) {
    await this.searchInput.fill(keyword);
    await this.searchButton.click();
    await this.page.waitForTimeout(500); // 等待搜尋結果載入
  }

  async getPostsCount() {
    return await this.postRows.count();
  }

  async clickNewPost() {
    await this.newPostButton.click();
  }

  async editPost(title) {
    const row = this.page.locator(`tr:has-text("${title}")`);
    await row.locator('button:has-text("編輯")').click();
  }

  async deletePost(title) {
    const row = this.page.locator(`tr:has-text("${title}")`);
    await row.locator('button:has-text("刪除")').click();
  }
}

/**
 * 頁面物件 - 文章編輯器
 */
class PostEditorPage {
  constructor(page) {
    this.page = page;
    this.titleInput = page.locator('input[name="title"]');
    this.contentEditor = page.locator('.ck-editor__editable');
    this.statusSelect = page.locator('select[name="status"]');
    this.publishDateInput = page.locator('input[name="publish_date"]');
    this.excerptTextarea = page.locator('textarea[name="excerpt"]');
    this.submitButton = page.locator('button[type="submit"]');
    this.saveDraftButton = page.locator('button:has-text("儲存草稿")');
    this.cancelButton = page.locator('button:has-text("取消")');
  }

  async goto(postId = null) {
    if (postId) {
      await this.page.goto(`/admin/posts/${postId}/edit`);
    } else {
      await this.page.goto('/admin/posts/create');
    }
  }

  async fillPost({ title, content, status, publishDate, excerpt }) {
    if (title) await this.titleInput.fill(title);
    
    if (content) {
      // CKEditor 需要特殊處理
      await this.contentEditor.click();
      await this.contentEditor.fill(content);
    }
    
    if (status) await this.statusSelect.selectOption(status);
    if (publishDate) await this.publishDateInput.fill(publishDate);
    if (excerpt) await this.excerptTextarea.fill(excerpt);
  }

  async submitPost() {
    await this.submitButton.click();
    await this.page.waitForURL('**/admin/posts', { timeout: 10000 });
  }

  async saveDraft() {
    await this.saveDraftButton.click();
  }
}

module.exports = {
  test,
  expect,
  TEST_USER,
  LoginPage,
  DashboardPage,
  PostsManagementPage,
  PostEditorPage,
};
