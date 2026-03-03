# E2E 測試快速指南

## 🎯 目標

建立一套完整的前端自動化測試流程，確保程式碼修改後前端行為保持一致。

## 📦 已建立的內容

### 1. 測試框架設定
- ✅ Playwright 測試配置 (`playwright.config.js`)
- ✅ Package.json 與依賴管理
- ✅ Git 忽略規則

### 2. 測試輔助工具
- ✅ Page Object Model (`tests/fixtures/page-objects.js`)
  - LoginPage - 登入頁面
  - DashboardPage - 儀表板
  - PostsManagementPage - 文章管理
  - PostEditorPage - 文章編輯器
- ✅ 認證 Fixture (自動登入)
- ✅ 測試用戶設定

### 3. 測試用例 (6 個測試檔案)

#### `01-home.spec.js` - 首頁測試
- 頁面標題和導航顯示
- 文章列表載入
- 搜尋功能
- 頁腳資訊

#### `02-auth.spec.js` - 認證測試
- 登入頁面元素顯示
- 成功登入流程
- 錯誤密碼處理
- 記住我功能

#### `03-dashboard.spec.js` - Dashboard 測試
- 統計卡片顯示
- 最近文章列表
- 快速操作連結
- 側邊欄導航
- 使用者資訊顯示

#### `04-posts-management.spec.js` - 文章管理測試
- 文章列表顯示
- 搜尋功能
- 狀態篩選
- 操作按鈕（編輯、刪除、發布）

#### `05-post-editor.spec.js` - 文章編輯器測試
- 新增文章流程
- 編輯現有文章
- 發布時間設定
- 草稿儲存
- 表單驗證

#### `06-timezone.spec.js` - 時區功能測試
- 時區顯示正確性
- 發布時間轉換
- UTC 儲存驗證

### 4. 執行工具
- ✅ 執行腳本 (`run-tests.sh`)
- ✅ npm 腳本指令
- ✅ GitHub Actions 工作流程

### 5. 文件
- ✅ 詳細的 README
- ✅ 快速開始指南（本文件）

## 🚀 快速開始

### 第一次使用

```bash
# 1. 進入測試目錄
cd tests/e2e

# 2. 安裝依賴
npm install

# 3. 安裝 Playwright 瀏覽器
npx playwright install

# 4. 確保應用程式執行（在專案根目錄）
cd ../..
docker compose up -d

# 5. 執行測試
cd tests/e2e
npm test
```

### 使用執行腳本（推薦）

```bash
cd tests/e2e

# 自動檢查環境並執行測試
./run-tests.sh

# 有頭模式（可以看到瀏覽器）
./run-tests.sh headed

# UI 模式（互動式）
./run-tests.sh ui

# 快速執行（跳過環境檢查）
./run-tests.sh headless skip-setup
```

## 📝 常用指令

```bash
# 執行所有測試
npm test

# 執行特定測試檔案
npx playwright test tests/01-home.spec.js

# 執行特定測試（使用名稱匹配）
npx playwright test --grep "登入"

# 有頭模式
npm run test:headed

# UI 模式（最佳除錯體驗）
npm run test:ui

# 除錯模式
npm run test:debug

# 查看測試報告
npm run test:report

# 錄製新測試
npm run test:codegen
```

## 🔍 除錯技巧

### 1. 使用 UI 模式（推薦）
```bash
npm run test:ui
```
- 視覺化介面
- 逐步執行
- 檢視每個步驟的截圖
- 時光旅行除錯

### 2. 使用除錯模式
```bash
npm run test:debug
```
- Playwright Inspector
- 設定中斷點
- 檢視 DOM 狀態

### 3. 查看失敗的測試
測試失敗時會自動保存：
- 截圖（`test-results/` 目錄）
- 影片（`test-results/` 目錄）
- 追蹤檔案（可用 `npx playwright show-trace` 查看）

## 🎨 撰寫新測試

### 1. 使用 Page Object

