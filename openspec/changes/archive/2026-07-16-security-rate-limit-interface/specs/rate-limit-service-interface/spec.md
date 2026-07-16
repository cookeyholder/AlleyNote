## ADDED Requirements

### Requirement: 定義 RateLimitServiceInterface

新 `RateLimitServiceInterface` SHALL 定義速率限制服務的公開契約，位於 `App\Domains\Security\Contracts\RateLimitServiceInterface`。

#### Scenario: checkLimit 方法
- **WHEN** 呼叫端調用 `checkLimit(string $ip, int $maxRequests = 60, int $timeWindow = 60): array`
- **THEN** 系統 SHALL 回傳包含 `allowed`（bool）、`remaining`（int）、`reset`（int）的陣列

#### Scenario: isAllowed 方法
- **WHEN** 呼叫端調用 `isAllowed(string $ip, int $maxRequests = 60, int $timeWindow = 60): bool`
- **THEN** 系統 SHALL 回傳 true 表示請求允許，false 表示已達速率限制

### Requirement: 介面位置與 namespace

`RateLimitServiceInterface` SHALL 位於 `App\Domains\Security\Contracts\RateLimitServiceInterface`。

#### Scenario: 正確 namespace
- **WHEN** 載入 `RateLimitServiceInterface`
- **THEN** 其完整類別名稱 SHALL 為 `App\Domains\Security\Contracts\RateLimitServiceInterface`

### Requirement: RateLimitService 實作介面

`RateLimitService` SHALL 實作 `RateLimitServiceInterface`。

#### Scenario: 實作檢查
- **WHEN** PHPStan 分析 `RateLimitService`
- **THEN** `RateLimitService` SHALL 通過 `implements RateLimitServiceInterface` 的型別檢查

### Requirement: Middleware 使用介面

`RateLimitMiddleware` 與 `PostViewRateLimitMiddleware` SHALL 型別提示 `RateLimitServiceInterface` 而非 `RateLimitService`。

#### Scenario: Middleware 建構子型別提示
- **WHEN** 檢查 `RateLimitMiddleware` 的建構子參數
- **THEN** 其型別提示 SHALL 為 `RateLimitServiceInterface`

#### Scenario: PostViewRateLimitMiddleware 建構子型別提示
- **WHEN** 檢查 `PostViewRateLimitMiddleware` 的建構子參數
- **THEN** 其型別提示 SHALL 為 `RateLimitServiceInterface`

### Requirement: DI 容器繫結

DI 容器 SHALL 將 `RateLimitServiceInterface` 繫結至 `RateLimitService`。

#### Scenario: 容器解析
- **WHEN** 容器解析 `RateLimitServiceInterface`
- **THEN** 容器 SHALL 回傳 `RateLimitService` 實例
