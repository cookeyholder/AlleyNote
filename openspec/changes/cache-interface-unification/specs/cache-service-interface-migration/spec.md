## ADDED Requirements

### Requirement: CacheServiceInterface 繼承 CacheInterface

`CacheServiceInterface` SHALL `extends CacheInterface`，並移除 `CacheInterface` 中已定義的 7 個核心方法（get、set、has、delete、clear、remember、getStats）。

#### Scenario: 繼承關係正確
- **WHEN** 檢查 `CacheServiceInterface` 定義
- **THEN** 其 SHALL `extends \App\Shared\Cache\Contracts\CacheInterface`

#### Scenario: 重複方法已移除
- **WHEN** 檢查 `CacheServiceInterface` 的方法清單
- **THEN** `get`、`set`、`has`、`delete`、`clear`、`remember`、`getStats` SHALL 不存在於此介面中

#### Scenario: 獨有方法保留
- **WHEN** 檢查 `CacheServiceInterface`
- **THEN** `getMultiple`、`setMultiple`、`deleteMultiple`、`deletePattern` SHALL 仍然保留

### Requirement: 向後相容

所有 `CacheServiceInterface` 的呼叫端 SHALL 不需修改即可繼續運作。
實作類別 `CacheService` SHALL 更新其 `get()` 與 `remember()` 簽章以匹配 `CacheInterface`。

#### Scenario: CacheService 簽章相容
- **WHEN** PHP 編譯器檢查 `CacheService`
- **THEN** `CacheService` 的 `get()` SHALL 加入 `$default = null` 參數；`remember()` SHALL 改為 `int $ttl = 3600`
- **AND** 既有呼叫端因未傳入 `$default` 或 `null` TTL，行為完全不受影響

#### Scenario: 現有呼叫端不受影響
- **WHEN** 檢查所有 `CacheServiceInterface` 的 type hint 用
- **THEN** 所有使用 `CacheServiceInterface` 作為型別的參數、屬性、回傳值 SHALL 維持不變
