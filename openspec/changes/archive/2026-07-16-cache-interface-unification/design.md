## Context

`CacheServiceInterface`（`App\Shared\Contracts`）與 `CacheManagerInterface`（`App\Shared\Cache\Contracts`）各自獨立定義了 7 個功能重疊的核心方法：`get`、`set`、`has`、`delete`、`clear`、`remember`、`getStats`。這些方法在兩個介面中的簽章並非完全一致：

| 方法 | CacheServiceInterface | CacheManagerInterface |
|------|----------------------|-----------------------|
| `get` | `get(string $key): mixed` | `get(string $key, mixed $default = null): mixed` |
| `remember` | `remember(string $key, callable $callback, ?int $ttl = null): mixed` | `remember(string $key, callable $callback, int $ttl = 3600): mixed` |

其餘核心方法（`set`、`has`、`delete`、`clear`、`getStats`）簽章完全一致。

## Goals / Non-Goals

**Goals:**
- 提取共用的 `CacheInterface`，統一核心方法簽章
- `CacheServiceInterface` 與 `CacheManagerInterface` 皆繼承 `CacheInterface`，消除重複方法定義
- 維持向後相容 — 所有呼叫端不需修改；`CacheManager` 與 `PrefixedCacheManager` 實作不需修改；`CacheService` 僅需微調 `get()` 與 `remember()` 簽章以匹配統一介面
- 保留批次操作（CacheServiceInterface 獨有）與管理操作（CacheManagerInterface 獨有）

**Non-Goals:**
- 不改變任何現有類別的實作邏輯
- 不重構 `CacheService` 或 `CacheManager` 的內部實作
- 不改變 DI 容器繫結或服務名稱
- 不更改現有測試

## Decisions

### 1. 共用介面命名與位置
- **決定**: `CacheInterface`，位於 `App\Shared\Cache\Contracts\CacheInterface`
- **理由**: 與 `CacheManagerInterface` 同 namespace，符合既有結構。`App\Shared\Contracts` 為舊有位置，不適合放置新檔案
- **替代方案**: 放在 `App\Shared\Contracts` — 但該 namespace 主要為歷史遺留，無需延續

### 2. 統一 `get()` 簽章
- **決定**: `get(string $key, mixed $default = null): mixed`
- **理由**: `CacheManagerInterface` 已有 `$default` 參數，且此為 PSR-16 標準簽章。`CacheServiceInterface` 沒有 `$default`，但加入此參數為純新增（向後相容），不影響現有呼叫端
- **替代方案**: 不加入 `$default` — 會失去統一化的機會

### 3. 統一 `remember()` 簽章
- **決定**: `remember(string $key, callable $callback, int $ttl = 3600): mixed`
- **理由**: `CacheManagerInterface` 使用 `int $ttl = 3600`，`CacheServiceInterface` 使用 `?int $ttl = null`。採用 `int $ttl = 3600` 因為所有底層實作最終都需要一個明確的 TTL 值；`null` 語意模糊
- **⚠️ 實作類別 `CacheService` 需更新簽章（`?int $ttl = null` → `int $ttl = 3600`），但其所有現有呼叫端均未傳入 `null`，故無需修改

### 4. 繼承方向
- **決定**: 兩者皆繼承 `CacheInterface`，非其中一個繼承另一個
- **理由**: `CacheServiceInterface` 與 `CacheManagerInterface` 職責正交（服務 vs 管理），無合理繼承關係。共同點僅為核心快取操作，適合提取為扁平父介面
- **替代方案**:
  - A: `CacheManagerInterface extends CacheServiceInterface` — 不合理，管理操作與批次操作無關
  - C: 直接合併為單一介面 — 違反介面隔離原則（ISP），強迫服務端依賴管理方法

### 5. 兩個繼承介面保留各自獨有方法
- 無需將批次操作或管理操作提升至 `CacheInterface`
- `CacheInterface` 保持精簡（7 個方法），符合最小知識原則

## Risks / Trade-offs

| 風險 | 影響 | 緩解措施 |
|------|------|----------|
| `remember()` 的 `?int` → `int` 變更影響實作類別 | `CacheService` 需更新簽章與實作 | 編譯時即可發現錯誤；影響範圍僅 1 個類別 |
| `get()` 加入 `$default` 參數需同步實作類別 | `CacheService` 的 `get()` 簽章與實作邏輯需更新 | 加入選擇性參數為向後相容；回傳值改為 `$default` 而非硬編碼 `null` |
| 雙 namespace 導致 `use` 陳述混淆 | 開發者需注意 `CacheInterface` 來自新位置 | 新檔案位於 `Cache\Contracts` 子 namespace，命名清晰 |
| 現有 mock/stub 無需更新 | 因 PHP 介面繼承為向後相容，`createMock()` 與 `Mockery::mock()` 自動處理繼承方法 | 無需額外操作 |

## Migration Plan

1. 建立 `CacheInterface`，定義 7 個核心方法
2. 修改 `CacheServiceInterface` — `extends CacheInterface`，移除重複方法
3. **同步更新 `CacheService` 實作類別**：
   - `get(string $key): mixed` → `get(string $key, mixed $default = null): mixed`（回傳值改為 `$default`）
   - `remember(string $key, callable $callback, ?int $ttl = null): mixed` → `remember(string $key, callable $callback, int $ttl = 3600): mixed`（移除 `?:` 回退邏輯）
4. 修改 `CacheManagerInterface` — `extends CacheInterface`，移除重複方法
   - `CacheManager` 與 `PrefixedCacheManager` 簽章已相容，無需修改
5. 執行 `composer check-all` 驗證全部通過

## Open Questions

- 是否有任何程式碼直接將 `CacheServiceInterface` 與 `CacheManagerInterface` 互相比較或型別判斷？（可能性極低，但需確認）
