# AlleyNote 全方位程式碼審查報告 (v3.0.0)

> **審查日期**: 2026-04-02
> **審查範圍**: 後端 (PHP/Slim) + 前端 (Vanilla JS) + 基礎設施 (Docker/CI/CD)
> **審查視角**: 20 年經驗資深架構師與資安工程師

## 執行摘要

本次審查發現，雖然先前已完成多項 P0 等級的修復（如 `RichTextProcessorService` 整合 `HTMLPurifier`、`Math.random()` 移除、Legacy `PostController` 刪除等），但仍有部分核心安全機制與架構問題尚未落實。特別是 **CSRF 中介層尚未啟用**、**JWT 仍存放在 localStorage** 以及 **`JwtAuthorizationMiddleware` 存在嚴重的上帝類別 (God Class) 現象**。

| 類別                      | P0_CRITICAL | P1_HIGH | P2_MEDIUM | P3_LOW | 合計 |
| ------------------------- | :---------: | :-----: | :-------: | :----: | :--: |
| 資安 (Security)           |      4      |    3    |     2     |   1    |  10  |
| 效能 (Performance)        |      0      |    1    |     2     |   1    |  4   |
| Clean Code                |      1      |    2    |     3     |   2    |  8   |
| 架構與遺跡 (Architecture) |      1      |    2    |     1     |   1    |  5   |
| 邏輯錯誤 (Logic Bugs)     |      0      |    1    |     2     |   1    |  4   |
| **總計**                  |    **6**    |  **9**  |  **10**   | **6**  | **31** |

---

## P0_CRITICAL — 致命問題（必須立即修復）

### SEC-010: CSRF 中介層已實作但未在路由中啟用
- **位置**: `backend/app/Application/Middleware/CsrfMiddleware.php` & `backend/config/routes/api.php`
- **影響**: 所有變更狀態的請求（POST/PUT/DELETE）目前均不受 CSRF 保護。
- **風險**: 跨站請求偽造攻擊，可導致管理者權限被盜用進行刪除或修改文章。

### SEC-011: JWT Token 仍存放在 localStorage
- **位置**: `frontend/js/utils/storage.js`, `frontend/js/api/client.js`
- **影響**: Token 容易受到 XSS 攻擊被竊取。
- **風險**: 一旦前端存在任何 XSS 漏洞，攻擊者可立即獲取使用者的存取憑證。
- **建議**: 遷移至 `HttpOnly` Cookie 儲存。

### SEC-008: CKEditor 允許所有 HTML 元素與屬性
- **位置**: `frontend/js/components/RichTextEditor.js:248-255`
- **影響**: `htmlSupport.allow` 設定為 `/.*/`（允許所有）。
- **風險**: 繞過前端淨化，允許惡意屬性或標籤進入資料庫，增加 XSS 風險。

### ARCH-001: JwtAuthorizationMiddleware 上帝類別 (1065 行)
- **位置**: `backend/app/Application/Middleware/JwtAuthorizationMiddleware.php`
- **影響**: 職責極度不清晰，同時處理驗證、授權、IP 限制、黑名單、權限檢查。
- **風險**: 極難維護，新增權限邏輯時容易引入副作用。

### SEC-007: 前端部分欄位仍未進行 HTML 跳脫 (escapeHtml)
- **位置**: `frontend/js/pages/admin/dashboard.js:254` (`post.author` 等)。
- **影響**: 雖然標題已跳脫，但作者、類別名稱等欄位仍可能存在注入風險。
- **風險**: 存儲型 XSS。

### ARCH-002: 前端 require() 在 ES Module 環境中仍存在
- **位置**: `frontend/js/main.js`（部分路徑）
- **影響**: 導致 `ReferenceError: require is not defined`。
- **風險**: 功能失效。

---

## P1_HIGH — 嚴重問題

### PERF-004: Tailwind CSS CDN 用於「生產環境」佈署
- **位置**: `frontend/index.html`
- **影響**: 執行時解析 Class，造成瀏覽器解析負擔。
- **建議**: 應整合至構建流程（或使用 Preline/Flowbite 等預編譯 CSS）。

### ARCH-003: 重複的路由定義
- **位置**: `backend/config/routes/admin.php` vs `backend/config/routes/api.php`
- **影響**: 部分路由如 `/api/v1/users` 在不同檔案中定義。
- **建議**: 統一路由管理結構。

### BUG-002: 容器 (DI Container) 配置重複定義
- **位置**: `backend/config/container.php`
- **影響**: 導致服務被多次實例化或配置被覆蓋。

---

## 下一步行動

1. **啟動 OpenSpec 修復任務**：針對上述 P0/P1 問題進行修復。
2. **自動化測試驗證**：確保 CSRF 與 JWT 安全性測試通過。
3. **進行二次審查**：驗證修復成果。
