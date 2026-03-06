# AlleyNote E2E 測試框架

## 🎯 已建立完成

我已經為 AlleyNote 建立了一套完整的端對端（E2E）自動化測試框架，使用 **Playwright** 作為測試工具。

## 📂 檔案結構

```
tests/e2e/
├── tests/
│   ├── fixtures/
│   │   └── page-objects.js           # Page Object Model 實作
│   ├── 01-home.spec.js                # ✅ 首頁測試 (5 個測試)
│   ├── 02-auth.spec.js                # ✅ 登入認證測試 (5 個測試)
│   ├── 03-dashboard.spec.js           # ✅ Dashboard 測試 (8 個測試)
│   ├── 04-posts-management.spec.js    # ✅ 文章管理測試 (6 個測試)
│   ├── 05-post-editor.spec.js         # ✅ 文章編輯器測試 (8 個測試)
│   └── 06-timezone.spec.js            # ✅ 時區功能測試 (3 個測試)
├── playwright.config.js               # Playwright 配置
├── package.json                       # 依賴管理
├── setup.sh                          # 🔧 安裝腳本
├── run-tests.sh                      # 🚀 執行腳本
├── README.md                         # 詳細文件
└── QUICK_START.md                    # 快速開始

.github/workflows/
└── e2e-tests.yml                     # GitHub Actions CI/CD
```

## 🚀 快速開始

### 1️⃣ 安裝環境

```bash
cd tests/e2e
./setup.sh
```

這會自動：

- 檢查 Node.js 版本
- 安裝 npm 依賴
- 下載 Playwright 瀏覽器

### 2️⃣ 啟動應用程式

```bash
# 在專案根目錄
docker compose up -d
```

### 3️⃣ 執行測試

```bash
cd tests/e2e

# 方法 1: 使用執行腳本（推薦）
./run-tests.sh

# 方法 2: 使用 npm 指令
npm test
```

## 🎬 執行模式

```bash
# 無頭模式（預設，快速）
./run-tests.sh
npm test

# 有頭模式（可以看到瀏覽器）
./run-tests.sh headed
npm run test:headed

# UI 模式（最佳除錯體驗）
./run-tests.sh ui
npm run test:ui

# 除錯模式
./run-tests.sh debug
npm run test:debug

# 快速執行（跳過環境檢查）
./run-tests.sh headless skip-setup
```

## 📊 測試涵蓋範圍

| 測試檔案                    | 功能     | 測試數量 | 狀態 |
| --------------------------- | -------- | -------- | ---- |
| 01-home.spec.js             | 首頁功能 | 5        | ✅   |
| 02-auth.spec.js             | 登入認證 | 5        | ✅   |
| 03-dashboard.spec.js        | 儀表板   | 8        | ✅   |
| 04-posts-management.spec.js | 文章管理 | 6        | ✅   |
| 05-post-editor.spec.js      | 文章編輯 | 8        | ✅   |
| 06-timezone.spec.js         | 時區轉換 | 3        | ✅   |
| **總計**                    |          | **35+**  | ✅   |

## 🔍 主要功能測試

### ✅ 首頁 (Home)

- 標題和導航顯示
- 文章列表載入
- 搜尋功能
- 登入按鈕導航
- 頁腳資訊

### ✅ 登入認證 (Auth)

- 登入表單元素顯示
- 成功登入流程
- 錯誤密碼處理
- 記住我功能
- 忘記密碼連結

### ✅ 儀表板 (Dashboard)

- 統計卡片 (文章數、瀏覽量等)
- 最近發布文章列表
- 快速操作連結
- 側邊欄導航
- 使用者資訊顯示

### ✅ 文章管理 (Posts Management)

- 文章列表顯示
- 搜尋和重置
- 狀態篩選
- 新增文章導航
- 編輯/刪除/發布按鈕

### ✅ 文章編輯器 (Post Editor)

- 新增文章流程
- 編輯現有文章
- 發布時間設定（含時區）
- 草稿儲存
- 取消操作
- 表單驗證
- 摘要功能

### ✅ 時區功能 (Timezone)

- 網站時區顯示
- 發布時間轉換正確性
- UTC 儲存驗證
- 系統設定時區

## 🛠️ Page Object Model

使用 Page Object 模式提高測試可維護性：

```javascript
// 引入 Page Objects
const {
  LoginPage,
  DashboardPage,
  PostEditorPage,
} = require("./fixtures/page-objects");

// 使用範例
test("登入測試", async ({ page }) => {
  const loginPage = new LoginPage(page);
  await loginPage.goto();
  await loginPage.login("admin@example.com", "Admin@123456");
  // ...
});
```

可用的 Page Objects：

