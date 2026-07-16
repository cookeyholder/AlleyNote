## 1. 建立 RateLimitServiceInterface

- [ ] 1.1 在 `backend/app/Domains/Security/Contracts/` 建立 `RateLimitServiceInterface.php`，定義 `checkLimit()` 與 `isAllowed()` 方法，簽章比照現有 `RateLimitService`

## 2. 更新 RateLimitService

- [ ] 2.1 修改 `RateLimitService`：加入 `implements \App\Domains\Security\Contracts\RateLimitServiceInterface`

## 3. 更新 Middleware

- [ ] 3.1 修改 `RateLimitMiddleware`：`use` 陳述從 `RateLimitService` 改為 `RateLimitServiceInterface`，建構子型別提示改為介面
- [ ] 3.2 修改 `PostViewRateLimitMiddleware`：同上

## 4. 更新 DI 容器

- [ ] 4.1 修改 `config/container.php`：加入或更新 `RateLimitServiceInterface::class => \DI\autowire(RateLimitService::class)`
- [ ] 4.2 修改 `Infrastructure/Config/container.php`：同上

## 5. 驗證

- [ ] 5.1 執行 `composer analyse`（PHPStan Level 10）確認無型別錯誤
- [ ] 5.2 執行 `composer cs-check` 確認符合程式碼風格
- [ ] 5.3 執行 `composer test` 確認所有單元與整合測試通過
