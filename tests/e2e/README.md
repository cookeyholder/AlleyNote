# AlleyNote E2E 測試

這是 AlleyNote 前端的端對端（E2E）自動化測試套件，使用 Playwright 框架建立。

## 📋 目錄

- [安裝](#安裝)
- [執行測試](#執行測試)
- [測試結構](#測試結構)
- [撰寫測試](#撰寫測試)
- [CI/CD 整合](#cicd-整合)

## 🚀 安裝

### 前置需求

- Node.js 16.x 或更高版本
- Docker 和 Docker Compose（用於執行應用程式）

### 安裝步驟

```bash
# 進入測試目錄
cd tests/e2e

# 安裝依賴
npm install

# 安裝 Playwright 瀏覽器
npx playwright install
```

## ▶️ 執行測試

### 啟動應用程式

測試執行前，需要先啟動 AlleyNote 應用程式：

```bash
# 在專案根目錄
docker compose up -d
```

### 執行所有測試

```bash
# 無頭模式（headless）
npm test

# 有頭模式（可以看到瀏覽器）
npm run test:headed

# UI 模式（互動式測試）
npm run test:ui

# 除錯模式
npm run test:debug
```

### 執行特定測試

```bash
# 執行特定測試檔案
npx playwright test tests/01-home.spec.js

# 執行特定測試套件
npx playwright test --grep "首頁功能測試"

# 執行特定測試案例
npx playwright test --grep "應該正確顯示首頁標題"
```

### 查看測試報告

```bash
npm run test:report
```

## 📁 測試結構

```
tests/e2e/
├── tests/
│   ├── fixtures/
│   │   └── page-objects.js      # 頁面物件模式（Page Object Model）
│   ├── 01-home.spec.js           # 首頁測試
│   ├── 02-auth.spec.js           # 認證測試
│   ├── 03-dashboard.spec.js      # Dashboard 測試
│   ├── 04-posts-management.spec.js  # 文章管理測試
│   ├── 05-post-editor.spec.js    # 文章編輯器測試
│   └── 06-timezone.spec.js       # 時區功能測試
├── playwright.config.js          # Playwright 配置
├── package.json
└── README.md
```

## 📝 測試涵蓋範圍

### ✅ 已實作的測試

1. **首頁功能** (`01-home.spec.js`)
   - 頁面標題和導航
   - 文章列表顯示
   - 搜尋功能
   - 頁腳資訊

2. **認證功能** (`02-auth.spec.js`)
   - 登入頁面元素
   - 成功登入流程
   - 錯誤處理
   - 記住我功能

3. **Dashboard** (`03-dashboard.spec.js`)
   - 統計卡片顯示
   - 最近文章列表
   - 快速操作連結
   - 側邊欄導航

4. **文章管理** (`04-posts-management.spec.js`)
   - 文章列表顯示
   - 搜尋和篩選
   - 操作按鈕功能
   - 狀態切換

5. **文章編輯器** (`05-post-editor.spec.js`)
   - 新增文章
   - 編輯文章
   - 發布時間設定
   - 草稿儲存
   - 表單驗證

6. **時區功能** (`06-timezone.spec.js`)
   - 時區顯示
   - 時間轉換
   - UTC 儲存驗證

## 🔧 撰寫測試

### 使用 Page Object Model

```javascript
const { test, expect, LoginPage } = require("./fixtures/page-objects");

test("登入測試", async ({ page }) => {
  const loginPage = new LoginPage(page);
  await loginPage.goto();
  await loginPage.login("admin@example.com", "Admin@123456");
  await expect(page).toHaveURL("/dashboard");
});
```

### 使用認證 Fixture

```javascript
test("需要登入的測試", async ({ authenticatedPage }) => {
  // authenticatedPage 已經登入
  await authenticatedPage.goto("/admin/dashboard");
  // ... 測試邏輯
});
```

### 新增測試用例

1. 在 `tests/` 目錄下建立新的 `.spec.js` 檔案
2. 使用描述性的測試名稱
3. 遵循 AAA 模式（Arrange, Act, Assert）
4. 適當使用 Page Objects 來提高可維護性

範例：

```javascript
test.describe("新功能測試", () => {
  test.beforeEach(async ({ page }) => {
    // 設定
  });

  test("應該能夠執行某個操作", async ({ page }) => {
    // Arrange - 準備測試資料和狀態
    await page.goto("/some-page");

    // Act - 執行操作
    await page.click("button");

    // Assert - 驗證結果
    await expect(page.locator(".result")).toBeVisible();
  });
});
```

## 🔄 CI/CD 整合

### GitHub Actions 範例

```yaml
name: E2E Tests

on:
  push:
    branches: [main, develop]
  pull_request:
    branches: [main, develop]

jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3

      - name: Setup Node.js
        uses: actions/setup-node@v3
        with:
          node-version: "18"

      - name: Start services
        run: docker compose up -d

      - name: Install dependencies
        working-directory: tests/e2e
        run: npm ci

      - name: Install Playwright
        working-directory: tests/e2e
        run: npx playwright install --with-deps

      - name: Run tests
        working-directory: tests/e2e
        run: npm test

      - name: Upload test results
        if: always()
        uses: actions/upload-artifact@v3
        with:
          name: playwright-report
          path: tests/e2e/playwright-report/
```

## 🐛 除錯技巧

### 使用 UI 模式

```bash
npm run test:ui
```

這會開啟互動式 UI，可以：

- 逐步執行測試
- 檢視每個步驟的截圖
- 時光旅行除錯

### 使用除錯模式

```bash
npm run test:debug
```

這會開啟 Playwright Inspector，可以：

- 設定中斷點
- 逐步執行
- 檢視 DOM 狀態

### 查看追蹤記錄

當測試失敗時，Playwright 會自動儲存追蹤記錄。查看方式：

```bash
npx playwright show-trace test-results/path-to-trace.zip
```

### 生成測試程式碼

使用 Codegen 錄製操作並生成測試程式碼：

```bash
npm run test:codegen
```

## 📊 測試報告

測試執行後，會生成 HTML 報告：

```bash
npm run test:report
```

報告包含：

- 所有測試的執行結果
- 失敗測試的截圖和影片
- 詳細的錯誤堆疊
- 執行時間統計

## 🔑 測試帳號

預設測試帳號：

- Email: `admin@example.com`
- Password: `Admin@123456`

若登入失敗，請先在專案根目錄執行：

```bash
php scripts/reset_admin.php
```

## 📌 注意事項

1. **測試隔離**：每個測試應該獨立，不依賴其他測試的狀態
2. **資料清理**：測試後應清理建立的測試資料
3. **等待策略**：優先使用 Playwright 的自動等待，避免使用固定延遲
4. **選擇器穩定性**：優先使用語義化的選擇器（text, role, label）
5. **環境一致性**：確保測試環境與生產環境配置一致

## 🤝 貢獻

新增測試時請遵循：

1. 使用 Page Object 模式
2. 撰寫清晰的測試描述
3. 添加適當的註解
4. 確保測試可重複執行
5. 更新此 README

## 📚 參考資源

- [Playwright 官方文檔](https://playwright.dev)
- [Playwright 最佳實踐](https://playwright.dev/docs/best-practices)
- [Page Object Model](https://playwright.dev/docs/pom)
