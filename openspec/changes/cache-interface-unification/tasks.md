## 1. 建立共用 CacheInterface

- [ ] 1.1 在 `backend/app/Shared/Cache/Contracts/` 建立 `CacheInterface.php`，定義 7 個核心方法（get/set/has/delete/clear/remember/getStats），方法簽章依設計決定
- [ ] 1.2 確認 `CacheInterface` 方法簽章與兩現有介面相容：`get()` 含 `$default = null`，`remember()` 使用 `int $ttl = 3600`

## 2. 遷移 CacheServiceInterface

- [ ] 2.1 修改 `CacheServiceInterface`：加入 `extends \App\Shared\Cache\Contracts\CacheInterface`
- [ ] 2.2 從 `CacheServiceInterface` 移除 7 個核心方法（get/set/has/delete/clear/remember/getStats）
- [ ] 2.3 確認保留的獨有方法（getMultiple/setMultiple/deleteMultiple/deletePattern）簽章不變
- [ ] 2.4 同步更新 `CacheService` 實作類別：
  - `get()` 加入 `$default = null` 參數，回傳值從 `null` 改為 `$default`
  - `remember()` 改為 `?int $ttl = null` → `int $ttl = 3600`，內部 `$ttl ?: self::TTL` 改為 `$ttl`

## 3. 遷移 CacheManagerInterface

- [ ] 3.1 修改 `CacheManagerInterface`：加入 `extends \App\Shared\Cache\Contracts\CacheInterface`
- [ ] 3.2 從 `CacheManagerInterface` 移除 7 個核心方法（get/set/has/delete/clear/remember/getStats）
- [ ] 3.3 確認保留的獨有方法（tags/prefix/driver/getHealthStatus/warmup/cleanup/getDriver/getDrivers）簽章不變

## 4. 驗證與測試

- [ ] 4.1 執行 `composer analyse`（PHPStan Level 10）確認無型別錯誤
- [ ] 4.2 執行 `composer cs-check` 確認符合程式碼風格
- [ ] 4.3 執行 `composer test` 確認所有單元與整合測試通過
- [ ] 4.4 手動確認所有使用 `CacheServiceInterface` 或 `CacheManagerInterface` 作為 type hint 的 29 個呼叫端無需修改（不含實作類別）
