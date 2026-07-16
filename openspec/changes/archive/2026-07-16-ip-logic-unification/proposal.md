## Why

IP 位址提取與比對邏輯在 5 個以上的檔案中重複實作，各有不同的標頭優先順序、不同的代理信任檢查方式、不同的驗證旗標，以及不一致的安全姿態。這種重複導致維護負擔增加，也容易引入安全性漏洞（例如某個實作遺漏了代理信任檢查）。現在進行統一重構，將所有 IP 工具方法集中到 NetworkHelper，作為後續 JWT 授權中介軟體重構的前置步驟。

## What Changes

- 擴充 `NetworkHelper`（`Shared/Helpers/NetworkHelper.php`），新增以下 `public static` 方法：
  - `getClientIpFromServerParams()`：僅從 `$serverParams` 提取 IP（無 trusted proxy 檢查）
  - `getClientIpWithPrivateCheck()`：使用私有 IP 範圍判斷是否為受信任代理
  - `getClientIpWithTrustedProxies()`：使用明確的信任代理清單檢查
  - `maskIpAddress()`：IP 遮罩邏輯（從 DeviceInfo 搬移）
- 修改 `isIpInRanges()` 與 `ipInNetwork()` 為 `public`，並在 `isIpInRanges()` 中新增萬用字元 (`*`) 比對支援
- 逐一取代 5 個 callers 中的內聯實作，確保每個取代維持完全相同的行為（標頭順序、驗證旗標、代理信任邏輯）
- 新增 NetworkHelper 的單元測試

## Capabilities

### New Capabilities
- `ip-extraction`: 統一的 IP 位址提取功能，支援三種代理信任模式（不信任、私有範圍偵測、信任清單）
- `ip-matching`: IP 位址比對功能，支援完全比對、CIDR 網路範圍、萬用字元模式
- `ip-masking`: IP 位址遮罩功能，用於日誌記錄與隱私保護

### Modified Capabilities
（無現有 spec 需要修改）

## Impact

- `Shared/Helpers/NetworkHelper.php`：新增 public 方法與萬用字元支援
- `Application/Middleware/JwtAuthorizationMiddleware.php`：取代 `getClientIpAddress()`、`isIpInList()`、`ipMatches()`
- `Application/Middleware/RateLimitMiddleware.php`：取代 `getRealClientIP()`
- `Application/Middleware/PostViewRateLimitMiddleware.php`：取代 `getRealClientIP()`
- `Application/Controllers/Api/V1/StatisticsAdminController.php`：取代 `getClientIpAddress()`
- `Application/Controllers/Security/CSPReportController.php`：取代 `getClientIP()`
- `Domains/Auth/ValueObjects/DeviceInfo.php`：改為委託 NetworkHelper::maskIpAddress() 實作
- 新增 `tests/Unit/Shared/Helpers/NetworkHelperTest.php`