```javascript
const { test, expect, LoginPage } = require('./fixtures/page-objects');

test('我的測試', async ({ page }) => {
  const loginPage = new LoginPage(page);
  await loginPage.goto();
  await loginPage.login('user@example.com', 'password');
  // ...
});
```

### 2. 使用認證 Fixture

```javascript
test('需要登入的測試', async ({ authenticatedPage }) => {
  // authenticatedPage 已經登入完成
  await authenticatedPage.goto('/admin/dashboard');
  // ...
});
```

### 3. 測試範本

```javascript
test.describe('功能名稱測試', () => {
  test.beforeEach(async ({ page }) => {
    // 每個測試前的設定
  });

  test('應該能夠...', async ({ page }) => {
    // Arrange - 準備
    await page.goto('/path');
    
    // Act - 執行
    await page.click('button');
    
    // Assert - 驗證
    await expect(page.locator('.result')).toBeVisible();
  });
});
```

## 🔄 CI/CD 整合

### GitHub Actions
已建立工作流程檔案：`.github/workflows/e2e-tests.yml`

觸發時機：
- Push 到 main、develop 分支
- Pull Request
- 手動觸發

結果會：
- 自動執行測試
- 上傳測試報告
- 失敗時上傳截圖和影片
- 在 PR 中留言測試結果

## 📊 測試涵蓋範圍

| 功能模組 | 測試數量 | 狀態 |
|---------|---------|------|
| 首頁 | 5 | ✅ |
| 登入/認證 | 5 | ✅ |
| Dashboard | 8 | ✅ |
| 文章管理 | 6 | ✅ |
| 文章編輯器 | 8 | ✅ |
| 時區功能 | 3 | ✅ |
| **總計** | **35** | ✅ |

## 🐛 常見問題

### Q: 測試無法連線到應用程式
**A:** 確認應用程式正在執行
```bash
curl http://localhost:3000
docker compose ps
```

### Q: 瀏覽器沒有安裝
**A:** 執行安裝指令
```bash
npx playwright install
```

### Q: 測試隨機失敗
**A:** 可能是時序問題，檢查：
- 是否有足夠的等待時間
- 使用 Playwright 的自動等待而非固定延遲
- 網路速度是否正常

### Q: 想錄製新測試
**A:** 使用 codegen
```bash
npm run test:codegen
```

## 📈 後續擴充建議

### 短期
- [ ] 增加使用者管理測試
- [ ] 增加角色權限測試
- [ ] 增加標籤管理測試
- [ ] 增加系統設定測試

### 中期
- [ ] 視覺回歸測試（Visual Regression）
- [ ] 效能測試整合
- [ ] API 測試整合
- [ ] 無障礙測試（Accessibility）

### 長期
- [ ] 跨瀏覽器測試（Firefox, Safari）
- [ ] 行動裝置測試
- [ ] 負載測試
- [ ] 安全測試

## 💡 最佳實踐

1. **測試隔離**：每個測試獨立，不依賴其他測試
2. **清晰命名**：使用描述性的測試名稱
3. **適當等待**：使用 Playwright 自動等待，避免固定延遲
4. **穩定選擇器**：優先使用 text、role、label
5. **資料清理**：測試後清理建立的資料
6. **Page Objects**：提高可維護性
7. **錯誤處理**：考慮異常情況
8. **文件更新**：新增測試時更新文件

## 🔗 相關連結

- [Playwright 文檔](https://playwright.dev)
- [測試最佳實踐](https://playwright.dev/docs/best-practices)
- [Page Object Model](https://playwright.dev/docs/pom)
- [專案 README](/tests/e2e/README.md)

## 👥 維護者

- 測試框架：建立於 2025-10-11
- 更新頻率：每次新功能開發時

## 📞 支援

遇到問題？
1. 查看 [README.md](/tests/e2e/README.md)
2. 檢查測試報告
3. 使用 UI 模式除錯
4. 開 Issue 或聯繫團隊
