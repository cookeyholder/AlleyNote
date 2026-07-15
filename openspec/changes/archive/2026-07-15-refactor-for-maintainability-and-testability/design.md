## Context

AlleyNote 後端以 PHP 實作，採用 DDD（Domain-Driven Design）架構，包含 `Domains/`（業務邏輯）、`Infrastructure/`（技術實作）、`Shared/`（跨層公用）三個主要層次。目前有 376 個 PHP 類別、208 個測試檔案。服務尚未上線。

程式碼審查識別出四個具體維護痛點：

1. **`AuthService` 雙軌並存**：`AuthService`（帶 JWT feature flag `$jwtEnabled`）與已完整實作的 `AuthenticationService` 同時存在，兩者都有 `login()`，卻接收不同參數、回傳不同格式、採用不同錯誤策略。維護者無從判斷應該使用哪一個，是認知負擔也是潛在的錯誤來源。
2. **`PostService.recordView()` 職責越界**：`filter_var(FILTER_VALIDATE_IP)` 直接寫在 Service 方法中，違反「Service 協調業務流程、Validator 驗證輸入」的架構分工。IP 驗證邏輯散落在 Service 層，日後可能被重複實作。
3. **`AuthenticationService.login()` 過長難讀**：50+ 行包含 8 個步驟，閱讀者必須在實作細節與高階流程之間反覆切換才能理解整體意圖。
4. **領域驅動設計 (DDD) 的 Value Object 被大量繞過**：`Email` 和 `Password` 已經有專屬的 Value Object 被實作在 Domains 底下，但 codebase 中各個 DTO、Service、甚至 Repository 仍然在滿地手寫 `filter_var($email, FILTER_VALIDATE_EMAIL)` 和 `password_hash($pw, PASSWORD_ARGON2ID)`。防線破碎。

## Goals / Non-Goals

**Goals:**
- 完全刪除 `AuthService`（服務未上線，無需漸進廢棄）
- 將 `PostService.recordView()` 與 `PostRepository.incrementViews()` 內的原生 IP 驗證改為實例化 `IPAddress` Value Object
- 將 `AuthenticationService.login()` 的步驟拆解為語意明確的 `private` 方法
- 強制規範所有原生 `filter_var(EMAIL)` 和 `password_hash` 收斂為使用 `Email` 與 `Password` Value Object
- 確保重構後所有現有測試仍通過（無回歸）

**Non-Goals:**
- 更改任何公開 API 端點的行為或 DTO 結構
- 引入新的外部套件或框架
- 重構 `PostValidator` 的 closure 規則（效益邊際，不在本次範圍）
- 重構 `JwtTokenService` 的內部解析邏輯（效益邊際，不在本次範圍）
- 重構 `Application.php` 建構子（現有結構已清晰）
- 處理前端 (`frontend/`) 程式碼

## Decisions

### D1：直接刪除 `AuthService`（而非漸進廢棄）

**決策**：盤點所有使用點後，直接刪除 `AuthService.php`，更新 DI 容器綁定改指向 `AuthenticationService`。

**備選方案**：標記 `@deprecated` + `trigger_error`，數個 sprint 後再刪 → 服務未上線，漸進廢棄只是拖延，沒有保護既有生產環境的需求。

**理由**：現在刪最乾淨。`AuthService` 的三個方法（`register`、`login`、`logout`）在 `AuthenticationService` 都有更完整的對應實作，不需要 adapter 包裝。

**執行前提**：先執行 `grep -rn 'AuthService' backend/` 確認所有使用點（包含測試與 DI 容器）都已清理。

### D2：改用已有的 `IPAddress` Value Object 取代散落的 IP 驗證

**背景**：搜尋 codebase 後發現 `filter_var(FILTER_VALIDATE_IP)` 至少出現 25+ 次，且 `app/Domains/Shared/ValueObjects/IPAddress.php` 已提供完整的 Value Object（含 IPv4/IPv6 識別、私有 IP 判斷、`mask()`），`app/Shared/Helpers/functions.php` 也有 `is_valid_ip()` 全域函數——已有兩套工具，卻沒有人使用。

