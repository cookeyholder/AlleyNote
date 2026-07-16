## Context

`RateLimitMiddleware`（`App\Application\Middleware`）的建構子目前型別提示 `App\Infrastructure\Services\RateLimitService`，違反了 Security BC 的邊界原則：

```
RateLimitMiddleware
  └── depends on → RateLimitService (Infrastructure\Services)  ❌ 跨層直接依賴
```

正確的依賴方向應為：

```
RateLimitMiddleware
  └── depends on → RateLimitServiceInterface (Domains\Security\Contracts)  ✅
                        ↑
              RateLimitService (Infrastructure\Services) — implements
```

`PostViewRateLimitMiddleware` 有同樣的問題。

## Goals / Non-Goals

**Goals:**
- 定義 `RateLimitServiceInterface`，包含 `checkLimit()` 與 `isAllowed()` 方法，簽章完全比照現有 `RateLimitService`
- `RateLimitService` 實作此介面
- `RateLimitMiddleware` 與 `PostViewRateLimitMiddleware` 改為型別提示介面
- DI 容器綁定介面至實作

**Non-Goals:**
- 不改變 `checkLimit()` 或 `isAllowed()` 的簽章或行為
- 不重構 `RateLimitService` 內部實作邏輯
- 不變更現有測試（介面向後相容）
- 不新增或修改路由、資料庫、前端

## Decisions

### 1. 介面命名與位置
- **決定**: `RateLimitServiceInterface`，位於 `App\Domains\Security\Contracts\RateLimitServiceInterface`
- **理由**: 與 Security BC 的其他 12 個介面位於同一 namespace，符合既有結構
- **替代方案**: 放在 `App\Domains\Security\Contracts\RateLimiting\` 子 namespace — 但僅一個介面無需額外目錄

### 2. 方法簽章
- **決定**: 完全比照 `RateLimitService` 現有公開方法簽章
  - `checkLimit(string $ip, int $maxRequests = 60, int $timeWindow = 60): array`
  - `isAllowed(string $ip, int $maxRequests = 60, int $timeWindow = 60): bool`
- **理由**: 最小變更原則，所有現有呼叫端不需任何修改

### 3. 介面文件
- **決定**: 使用 PHPDoc 繁體中文說明，比照 Security BC 其他介面風格
- **理由**: 專案規範要求所有 PHPDoc 使用繁體中文

## Risks / Trade-offs

| 風險 | 影響 | 緩解措施 |
|------|------|----------|
| 其他直接 new `RateLimitService` 的程式碼需更新 | 僅測試用 `new RateLimitService(...)` | 測試不改建構子方式，不受影響；介面相容，`instanceof` 判斷仍可通過 |
| `PostViewRateLimitMiddleware` 使用不同介面模式（RoutingMiddlewareInterface） | 需確保 `use` 陳述與型別提示正確更新 | 編譯時即可發現，影響範圍明確 |

## Migration Plan

1. 建立 `RateLimitServiceInterface`，定義 `checkLimit()` 與 `isAllowed()`，附 PHPDoc
2. 修改 `RateLimitService` — 加入 `implements RateLimitServiceInterface`
3. 修改 `RateLimitMiddleware` — `use` 改為介面，建構子型別提示改為介面
4. 修改 `PostViewRateLimitMiddleware` — 同上
5. 更新 `config/container.php` — 加入 `RateLimitServiceInterface::class => \DI\autowire(RateLimitService::class)`
6. 更新 `Infrastructure/Config/container.php` — 同上
7. 執行 `composer check-all` 驗證全部通過

## Open Questions

- 無
