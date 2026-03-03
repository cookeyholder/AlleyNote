# Comprehensive Code Review Report (v1.0)

- **Date**: 2026-03-03
- **Auditor**: Senior Architect & Security Engineer (AI)
- **Project**: AlleyNote (Modern Bulletin Board System)

## Executive Summary

此專案具備良好的 DDD 架構雛形與豐富的測試覆蓋率（2,200+ tests），但在經歷大規模合併後，在**型別嚴謹性**、**資安防禦細節**以及**代碼純淨度**上仍有顯著的改進空間。特別是 CSRF 處理、日誌管理與 N+1 查詢問題需優先處理。

---

## 1. Security (資安)

### [P0_CRITICAL] CSRF 防護實作不完整
- **問題**：前端 `apiClient.js` 未自動在寫入操作（POST, PUT, DELETE）中帶入 `X-CSRF-TOKEN`。雖然伺服器端可能啟用了檢查，但前端未對齊會導致寫入失敗或防護降級。
- **影響**：跨站請求偽造風險。

### [P1_HIGH] JWT Token 儲存風險
- **問題**：`apiClient.js` 將 `access_token` 與 `refresh_token` 儲存於 `localStorage` (透過 `storage.js`)。
- **影響**：若發生 XSS 攻擊，攻擊者可輕易竊取 Token 導致帳號完全遭竊。建議改用 `HttpOnly` Cookie 配合 CSRF 保護。

### [P1_HIGH] IP 獲取邏輯仍可被偽造 (邊界情況)
- **問題**：雖然已優先使用 `REMOTE_ADDR`，但在 `JwtAuthenticationMiddleware` 中仍缺乏對 Proxy 信任鏈的顯式驗證。
- **影響**：在多層 Proxy 環境下可能誤判來源 IP。

---

## 2. Performance (效能)

### [P1_HIGH] PostRepository 中的 N+1 標籤更新問題
- **問題**：`updateTagsUsageCount` 方法在 `foreach` 迴圈內重複執行 `SELECT COUNT` 與 `UPDATE`。
- **影響**：大量標籤操作時會造成資料庫連線壓力與效能瓶頸。
- **建議**：改用批次更新 SQL (e.g., `UPDATE tags SET usage_count = (SELECT COUNT(*) ...) WHERE id IN (...)`)。

### [P2_MEDIUM] 統計快取 TTL 策略過於單一
- **問題**：目前所有統計資料均統一為 3600 秒 (1小時) 快取。
- **影響**：即時性要求高的數據（如流量趨勢）可能過於陳舊，而穩定性高的數據（如來源分布）則不需要頻繁刷新。

---

## 3. Code Style & Clean Code (規範與純淨度)

### [P2_MEDIUM] 遺留的偵錯輸出 (Debug Leakage)
- **問題**：全案仍有 `error_log` (PHP) 與 `console.log` (JS) 殘留。
- **影響**：污染伺服器日誌，並可能在前端外洩內部路徑或變數結構。

### [P2_MEDIUM] 型別註解不一致
- **問題**：雖然通過了 PHPStan Level 10，但許多 DTO 的建構子參數仍使用 `mixed` 搭配手動轉型。
- **建議**：應推動更嚴格的參數型別宣告，減少 `mixed` 使用。

---

## 4. Architecture (架構)

### [P2_MEDIUM] 控制器邏輯仍顯沉重
- **問題**：`PostViewController.php` 與 `AuthController.php` 內部仍包含部分參數驗證與資料轉換邏輯。
- **建議**：應徹底將驗證邏輯封裝至 Domain Service 或專用的 Validator 類別中。

---

## 5. Next Steps (後續行動)

1. **建立 OpenSpec 提案**：啟動 `comprehensive-quality-fix` 計畫。
2. **優先執行 P0/P1 修復**。
3. **優化資料庫查詢與快取策略**。
4. **全面清理偵錯代碼**。
