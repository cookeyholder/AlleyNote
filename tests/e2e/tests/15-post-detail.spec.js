// @ts-check
const { test, expect, PublicPostPage } = require('./fixtures/page-objects');

test.describe('文章詳情頁面安全與渲染測試 (Secure-UI Spec)', () => {
  
  test('應該正確渲染富文本內容且無資訊洩漏', async ({ page }) => {
    const postPage = new PublicPostPage(page);
    
    // 假設文章 ID 1 是預先播種的測試資料
    await postPage.goto(1);
    
    // 1. 安全檢查：確保無敏感資訊洩漏
    await postPage.assertNoSensitiveInfoLeaked();
    
    // 2. 渲染檢查：確保 HTML 標籤被正確解析而非轉義顯示
    // 這裡我們預期標題與內容已通過後端 HTMLPurifier 與前端 DOMPurify 雙重處理
    await postPage.assertSafeContent('這是一篇範例文章', ['p', 'strong']);
  });

  test('應該包含必要的安全性 Meta 標籤', async ({ page }) => {
    const postPage = new PublicPostPage(page);
    await postPage.goto(1);
    await postPage.assertSecurityMetaTags();
  });
});
