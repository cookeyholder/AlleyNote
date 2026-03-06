# Design: 全方位程式碼審查修復 (Comprehensive Code Review Fixes)

## Context

AlleyNote 的現有架構中，`OutputSanitizerService` 被廣泛用於清理輸出的 HTML 內容。然而，其現有實作僅調用 `htmlspecialchars`，這對於富文本（如公告內容）是過度的，因為它會轉義所有的 HTML 標籤。此外，系統在 IP 取得與 JWT 驗證上存在冗餘與不安全的實作。

## Goals / Non-Goals

**Goals:**
- **正確渲染富文本**: 允許 `content` 欄位包含安全的 HTML 標籤（如 `<b>`, `<i>`, `<p>` 等），同時防禦 XSS。
- **統一 IP 辨識**: 建立一個集中且可信任的 IP 取得機制，支援負載平衡器與反向代理。
- **清理重複代碼**: 移除 `PostController` 與 `AuthController` 中的邏輯冗餘。
- **提升品質**: 修正 `PostRepository` 中的語法遺跡與快取清理策略。

**Non-Goals:**
- **重構整個前端**: 不涉及前端 UI 的重新設計，僅修正資料流。
- **資料庫遷移**: 不改變現有資料表結構。

## Decisions

### 1. 引入 `HTMLPurifier` 作為富文本清理器
- **方案**: 在 `OutputSanitizerService` 中整合 `ezyang/htmlpurifier`。
- **理由**: `htmlspecialchars` 會轉義所有內容，而 `HTMLPurifier` 能精確保留白名單內的標籤，適合 CKEditor 產生的富文本。
- **替代方案**: 僅依賴前端 `DOMPurify`。**被否決**，因為後端仍應保證資料輸出的安全性（Defense in depth）。

### 2. 統一 IP 辨識邏輯
- **方案**: 建立 `App\Shared\Helpers\NetworkHelper::getClientIp(Request $request, array $trustedProxies = [])`。
- **理由**: 目前多處重複實作且不安全。集中處理有利於未來擴充信任代理清單。

### 3. 合併控制器方法
- **方案**: 
    - 移除 `PostController::destroy`，修改 `routes.php` 指向 `PostController::delete`。
    - 在 `togglePin` 中處理置頂與取消置頂，簡化 `unpin` 邏輯。
- **理由**: DRY (Don't Repeat Yourself) 原則，減少維護多套類似邏輯的風險。

### 4. 改進快取清理模式
- **方案**: 在 `PostRepository::invalidateCache` 中，將 `deletePattern` 改為精確的鍵值刪除或評估更輕量的方案。
- **理由**: 避免在高負載下 Redis 的效能瓶頸。

## Risks / Trade-offs

- **[Risk] HTMLPurifier 效能消耗** → **Mitigation**: 僅在 `show` 端點或 `toSafeArray` 中對 `content` 使用，並結合現有的 `Cache` 機制。
- **[Risk] IP 辨識誤判** → **Mitigation**: 預設僅使用 `REMOTE_ADDR`，除非明確設定了信任代理。
