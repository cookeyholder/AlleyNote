# AlleyNote 前端測試策略

## 📋 目錄

1. [概述](#概述)
2. [測試金字塔](#測試金字塔)
3. [E2E 測試](#e2e-測試)
4. [整合測試](#整合測試)
5. [單元測試](#單元測試)
6. [視覺回歸測試](#視覺回歸測試)
7. [測試環境設定](#測試環境設定)
8. [最佳實踐](#最佳實踐)

---

## 概述

AlleyNote 前端採用**完整的測試策略**，確保應用程式的穩定性與可靠性。本文件說明各類測試的實作方式與最佳實踐。

### 測試目標

- ✅ **功能正確性**: 確保功能符合規格
- ✅ **使用者體驗**: 驗證關鍵使用者流程
- ✅ **迴歸防護**: 防止舊功能被破壞
- ✅ **程式碼品質**: 提升程式碼可維護性
- ✅ **信心保證**: 安心重構與新增功能

---

## 測試金字塔

```
        ┌───────────┐
        │  E2E 測試  │  少量（10-20%）- 完整使用者流程
        │  5-10 個   │
        ├───────────┤
        │ 整合測試   │  中量（20-30%）- 組件協作
        │ 20-30 個   │
        ├───────────┤
        │ 單元測試   │  大量（50-70%）- 獨立函式
        │ 100+ 個    │
        └───────────┘
```

### 各層級測試比較

| 測試類型 | 執行速度 | 覆蓋範圍 | 維護成本 | 建議數量 |
| -------- | -------- | -------- | -------- | -------- |
| E2E 測試 | 🐌 慢    | 廣       | 高       | 少       |
| 整合測試 | 🐇 中    | 中       | 中       | 中       |
| 單元測試 | ⚡ 快    | 窄       | 低       | 多       |

---

## E2E 測試

### 使用 Playwright

**為什麼選擇 Playwright？**

- ✅ 跨瀏覽器支援（Chromium、Firefox、WebKit）
- ✅ 自動等待機制，減少 flaky tests
- ✅ 強大的選擇器與斷言
- ✅ 內建截圖與影片錄製
- ✅ TypeScript 支援良好

### 安裝與設定

```bash
npm install -D @playwright/test
npx playwright install
```

**`playwright.config.js`**

```javascript
import { defineConfig, devices } from "@playwright/test";

export default defineConfig({
  testDir: "./tests/e2e",

  // 測試超時時間
  timeout: 30000,

  // 重試失敗的測試
  retries: process.env.CI ? 2 : 0,

  // 平行執行
  workers: process.env.CI ? 1 : undefined,

  // 報告器
  reporter: [["html"], ["json", { outputFile: "test-results/results.json" }]],

  use: {
    // 基礎 URL
    baseURL: "http://localhost:3000",

    // 錄製失敗的測試
    trace: "on-first-retry",
    screenshot: "only-on-failure",
    video: "retain-on-failure",

    // 瀏覽器選項
    viewport: { width: 1280, height: 720 },
    locale: "zh-TW",
    timezoneId: "Asia/Taipei",
  },

  // 測試專案（不同瀏覽器）
  projects: [
    {
      name: "chromium",
      use: { ...devices["Desktop Chrome"] },
    },
    {
      name: "firefox",
      use: { ...devices["Desktop Firefox"] },
    },
    {
      name: "webkit",
      use: { ...devices["Desktop Safari"] },
    },

    // 行動裝置
    {
      name: "Mobile Chrome",
      use: { ...devices["Pixel 5"] },
    },
    {
      name: "Mobile Safari",
      use: { ...devices["iPhone 12"] },
    },
  ],

  // 開發伺服器
  webServer: {
    command: "直接編輯文件並刷新瀏覽器",
    port: 3000,
    reuseExistingServer: !process.env.CI,
  },
});
```

### E2E 測試範例

**`tests/e2e/auth.spec.js`**

```javascript
import { test, expect } from "@playwright/test";

test.describe("使用者認證", () => {
  test.beforeEach(async ({ page }) => {
    await page.goto("/");
  });

  test("成功登入並導向後台", async ({ page }) => {
    // 點擊登入按鈕
    await page.click("text=登入");

    // 等待導向到登入頁面
    await expect(page).toHaveURL(/\/login/);

    // 填寫登入表單
    await page.fill('input[name="email"]', "admin@example.com");
    await page.fill('input[name="password"]', "Admin@123456");

    // 提交表單
    await page.click('button[type="submit"]');

    // 等待導向到後台
    await expect(page).toHaveURL(/\/admin\/dashboard/);

    // 驗證使用者資訊顯示
    await expect(page.locator("text=歡迎回來")).toBeVisible();
  });

  test("登入失敗顯示錯誤訊息", async ({ page }) => {
    await page.goto("/login");

    await page.fill('input[name="email"]', "wrong@example.com");
    await page.fill('input[name="password"]', "wrongpassword");
    await page.click('button[type="submit"]');

    // 驗證錯誤訊息
    await expect(page.locator(".error-message")).toContainText(
      "帳號或密碼錯誤",
    );
  });

  test("登出後清除使用者狀態", async ({ page, context }) => {
    // 先登入
    await page.goto("/login");
    await page.fill('input[name="email"]', "admin@example.com");
    await page.fill('input[name="password"]', "Admin@123456");
    await page.click('button[type="submit"]');

    await expect(page).toHaveURL(/\/admin\/dashboard/);

    // 點擊登出
    await page.click('[aria-label="使用者選單"]');
    await page.click("text=登出");

    // 驗證導向到首頁
    await expect(page).toHaveURL("/");

    // 驗證 SessionStorage 已清除
    const token = await page.evaluate(() =>
      sessionStorage.getItem("alleynote_token"),
    );
    expect(token).toBeNull();
  });
});
```

**`tests/e2e/posts.spec.js`**

```javascript
import { test, expect } from "@playwright/test";

test.describe("文章管理", () => {
  // 登入設定
  test.use({
    storageState: "tests/fixtures/auth.json", // 預先登入的狀態
  });

  test("建立新文章", async ({ page }) => {
    await page.goto("/admin/posts");

    // 點擊新增文章
    await page.click("text=新增文章");
    await expect(page).toHaveURL(/\/admin\/posts\/create/);

    // 填寫標題
    await page.fill('input[name="title"]', "測試文章標題");

    // 填寫內容（CKEditor）
    const editor = page.locator(".ck-editor__editable");
    await editor.click();
    await editor.fill("這是測試文章的內容");

    // 選擇分類
    await page.selectOption('select[name="category"]', "tech");

    // 儲存草稿
    await page.click('button:has-text("儲存草稿")');

    // 驗證成功訊息
    await expect(page.locator(".toast-success")).toContainText("草稿已儲存");

    // 驗證導向到編輯頁
    await expect(page).toHaveURL(/\/admin\/posts\/\d+\/edit/);
  });

  test("上傳圖片到文章", async ({ page }) => {
    await page.goto("/admin/posts/create");

    // 等待 CKEditor 載入
    await page.waitForSelector(".ck-editor__editable");

    // 模擬圖片上傳
    const [fileChooser] = await Promise.all([
      page.waitForEvent("filechooser"),
      page.click('.ck-button[title*="插入圖片"]'),
    ]);

    await fileChooser.setFiles("tests/fixtures/test-image.jpg");

    // 等待上傳完成
    await page.waitForSelector('img[src*="uploads"]');

    // 驗證圖片已插入
    const images = await page.locator(".ck-editor__editable img").count();
    expect(images).toBeGreaterThan(0);
  });

  test("發布文章", async ({ page }) => {
    // 假設已有草稿文章 ID 為 1
    await page.goto("/admin/posts/1/edit");

    // 點擊發布
    await page.click('button:has-text("發布")');

    // 確認對話框
    await page.click('button:has-text("確認發布")');

    // 驗證成功訊息
    await expect(page.locator(".toast-success")).toContainText("文章已發布");

    // 驗證狀態更新
    await expect(page.locator(".post-status")).toContainText("已發布");
  });

  test("刪除文章需要確認", async ({ page }) => {
    await page.goto("/admin/posts");

    // 點擊第一篇文章的刪除按鈕
    await page.click('tr:first-child button[aria-label="刪除"]');

    // 驗證確認對話框出現
    await expect(page.locator(".modal-confirm")).toBeVisible();

    // 點擊取消
    await page.click('button:has-text("取消")');

    // 驗證文章仍然存在
    const rowCount = await page.locator("tbody tr").count();
    expect(rowCount).toBeGreaterThan(0);
  });
});
```

### 產生認證狀態

```javascript
// tests/setup/auth.setup.js
import { test as setup } from "@playwright/test";

setup("authenticate", async ({ page }) => {
  await page.goto("/login");
  await page.fill('input[name="email"]', "admin@example.com");
  await page.fill('input[name="password"]', "Admin@123456");
  await page.click('button[type="submit"]');

  await page.waitForURL("/admin/dashboard");

  // 儲存認證狀態
  await page.context().storageState({
    path: "tests/fixtures/auth.json",
  });
});
```

---

## 整合測試

### 使用 Jest 或瀏覽器原生測試

**安裝**

```bash
npm install -D vitest jsdom @testing-library/dom
```

**`jest 或瀏覽器原生測試.config.js`**

```javascript
import { defineConfig } from "jest 或瀏覽器原生測試/config";

export default defineConfig({
  test: {
    environment: "jsdom",
    setupFiles: ["./tests/setup.js"],
    coverage: {
      provider: "v8",
      reporter: ["text", "json", "html"],
      exclude: ["node_modules/", "tests/"],
    },
  },
});
```

### 整合測試範例

**`tests/integration/api-integration.test.js`**

```javascript
import { describe, it, expect, beforeEach, vi } from "jest 或瀏覽器原生測試";
import { authAPI } from "../../src/api/modules/auth.js";
import { postsAPI } from "../../src/api/modules/posts.js";
import { setupMockServer } from "../mocks/server.js";

describe("API 整合測試", () => {
  beforeEach(() => {
    setupMockServer();
  });

  it("登入成功後 Token 被儲存", async () => {
    const result = await authAPI.login({
      email: "admin@example.com",
      password: "Admin@123456",
    });

    expect(result.token).toBeDefined();

    // 驗證 Token 已儲存到 SessionStorage
    const storedToken = sessionStorage.getItem("alleynote_token");
    expect(storedToken).toBeTruthy();
  });

  it("API 請求自動加入 JWT Token", async () => {
    // 先登入
    await authAPI.login({
      email: "admin@example.com",
      password: "Admin@123456",
    });

    // 發送需要認證的請求
    const posts = await postsAPI.list();

    expect(posts.data).toBeDefined();
    expect(Array.isArray(posts.data)).toBe(true);
  });

  it("Token 過期時自動導向登入頁", async () => {
    // Mock 過期的 Token
    sessionStorage.setItem(
      "alleynote_token",
      JSON.stringify({
        token: "expired_token",
        expiresAt: Date.now() - 1000, // 已過期
      }),
    );

    // 模擬頁面導向
    const locationMock = { href: "" };
    global.window = { location: locationMock };

    try {
      await postsAPI.list();
    } catch (error) {
      // 應該拋出 UNAUTHORIZED 錯誤
      expect(error.code).toBe("UNAUTHORIZED");
    }

    // 驗證導向到登入頁
    expect(locationMock.href).toContain("/login");
  });
});
```

---

## 單元測試

### 工具函式測試

**`tests/unit/utils/validation.test.js`**

```javascript
import { describe, it, expect } from "jest 或瀏覽器原生測試";
import { validators } from "../../../src/utils/formManager.js";

describe("Validators", () => {
  describe("required", () => {
    it("空值應該回傳錯誤訊息", () => {
      const validator = validators.required();
      expect(validator("")).toBe("此欄位為必填");
      expect(validator(null)).toBe("此欄位為必填");
      expect(validator(undefined)).toBe("此欄位為必填");
    });

    it("有值應該回傳 true", () => {
      const validator = validators.required();
      expect(validator("test")).toBe(true);
      expect(validator("0")).toBe(true);
    });
  });

  describe("email", () => {
    it("有效的 Email 應該回傳 true", () => {
      const validator = validators.email();
      expect(validator("test@example.com")).toBe(true);
      expect(validator("user+tag@domain.co.uk")).toBe(true);
    });

    it("無效的 Email 應該回傳錯誤訊息", () => {
      const validator = validators.email();
      expect(validator("invalid")).toBe("請輸入有效的電子郵件");
      expect(validator("test@")).toBe("請輸入有效的電子郵件");
      expect(validator("@example.com")).toBe("請輸入有效的電子郵件");
    });

    it("空值應該回傳 true（選填）", () => {
      const validator = validators.email();
      expect(validator("")).toBe(true);
    });
  });

  describe("minLength", () => {
    it("長度不足應該回傳錯誤訊息", () => {
      const validator = validators.minLength(8);
      expect(validator("short")).toBe("至少需要 8 個字元");
    });

    it("長度足夠應該回傳 true", () => {
      const validator = validators.minLength(8);
      expect(validator("longenough")).toBe(true);
    });
  });
});
```

### Store 測試

**`tests/unit/store/Store.test.js`**

```javascript
import { describe, it, expect, beforeEach, vi } from "jest 或瀏覽器原生測試";
import { Store } from "../../../src/store/Store.js";

describe("Store", () => {
  let store;

  beforeEach(() => {
    store = new Store({ count: 0, user: null });
  });

  it("應該取得初始狀態", () => {
    expect(store.get("count")).toBe(0);
    expect(store.get("user")).toBeNull();
  });

  it("應該設定狀態", () => {
    store.set("count", 10);
    expect(store.get("count")).toBe(10);
  });

  it("設定狀態時應該通知訂閱者", () => {
    const callback = vi.fn();
    store.subscribe("count", callback);

    store.set("count", 5);

    expect(callback).toHaveBeenCalledWith(5, 0);
  });

  it("應該支援多個訂閱者", () => {
    const callback1 = vi.fn();
    const callback2 = vi.fn();

    store.subscribe("count", callback1);
    store.subscribe("count", callback2);

    store.set("count", 10);

    expect(callback1).toHaveBeenCalled();
    expect(callback2).toHaveBeenCalled();
  });

  it("取消訂閱後不應該收到通知", () => {
    const callback = vi.fn();
    const unsubscribe = store.subscribe("count", callback);

    unsubscribe();
    store.set("count", 10);

    expect(callback).not.toHaveBeenCalled();
  });

  it("update 應該正確更新狀態", () => {
    store.set("count", 5);
    store.update("count", (oldValue) => oldValue + 1);

    expect(store.get("count")).toBe(6);
  });
});
```

---

## 視覺回歸測試

### 使用 Playwright 的截圖比對

```javascript
import { test, expect } from "@playwright/test";

test.describe("視覺回歸測試", () => {
  test("首頁視覺不變", async ({ page }) => {
    await page.goto("/");
    await expect(page).toHaveScreenshot("homepage.png");
  });

  test("登入頁面視覺不變", async ({ page }) => {
    await page.goto("/login");
    await expect(page).toHaveScreenshot("login-page.png");
  });

  test("文章列表視覺不變", async ({ page }) => {
    await page.goto("/posts");

    // 等待內容載入
    await page.waitForSelector(".post-card");

    await expect(page).toHaveScreenshot("posts-list.png", {
      fullPage: true,
      mask: [page.locator(".timestamp")], // 遮蓋時間戳記
    });
  });

  test("按鈕 hover 狀態", async ({ page }) => {
    await page.goto("/");

    const button = page.locator("button.primary");
    await button.hover();

    await expect(button).toHaveScreenshot("button-hover.png");
  });
});
```

---

## 測試環境設定

### Mock Server (MSW)

**安裝**

```bash
npm install -D msw
```

**`tests/mocks/handlers.js`**

```javascript
import { http, HttpResponse } from "msw";

export const handlers = [
  // 登入 API
  http.post("/api/auth/login", async ({ request }) => {
    const { email, password } = await request.json();

    if (email === "admin@example.com" && password === "Admin@123456") {
      return HttpResponse.json({
        success: true,
        data: {
          token: "mock_jwt_token",
          expires_in: 3600,
          user: {
            id: 1,
            email: "admin@example.com",
            role: "admin",
          },
        },
      });
    }

    return HttpResponse.json(
      {
        success: false,
        message: "帳號或密碼錯誤",
      },
      { status: 401 },
    );
  }),

  // 文章列表 API
  http.get("/api/posts", () => {
    return HttpResponse.json({
      success: true,
      data: [
        {
          id: 1,
          title: "測試文章 1",
          content: "這是測試內容",
          status: "published",
          created_at: "2024-01-01T00:00:00Z",
        },
        {
          id: 2,
          title: "測試文章 2",
          content: "這是測試內容",
          status: "draft",
          created_at: "2024-01-02T00:00:00Z",
        },
      ],
      pagination: {
        current_page: 1,
        total_pages: 1,
        total_items: 2,
      },
    });
  }),
];
```

**`tests/mocks/server.js`**

```javascript
import { setupServer } from "msw/node";
import { handlers } from "./handlers.js";

export const server = setupServer(...handlers);

export function setupMockServer() {
  beforeAll(() => server.listen());
  afterEach(() => server.resetHandlers());
  afterAll(() => server.close());
}
```

---

## 最佳實踐

### 1. 測試金字塔平衡

```javascript
// ✅ 大量單元測試
describe("formatDate utility", () => {
  it("should format ISO date correctly", () => {
    expect(formatDate("2024-01-01")).toBe("2024年1月1日");
  });
});

// ✅ 適量整合測試
describe("Post creation flow", () => {
  it("should save post and show success message", async () => {
    const post = await postsAPI.create({ title: "Test" });
    expect(post.id).toBeDefined();
  });
});

// ✅ 少量 E2E 測試（關鍵流程）
test("complete user journey: login → create post → publish", async ({
  page,
}) => {
  // 完整流程測試
});
```

### 2. 測試命名清晰

```javascript
// ✅ 好的命名
test("登入失敗時顯示錯誤訊息", () => {});
test("文章標題超過 255 字元時顯示驗證錯誤", () => {});

// ❌ 不好的命名
test("test1", () => {});
test("it works", () => {});
```

### 3. AAA 模式（Arrange-Act-Assert）

```javascript
test("使用者可以更新個人資料", async () => {
  // Arrange - 準備測試資料
  const user = { name: "John", email: "john@example.com" };

  // Act - 執行操作
  const result = await userAPI.update(user);

  // Assert - 驗證結果
  expect(result.name).toBe("John");
  expect(result.email).toBe("john@example.com");
});
```

### 4. 避免測試實作細節

```javascript
// ❌ 測試實作細節
test("按鈕有 onClick 事件監聽器", () => {
  const button = document.querySelector("button");
  expect(button.onclick).toBeDefined();
});

// ✅ 測試行為
test("點擊按鈕後顯示模態框", () => {
  const button = screen.getByRole("button", { name: "開啟" });
  button.click();
  expect(screen.getByRole("dialog")).toBeVisible();
});
```

### 5. 使用 Test Fixtures

```javascript
// tests/fixtures/posts.js
export const mockPosts = [
  {
    id: 1,
    title: "測試文章 1",
    content: "內容...",
    status: "published",
  },
  {
    id: 2,
    title: "測試文章 2",
    content: "內容...",
    status: "draft",
  },
];

// 在測試中使用
import { mockPosts } from "../fixtures/posts.js";

test("顯示文章列表", () => {
  renderPostsList(mockPosts);
  expect(screen.getAllByRole("article")).toHaveLength(2);
});
```

---

## 執行測試

### NPM Scripts

```json
{
  "scripts": {
    "test": "jest 或瀏覽器原生測試",
    "test:ui": "jest 或瀏覽器原生測試 --ui",
    "test:coverage": "jest 或瀏覽器原生測試 --coverage",
    "test:e2e": "playwright test",
    "test:e2e:ui": "playwright test --ui",
    "test:e2e:headed": "playwright test --headed",
    "test:all": "npm run test && npm run test:e2e"
  }
}
```

### CI/CD 整合

```yaml
# .github/workflows/test.yml
name: Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest

    steps:
      - uses: actions/checkout@v3

      - name: Setup Node.js
        uses: actions/setup-node@v3
        with:
          node-version: "18"

      - name: Install dependencies
        run: npm ci

      - name: Run unit tests
        run: npm run test:coverage

      - name: Run E2E tests
        run: npm run test:e2e

      - name: Upload coverage
        uses: codecov/codecov-action@v3
        with:
          files: ./coverage/coverage-final.json

      - name: Upload test results
        if: always()
        uses: actions/upload-artifact@v3
        with:
          name: playwright-report
          path: playwright-report/
```

---

## 總結

AlleyNote 前端測試策略涵蓋：

1. ✅ **E2E 測試** - Playwright 驗證關鍵使用者流程
2. ✅ **整合測試** - Jest 或瀏覽器原生測試 + MSW 測試 API 整合
3. ✅ **單元測試** - Jest 或瀏覽器原生測試 測試獨立函式與模組
4. ✅ **視覺回歸測試** - Playwright 截圖比對
5. ✅ **Mock Server** - MSW 隔離外部依賴

遵循本策略，可確保應用程式的**穩定性**、**可維護性**與**開發信心**。
