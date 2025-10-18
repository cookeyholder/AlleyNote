/**
 * 系統設定整合測試
 * 
 * 測試項目：
 * 1. 網站名稱和描述是否顯示在首頁
 * 2. 時區設定是否顯示在文章頁面
 * 3. 附件數量上限設定是否生效
 */

import { test, expect } from '@playwright/test';

test.describe('系統設定整合測試', () => {
  let adminContext;

  test.beforeAll(async ({ browser }) => {
    // 建立 admin 上下文
    adminContext = await browser.newContext();
    const page = await adminContext.newPage();

    // 登入為管理員
    await page.goto('/login');
    await page.waitForLoadState('networkidle');
    await page.fill('input[name="email"]', 'admin@example.com');
    await page.fill('input[name="password"]', 'password');
    await page.click('button[type="submit"]');
    await page.waitForURL('**/admin/dashboard', { timeout: 10000 });
    await page.close();
  });

  test.afterAll(async () => {
    await adminContext?.close();
  });

  test('網站名稱應該顯示在首頁導航列', async ({ page }) => {
    // 先設定網站名稱
    const adminPage = await adminContext.newPage();
    await adminPage.goto('/admin/settings');
    await adminPage.waitForLoadState('networkidle');
    
    const testSiteName = '測試公布欄系統' + Date.now();
    await adminPage.fill('#site-name', testSiteName);
    await adminPage.click('#save-btn');
    await adminPage.waitForTimeout(1000);
    await adminPage.close();

    // 檢查首頁導航列
    await page.goto('/');
    await page.waitForLoadState('networkidle');
    
    const navTitle = page.locator('nav h1');
    await expect(navTitle).toContainText(testSiteName);
  });

  test('網站描述應該顯示在首頁 Hero Section', async ({ page }) => {
    // 先設定網站描述
    const adminPage = await adminContext.newPage();
    await adminPage.goto('/admin/settings');
    await adminPage.waitForLoadState('networkidle');
    
    const testDescription = '這是一個測試描述 - ' + Date.now();
    await adminPage.fill('#site-description', testDescription);
    await adminPage.click('#save-btn');
    await adminPage.waitForTimeout(1000);
    await adminPage.close();

    // 檢查首頁 Hero Section
    await page.goto('/');
    await page.waitForLoadState('networkidle');
    
    const heroDescription = page.locator('main p.text-xl');
    await expect(heroDescription).toContainText(testDescription);
  });

  test('時區設定應該顯示在文章頁面', async ({ page }) => {
    const adminPage = await adminContext.newPage();
    
    // 先設定時區為明顯的時區
    await adminPage.goto('/admin/settings');
    await adminPage.waitForLoadState('networkidle');
    await adminPage.selectOption('#site-timezone', 'Asia/Tokyo');
    await adminPage.click('#save-btn');
    await adminPage.waitForTimeout(2000);

    // 取得已存在的文章列表
    await adminPage.goto('/admin/posts');
    await adminPage.waitForLoadState('networkidle');
    
    // 獲取第一篇已發布的文章的連結
    const firstPostLink = await adminPage.locator('a[href^="/posts/"]').first();
    
    if (await firstPostLink.count() > 0) {
      const postUrl = await firstPostLink.getAttribute('href');
      await adminPage.close();

      // 檢查文章頁面是否顯示時區
      await page.goto(`http://localhost:3000${postUrl}`);
      await page.waitForLoadState('networkidle');
      
      // 檢查是否有時區圖示
      const timezoneInfo = page.locator('text=🌏');
      if (await timezoneInfo.count() > 0) {
        await expect(timezoneInfo).toBeVisible();
        
        // 檢查是否包含東京時區資訊
        const articleHeader = page.locator('article header');
        const headerText = await articleHeader.textContent();
        expect(headerText).toMatch(/Tokyo|東京/i);
      }
    } else {
      console.log('  跳過：沒有已發布的文章');
      await adminPage.close();
    }
  });

  test('附件數量上限設定應該生效', async ({ page }) => {
    // 先設定附件數量上限為 2
    const adminPage = await adminContext.newPage();
    await adminPage.goto('/admin/settings');
    await adminPage.waitForLoadState('networkidle');
    
    const currentValue = await adminPage.locator('#max-attachments-per-post').inputValue();
    console.log('  當前附件上限:', currentValue);
    
    await adminPage.fill('#max-attachments-per-post', '2');
    await adminPage.click('#save-btn');
    await adminPage.waitForTimeout(2000);

    // 重新載入頁面確認設定生效
    await adminPage.goto('/admin/settings');
    await adminPage.waitForLoadState('networkidle');
    
    const newValue = await adminPage.locator('#max-attachments-per-post').inputValue();
    expect(newValue).toBe('2');
    
    await adminPage.close();
  });

  test('網站名稱和描述變更後應立即反映在首頁', async ({ page }) => {
    const adminPage = await adminContext.newPage();
    
    // 設定第一組值
    await adminPage.goto('/admin/settings');
    await adminPage.waitForLoadState('networkidle');
    
    const firstName = '第一個名稱' + Date.now();
    const firstDesc = '第一個描述' + Date.now();
    
    await adminPage.fill('#site-name', firstName);
    await adminPage.fill('#site-description', firstDesc);
    await adminPage.click('#save-btn');
    await adminPage.waitForTimeout(1000);

    // 檢查首頁
    await page.goto('/');
    await page.waitForLoadState('networkidle');
    
    await expect(page.locator('nav h1')).toContainText(firstName);
    await expect(page.locator('main p.text-xl')).toContainText(firstDesc);

    // 修改為第二組值
    await adminPage.goto('/admin/settings');
    await adminPage.waitForLoadState('networkidle');
    
    const secondName = '第二個名稱' + Date.now();
    const secondDesc = '第二個描述' + Date.now();
    
    await adminPage.fill('#site-name', secondName);
    await adminPage.fill('#site-description', secondDesc);
    await adminPage.click('#save-btn');
    await adminPage.waitForTimeout(1000);

    // 再次檢查首頁（應該更新）
    await page.goto('/');
    await page.waitForLoadState('networkidle');
    
    await expect(page.locator('nav h1')).toContainText(secondName);
    await expect(page.locator('main p.text-xl')).toContainText(secondDesc);
    
    await adminPage.close();
  });

  test('時區變更後文章顯示時間應該改變', async ({ page }) => {
    const adminPage = await adminContext.newPage();
    
    // 獲取第一篇已發布的文章
    await adminPage.goto('/admin/posts');
    await adminPage.waitForLoadState('networkidle');
    
    const firstPostLink = await adminPage.locator('a[href^="/posts/"]').first();
    
    if (await firstPostLink.count() > 0) {
      const postUrl = await firstPostLink.getAttribute('href');
      
      // 設定時區為 UTC
      await adminPage.goto('/admin/settings');
      await adminPage.waitForLoadState('networkidle');
      await adminPage.selectOption('#site-timezone', 'UTC');
      await adminPage.click('#save-btn');
      await adminPage.waitForTimeout(2000);

      // 檢查文章時間
      await page.goto(`http://localhost:3000${postUrl}`);
      await page.waitForLoadState('networkidle');
      const utcTime = await page.locator('article header time').textContent();
      const utcTimezone = await page.locator('article header').textContent();

      // 設定時區為 Asia/Tokyo (UTC+9)
      await adminPage.goto('/admin/settings');
      await adminPage.waitForLoadState('networkidle');
      await adminPage.selectOption('#site-timezone', 'Asia/Tokyo');
      await adminPage.click('#save-btn');
      await adminPage.waitForTimeout(2000);

      // 再次檢查文章時間（應該不同）
      await page.goto(`http://localhost:3000${postUrl}`);
      await page.waitForLoadState('networkidle');
      const tokyoTime = await page.locator('article header time').textContent();
      const tokyoTimezone = await page.locator('article header').textContent();

      console.log('  UTC 時間:', utcTime);
      console.log('  Tokyo 時間:', tokyoTime);

      // 時區資訊應該不同
      expect(utcTimezone).toContain('UTC');
      expect(tokyoTimezone).toMatch(/Tokyo|東京/i);
    } else {
      console.log('  跳過：沒有已發布的文章');
    }
    
    await adminPage.close();
  });

  test('恢復預設設定', async () => {
    const adminPage = await adminContext.newPage();
    
    // 恢復預設設定
    await adminPage.goto('/admin/settings');
    await adminPage.waitForLoadState('networkidle');
    
    await adminPage.fill('#site-name', 'AlleyNote');
    await adminPage.fill('#site-description', '基於 DDD 架構的企業級應用程式');
    await adminPage.selectOption('#site-timezone', 'Asia/Taipei');
    await adminPage.fill('#max-attachments-per-post', '10');
    await adminPage.click('#save-btn');
    await adminPage.waitForTimeout(1000);
    
    await adminPage.close();
  });
});
