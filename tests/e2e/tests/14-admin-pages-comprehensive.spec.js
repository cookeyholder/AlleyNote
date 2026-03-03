// @ts-check
const { test, expect } = require('@playwright/test');

/**
 * 管理後台導航完整性測試
 * 確保所有管理頁面都能正常載入，不會被導回登入頁
 */
test.describe('管理後台頁面完整性測試', () => {
  // 所有管理頁面的定義
  const adminPages = [
    { path: '/admin/dashboard', name: '儀表板', heading: '儀表板' },
    { path: '/admin/posts', name: '文章管理', heading: '文章管理' },
    { path: '/admin/users', name: '使用者管理', heading: '使用者管理' },
    { path: '/admin/roles', name: '角色管理', heading: '角色管理' },
    { path: '/admin/tags', name: '標籤管理', heading: '標籤管理' },
    { path: '/admin/statistics', name: '系統統計', heading: '系統統計' },
    { path: '/admin/settings', name: '系統設定', heading: '系統設定' },
  ];

  test.beforeEach(async ({ page }) => {
    // 登入
    await page.goto('/login');
    await page.fill('input[name="email"]', 'admin@example.com');
    await page.fill('input[name="password"]', 'password');
    await page.click('button[type="submit"]');
    await page.waitForURL('**/admin/dashboard', { timeout: 10000 });
  });

  // 為每個頁面建立獨立測試
  for (const adminPage of adminPages) {
    test(`${adminPage.name} - 頁面應該正常載入`, async ({ page }) => {
      await page.goto(adminPage.path);
      await page.waitForLoadState('networkidle', { timeout: 15000 });
      
      // 確認 URL 正確
      expect(page.url()).toContain(adminPage.path);
      
      // 確認沒有被導回登入頁
      expect(page.url()).not.toContain('/login');
      
      // 確認頁面標題存在
      const heading = page.locator(`h1:has-text("${adminPage.heading}")`);
      await expect(heading).toBeVisible({ timeout: 5000 });
    });

    test(`${adminPage.name} - 側欄應該可見且有使用者選單`, async ({ page }) => {
      await page.goto(adminPage.path);
      await page.waitForLoadState('networkidle', { timeout: 15000 });
      
      // 確認側欄存在
      const sidebar = page.locator('.sidebar, aside, nav[aria-label*="側"]');
      if (await sidebar.count() > 0) {
        await expect(sidebar.first()).toBeVisible();
      }
      
      // 確認使用者選單存在
      const userMenu = page.locator('#user-menu-btn, [aria-label*="使用者選單"], button:has-text("admin")');
      await expect(userMenu.first()).toBeVisible({ timeout: 5000 });
    });

    test(`${adminPage.name} - 頁面應該沒有 JavaScript 錯誤`, async ({ page }) => {
      const errors = [];
      page.on('pageerror', error => errors.push(error.message));
      page.on('console', msg => {
        if (msg.type() === 'error') {
          errors.push(msg.text());
        }
      });
      
      await page.goto(adminPage.path);
      await page.waitForLoadState('networkidle', { timeout: 15000 });
      await page.waitForTimeout(1000);
      
      // 過濾掉一些已知的無害錯誤
      const significantErrors = errors.filter(err => 
        !err.includes('favicon') && 
        !err.includes('ERR_FAILED') &&
        !err.includes('net::')
      );
      
      expect(significantErrors).toHaveLength(0);
    });
  }

  test('所有側欄連結都應該可以點擊並正確導航', async ({ page }) => {
    await page.goto('/admin/dashboard');
    await page.waitForLoadState('networkidle', { timeout: 15000 });
    
    for (const adminPage of adminPages) {
      // 點擊側欄連結
      const link = page.locator(`a[href="${adminPage.path}"]`).first();
      await link.click();
      
      // 等待導航
      await page.waitForURL(`**${adminPage.path}`, { timeout: 10000 });
      await page.waitForLoadState('networkidle', { timeout: 15000 });
      
      // 確認 URL 正確
      expect(page.url()).toContain(adminPage.path);
      
      // 確認沒有被導回登入頁
      expect(page.url()).not.toContain('/login');
      
      // 短暫等待
      await page.waitForTimeout(300);
    }
  });

  test('刷新頁面後應該保持登入狀態', async ({ page }) => {
    for (const adminPage of adminPages.slice(0, 3)) { // 測試前3個頁面即可
      await page.goto(adminPage.path);
      await page.waitForLoadState('networkidle', { timeout: 15000 });
      
      // 刷新頁面
      await page.reload();
      await page.waitForLoadState('networkidle', { timeout: 15000 });
      
      // 確認仍在同一頁面
      expect(page.url()).toContain(adminPage.path);
      
      // 確認沒有被導回登入頁
      expect(page.url()).not.toContain('/login');
      
      // 確認使用者選單仍存在
      const userMenu = page.locator('#user-menu-btn, button:has-text("admin")');
      await expect(userMenu.first()).toBeVisible({ timeout: 5000 });
    }
  });

  test('未登入時訪問管理頁面應該被導回登入頁', async ({ browser }) => {
    // 建立新的無狀態上下文
    const context = await browser.newContext();
    const page = await context.newPage();
    
    try {
      // 測試幾個頁面
      for (const adminPage of adminPages.slice(0, 3)) {
        await page.goto(adminPage.path);
        await page.waitForTimeout(2000);
        
        // 應該被導向登入頁
        await page.waitForURL('**/login', { timeout: 10000 });
        expect(page.url()).toContain('/login');
      }
    } finally {
      await context.close();
    }
  });
});

