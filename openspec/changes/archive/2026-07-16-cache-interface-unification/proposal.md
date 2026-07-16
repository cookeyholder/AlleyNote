## Why

AlleyNote 目前有兩個快取介面 — `CacheServiceInterface`（10 個方法、22 個呼叫端）與 `CacheManagerInterface`（16 個方法、7 個呼叫端）— 各自獨立定義了 get/set/has/delete/clear/remember/getStats 等核心方法，但簽章細節略有差異。這種重複造成維護負擔：核心行為修改需同步兩處，新功能（如批次操作）若只加在其中一個介面會導致功能缺口。提取共用的父介面可消除重複、統一契約，並為後續快取層演進建立一致性基礎。

## What Changes

- 新增 `CacheInterface` 作為共用父介面，包含兩者皆有的核心方法，並統一方法簽章
- `CacheServiceInterface` 改為繼承 `CacheInterface`，保留 `getMultiple`、`setMultiple`、`deleteMultiple`、`deletePattern` 等批次操作方法
- `CacheManagerInterface` 改為繼承 `CacheInterface`，保留 `tags`、`prefix`、`driver`、`getHealthStatus`、`warmup`、`cleanup`、`getDriver`、`getDrivers` 等管理操作方法
- `CacheService` 實作類別需配合更新 `get()` 與 `remember()` 方法簽章（其餘所有呼叫端無需修改，非破壞性變更）

## Capabilities

### New Capabilities
- `cache-interface-core`: 新 `CacheInterface`，定義 get/set/has/delete/clear/remember/getStats 共 7 個核心方法，統一 `get()` 與 `remember()` 簽章
- `cache-service-interface-migration`: 將 `CacheServiceInterface` 改為繼承 `CacheInterface`，移除重複的核心方法定義
- `cache-manager-interface-migration`: 將 `CacheManagerInterface` 改為繼承 `CacheInterface`，移除重複的核心方法定義

### Modified Capabilities
<!-- 無既有規格受影響 -->

## Impact

- 受影響檔案：
  - 新增：`backend/app/Shared/Cache/Contracts/CacheInterface.php`
  - 修改：`CacheServiceInterface.php`、`CacheManagerInterface.php`
  - 潛在影響：29 個直接介面實作與呼叫端（含 1 個實作類別需更新簽章，其餘 28 個純 type-hint 呼叫端無需修改）
- 無資料庫變更、無路由變更、無前端變更
- 非破壞性向後相容
