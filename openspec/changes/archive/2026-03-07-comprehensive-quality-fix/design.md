## Context

根據代碼審查報告，系統存在多個 P0/P1 風險。本設計旨在提供不影響現有功能的前提下，最優雅且高效的修復方案。

## Decisions

### 1. 前端自動化 CSRF 注入
**方案**：在 `apiClient.js` 的 `request` 方法中，從 Cookie (或 Meta tag) 讀取 `csrf_token`，並在非 GET 請求時自動加入 `X-CSRF-TOKEN` Header。
**理由**：降低開發者忘記手動傳入 Token 的風險，統一安全層級。

### 2. 解決 PostRepository N+1 問題
**方案**：重構 `updateTagsUsageCount` 方法。不再使用 `foreach` 執行單一 SQL，改用 `IN` 子句一次性獲取所有標籤的計數，並使用 `CASE` 語句或批次 `UPDATE`。
**理由**：將 $2N$ 次查詢降為 2 次，顯著提升寫入效能。

### 3. IP 獲取安全加固
**方案**：在 `JwtAuthenticationMiddleware` 中，僅在來源 IP 屬於「已知信任代理」清單時才解析 `HTTP_X_FORWARDED_FOR`。
**理由**：防止攻擊者透過偽造 HTTP Header 繞過 IP 綁定安全檢查。

### 4. 全案偵錯代碼清理
**方案**：使用 Regex 掃描並移除所有非正規日誌框架的輸出。
**理由**：提升日誌可讀性，減少系統內部資訊外洩風險。

## Risks / Trade-offs

- **[Risk]** 變動前端 API Client 可能導致現有請求失敗，需配合完整測試。
- **[Trade-off]** 強型別 DTO 重構會增加代碼量，但長期維護成本降低。
