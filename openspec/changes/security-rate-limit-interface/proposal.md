## Why

`RateLimitMiddleware`（`Application\Middleware`）目前直接依賴 `Infrastructure\Services\RateLimitService`，繞過了 Security Bounded Context 的邊界。Middleware 屬於應用層，不應直接參考基礎設施層的實作類別。這使得 Security 領域的速率限制功能無法透過介面進行抽象，也無法在測試中替換實作。

## What Changes

- 新增 `RateLimitServiceInterface` 於 `Domains\Security\Contracts`，定義 `checkLimit()` 與 `isAllowed()` 方法
- `RateLimitService` 改為實作 `RateLimitServiceInterface`
- `RateLimitMiddleware` 改為型別提示介面而非具體類別
- `PostViewRateLimitMiddleware` 同步更新為型別提示介面
- DI 容器（`config/container.php`、`Infrastructure/Config/container.php`）綁定介面至實作

## Capabilities

### New Capabilities
- `rate-limit-service-interface`: 新 `RateLimitServiceInterface`，定義速率限制的公開契約

### Modified Capabilities
<!-- 無既有規格受影響 -->

## Impact

- 受影響檔案：
  - 新增：`Domains/Security/Contracts/RateLimitServiceInterface.php`
  - 修改：`RateLimitService.php`、`RateLimitMiddleware.php`、`PostViewRateLimitMiddleware.php`、`config/container.php`、`Infrastructure/Config/container.php`
  - 測試不受影響（介面相容）
- 無資料庫變更、無路由變更、無前端變更
- 非破壞性向後相容
