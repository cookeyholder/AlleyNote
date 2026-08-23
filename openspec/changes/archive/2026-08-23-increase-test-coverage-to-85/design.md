# 設計文件：提升代碼測試覆蓋率至 85%

## 架構與實作策略

### 1. 模組分層覆蓋策略
1. **Application Controllers & Middleware**：
   - 補齊 `PostController`, `AuthController`, `SettingController`, `UserController`, `ActivityLogController`, `AttachmentController` 的單元與整合測試。
2. **Domain Services & Repositories**：
   - `PostService`, `TagService`, `CommentService`, `AuthenticationService`, `AuthorizationOrchestratorService`, `SettingService`, `SecurityHeaderService`, `StatisticsQueryService` 等。
3. **Infrastructure & Shared Helpers**：
   - `ControllerResolver`, `RouteDispatcher`, `ValidatorFactory`, `CacheMonitor`, `ErrorTrackerService`, `PasswordValidationService`。

### 2. 整合測試架構
- 繼承 `IntegrationTestCase`。
- 使用 SQLite `:memory:` 資料庫，自動載入 Migration 與初始種子資料。
- 透過 `ServerRequestFactory` 模擬真實 PSR-7 請求與完整的 DI 容器解析流程。

### 3. 覆蓋率指標檢驗
- 執行 `composer test-coverage` 或 `vendor/bin/phpunit --coverage-text` 驗證總體覆蓋率達到 85% 以上。
