/**
 * E2E 測試：文章詳細頁面
 * 
 * 測試範圍：
 * 1. 公開使用者可以查看已發布的文章詳情
 * 2. 已登入管理員可以查看已發布的文章詳情
 * 3. 文章詳情包含標題、內容、作者、發布日期等資訊
 * 4. 顯示文章導航（上一篇/下一篇）
 */

import { test, expect } from '@playwright/test';

test.describe('文章詳細頁面', () => {
  test.beforeEach(async ({ page }) => {
    // 前往首頁
    await page.goto('http://localhost:3000');
    await page.waitForSelector('article', { timeout: 10000 });
  });

  test('公開使用者可以查看文章詳情', async ({ page }) => {
    // 點擊第一篇文章
    const firstArticleLink = page.locator('article').first().locator('a').first();
    await firstArticleLink.click();

    // 等待頁面載入
    await page.waitForLoadState('networkidle');

    // 檢查是否顯示文章標題
    await expect(page.locator('h1')).toBeVisible();

    // 檢查是否顯示文章內容
    await expect(page.locator('article .prose')).toBeVisible();

    // 檢查是否顯示發布日期
    await expect(page.locator('time')).toBeVisible();

    // 檢查是否顯示時區資訊
    await expect(page.getByText(/🌏/)).toBeVisible();

    // 檢查是否有返回首頁的連結
    await expect(page.getByRole('link', { name: /返回首頁/ })).toBeVisible();
  });

  test('已登入管理員可以查看文章詳情', async ({ page }) => {
    // 登入
    await page.goto('http://localhost:3000/login');
    await page.fill('input[name="username"]', 'admin');
    await page.fill('input[name="password"]', 'admin123');
    await page.click('button[type="submit"]');
    await page.waitForURL('http://localhost:3000/admin/dashboard', { timeout: 10000 });

    // 前往首頁
    await page.goto('http://localhost:3000');
    await page.waitForSelector('article', { timeout: 10000 });

    // 點擊第一篇文章
    const firstArticleLink = page.locator('article').first().locator('a').first();
    await firstArticleLink.click();

    // 等待頁面載入
    await page.waitForLoadState('networkidle');

    // 檢查是否正確載入文章（不會被導向登入頁面）
    await expect(page).not.toHaveURL(/\/login$/);

    // 檢查是否顯示文章標題
    await expect(page.locator('h1')).toBeVisible();

    // 檢查是否顯示文章內容
    await expect(page.locator('article .prose')).toBeVisible();
  });

  test('文章詳情包含完整資訊', async ({ page }) => {
    // 點擊第一篇文章
    const firstArticleLink = page.locator('article').first().locator('a').first();
    const articleTitle = await firstArticleLink.textContent();
    await firstArticleLink.click();

    // 等待頁面載入
    await page.waitForLoadState('networkidle');

    // 檢查文章標題
    await expect(page.locator('h1')).toContainText(articleTitle.trim());

    // 檢查發布日期
    const dateElement = page.locator('time').first();
    await expect(dateElement).toBeVisible();
    
    // 檢查時區資訊
    const timezoneElement = page.getByText(/🌏/).locator('..').getByText(/UTC|GMT|Asia/);
    await expect(timezoneElement).toBeVisible();
  });

  test('顯示文章導航', async ({ page }) => {
    // 點擊第一篇文章
    const firstArticleLink = page.locator('article').first().locator('a').first();
    await firstArticleLink.click();

    // 等待頁面載入
    await page.waitForLoadState('networkidle');

    // 檢查是否有文章導航區塊
    await expect(page.getByRole('heading', { name: '文章導航' })).toBeVisible();

    // 檢查導航區塊
    const navigationSection = page.locator('#post-navigation');
    await expect(navigationSection).toBeVisible();
  });

  test('可以透過文章導航瀏覽其他文章', async ({ page }) => {
    // 點擊第一篇文章
    const firstArticleLink = page.locator('article').first().locator('a').first();
    await firstArticleLink.click();

    // 等待頁面載入
    await page.waitForLoadState('networkidle');

    // 檢查是否有上一篇或下一篇文章連結
    const navigationSection = page.locator('#post-navigation');
    const navigationLinks = navigationSection.locator('a');
    
    const linkCount = await navigationLinks.count();
    if (linkCount > 0) {
      // 點擊第一個導航連結
      const firstNavLink = navigationLinks.first();
      await firstNavLink.click();

      // 等待頁面載入
      await page.waitForLoadState('networkidle');

      // 檢查是否成功載入另一篇文章
      await expect(page.locator('h1')).toBeVisible();
      await expect(page.locator('article .prose')).toBeVisible();
    }
  });

  test('文章詳情 API 請求使用正確的端點', async ({ page }) => {
    // 監聽網路請求
    const requests = [];
    page.on('request', request => {
      if (request.url().includes('/api/')) {
        requests.push(request.url());
      }
    });

    // 點擊第一篇文章
    const firstArticleLink = page.locator('article').first().locator('a').first();
    const href = await firstArticleLink.getAttribute('href');
    const postId = href.split('/').pop();
    
    await firstArticleLink.click();

    // 等待頁面載入
    await page.waitForLoadState('networkidle');

    // 檢查 API 請求是否使用正確的端點 (/api/posts/{id} 而非 /api/admin/posts/{id})
    const postDetailRequest = requests.find(url => url.includes(`/api/posts/${postId}`));
    expect(postDetailRequest).toBeDefined();

    // 確保沒有使用錯誤的 /admin/posts 端點
    const wrongRequest = requests.find(url => url.includes(`/api/admin/posts/${postId}`));
    expect(wrongRequest).toBeUndefined();
  });
});
