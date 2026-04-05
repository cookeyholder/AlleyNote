## Why

後端 `app/` 套件有三個具體的維護痛點，影響程式碼可讀性與職責清晰度：

1. **`AuthService` 與 `AuthenticationService` 雙軌並存**：兩個都有 `login()`，接收不同參數、回傳不同格式、使用不同的錯誤策略，維護者無從判斷該用哪一個。服務尚未上線，是清理的最佳時機。
2. **Post Domain 夾帶重複輸入驗證**：`PostService.recordView()` 與 `PostRepository.incrementViews()` 重複寫了 `filter_var(FILTER_VALIDATE_IP)`。這不僅違反了分層架構（Service/Repository 不該做格式驗證），而且忽略了系統內已有的 `IPAddress` Value Object，造成防護網設計形同虛設。
3. **`AuthenticationService.login()` 過長難讀**：50+ 行包含 8 個步驟（驗證憑證、清理 token、查角色、產 token 對、更新登入時間...），閱讀時需要在細節與流程之間反覆切換。
4. **領域驅動設計 (DDD) 的 Value Object 被大量繞過**：系統內已有實作完善的 `Email` 與 `Password` 等 Value Object，但各個 DTO、Service、Repository 卻直接呼叫原生函式（如 `filter_var(FILTER_VALIDATE_EMAIL)` 或 `password_hash`）。防護網形同虛設，一旦需要整體修改驗證或加密規則，必定會有漏網之魚。

## What Changes

- **刪除 `AuthService`**：服務尚未上線，直接移除，統一由 `AuthenticationService` 負責，無需漸進廢棄
- **Post Domain IP 驗證擁抱 Value Object**：在 `PostService` 和 `PostRepository` 中移除 `filter_var` 檢查，改為在 Service 實例化 `IPAddress` 物件（利用系統已有的 Value Object 防護網）。
- **`AuthenticationService.login()` 步驟分離**：提取三個語意明確的私有方法（驗證帳號狀態、執行 token 數量限制、解析使用者角色），讓 `login()` 成為一目了然的流程協調器
- **全面收斂 Value Object**：強調 `Email` 與 `Password` 驗證的 Single Source of Truth。將 codebase 中散落的 `filter_var(FILTER_VALIDATE_EMAIL)` 和 `password_hash` 替換為 `new Email()` 與 `new Password()`；統一 UUID 的生成方式。

## Capabilities

### New Capabilities

- `post-view-recording`: 重構 `recordView` 流程，將 IP 驗證職責歸還 Validator 層，讓 Service 層只做業務協調
- `auth-service-consolidation`: 刪除 `AuthService`，統一使用 `AuthenticationService`，消除雙軌並存的認知負擔

### Modified Capabilities

（無：本次重構不改動任何對外行為或規格需求）

## Impact

- **刪除**：`backend/app/Domains/Auth/Services/AuthService.php`
- **修改**：涵蓋 `Auth` 相關 Service 及 Repository、`Post` 相關 Service 及 Repository、`Shared` Validation 等（包含所有繞過 Email 與 Password Value Object 的邏輯）
- **測試**：相依的單元測試需同步更新以匹配 Exceptional Throwing 或是 Mock 行為；整合測試不應受影響
- **無 API 破壞性變更**：不改動對外 API 端點、DTO 結構或任何公開介面
- **依賴**：不引入新套件
