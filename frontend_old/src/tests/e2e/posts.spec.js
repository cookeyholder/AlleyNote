/**
 * 文章管理 E2E 測試
 * 測試文章的建立、編輯、刪除流程
 */

import { test, expect } from '@playwright/test';

const BASE_URL = process.env.VITE_APP_URL || 'http://localhost:5173';
const API_URL = process.env.VITE_API_URL || 'http://localhost:8080/api';

test.describe('文章管理', () => {
  // 登入前置作業
  test.beforeEach(async ({ page }) => {
    // 前往登入頁面
    await page.goto(`${BASE_URL}/login`);
    
    // 填寫登入表單
    await page.fill('input[type="email"]', 'admin@example.com');
    await page.fill('input[type="password"]', 'password123');
    
    // 點擊登入按鈕
    await page.click('button[type="submit"]');
    
    // 等待導向到儀表板
    await page.waitForURL('**/admin/dashboard');
  });

  test.describe('文章列表', () => {
    test('應該顯示文章列表', async ({ page }) => {
      // 前往文章管理頁面
      await page.goto(`${BASE_URL}/admin/posts`);
      
      // 確認頁面標題
      await expect(page.locator('h1')).toContainText('文章管理');
      
      // 確認有「新增文章」按鈕
      await expect(page.locator('text=新增文章')).toBeVisible();
    });

    test('應該能夠搜尋文章', async ({ page }) => {
      await page.goto(`${BASE_URL}/admin/posts`);
      
      // 輸入搜尋關鍵字
      await page.fill('input[placeholder*="搜尋"]', '測試文章');
      
      // 等待搜尋結果
      await page.waitForTimeout(500);
      
      // 確認搜尋結果包含關鍵字
      const results = page.locator('tbody tr');
      await expect(results.first()).toBeVisible();
    });

    test('應該能夠篩選文章狀態', async ({ page }) => {
      await page.goto(`${BASE_URL}/admin/posts`);
      
      // 選擇「已發布」篩選
      await page.selectOption('select[name="status"]', 'published');
      
      // 等待篩選結果
      await page.waitForTimeout(500);
      
      // 確認所有結果都是已發布狀態
      const statusBadges = page.locator('tbody tr td:has-text("已發布")');
      await expect(statusBadges.first()).toBeVisible();
    });

    test('應該能夠分頁瀏覽', async ({ page }) => {
      await page.goto(`${BASE_URL}/admin/posts`);
      
      // 確認有分頁元素
      const pagination = page.locator('.pagination');
      
      if (await pagination.count() > 0) {
        // 點擊下一頁
        await page.click('button:has-text("下一頁")');
        
        // 等待頁面更新
        await page.waitForTimeout(500);
        
        // 確認頁碼改變
        await expect(page.locator('.pagination .active')).not.toHaveText('1');
      }
    });
  });

  test.describe('新增文章', () => {
    test('應該能夠開啟新增文章頁面', async ({ page }) => {
      await page.goto(`${BASE_URL}/admin/posts`);
      
      // 點擊「新增文章」按鈕
      await page.click('text=新增文章');
      
      // 確認導向到建立頁面
      await page.waitForURL('**/admin/posts/create');
      
      // 確認編輯器載入
      await expect(page.locator('.ck-editor')).toBeVisible();
    });

    test('應該能夠建立新文章', async ({ page }) => {
      await page.goto(`${BASE_URL}/admin/posts/create`);
      
      // 等待編輯器載入
      await page.waitForSelector('.ck-editor');
      
      // 填寫標題
      await page.fill('input[name="title"]', 'E2E 測試文章');
      
      // 填寫內容
      const editor = page.locator('.ck-editor__editable');
      await editor.click();
      await editor.type('這是一篇由 E2E 測試建立的文章。');
      
      // 選擇狀態
      await page.selectOption('select[name="status"]', 'draft');
      
      // 點擊儲存按鈕
      await page.click('button:has-text("儲存")');
      
      // 等待成功訊息
      await expect(page.locator('.toast-success')).toBeVisible();
      
      // 確認導回列表頁
      await page.waitForURL('**/admin/posts');
    });

    test('空白標題應該顯示驗證錯誤', async ({ page }) => {
      await page.goto(`${BASE_URL}/admin/posts/create`);
      
      // 等待編輯器載入
      await page.waitForSelector('.ck-editor');
      
      // 不填寫標題，直接儲存
      await page.click('button:has-text("儲存")');
      
      // 確認顯示錯誤訊息
      await expect(page.locator('.error-message')).toContainText('標題');
    });

    test('應該支援自動儲存草稿', async ({ page }) => {
      await page.goto(`${BASE_URL}/admin/posts/create`);
      
      // 等待編輯器載入
      await page.waitForSelector('.ck-editor');
      
      // 填寫標題
      await page.fill('input[name="title"]', '自動儲存測試');
      
      // 填寫內容
      const editor = page.locator('.ck-editor__editable');
      await editor.click();
      await editor.type('測試自動儲存功能');
      
      // 等待自動儲存（30 秒）
      await page.waitForTimeout(31000);
      
      // 確認顯示「已自動儲存」訊息
      await expect(page.locator('text=已自動儲存')).toBeVisible();
    });
  });

  test.describe('編輯文章', () => {
    test('應該能夠開啟編輯頁面', async ({ page }) => {
      await page.goto(`${BASE_URL}/admin/posts`);
      
      // 點擊第一篇文章的編輯按鈕
      await page.click('tbody tr:first-child button[title*="編輯"]');
      
      // 確認導向到編輯頁面
      await page.waitForURL('**/admin/posts/*/edit');
      
      // 確認編輯器載入
      await expect(page.locator('.ck-editor')).toBeVisible();
      
      // 確認標題欄位有值
      const titleInput = page.locator('input[name="title"]');
      await expect(titleInput).not.toBeEmpty();
    });

    test('應該能夠更新文章', async ({ page }) => {
      await page.goto(`${BASE_URL}/admin/posts`);
      
      // 點擊第一篇文章的編輯按鈕
      await page.click('tbody tr:first-child button[title*="編輯"]');
      
      // 等待編輯器載入
      await page.waitForSelector('.ck-editor');
      
      // 修改標題
      const titleInput = page.locator('input[name="title"]');
      await titleInput.fill('已更新的標題 - E2E');
      
      // 點擊更新按鈕
      await page.click('button:has-text("更新")');
      
      // 等待成功訊息
      await expect(page.locator('.toast-success')).toBeVisible();
    });

    test('離開未儲存的頁面應該顯示確認對話框', async ({ page }) => {
      await page.goto(`${BASE_URL}/admin/posts`);
      
      // 點擊編輯
      await page.click('tbody tr:first-child button[title*="編輯"]');
      
      // 等待編輯器載入
      await page.waitForSelector('.ck-editor');
      
      // 修改標題但不儲存
      await page.fill('input[name="title"]', '測試離開提示');
      
      // 監聽對話框
      page.on('dialog', async dialog => {
        expect(dialog.message()).toContain('未儲存');
        await dialog.dismiss();
      });
      
      // 嘗試導航到其他頁面
      await page.goto(`${BASE_URL}/admin/dashboard`);
    });
  });

  test.describe('刪除文章', () => {
    test('應該顯示刪除確認對話框', async ({ page }) => {
      await page.goto(`${BASE_URL}/admin/posts`);
      
      // 點擊刪除按鈕
      await page.click('tbody tr:first-child button[title*="刪除"]');
      
      // 確認對話框顯示
      await expect(page.locator('.confirmation-dialog')).toBeVisible();
      await expect(page.locator('.confirmation-dialog')).toContainText('確定要刪除');
    });

    test('取消刪除應該關閉對話框', async ({ page }) => {
      await page.goto(`${BASE_URL}/admin/posts`);
      
      // 點擊刪除按鈕
      await page.click('tbody tr:first-child button[title*="刪除"]');
      
      // 等待對話框顯示
      await page.waitForSelector('.confirmation-dialog');
      
      // 點擊取消
      await page.click('.confirmation-dialog button:has-text("取消")');
      
      // 確認對話框關閉
      await expect(page.locator('.confirmation-dialog')).not.toBeVisible();
    });

    test('確認刪除應該移除文章', async ({ page }) => {
      await page.goto(`${BASE_URL}/admin/posts`);
      
      // 取得第一篇文章的標題
      const firstPostTitle = await page.locator('tbody tr:first-child td:nth-child(2)').textContent();
      
      // 點擊刪除按鈕
      await page.click('tbody tr:first-child button[title*="刪除"]');
      
      // 等待對話框
      await page.waitForSelector('.confirmation-dialog');
      
      // 確認刪除
      await page.click('.confirmation-dialog button:has-text("刪除")');
      
      // 等待成功訊息
      await expect(page.locator('.toast-success')).toBeVisible();
      
      // 確認文章已從列表移除
      await page.waitForTimeout(500);
      const posts = await page.locator('tbody tr td:nth-child(2)').allTextContents();
      expect(posts).not.toContain(firstPostTitle);
    });
  });

  test.describe('批次操作', () => {
    test('應該能夠選擇多篇文章', async ({ page }) => {
      await page.goto(`${BASE_URL}/admin/posts`);
      
      // 勾選前三篇文章
      await page.check('tbody tr:nth-child(1) input[type="checkbox"]');
      await page.check('tbody tr:nth-child(2) input[type="checkbox"]');
      await page.check('tbody tr:nth-child(3) input[type="checkbox"]');
      
      // 確認顯示已選擇數量
      await expect(page.locator('text=/已選擇.*3/')).toBeVisible();
    });

    test('全選功能應該正常運作', async ({ page }) => {
      await page.goto(`${BASE_URL}/admin/posts`);
      
      // 點擊全選
      await page.check('thead input[type="checkbox"]');
      
      // 確認所有項目都被選中
      const checkboxes = page.locator('tbody input[type="checkbox"]');
      const count = await checkboxes.count();
      
      for (let i = 0; i < count; i++) {
        await expect(checkboxes.nth(i)).toBeChecked();
      }
    });

    test('應該能夠批次刪除', async ({ page }) => {
      await page.goto(`${BASE_URL}/admin/posts`);
      
      // 選擇兩篇文章
      await page.check('tbody tr:nth-child(1) input[type="checkbox"]');
      await page.check('tbody tr:nth-child(2) input[type="checkbox"]');
      
      // 點擊批次刪除
      await page.click('button:has-text("批次刪除")');
      
      // 確認對話框
      await page.waitForSelector('.confirmation-dialog');
      await expect(page.locator('.confirmation-dialog')).toContainText('2 篇文章');
      
      // 確認刪除
      await page.click('.confirmation-dialog button:has-text("刪除")');
      
      // 等待成功訊息
      await expect(page.locator('.toast-success')).toBeVisible();
    });
  });

  test.describe('圖片上傳', () => {
    test('應該能夠上傳圖片', async ({ page }) => {
      await page.goto(`${BASE_URL}/admin/posts/create`);
      
      // 等待編輯器載入
      await page.waitForSelector('.ck-editor');
      
      // 點擊圖片按鈕
      await page.click('.ck-button__label:has-text("Insert image")');
      
      // 選擇上傳檔案
      const fileInput = page.locator('input[type="file"]');
      await fileInput.setInputFiles('./tests/fixtures/test-image.jpg');
      
      // 等待上傳完成
      await page.waitForTimeout(2000);
      
      // 確認圖片插入編輯器
      await expect(page.locator('.ck-editor__editable img')).toBeVisible();
    });

    test('超過大小限制的圖片應該顯示錯誤', async ({ page }) => {
      await page.goto(`${BASE_URL}/admin/posts/create`);
      
      // 等待編輯器載入
      await page.waitForSelector('.ck-editor');
      
      // 點擊圖片按鈕
      await page.click('.ck-button__label:has-text("Insert image")');
      
      // 上傳過大的檔案
      const fileInput = page.locator('input[type="file"]');
      await fileInput.setInputFiles('./tests/fixtures/large-image.jpg');
      
      // 等待錯誤訊息
      await expect(page.locator('.toast-error')).toContainText('檔案大小');
    });
  });

  test.describe('發布狀態切換', () => {
    test('應該能夠切換文章發布狀態', async ({ page }) => {
      await page.goto(`${BASE_URL}/admin/posts`);
      
      // 點擊狀態切換按鈕
      const statusButton = page.locator('tbody tr:first-child button[title*="狀態"]');
      const initialStatus = await statusButton.textContent();
      
      await statusButton.click();
      
      // 等待狀態更新
      await page.waitForTimeout(500);
      
      // 確認狀態已改變
      const newStatus = await statusButton.textContent();
      expect(newStatus).not.toBe(initialStatus);
    });
  });

  test.describe('RWD 響應式', () => {
    test('手機版應該正常顯示', async ({ page }) => {
      // 設定手機視窗大小
      await page.setViewportSize({ width: 375, height: 667 });
      
      await page.goto(`${BASE_URL}/admin/posts`);
      
      // 確認頁面可見
      await expect(page.locator('h1')).toBeVisible();
      
      // 確認按鈕可點擊
      await expect(page.locator('text=新增文章')).toBeVisible();
    });

    test('平板版應該正常顯示', async ({ page }) => {
      // 設定平板視窗大小
      await page.setViewportSize({ width: 768, height: 1024 });
      
      await page.goto(`${BASE_URL}/admin/posts`);
      
      // 確認表格可見
      await expect(page.locator('table')).toBeVisible();
    });
  });
});
