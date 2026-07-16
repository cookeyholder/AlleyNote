## ADDED Requirements

### Requirement: 定義 CacheInterface 核心方法

新 `CacheInterface` SHALL 定義以下 7 個核心快取操作方法，作為 `CacheServiceInterface` 與 `CacheManagerInterface` 的共用父介面。

#### Scenario: get 方法簽章
- **WHEN** 呼叫端調用 `get(string $key, mixed $default = null): mixed`
- **THEN** 系統 SHALL 回傳指定鍵的快取值，若不存在則回傳 `$default`

#### Scenario: set 方法簽章
- **WHEN** 呼叫端調用 `set(string $key, mixed $value, int $ttl = 3600): bool`
- **THEN** 系統 SHALL 將資料存入快取，回傳是否成功

#### Scenario: has 方法簽章
- **WHEN** 呼叫端調用 `has(string $key): bool`
- **THEN** 系統 SHALL 回傳指定鍵是否存在

#### Scenario: delete 方法簽章
- **WHEN** 呼叫端調用 `delete(string $key): bool`
- **THEN** 系統 SHALL 刪除指定鍵的快取，回傳是否成功

#### Scenario: clear 方法簽章
- **WHEN** 呼叫端調用 `clear(): bool`
- **THEN** 系統 SHALL 清空所有快取，回傳是否成功

#### Scenario: remember 方法簽章
- **WHEN** 呼叫端調用 `remember(string $key, callable $callback, int $ttl = 3600): mixed`
- **THEN** 系統 SHALL 回傳快取值；若不存在則執行回調、快取結果後回傳

#### Scenario: getStats 方法簽章
- **WHEN** 呼叫端調用 `getStats(): array`
- **THEN** 系統 SHALL 回傳包含命中率與快取數量的統計陣列

### Requirement: 介面位置與 namespace

`CacheInterface` SHALL 位於 `App\Shared\Cache\Contracts\CacheInterface`。

#### Scenario: 正確 namespace
- **WHEN** 載入 `CacheInterface`
- **THEN** 其完整類別名稱 SHALL 為 `App\Shared\Cache\Contracts\CacheInterface`
