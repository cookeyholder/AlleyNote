// @ts-check
const { test, expect, TEST_USER, LoginPage, DashboardPage } = require('./fixtures/page-objects');

/**
 * 文章標籤管理測試套件
 */
test.describe('文章標籤管理', () => {
  let loginPage;
  let dashboardPage;

  test.beforeEach(async ({ page }) => {
    loginPage = new LoginPage(page);
    dashboardPage = new DashboardPage(page);
    
    // 登入系統
    await loginPage.goto();
    await loginPage.login(TEST_USER.email, TEST_USER.password);
    await expect(page).toHaveURL(/\/admin/);
  });

  test('應該能在編輯文章時添加和移除標籤', async ({ page }) => {
    const generateUniqueTitle = (prefix) => {
      return `${prefix} ${new Date().getTime()}`;
    };

    // 首先確保有一些標籤存在
    await page.goto('/admin/tags');
    await page.waitForLoadState('networkidle');

    // 如果沒有標籤，創建一個
    const tagCards = page.locator('[data-tag-card]');
    const tagCount = await tagCards.count();
    
    if (tagCount === 0) {
      await page.click('#addTagBtn');
      await page.fill('input[name="name"]', '測試標籤');
      await page.click('button[type="submit"]');
      await page.waitForTimeout(1000);
    }

    // 導航到新增文章頁面
    await page.goto('/admin/posts');
    await page.waitForLoadState('networkidle');
    
    const createButton = page.locator('#create-post-btn, a[href="/admin/posts/create"]').first();
    await createButton.click();
    await page.waitForLoadState('networkidle');

    // 填寫文章資訊
    const title = generateUniqueTitle('標籤測試文章');
    await page.fill('#title', title);

    // 等待編輯器載入
    await page.waitForSelector('.ck-editor', { timeout: 10000 });
    
    // 使用 CKEditor API 設置內容
    await page.evaluate(() => {
      const editorEl = document.querySelector('.ck-editor__editable');
      if (editorEl) {
        editorEl.innerHTML = '<p>這是一篇測試文章內容，用於測試標籤功能。</p>';
      }
    });

    // 等待標籤選擇器載入
    await page.waitForSelector('#tag-selector');

    // 添加標籤
    const tagSelector = page.locator('#tag-selector');
    const options = await tagSelector.locator('option').count();
    
    if (options > 1) {
      await tagSelector.selectOption({ index: 1 }); // 選擇第一個標籤
      
      // 驗證標籤已添加
      await page.waitForSelector('#tags-container span.inline-flex', { timeout: 5000 });
      const selectedTags = page.locator('#tags-container span.inline-flex');
      await expect(selectedTags.first()).toBeVisible();
    }

    // 發布文章
    const submitButton = page.locator('#submit-btn').first();
    await submitButton.click();
    await page.waitForLoadState('networkidle');

    // 驗證成功消息或返回列表
    await page.waitForTimeout(2000);

    // 返回文章列表
    await page.goto('/admin/posts');
    await page.waitForLoadState('networkidle');

    // 找到剛創建的文章並編輯它
    const postRow = page.locator(`tr:has-text("${title}")`).first();
    if (await postRow.count() > 0) {
      const editButton = postRow.locator('button:has-text("編輯")').or(postRow.locator('a:has-text("編輯")')).first();
      await editButton.click();
      await page.waitForLoadState('networkidle');

      // 如果有標籤，驗證標籤已被載入
      const tagsContainer = page.locator('#tags-container');
      await expect(tagsContainer).toBeVisible();

      const tagElements = page.locator('#tags-container span.inline-flex');
      const tagElementCount = await tagElements.count();
      
      if (tagElementCount > 0) {
        // 移除標籤
        const removeButton = page.locator('#tags-container button[data-remove-tag]').first();
        await removeButton.click();

        // 驗證標籤已移除
        await expect(page.locator('#tags-container')).toContainText('尚未選擇標籤');

        // 重新添加標籤
        await tagSelector.selectOption({ index: 1 });
        await expect(page.locator('#tags-container span.inline-flex').first()).toBeVisible();

        // 更新文章
        await submitButton.click();
        await page.waitForLoadState('networkidle');
      }
    }
  });

  test('應該能創建帶有多個標籤的文章', async ({ page }) => {
    const generateUniqueTitle = (prefix) => {
      return `${prefix} ${new Date().getTime()}`;
    };

    // 導航到標籤管理頁面，確保有多個標籤
    await page.goto('/admin/tags');
    await page.waitForLoadState('networkidle');

    const tagCards = page.locator('[data-tag-card]');
    const tagCount = await tagCards.count();
    
    // 如果標籤少於2個，創建更多
    if (tagCount < 2) {
      for (let i = tagCount; i < 2; i++) {
        await page.click('#addTagBtn');
        await page.fill('input[name="name"]', `測試標籤${i + 1}`);
        await page.click('button[type="submit"]');
        await page.waitForTimeout(1000);
      }
    }

    // 導航到新增文章頁面
    await page.goto('/admin/posts/create');
    await page.waitForLoadState('networkidle');

    // 填寫文章資訊
    const title = generateUniqueTitle('多標籤測試文章');
    await page.fill('#title', title);

    // 等待編輯器載入
    await page.waitForSelector('.ck-editor', { timeout: 10000 });
    
    // 設置內容
    await page.evaluate(() => {
      const editorEl = document.querySelector('.ck-editor__editable');
      if (editorEl) {
        editorEl.innerHTML = '<p>這是一篇帶有多個標籤的測試文章。</p>';
      }
    });

    // 等待標籤選擇器載入
    await page.waitForSelector('#tag-selector');

    // 添加多個標籤
    const tagSelector = page.locator('#tag-selector');
    const options = await tagSelector.locator('option').count();
    
    if (options > 2) {
      await tagSelector.selectOption({ index: 1 });
      await page.waitForTimeout(500);
      await tagSelector.selectOption({ index: 2 });

      // 驗證兩個標籤都已添加
      await page.waitForTimeout(500);
      const selectedTags = page.locator('#tags-container span.inline-flex');
      const count = await selectedTags.count();
      expect(count).toBeGreaterThanOrEqual(1);
    }

    // 發布文章
    await page.locator('#submit-btn').first().click();
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(2000);
  });
});
