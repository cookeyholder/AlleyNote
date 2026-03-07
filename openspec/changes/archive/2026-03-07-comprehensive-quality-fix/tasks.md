## 1. 資安修復 (Security Fixes)

- [x] 1.1 修改 `frontend/js/api/client.js`：實作 CSRF Token 自動提取與 Header 注入。
- [x] 1.2 更新 `backend/app/Application/Middleware/JwtAuthenticationMiddleware.php`：加入信任代理檢查邏輯。
- [x] 1.3 評估並實作 JWT Token 從 localStorage 移轉至 Secure Cookie 的過渡方案。

## 2. 效能與架構優化 (Performance & Architecture)

- [x] 2.1 重構 `backend/app/Domains/Post/Repositories/PostRepository.php`：使用批次 SQL 解決 N+1 標籤計數更新問題。
- [x] 2.2 修改統計服務的快取 TTL 設定，實施分級快取策略。
- [x] 2.3 為關鍵 DTO 補齊強型別建構子。

## 3. 代碼清理 (Cleanup)

- [x] 3.1 全案搜尋並移除 `error_log` (PHP)。
- [x] 3.2 全案搜尋並移除 `console.log` (JS)。
- [x] 3.3 執行 `composer cs-fix` 與 `npm run lint:fix` 統一風格。

## 4. 驗證與測試 (Verification)

- [x] 4.1 執行並通過所有 PHPUnit 測試。
- [x] 4.2 執行並通過所有 Playwright E2E 測試。
- [x] 4.3 執行 PHPStan 分析，確保錯誤數不增加。
