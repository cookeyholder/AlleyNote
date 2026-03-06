# Tasks: 全方位程式碼審查修復 (Comprehensive Code Review Fixes)

## 1. 基礎架構與工具類優化

- [x] 1.1 建立 `App\Shared\Helpers\NetworkHelper` 並實現安全且統一的 `getClientIp` 方法。
- [x] 1.2 在 `OutputSanitizerService` 中整合 `HTMLPurifier`，新增 `sanitizeRichText` 方法。
- [x] 1.3 修正 `PostRepository.php` 中帶有語法錯誤的註解，清理技術債。
- [x] 1.4 統一 `PostController` 中的錯誤回應格式。

## 2. 後端核心邏輯修復 (Post Domain)

- [x] 2.1 修改 `Post::toSafeArray`，對 `content` 使用 `sanitizeRichText` 而非 `sanitizeHtml`。
- [x] 2.2 在 `PostController` 中移除重複的 `destroy` 方法，並確保路由指向功能更完整的 `delete`。
- [x] 2.3 重構 `PostController::unpin` 與 `togglePin`，移除重複邏輯。
- [x] 2.4 更新 `PostController` 中取得 IP 的方式，改用 `NetworkHelper`。

## 3. 認證流程優化 (Auth Domain)

- [x] 3.1 移除 `AuthController::updateProfile` 中重複的手動 JWT 驗證邏輯。
- [x] 3.2 移除 `AuthController::changePassword` 中重複的手動 JWT 驗證邏輯。
- [x] 3.3 更新 `AuthController` 中取得 IP 的方式，改用 `NetworkHelper`。
- [x] 3.4 確保 `JwtAuthenticationMiddleware` 中的 IP 取得邏輯也已更新為 `NetworkHelper`。

## 4. 效能與清理 (Cache & Refactoring)

- [x] 4.1 檢視 `PostRepository::invalidateCache` 中的 `deletePattern`，優化為更具針對性的刪除。
- [x] 4.2 移除 `PostController` 中所有未使用的私有輔助方法（如原有的 `getUserIp`）。

## 5. 驗證與測試

- [x] 5.1 撰寫單元測試驗證 `HTMLPurifier` 能正確保留安全標籤並移除惡意腳本。
- [x] 5.2 撰寫測試驗證 `NetworkHelper` 在不同標頭組合下能正確識別 IP。
- [x] 5.3 執行所有既有測試，確保無迴歸問題（Regression）。
- [x] 5.4 驗證前端 CKEditor 內容在修復後能正確渲染（需手動或 E2E 驗證）。