**決策**：`PostService::recordView()` 與 `PostRepository::incrementViews()` 改用 `IPAddress` Value Object 進行驗證：
```
try {
    new IPAddress($userIp);  // 無效 IP 拋出 InvalidArgumentException
} catch (InvalidArgumentException) {
    throw new ValidationException(['user_ip' => '無效的 IP 位址格式']);
}
```
兩處都要同步修改，否則 Service 驗證過、Repository 仍舊驗證，邏輯依然重複。

**備選方案 A**：在 `PostValidator` 新增 `isValidIp()` → 製造第 N+1 個 IP 驗證來源，加深現有問題。  
**備選方案 B**：保留現狀 → `PostRepository`（資料層）做格式驗證依然是架構違規。

**理由**：`IPAddress` 是 `Domains/Shared/` 的共用 Value Object，用在 Post Domain 完全合理。`PostRepository::incrementViews()` 的 IP 驗證應該在 Service 層已完成、Repository 只負責存取——改用 Value Object 後，Repository 可以移除驗證，由 Service 接收 `IPAddress` 物件（或 string，因為 `IPAddress` 實作 `Stringable`）。

### D3：`AuthenticationService.login()` 步驟分離為私有方法

**決策**：從 `login()` 提取三個具名私有方法：
- `validateUserStatus(array $user): void`：確認帳號未被停用
- `enforceTokenLimit(int $userId): void`：清理過期 token、在超限時撤銷最舊的
- `resolveUserRole(int $userId): ?string`：取得第一個角色名稱

讓 `login()` 主體降至 ≤ 25 行的高階流程協調。

**理由**：方法命名即是文件。閱讀者看到 `$this->enforceTokenLimit($userId)` 就能理解這個步驟的意圖，不需要閱讀 10 行實作才能知道「原來這在管 token 數量上限」。

### D4：全面收斂 Email 與 Password 的 Value Object 使用

**決策**：在 `RegisterUserDTO`、`ValidatorFactory` 以及 `UserManagementService` 等地方，將原生的 `filter_var(..., FILTER_VALIDATE_EMAIL)` 與 `password_hash` 原生呼叫廢棄，全部改為實例化 `Email` 與 `Password` Value Object。

**備選方案**：保持現狀，僅重構 Post 的 IP 驗證。 → 治標不治本，團隊依然會「這裏建了 Value Object 卻放在一邊不用，那裡自己手寫 `filter_var`」。

**理由**：「繞過 Value Object」是這套 DDD 架構中造成驗證邏輯混亂的根源。趁此次重構一次性修復 Email 和 Password 這些最關鍵的欄位，建立強制使用 Value Object 的規範（Single Source of Truth），防範未來的維護災難。

## Risks / Trade-offs

- **[風險] `AuthService` 有未知使用點** → 緩和：刪除前先執行 `rg` 掃描，確認 app/、tests/、config/ 下所有參照都已清除
- **[風險] `AuthService` 的測試目前直接初始化該類別** → 緩和：掃描後更新或刪除對應測試；`AuthenticationService` 有更完整的測試覆蓋
- **[風險] `PostRepository::incrementViews()` 移除 IP 驗證後，若有呼叫方繞過 Service 直接呼叫 Repository，IP 就不再被驗證** → 緩和：`rg 'incrementViews' backend/` 確認 PostRepository 是否只被 PostService 呼叫
- **[取捨] 全 codebase 的 IP 驗證重複問題（25+ 處）超出本次範圍** → 接受：本次只處理 Post Domain 的兩處；其他位置（Security Domain、Middleware 等）留待後續，不強求一次全清
- **[風險] 重構與其他進行中 PR 的合併衝突** → 緩和：在獨立分支進行，完成後立即發 PR，縮短分支存在時間

## Migration Plan

1. 建立新分支 `refactor/auth-service-removal-and-post-cleanup`
2. 依 tasks.md 順序逐步提交，每個子任務一個 commit（使用 `refactor:` prefix）
3. 執行 `composer test` 確認所有測試通過
4. 執行 `composer phpstan` 確認無型別錯誤
5. 執行 `composer cs-fix` 確認程式碼風格
6. 開 PR

**Rollback**：無 API 破壞性變更，若有問題可直接 revert 分支，不影響其他功能。

## Open Questions

- `PostRepository::incrementViews()` 是否只被 `PostService` 呼叫？若有其他呼叫點，移除 Repository 層的 IP 驗證前需確認其輸入來源是否已在上游驗證過。