- `LoginPage` - 登入頁面
- `DashboardPage` - 儀表板
- `PostsManagementPage` - 文章管理
- `PostEditorPage` - 文章編輯器

## 🔐 認證 Fixture

自動登入功能，無需每個測試都重複登入：

```javascript
test("需要登入的測試", async ({ authenticatedPage }) => {
  // authenticatedPage 已經完成登入
  await authenticatedPage.goto("/admin/dashboard");
  // 直接開始測試邏輯
});
```

## 📈 測試報告

測試執行後會生成 HTML 報告：

```bash
# 查看報告
npm run test:report
```

報告包含：

- ✅ 通過的測試
- ❌ 失敗的測試（含截圖和影片）
- ⏱️ 執行時間統計
- 📸 每個步驟的截圖

## 🐛 除錯工具

### UI 模式（推薦）

```bash
npm run test:ui
```

- 視覺化介面
- 逐步執行
- 時光旅行除錯
- 檢視 DOM 狀態

### 錄製新測試

```bash
npm run test:codegen
```

自動生成測試程式碼！

## 🔄 CI/CD 整合

已設定 GitHub Actions (`.github/workflows/e2e-tests.yml`)：

**觸發時機：**

- Push 到 main/develop 分支
- Pull Request
- 手動觸發

**執行流程：**

1. 自動設定環境
2. 啟動應用程式
3. 執行所有測試
4. 上傳測試報告
5. 失敗時上傳截圖/影片
6. 在 PR 中留言測試結果

## 📝 撰寫新測試

### 範本

```javascript
const { test, expect } = require("@playwright/test");

test.describe("新功能測試", () => {
  test.beforeEach(async ({ page }) => {
    // 每個測試前的設定
    await page.goto("/path");
  });

  test("應該能夠執行某操作", async ({ page }) => {
    // Arrange - 準備
    const button = page.locator("button.action");

    // Act - 執行
    await button.click();

    // Assert - 驗證
    await expect(page.locator(".result")).toBeVisible();
  });
});
```

### 最佳實踐

1. ✅ 使用描述性的測試名稱
2. ✅ 每個測試獨立運行
3. ✅ 優先使用 Page Objects
4. ✅ 使用語義化選擇器 (text, role, label)
5. ✅ 避免固定延遲，使用自動等待
6. ✅ 測試後清理資料

## 🔧 常用指令速查

```bash
# 安裝
./setup.sh

# 執行測試
./run-tests.sh                    # 標準執行
./run-tests.sh headed             # 有頭模式
./run-tests.sh ui                 # UI 模式
./run-tests.sh headless skip-setup # 快速執行

# npm 指令
npm test                          # 執行所有測試
npm run test:headed               # 有頭模式
npm run test:ui                   # UI 模式
npm run test:debug                # 除錯模式
npm run test:report               # 查看報告
npm run test:codegen              # 錄製測試

# 特定測試
npx playwright test tests/01-home.spec.js
npx playwright test --grep "登入"

# 清理
./run-tests.sh clean
```

## 📚 文件資源

- 📖 [README.md](tests/e2e/README.md) - 完整文件
- 🚀 [QUICK_START.md](tests/e2e/QUICK_START.md) - 快速指南
- 🌐 [Playwright 官方文檔](https://playwright.dev)
- 💡 [測試最佳實踐](https://playwright.dev/docs/best-practices)

## ✨ 特色功能

### 1. 自動登入

無需每次測試都登入，使用 `authenticatedPage` fixture

### 2. 智慧等待

Playwright 自動等待元素可見/可用

### 3. 失敗重試

CI 環境自動重試 2 次

### 4. 完整報告

HTML 報告 + 截圖 + 影片

### 5. 時區測試

專門測試時區轉換功能

### 6. Page Objects

提高測試可維護性

## 🎯 使用情境

### 開發時

```bash
# 監控前端改動
npm run test:ui
```

### 提交前

```bash
# 快速驗證
./run-tests.sh headless skip-setup
```

### CI/CD

自動執行，無需手動操作

### 除錯

```bash
# 互動式除錯
npm run test:ui

# 錄製操作
npm run test:codegen
```

## 🔜 未來擴充

- [ ] 視覺回歸測試
- [ ] 效能測試
- [ ] 無障礙測試
- [ ] 跨瀏覽器測試
- [ ] 行動裝置測試

## 💬 需要協助？

1. 查看 [README.md](tests/e2e/README.md)
2. 使用 UI 模式除錯
3. 查看測試報告
4. 開 Issue

---

**測試框架版本：** 1.0.0
**建立日期：** 2025-10-11
**Playwright 版本：** 1.40.0

🎉 開始使用吧！
