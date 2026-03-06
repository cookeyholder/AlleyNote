# Proposal: 全方位程式碼審查修復 (Comprehensive Code Review Fixes)

## Why

透過全方位程式碼審查發現，AlleyNote 在富文本處理上存在 P0 級別的顯示錯誤，且在安全性、效能及程式碼維護性上有多項 P1-P3 級別的隱憂。本次變更旨在修復這些問題，提升系統的穩定性、安全性與使用者體驗，並清理技術債以符合 DDD 與 Clean Code 規範。

## What Changes

- **修復富文本顯示 Bug**: 修改 `OutputSanitizerService` 並引入 `HTMLPurifier`，確保 CKEditor 產生的內容能正確渲染。
- **強化 IP 取得安全性**: 統一 IP 取得邏輯，並預留信任代理 (Trusted Proxies) 設定，防止 IP 偽造。
- **清理控制器冗餘**: 移除 `PostController` 中重複的 `destroy` 方法，統一使用 `delete`；合併 `unpin` 與 `togglePin` 邏輯。
- **優化 JWT 驗證**: 移除 `AuthController` 中重複的手動 Token 驗證，改用中介軟體注入的屬性。
- **清理技術債**: 移除 `PostRepository` 中帶有語法錯誤的註解，統一錯誤回應格式。
- **優化快取機制**: 評估並改進 `deletePattern` 的使用，降低潛在的效能瓶頸。

## Capabilities

### New Capabilities
- `rich-text-sanitization`: 專門處理富文本的安全過濾能力，支援保留安全的 HTML 標籤。
- `secure-network-utils`: 統一且安全的網路資訊處理能力，包含可靠的 IP 辨識。

### Modified Capabilities
- `post-management`: 修改文章輸出的資料處理邏輯，確保安全性與顯示效果平衡。
- `auth-system`: 優化內部認證流程，減少冗餘驗證。

## Impact

- **後端**: `PostController`, `AuthController`, `PostRepository`, `OutputSanitizerService`, `JwtAuthenticationMiddleware`。
- **API**: 所有受影響的端點將維持原有的 JSON 結構，但內容（如 `content`）將正確包含 HTML 標籤。
- **依賴**: 深度整合已有的 `ezyang/htmlpurifier`。