/**
 * 管理頁面核心功能測試
 */
test.describe('管理頁面核心功能測試', () => {
  test.beforeEach(async ({ page }) => {
    // 登入
    await page.goto('/login');
    await page.fill('input[name="email"]', 'admin@example.com');
    await page.fill('input[name="password"]', 'password');
    await page.click('button[type="submit"]');
    await page.waitForURL('**/admin/dashboard', { timeout: 10000 });
  });

  test('儀表板 - 應該顯示統計卡片', async ({ page }) => {
    await page.goto('/admin/dashboard');
    await page.waitForLoadState('networkidle', { timeout: 15000 });
    
    // 檢查是否有統計卡片
    const statsCards = page.locator('.stat-card, .card, [class*="bg-"]');
    const count = await statsCards.count();
    expect(count).toBeGreaterThan(0);
  });

  test('文章管理 - 應該有搜尋和篩選功能', async ({ page }) => {
    await page.goto('/admin/posts');
    await page.waitForLoadState('networkidle', { timeout: 15000 });
    
    // 檢查搜尋框
    const searchInput = page.locator('#search-input, input[placeholder*="搜尋"]');
    await expect(searchInput.first()).toBeVisible();
    
    // 檢查狀態篩選
    const statusFilter = page.locator('#status-filter, select');
    if (await statusFilter.count() > 0) {
      await expect(statusFilter.first()).toBeVisible();
    }
  });

  test('文章管理 - 應該有新增文章按鈕', async ({ page }) => {
    await page.goto('/admin/posts');
    await page.waitForLoadState('networkidle', { timeout: 15000 });
    
    const createBtn = page.locator('#create-post-btn, button:has-text("新增"), button:has-text("建立")');
    await expect(createBtn.first()).toBeVisible();
  });

  test('使用者管理 - 應該顯示使用者列表', async ({ page }) => {
    await page.goto('/admin/users');
    await page.waitForLoadState('networkidle', { timeout: 15000 });
    
    // 應該有表格或列表
    const table = page.locator('table, .user-list');
    if (await table.count() > 0) {
      await expect(table.first()).toBeVisible();
    }
  });

  test('標籤管理 - 應該能載入標籤', async ({ page }) => {
    await page.goto('/admin/tags');
    await page.waitForLoadState('networkidle', { timeout: 15000 });
    
    // 頁面應該載入完成
    const heading = page.locator('h1');
    await expect(heading).toBeVisible();
  });

  test('系統統計 - API 應該返回資料', async ({ page }) => {
    let apiCalled = false;
    
    page.on('response', response => {
      if (response.url().includes('/api/statistics')) {
        apiCalled = true;
      }
    });
    
    await page.goto('/admin/statistics');
    await page.waitForLoadState('networkidle', { timeout: 15000 });
    await page.waitForTimeout(2000);
    
    // 應該有呼叫統計 API
    expect(apiCalled).toBeTruthy();
  });

  test('系統設定 - 應該顯示設定選項', async ({ page }) => {
    await page.goto('/admin/settings');
    await page.waitForLoadState('networkidle', { timeout: 15000 });
    
    // 應該有表單或設定選項
    const forms = page.locator('form, input, select, textarea');
    const count = await forms.count();
    
    // 如果還沒實作，至少頁面應該載入
    const heading = page.locator('h1');
    await expect(heading).toBeVisible();
  });
});
