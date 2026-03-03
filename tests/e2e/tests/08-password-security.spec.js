const { test, expect } = require('./fixtures/page-objects');

test.describe('密碼安全性測試', () => {
  test.beforeEach(async ({ authenticatedPage: page }) => {
    // 進入使用者管理頁面
    await page.goto('http://localhost:3000/admin/users');
    await page.waitForLoadState('networkidle');
  });

  test('弱密碼應該被拒絕 - 太短', async ({ authenticatedPage: page }) => {
    // 點擊新增使用者
    await page.click('button:has-text("新增使用者")');
    await page.waitForTimeout(500);

    // 填寫表單
    await page.fill('input[name="username"]', 'testuser');
    await page.fill('input[name="email"]', 'test@example.com');
    await page.fill('input[name="password"]', 'abc123');
    await page.fill('input[name="password_confirmation"]', 'abc123');

    // 檢查密碼強度指示器顯示弱密碼
    await expect(page.locator('.strength-text')).toContainText('弱');
    
    // 應該看到錯誤訊息
    await expect(page.locator('.requirement-item').filter({ hasText: '至少 8 個字元' })).toHaveClass(/text-modern-600/);
  });

  test('弱密碼應該被拒絕 - 缺少大寫字母', async ({ authenticatedPage: page }) => {
    // 點擊新增使用者
    await page.click('button:has-text("新增使用者")');
    await page.waitForTimeout(500);

    await page.fill('input[name="username"]', 'testuser');
    await page.fill('input[name="email"]', 'test@example.com');
    await page.fill('input[name="password"]', 'abcdefgh123');
    
    // 檢查要求清單
    await expect(page.locator('.requirement-item').filter({ hasText: '包含大寫字母' })).toHaveClass(/text-modern-600/);
    await expect(page.locator('.strength-text')).not.toContainText('強');
  });

  test('弱密碼應該被拒絕 - 包含連續字元', async ({ authenticatedPage: page }) => {
    await page.click('button:has-text("新增使用者")');
    await page.waitForTimeout(500);

    await page.fill('input[name="username"]', 'testuser');
    await page.fill('input[name="email"]', 'test@example.com');
    await page.fill('input[name="password"]', 'Abcdefgh123');
    
    // 應該檢測到連續字元 (abc)
    await expect(page.locator('.requirement-item').filter({ hasText: '不包含連續字元' })).toHaveClass(/text-modern-600/);
  });

  test.skip('弱密碼應該被拒絕 - 包含重複字元', async ({ authenticatedPage: page }) => {
    await page.click('button:has-text("新增使用者")');
    await page.waitForTimeout(500);

    await page.fill('input[name="username"]', 'testuser');
    await page.fill('input[name="email"]', 'test@example.com');
    await page.fill('input[name="password"]', 'Aaa12345678');
    
    // 應該檢測到重複字元 (aaa)
    await expect(page.locator('.requirement-item').filter({ hasText: '不包含重複字元' })).toHaveClass(/text-modern-600/);
  });

  test('弱密碼應該被拒絕 - 常見密碼', async ({ authenticatedPage: page }) => {
    await page.click('button:has-text("新增使用者")');
    await page.waitForTimeout(500);

    await page.fill('input[name="username"]', 'testuser');
    await page.fill('input[name="email"]', 'test@example.com');
    await page.fill('input[name="password"]', 'Password123');
    
    // 密碼包含常見單字 "password"
    await expect(page.locator('.suggestions-list')).toContainText('常見');
  });

  test.skip('強密碼應該被接受', async ({ authenticatedPage: page }) => {
    await page.click('button:has-text("新增使用者")');
    await page.waitForTimeout(500);

    const uniqueUsername = 'testuser_' + Date.now();
    const strongPassword = 'Xk9@mP2#vL5!';

    await page.fill('input[name="username"]', uniqueUsername);
    await page.fill('input[name="email"]', `${uniqueUsername}@example.com`);
    await page.fill('input[name="password"]', strongPassword);
    await page.fill('input[name="password_confirmation"]', strongPassword);

    // 檢查所有要求都滿足
    await expect(page.locator('.requirement-item').filter({ hasText: '至少 8 個字元' })).toHaveClass(/text-green-600/);
    await expect(page.locator('.requirement-item').filter({ hasText: '包含小寫字母' })).toHaveClass(/text-green-600/);
    await expect(page.locator('.requirement-item').filter({ hasText: '包含大寫字母' })).toHaveClass(/text-green-600/);
    await expect(page.locator('.requirement-item').filter({ hasText: '包含數字' })).toHaveClass(/text-green-600/);
    await expect(page.locator('.requirement-item').filter({ hasText: '包含特殊符號' })).toHaveClass(/text-green-600/);
    
    // 密碼強度應該是強或非常強
    await expect(page.locator('.strength-text')).toMatch(/(強|非常強)/);
  });

  test('密碼顯示/隱藏切換功能', async ({ authenticatedPage: page }) => {
    await page.click('button:has-text("新增使用者")');
    await page.waitForTimeout(500);

    const passwordInput = page.locator('input[name="password"]');
    const toggleBtn = page.locator('#togglePasswordBtn');

    // 預設應該是密碼類型
    await expect(passwordInput).toHaveAttribute('type', 'password');

    // 點擊顯示
    await toggleBtn.click();
    await expect(passwordInput).toHaveAttribute('type', 'text');
    await expect(toggleBtn).toContainText('🙈');

    // 再次點擊隱藏
    await toggleBtn.click();
    await expect(passwordInput).toHaveAttribute('type', 'password');
    await expect(toggleBtn).toContainText('👁️');
  });

  test('生成密碼功能', async ({ authenticatedPage: page }) => {
    await page.click('button:has-text("新增使用者")');
    await page.waitForTimeout(500);

    const passwordInput = page.locator('input[name="password"]');
    const confirmInput = page.locator('input[name="password_confirmation"]');
    const generateBtn = page.locator('#generatePasswordBtn');

    // 點擊生成密碼
    await generateBtn.click();
    await page.waitForTimeout(500);

    // 密碼應該被填入
    const generatedPassword = await passwordInput.inputValue();
    expect(generatedPassword).toBeTruthy();
    expect(generatedPassword.length).toBeGreaterThanOrEqual(12);

    // 確認密碼應該相同
    const confirmPassword = await confirmInput.inputValue();
    expect(confirmPassword).toBe(generatedPassword);

    // 密碼應該自動顯示
    await expect(passwordInput).toHaveAttribute('type', 'text');

    // 應該顯示成功訊息
    await expect(page.locator('text=已生成安全密碼')).toBeVisible();
  });

  test.skip('密碼包含使用者名稱應該被警告', async ({ authenticatedPage: page }) => {
    await page.click('button:has-text("新增使用者")');
    await page.waitForTimeout(500);

    await page.fill('input[name="username"]', 'johndoe');
    await page.fill('input[name="email"]', 'john@example.com');
    await page.fill('input[name="password"]', 'Johndoe123!');
    
    // 應該警告密碼包含使用者名稱
    await expect(page.locator('.password-strength-indicator')).toContainText('使用者名稱');
  });

  test.skip('密碼包含 email 前綴應該被警告', async ({ authenticatedPage: page }) => {
    await page.click('button:has-text("新增使用者")');
    await page.waitForTimeout(500);

    await page.fill('input[name="username"]', 'testuser');
    await page.fill('input[name="email"]', 'johnsmith@example.com');
    await page.fill('input[name="password"]', 'Johnsmith123!');
    
    // 應該警告密碼包含 email
    await expect(page.locator('.password-strength-indicator')).toContainText('電子郵件');
  });

  test('密碼強度指示器應該即時更新', async ({ authenticatedPage: page }) => {
    await page.click('button:has-text("新增使用者")');
    await page.waitForTimeout(500);

    const passwordInput = page.locator('input[name="password"]');
    const strengthBar = page.locator('.strength-bar');
    
    // 輸入弱密碼
    await passwordInput.fill('abc');
    await expect(strengthBar).toHaveClass(/bg-red-500/);
    
    // 改進密碼
    await passwordInput.fill('Abc12345');
    await page.waitForTimeout(300);
    // 強度應該提升
    
    // 輸入強密碼
    await passwordInput.fill('Xk9@mP2#vL5!');
    await page.waitForTimeout(300);
    await expect(strengthBar).toHaveClass(/bg-(green|blue)-500/);
  });

  test('密碼不匹配時應該顯示錯誤', async ({ authenticatedPage: page }) => {
    await page.click('button:has-text("新增使用者")');
    await page.waitForTimeout(500);

    await page.fill('input[name="username"]', 'testuser_' + Date.now());
    await page.fill('input[name="email"]', 'test@example.com');
    await page.fill('input[name="password"]', 'Xk9@mP2#vL5!');
    await page.fill('input[name="password_confirmation"]', 'DifferentPassword123!');

    // 提交表單
    await page.click('button[type="submit"]');
    await page.waitForTimeout(500);

    // 應該顯示錯誤訊息
    await expect(page.locator('text=密碼與確認密碼不符')).toBeVisible();
  });
});
