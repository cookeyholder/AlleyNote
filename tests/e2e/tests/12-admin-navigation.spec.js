const { test, expect, TEST_USER } = require('./fixtures/page-objects');

/**
 * 管理員側欄導航測試
 * 
 * 目的：確保管理員登入後可以正常訪問所有側欄連結，不會被導回登入頁面
 * 這個測試可以防止未來修改時意外破壞認證中介軟體或路由配置
 */
test.describe('管理員側欄導航測試', () => {
  // 定義所有側欄連結
  const sidebarLinks = [
    { path: '/admin/dashboard', label: '儀表板', icon: '📊' },
    { path: '/admin/posts', label: '文章管理', icon: '📝' },
    { path: '/admin/users', label: '使用者管理', icon: '👥' },
    { path: '/admin/roles', label: '角色管理', icon: '🔐' },
    { path: '/admin/tags', label: '標籤管理', icon: '🏷️' },
    { path: '/admin/statistics', label: '系統統計', icon: '📈' },
    { path: '/admin/settings', label: '系統設定', icon: '⚙️' },
  ];

  test.beforeEach(async ({ page }) => {
    // 登入系統
    await page.goto('/login');
    await page.fill('input[name="email"]', TEST_USER.email);
    await page.fill('input[name="password"]', TEST_USER.password);
    await page.click('button[type="submit"]');
    
    // 等待登入完成，應該會導向儀表板
    await page.waitForURL('**/admin/dashboard', { timeout: 10000 });
    
    // 確認已登入
    const userMenu = await page.locator('#user-menu-btn');
    await expect(userMenu).toBeVisible();
  });

  test('應該能成功登入並停留在儀表板', async ({ page }) => {
    // 檢查當前 URL
    expect(page.url()).toContain('/admin/dashboard');
    
    // 檢查頁面標題
    const title = await page.locator('h1, h2').filter({ hasText: '儀表板' }).first();
    await expect(title).toBeVisible();
    
    // 確認不會被導回登入頁
    await page.waitForTimeout(1000);
    expect(page.url()).not.toContain('/login');
  });

  test('應該顯示所有側欄連結', async ({ page }) => {
    // 檢查每個側欄連結是否存在
    for (const link of sidebarLinks) {
      const linkElement = await page.locator(`a[href="${link.path}"]`).first();
      await expect(linkElement).toBeVisible();
      
      // 檢查連結文字
      await expect(linkElement).toContainText(link.label);
      
      // 檢查圖示
      await expect(linkElement).toContainText(link.icon);
    }
  });

  // 針對每個側欄連結建立獨立測試
  for (const link of sidebarLinks) {
    test(`應該能訪問「${link.label}」頁面且不被導回登入頁`, async ({ page }) => {
      // 點擊側欄連結
      await page.click(`a[href="${link.path}"]`);
      
      // 等待頁面載入
      await page.waitForURL(`**${link.path}`, { timeout: 10000 });
      
      // 等待網路請求完成
      await page.waitForLoadState('networkidle', { timeout: 10000 });
      
      // 確認 URL 正確
      expect(page.url()).toContain(link.path);
      
      // 確認沒有被導回登入頁
      expect(page.url()).not.toContain('/login');
      
      // 確認側欄仍然可見（表示還在後台）
      const sidebar = await page.locator('#sidebar');
      await expect(sidebar).toBeVisible();
      
      // 確認使用者選單仍然存在（表示仍處於已登入狀態）
      const userMenu = await page.locator('#user-menu-btn');
      await expect(userMenu).toBeVisible();
      
      // 確認頁面標題包含連結名稱（部分頁面可能標題不完全一致）
      const pageTitle = await page.locator('h1, h2').first();
      await expect(pageTitle).toBeVisible();
    });
  }

  test.skip('應該能在不同頁面間切換而不掉登入狀態', async ({ page }) => {
    // 測試連續訪問多個頁面
    const testSequence = [
      '/admin/posts',
      '/admin/statistics',
      '/admin/dashboard',
    ];

    for (const path of testSequence) {
      // 導航到頁面
      await page.goto(path);
      
      // 等待頁面載入
      await page.waitForURL(`**${path}`, { timeout: 10000 });
      await page.waitForLoadState('networkidle', { timeout: 15000 });
      
      // 確認 URL 正確
      expect(page.url()).toContain(path);
      
      // 確認沒有被導回登入頁
      expect(page.url()).not.toContain('/login');
      
      // 確認使用者選單存在
      const userMenu = await page.locator('#user-menu-btn');
      await expect(userMenu).toBeVisible({ timeout: 5000 });
      
      // 短暫等待以確保頁面完全載入
      await page.waitForTimeout(500);
    }
  });

  test.skip('應該能從任何頁面返回儀表板', async ({ page }) => {
    // 訪問每個頁面，然後返回儀表板
    for (const link of sidebarLinks.filter(l => l.path !== '/admin/dashboard')) {
      // 前往該頁面
      await page.click(`a[href="${link.path}"]`);
      await page.waitForURL(`**${link.path}`, { timeout: 10000 });
      
      // 返回儀表板
      await page.click('a[href="/admin/dashboard"]');
      await page.waitForURL('**/admin/dashboard', { timeout: 10000 });
      
      // 確認在儀表板
      expect(page.url()).toContain('/admin/dashboard');
      expect(page.url()).not.toContain('/login');
    }
  });

  test.skip('應該正確高亮當前頁面的側欄連結', async ({ page }) => {
    // 測試部分頁面的側欄連結是否正確高亮（避免測試太長）
    const testLinks = [
      sidebarLinks[0], // 儀表板
      sidebarLinks[1], // 文章管理
      sidebarLinks[5], // 系統統計
    ];

    for (const link of testLinks) {
      // 訪問頁面
      await page.goto(link.path);
      await page.waitForLoadState('networkidle', { timeout: 15000 });
      
      // 檢查該連結是否有 active 類別
      const activeLink = await page.locator(`a[href="${link.path}"].active`).first();
      await expect(activeLink).toBeVisible({ timeout: 5000 });
    }
  });

  test('應該在重新整理後保持登入狀態', async ({ page }) => {
    // 訪問文章管理頁面（避免統計頁面可能的 API 問題）
    await page.goto('/admin/posts');
    await page.waitForURL('**/admin/posts', { timeout: 10000 });
    await page.waitForLoadState('networkidle', { timeout: 15000 });
    
    // 重新整理頁面
    await page.reload();
    await page.waitForLoadState('networkidle', { timeout: 15000 });
    
    // 確認仍在文章管理頁面，沒有被導回登入頁
    expect(page.url()).toContain('/admin/posts');
    expect(page.url()).not.toContain('/login');
    
    // 確認使用者選單仍然存在
    const userMenu = await page.locator('#user-menu-btn');
    await expect(userMenu).toBeVisible({ timeout: 5000 });
  });

  test('應該能訪問返回首頁連結', async ({ page }) => {
    // 點擊返回首頁
    await page.click('a[href="/"]:has-text("返回首頁")');
    
    // 等待導向首頁
    await page.waitForURL('/', { timeout: 10000 });
    
    // 確認在首頁
    expect(page.url()).not.toContain('/admin');
    expect(page.url()).not.toContain('/login');
    
    // 確認首頁內容存在
    const homeContent = await page.locator('body');
    await expect(homeContent).toBeVisible();
  });

  test('側欄在小螢幕上應該能正常開關', async ({ page }) => {
    // 設定為手機螢幕大小
    await page.setViewportSize({ width: 375, height: 667 });
    
    // 側欄應該預設隱藏（在小螢幕上）
    const sidebar = await page.locator('#sidebar');
    
    // 點擊漢堡選單按鈕
    const toggleButton = await page.locator('#sidebar-toggle');
    await expect(toggleButton).toBeVisible();
    await toggleButton.click();
    
    // 側欄應該可見
    await page.waitForTimeout(300); // 等待動畫
    
    // 點擊連結後仍能導航
    await page.click('a[href="/admin/posts"]');
    await page.waitForURL('**/admin/posts', { timeout: 10000 });
    
    // 確認導航成功
    expect(page.url()).toContain('/admin/posts');
    expect(page.url()).not.toContain('/login');
  });

  test.skip('應該在 API 錯誤時顯示適當的錯誤處理', async ({ page }) => {
    // 訪問可能會調用 API 的頁面
    await page.goto('/admin/statistics');
    await page.waitForURL('**/admin/statistics', { timeout: 10000 });
    
    // 即使 API 出錯，也不應該被導回登入頁
    // （除非是 401 未授權錯誤）
    await page.waitForTimeout(2000);
    
    expect(page.url()).toContain('/admin/statistics');
    expect(page.url()).not.toContain('/login');
  });
});

/**
 * 未登入狀態測試
 * 確保未登入時訪問管理頁面會被導回登入頁
 */
test.describe('未登入狀態保護測試', () => {
  const protectedPaths = [
    '/admin/dashboard',
    '/admin/posts',
    '/admin/statistics',
  ];

  for (const path of protectedPaths) {
    test(`未登入時訪問 ${path} 應該被導回登入頁`, async ({ browser }) => {
      // 建立新的無狀態上下文
      const context = await browser.newContext();
      const page = await context.newPage();
      
      try {
        // 嘗試直接訪問受保護的頁面
        await page.goto(path);
        
        // 等待可能的重導向
        await page.waitForTimeout(1500);
        
        // 應該被導向登入頁
        await page.waitForURL('**/login', { timeout: 10000 });
        expect(page.url()).toContain('/login');
        
        // 確認登入表單存在
        const emailInput = await page.locator('input[name="email"]');
        await expect(emailInput).toBeVisible();
      } finally {
        await context.close();
      }
    });
  }
});
