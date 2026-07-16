## ADDED Requirements

### Requirement: CacheManagerInterface 繼承 CacheInterface

`CacheManagerInterface` SHALL `extends CacheInterface`，並移除 `CacheInterface` 中已定義的 7 個核心方法（get、set、has、delete、clear、remember、getStats）。

#### Scenario: 繼承關係正確
- **WHEN** 檢查 `CacheManagerInterface` 定義
- **THEN** 其 SHALL `extends \App\Shared\Cache\Contracts\CacheInterface`

#### Scenario: 重複方法已移除
- **WHEN** 檢查 `CacheManagerInterface` 的方法清單
- **THEN** `get`、`set`、`has`、`delete`、`clear`、`remember`、`getStats` SHALL 不存在於此介面中

#### Scenario: 獨有方法保留
- **WHEN** 檢查 `CacheManagerInterface`
- **THEN** `tags`、`prefix`、`driver`、`getHealthStatus`、`warmup`、`cleanup`、`getDriver`、`getDrivers` SHALL 仍然保留

### Requirement: 向後相容

所有現有實作 `CacheManagerInterface` 的類別 SHALL 不需修改即可繼續運作。

#### Scenario: 現有實作無需變更
- **WHEN** PHP 編譯器檢查實作 `CacheManagerInterface` 的類別
- **THEN** 所有現有實作 SHALL 通過型別檢查，不需新增任何方法

#### Scenario: 現有呼叫端不受影響
- **WHEN** 檢查所有 `CacheManagerInterface` 的 type hint 用法
- **THEN** 所有使用 `CacheManagerInterface` 作為型別的參數、屬性、回傳值 SHALL 維持不變
