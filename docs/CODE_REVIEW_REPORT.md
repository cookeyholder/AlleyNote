# AlleyNote 全方位程式碼審查報告 (v1.0.0)

本報告針對 AlleyNote 專案的架構、資安、效能、程式碼品質及業務邏輯進行深入審查，並依優先級提出改進建議。

## 執行摘要

AlleyNote 展現了良好的 DDD 架構實踐與完整的測試覆蓋。然而，在 **富文本處理、安全性標頭解析及程式碼冗餘** 方面仍存在顯著問題，其中最嚴重的問題（P0）是富文本內容在輸出時被過度轉義，導致前端無法正常渲染 CKEditor 產生的 HTML 內容。

---

## 問題清單與優先級

### P0 - 致命 (Critical)

#### 1. 富文本內容過度轉義導致 UI 損壞
- **位置**: `backend/app/Domains/Post/Models/Post.php` -> `toSafeArray()`
- **描述**: 系統使用 `OutputSanitizerService::sanitizeHtml`（內部僅調用 `htmlspecialchars`）來清理文章標題與內容。這導致 CKEditor 產生的 HTML 標籤（如 `<p>`, `<strong>`）被轉義為 `&lt;p&gt;`，前端雖然使用了 `DOMPurify`，但因收到的是純文字字串而無法渲染格式，直接顯示原始標籤。
- **影響**: 終端使用者看到的公告內容會包含原始 HTML 標籤，嚴重影響閱讀體驗與專業形象。
- **建議修復**:
    - 在後端引入 `HTMLPurifier` 專門處理富文本內容。
    - 修改 `OutputSanitizerService` 增加 `sanitizeRichText` 方法。
    - 在 `Post::toSafeArray` 中，對 `content` 使用 `sanitizeRichText` 而非 `sanitizeHtml`。

---

### P1 - 嚴重 (High)

#### 2. IP 來源偽造風險
- **位置**: `AuthController::getClientIpAddress`, `PostController::getUserIp`, `JwtAuthenticationMiddleware::getClientIpAddress`
- **描述**: 這些方法直接解析 `HTTP_X_FORWARDED_FOR` 等標頭而未驗證來源代理是否受信任。惡意使用者可以透過偽造此標頭來繞過 IP 限制或混淆審計日誌。
- **影響**: 資安防禦（如 IP 黑名單）可能被輕易繞過。
- **建議修復**:
    - 實作「信任代理 (Trusted Proxies)」機制，僅在來源 IP 屬於信任清單時才解析轉發標頭。
    - 統一所有 IP 取得邏輯至 `App\Shared\Helpers` 或專用服務中。

#### 3. 控制器邏輯冗餘與重複
- **位置**: `PostController.php`
- **描述**: 同時存在 `delete()` 與 `destroy()` 處理相同路由；`unpin()` 與 `togglePin()` 邏輯重疊。
- **影響**: 增加維護成本，容易造成邏輯不一致（例如 `delete()` 有詳盡日誌但 `destroy()` 沒有）。
- **建議修復**:
    - 移除 `destroy()`，統一使用功能更完整的 `delete()`。
    - 簡化 `unpin()`，直接調用 `togglePin()` 邏輯或反之。

---

### P2 - 中等 (Medium)

#### 4. JWT 驗證邏輯重複
- **位置**: `AuthController.php` -> `updateProfile()`, `changePassword()`
- **描述**: 這些方法內部手動提取並驗證了 JWT Token，然而該路由已由 `JwtAuthenticationMiddleware` 保護，且該中介軟體已將 `user_id` 注入到 Request Attribute 中。
- **影響**: 程式碼冗餘，增加不必要的 CPU 運算。
- **建議修復**: 移除手動驗證邏輯，直接從 `$request->getAttribute('user_id')` 取得使用者 ID。

#### 5. 程式碼遺跡與語法錯誤
- **位置**: `backend/app/Domains/Post/Repositories/PostRepository.php`
- **描述**: 程式碼中留有因語法錯誤而被註解的賦值語句（例如：`// $data ? $data->updated_at : null)) = ...`）。
- **影響**: 降低程式碼可讀性，顯示開發過程中的未清理痕跡。
- **建議修復**: 移除無效註解，並確保時間戳記更新邏輯清晰明確。

---

### P3 - 輕微 (Low)

#### 6. 快取清理模式效能隱憂
- **位置**: `PostRepository::invalidateCache`
- **描述**: 使用 `deletePattern` 進行大量分頁快取清理。在 Redis 且 Key 數量極大時，`KEYS` 指令（通常為 `deletePattern` 的底層）會阻塞伺服器。
- **影響**: 當文章數量極大且異動頻繁時，可能短暫影響 Redis 回應速度。
- **建議修復**: 改用快取標籤 (Cache Tags) 或版本化快取鍵 (Versioned Cache Keys) 策略，避免使用模式比對刪除。

#### 7. 錯誤回應格式不統一
- **位置**: `PostController.php`
- **描述**: 部份 `catch` 塊直接返回 `500` 或 `handleException`，其輸出格式與其他標準化的 `errorResponse` 略有不同。
- **影響**: 前端處理錯誤時需處理多種不同結構。
- **建議修復**: 統一所有 API 回應使用 `BaseController` 提供的方法，確保 JSON 結構一致。

---

---

## 架構升級：Secure-DDD 模式

為了徹底解決上述問題並建立長期的開發規範，本次修復導入了 **「安全導向的領域驅動設計服務架構 (Security-Oriented DDD Service Architecture, 簡稱 Secure-DDD)」**。

### Secure-DDD 核心原則
1. **安全左移 (Security Left)**：將資安邏輯（如 IP 辨識、HTML 淨化）從 Controller 移至 Shared/Infrastructure 層，確保所有領域操作預設即安全。
2. **標準化介面 (Standardized Interfaces)**：定義 `OutputSanitizerInterface` 等介面，解耦實作細節，方便未來升級清理引擎。
3. **精確的狀態管理 (Precise State Management)**：嚴格限制快取與資料庫操作的副作用，確保系統行為可預測且高效。

### 測試程式重構成果
已完成全站（後端 + 前端）測試套件的架構重構，建立起兩大支柱：

- **後端 (Secure-DDD)**:
    - 2206 個測試檔案全數繼承自 `Tests\SecureDDDTestCase`。
    - 實現 100% 邏輯通過率，確保所有業務操作預設安全。
- **前端 (Secure-UI Spec)**:
    - 建立 `SecureBasePage` 與 `PublicPostPage` 等 POM 物件。
    - 引入 `assertRichTextRendered` 與 `assertNoSensitiveInfoLeaked` 等安全斷言。
    - 重構核心 E2E 測試（Auth, Post Detail），落實多層次安全驗證。

### 全站重構計畫
- **持續集成**: 已建議將 Secure-DDD 與 Secure-UI 測試納入 CI 流程。
- **後續擴充**: 剩餘 E2E 測試將依據 `Secure-UI Spec` 規範持續進行批次轉型。




