// @ts-check
const { test, expect } = require('./fixtures/page-objects');

/**
 * 使用者管理功能測試套件
 */
test.describe('使用者管理功能測試', () => {
  test.beforeEach(async ({ authenticatedPage }) => {
    await authenticatedPage.goto('/admin/users');
    await authenticatedPage.waitForTimeout(1000); // 等待資料載入
  });

  test('應該正確顯示使用者管理頁面', async ({ authenticatedPage: page }) => {
    // 檢查標題
    await expect(page.locator('h1:has-text("使用者管理")')).toBeVisible();
    
    // 檢查新增使用者按鈕
    await expect(page.locator('button:has-text("新增使用者")')).toBeVisible();
  });

  test('應該顯示使用者列表', async ({ authenticatedPage: page }) => {
    // 檢查表頭
    await expect(page.locator('text=使用者名稱')).toBeVisible();
    await expect(page.locator('text=電子郵件')).toBeVisible();
    await expect(page.locator('text=角色')).toBeVisible();
    await expect(page.locator('text=註冊日期')).toBeVisible();
    await expect(page.locator('text=操作')).toBeVisible();
    
    // 應該至少有一個使用者（admin）
    const userRows = page.locator('tbody tr');
    const count = await userRows.count();
    expect(count).toBeGreaterThanOrEqual(1);
  });

  test('點擊新增使用者應該顯示新增對話框', async ({ authenticatedPage: page }) => {
    // 點擊新增使用者按鈕
    await page.click('button:has-text("新增使用者")');
    
    // 等待 modal 出現
    await page.waitForTimeout(500);
    
    // 檢查 modal 標題
    await expect(page.locator('text=新增使用者').first()).toBeVisible();
    
    // 檢查表單欄位
    await expect(page.locator('input[name="username"]')).toBeVisible();
    await expect(page.locator('input[name="email"]')).toBeVisible();
    await expect(page.locator('select[name="role_id"]')).toBeVisible();
    await expect(page.locator('input[name="password"]')).toBeVisible();
    await expect(page.locator('input[name="password_confirmation"]')).toBeVisible();
    
    // 檢查按鈕
    await expect(page.locator('button:has-text("取消")')).toBeVisible();
    await expect(page.locator('button:has-text("新增使用者")')).toBeVisible();
  });

  test('應該能夠取消新增使用者', async ({ authenticatedPage: page }) => {
    // 開啟新增對話框
    await page.click('button:has-text("新增使用者")');
    await page.waitForTimeout(500);
    
    // 點擊取消
    await page.locator('button:has-text("取消")').last().click();
    await page.waitForTimeout(500);
    
    // Modal 應該關閉
    await expect(page.locator('text=新增使用者').first()).not.toBeVisible();
  });

  test('新增使用者時應該驗證必填欄位', async ({ authenticatedPage: page }) => {
    // 開啟新增對話框
    await page.click('button:has-text("新增使用者")');
    await page.waitForTimeout(500);
    
    // 不填寫任何資料，直接提交
    await page.locator('button:has-text("新增使用者")').last().click();
    
    // 因為有 HTML5 驗證，表單不會提交
    // 檢查 modal 仍然存在
    await expect(page.locator('input[name="username"]')).toBeVisible();
  });

  test('新增使用者時密碼與確認密碼應該一致', async ({ authenticatedPage: page }) => {
    // 開啟新增對話框
    await page.click('button:has-text("新增使用者")');
    await page.waitForTimeout(500);
    
    // 填寫資料，但密碼不一致
    await page.fill('input[name="username"]', 'testuser');
    await page.fill('input[name="email"]', 'test@example.com');
    await page.selectOption('select[name="role_id"]', { index: 0 });
    await page.fill('input[name="password"]', 'password123');
    await page.fill('input[name="password_confirmation"]', 'different456');
    
    // 提交表單
    await page.locator('button:has-text("新增使用者")').last().click();
    await page.waitForTimeout(1000);
    
    // 應該顯示錯誤訊息
    await expect(page.locator('text=/密碼.*不符/')).toBeVisible({ timeout: 3000 });
  });

  test('點擊編輯應該顯示編輯對話框', async ({ authenticatedPage: page }) => {
    // 等待使用者列表載入
    const editButtons = page.locator('button:has-text("編輯")');
    const count = await editButtons.count();
    
    if (count > 0) {
      // 點擊第一個編輯按鈕
      await editButtons.first().click();
      await page.waitForTimeout(500);
      
      // 檢查 modal 標題
      await expect(page.locator('text=編輯使用者').first()).toBeVisible();
      
      // 檢查表單欄位（編輯時沒有密碼欄位）
      await expect(page.locator('input[name="username"]')).toBeVisible();
      await expect(page.locator('input[name="email"]')).toBeVisible();
      await expect(page.locator('select[name="role_id"]')).toBeVisible();
      
      // 密碼欄位不應該出現
      await expect(page.locator('input[name="password"]')).not.toBeVisible();
      
      // 檢查按鈕
      await expect(page.locator('button:has-text("取消")')).toBeVisible();
      await expect(page.locator('button:has-text("儲存變更")')).toBeVisible();
    }
  });

  test('應該能夠取消編輯使用者', async ({ authenticatedPage: page }) => {
    // 點擊編輯
    const editButtons = page.locator('button:has-text("編輯")');
    const count = await editButtons.count();
    
    if (count > 0) {
      await editButtons.first().click();
      await page.waitForTimeout(500);
      
      // 點擊取消
      await page.locator('button:has-text("取消")').last().click();
      await page.waitForTimeout(500);
      
      // Modal 應該關閉
      await expect(page.locator('text=編輯使用者').first()).not.toBeVisible();
    }
  });

  test('應該顯示使用者的角色資訊', async ({ authenticatedPage: page }) => {
    // 檢查至少有一個角色標籤顯示
    const roleBadges = page.locator('tbody tr td span.inline-flex');
    const count = await roleBadges.count();
    
    if (count > 0) {
      // 檢查第一個角色標籤是否可見
      await expect(roleBadges.first()).toBeVisible();
    }
  });

  test('每個使用者都應該有編輯和刪除按鈕', async ({ authenticatedPage: page }) => {
    const userRows = page.locator('tbody tr');
    const rowCount = await userRows.count();
    
    if (rowCount > 0) {
      // 檢查第一行
      const firstRow = userRows.first();
      await expect(firstRow.locator('button:has-text("編輯")')).toBeVisible();
      await expect(firstRow.locator('button:has-text("刪除")')).toBeVisible();
    }
  });

  test('點擊刪除應該顯示確認對話框', async ({ authenticatedPage: page }) => {
    const deleteButtons = page.locator('button:has-text("刪除")');
    const count = await deleteButtons.count();
    
    if (count > 0) {
      // 監聽 confirm 對話框
      page.once('dialog', async dialog => {
        expect(dialog.message()).toContain('確定要刪除');
        await dialog.dismiss(); // 取消刪除
      });
      
      // 點擊刪除按鈕
      await deleteButtons.first().click();
    }
  });
});
