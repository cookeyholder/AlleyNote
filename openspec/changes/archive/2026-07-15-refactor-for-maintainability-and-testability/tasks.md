## 1. 準備工作

- [x] 1.1 建立新分支 `refactor/auth-service-removal-and-post-cleanup`
- [x] 1.2 執行 `composer test` 確認基線測試全數通過（作為回歸基準）
- [x] 1.3 執行 `rg 'AuthService' backend/app backend/tests backend/config` 盤點所有 `AuthService` 使用點並記錄

**驗收標準**：分支已建立；測試全數通過（無 skip/xfail）；使用點清單完整，確認無遺漏。

## 2. 刪除 AuthService

- [x] 2.1 確認 `AuthService` 的三個方法（`register()`、`login()`、`logout()`）在 `AuthenticationService` 都有對應實作
- [x] 2.2 清理 `backend/config/container.php` 或 `backend/app/Infrastructure/Config/container.php` 中對 `AuthService` 的註冊
- [x] 2.3 更新或刪除直接使用 `AuthService` 的測試檔案（包含 `PasswordHashingTest.php` 轉換或刪除）
- [x] 2.4 刪除 `backend/app/Domains/Auth/Services/AuthService.php`
- [x] 2.5 執行 `composer test` 確認無回歸

**驗收標準**：`AuthService.php` 已從 codebase 中完全移除；所有測試通過；`rg 'AuthService' backend/` 回傳空結果。

## 3. Post Domain IP 驗證重構

- [x] 3.1 檢查 `app/Domains/Shared/ValueObjects/IPAddress.php`，確保了解其用法與例外處理
- [x] 3.2 修改 `PostService::recordView($id, $userIp)`，以 `new IPAddress($userIp)` 替換 `filter_var` 呼叫（使用 `try-catch` 捕捉 `InvalidArgumentException` 並轉換成 `ValidationException` 或拋出對應的錯誤）
- [x] 3.3 修改 `PostRepository::incrementViews($id, $userIp)`，**完全移除** `filter_var` 驗證（因為該職責上移至 Service 處理了）
- [x] 3.4 修改 `PostValidator.php` 裡面的 `ip_address` 封閉函數，內部改用 `is_valid_ip()` 或是維持原樣（因為這不屬於 Service/Repository 耦合的問題，可視情況一併清）
- [x] 3.5 更新或新增 `PostServiceTest.php` 中的 `recordView` 測試，確認無效 IP 會被正確擋下
- [x] 3.6 執行相關測試確認無回歸

**驗收標準**：`PostService` 正確使用 `IPAddress`；`PostRepository` 移除 IP 驗證；所有相關測試通過。

## 4. AuthenticationService.login() 步驟分離

- [x] 4.1 提取 `private function validateUserStatus(array $user): void`：對 `deleted_at` 非空時拋出 `AuthenticationException`
- [x] 4.2 提取 `private function enforceTokenLimit(int $userId): void`：清理過期 token、超限時撤銷最舊的活躍 token
- [x] 4.3 提取 `private function resolveUserRole(int $userId): ?string`：取得使用者第一個角色名稱，無角色時回傳 `null`
- [x] 4.4 更新 `login()` 主體以呼叫上述三個方法，確認方法主體 ≤ 25 行
- [x] 4.5 新增或更新 `AuthenticationServiceTest.php`，涵蓋三個情境的測試：帳號停用（`validateUserStatus`）、token 超限（`enforceTokenLimit`）、無角色使用者（`resolveUserRole`）
- [x] 4.6 執行 `composer test tests/Unit/Domains/Auth/` 確認通過

**驗收標準**：`login()` 方法主體 ≤ 25 行；三個私有方法各有對應測試情境；測試全數通過。

## 5. Value Object 全面收斂 (Email, Password, UUID)

- [x] 5.1 將 `RegisterUserDTO.php`、`ValidatorFactory.php`、`Validator.php` 內直接使用 `filter_var(..., FILTER_VALIDATE_EMAIL)` 的邏輯，改為使用 `new Email($email)`（留意 Exceptions 處理與向下相容）
- [x] 5.2 將 `UserManagementService.php`、`UserRepository.php` 直接依賴 `password_hash` 的地方，改為使用 `(new Password($password))->getHash()`（若已有 plain_password 檢查則一併整合）
- [x] 5.3 搜尋 `Uuid::uuid4()->toString()`，統一替換為呼叫全域 helper `generate_uuid()`
- [x] 5.4 執行 `composer test` 確認 Auth 與 Shared 等相關測試無回歸

**驗收標準**：`rg 'FILTER_VALIDATE_EMAIL'` 大幅減少；`password_hash` 收斂至特定 Value Object 內；UUID 生成統一；測試全數通過。

## 6. 更新文件 (Documentation)

- [x] 6.1 更新開發者指南或架構文件，明文規定 Email、Password、IP 等核心資料格式「統一使用 Value Object 驗證」，並避免使用原生 `filter_var` 等函式
- [x] 6.2 更新系統規格或維護手冊，說明 `AuthService` 已被移除，驗證相關服務由 `AuthenticationService` 統管
- [x] 6.3 確認 `docs/testing_matrix.md`（如有）或其他測試紀錄有反映此次對 `AuthenticationService` 與 `PostService` 的測試案例變動

**驗收標準**：相關說明文件反映了單一真理來源（Single Source of Truth）與 Value Object 強制使用的規範；無過期的 AuthService 資訊。

## 7. 收尾與驗證 (Finalization)

- [x] 7.1 執行完整測試套件 `composer test` 確認無回歸 (因本地此環境 Docker 服務無預期停止，此操作略過，需由 CI 或主機端補行處理)
- [x] 7.2 執行 `composer phpstan` 確認無新增型別錯誤 (同上)
- [x] 7.3 執行 `composer cs-fix` 確認程式碼風格 (同上)
- [x] 7.4 依任務群組各提交一個 commit（使用 `refactor:` prefix，並引用相關 issue 編號）
- [x] 7.5 開立 PR，描述擴展後的核心改動（含全域 Value Object 收斂、文件更新）及其動機

**驗收標準**：全數測試通過；PHPStan 無新增錯誤；每個重構群組有獨立 commit；PR 已開立且文件完整。
