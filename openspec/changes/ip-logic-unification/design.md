## Context

目前程式碼中有 5 個不同的 IP 提取實作、1 個 NetworkHelper 工具類別（部分方法為 `private static`），以及 1 個 DeviceInfo 中內聯的 IP 遮罩邏輯。每個實作的標頭優先順序、代理信任策略、IP 驗證旗標皆不相同：

| 實作位置 | 代理信任 | 標頭優先順序（完整清單） | 驗證旗標 | 特點 | 回退值 |
|---|---|---|---|---|---|
| NetworkHelper | 信任清單 (CIDR) | CF → X-Real-IP → X-Forwarded-For → Client-IP | `FILTER_VALIDATE_IP` | `$serverParams` ?? `getHeaderLine()` 雙源回退 | REMOTE_ADDR (預設 127.0.0.1) |
| JwtAuthorizationMiddleware | 無信任檢查 | CF → Client-IP → X-Forwarded-For → X-Forwarded → X-Cluster-Client-IP → Forwarded-For → Forwarded → REMOTE_ADDR | `NO_PRIV_RANGE \| NO_RES_RANGE` (server params) / 無 (hasHeader) | 最長標頭清單，每項雙重來源讀取（server params + hasHeader） | `'127.0.0.1'` |
| RateLimitMiddleware | 私有範圍判斷 | X-Forwarded-For → X-Real-IP → Client-IP → X-Cluster-Client-IP → X-Forwarded → Forwarded-For → Forwarded | `NO_PRIV_RANGE \| NO_RES_RANGE` | 僅讀 `$serverParams`，REMOTE_ADDR 先於標頭檢查 | `$remoteAddr` (REMOTE_ADDR 或 127.0.0.1) |
| PostViewRateLimitMiddleware | 無信任檢查 | X-Forwarded-For → X-Real-IP → CF → X-Forwarded → Forwarded-For → Forwarded | `NO_PRIV_RANGE \| NO_RES_RANGE` | 僅讀 `$serverParams`，迭代**所有** IP（不限第一個） | REMOTE_ADDR 或 `'127.0.0.1'` |
| StatisticsAdminController | 無信任檢查 | CF → Client-IP → X-Forwarded-For → X-Forwarded → X-Cluster-Client-IP → Forwarded-For → Forwarded → REMOTE_ADDR | `NO_PRIV_RANGE \| NO_RES_RANGE` | 僅讀 `$serverParams` | REMOTE_ADDR 或 `'unknown'` |
| CSPReportController | 無信任檢查 | CF → Client-IP → X-Forwarded-For → X-Forwarded → X-Cluster-Client-IP → Forwarded-For → Forwarded → REMOTE_ADDR | `NO_PRIV_RANGE \| NO_RES_RANGE` | 僅讀 `$serverParams` | REMOTE_ADDR 或 `'unknown'` |

## Goals / Non-Goals

**Goals:**
- 將所有 IP 提取邏輯集中到 NetworkHelper，消除重複實作
- 為 NetworkHelper 補上萬用字元 (`*`) 比對支援
- 為每個 caller 保留完全相同的行為（標頭順序、驗證旗標、代理信任邏輯）
- 撰寫完整的單元測試涵蓋所有提取模式與比對方法

**Non-Goals:**
- 不修改任何 caller 的既有行為（純 refactor）
- 不處理 JwtAuthorizationMiddleware 的整體重構（留給後續 `jwt-authorization-refactor`）
- 不新增 IPv6 CIDR 支援（僅保留原有的 IPv4 CIDR 實作）
- 不變更 DeviceInfo 的公開 API（只選擇性地使用 NetworkHelper 內部實作）

## Decisions

### 決定 1：NetworkHelper 方法命名與簽章

為每個代理信任模式提供獨立方法，讓 caller 選擇適合的行為：

| NetworkHelper 方法 | 對應 caller | 代理信任邏輯 |
|---|---|---|
| `getClientIpFromServerParams(Request, array $headerPriority, int $filterFlags, bool $iterateAllIps = false, string $fallback = '127.0.0.1')` | JwtAuthorization（無信任）、StatisticsAdmin、CSPReport、PostViewRateLimit | 純 server params 提取，無代理信任 |
| `getClientIpWithPrivateCheck(Request, array $headerPriority, bool $iterateAllIps = false, string $fallback = '127.0.0.1')` | RateLimitMiddleware | 若 REMOTE_ADDR 為私有/保留範圍則信任轉發標頭 |
| `getClientIp(Request, array $trustedProxies, array $headerPriority)` | NetworkHelper 既有方法（保留既有簽章） | 使用明確定義的信任代理清單 |

**參數說明：**
- `$headerPriority`：標頭優先順序陣列，caller 傳入與原始實作完全相同的順序
- `$filterFlags`：`filter_var` 驗證旗標
- `$iterateAllIps`：是否迭代標頭中的**所有** IP（如 PostViewRateLimitMiddleware 需要），預設 `false` 僅取第一個 IP
- `$fallback`：所有標頭皆無有效 IP 時的回退值
- `getClientIpFromServerParams()` 僅讀取 `$serverParams`，無 `$request->hasHeader()` 回退（保留 JWT 自行組合雙重來源的能力）

**各 caller 實際呼叫簽章：**

| Caller | NetworkHelper 方法 | `$iterateAllIps` | `$fallback` |
|---|---|---|---|
| JwtAuthorizationMiddleware | `getClientIpFromServerParams()`（僅 server params 部分，hasHeader 部分由 JWT 自行處理） | `false` | `'127.0.0.1'` |
| RateLimitMiddleware | `getClientIpWithPrivateCheck()` | `false` | `'127.0.0.1'` |
| PostViewRateLimitMiddleware | `getClientIpFromServerParams()` | `true` | `'127.0.0.1'` |
| StatisticsAdminController | `getClientIpFromServerParams()` | `false` | `'unknown'` |
| CSPReportController | `getClientIpFromServerParams()` | `false` | `'unknown'` |

**替代方案考量：** 曾考慮單一 `getClientIp()` 方法搭配策略模式參數，但由於各 caller 的標頭順序、驗證旗標、迭代方式皆不同，單一方法會導致過多條件分支，難以維護與測試。

### 決定 2：保留 JwtAuthorizationMiddleware 的雙重來源讀取

JwtAuthorizationMiddleware 同時讀取 `$serverParams` 和 `$request->hasHeader()`，且兩者使用不同的驗證旗標。這是不一致的行為，但為了保持純 refactor，NetworkHelper 提供 `getClientIpFromServerParams()` 基礎方法，由 JwtAuthorizationMiddleware 自行組合呼叫。

### 決定 3：萬用字元比對實作方式

沿用 JwtAuthorizationMiddleware 既有方式：使用 `preg_quote()` + `str_replace('*', '.*')` 轉換為正規表達式。與 CIDR 比放置於 `isIpInRangesPublic()` 方法中，讓 caller 透過統一的 `isIpInRanges()` 使用。

### 決定 4：IP 遮罩邏輯搬移

`DeviceInfo::maskIpAddress()` 的邏輯搬移至 NetworkHelper 的 `maskIpAddress()`，DeviceInfo 改為**強制委託**呼叫（非可選）。保持遮罩行為完全一致（IPv4 隱藏末段、IPv6 保留前 4 段）。注意：DeviceInfo 的 `maskIpAddress()` 為實例方法（存取 `$this->ipAddress`），搬移為靜態方法後需傳入欲遮罩的 IP 字串：`NetworkHelper::maskIpAddress(string $ip): string`。

## Risks / Trade-offs

- **[風險] 行為偏差** → 每個取代必須逐行對照測試，確保標頭順序與驗證旗標完全相同
- **[風險] JwtAuthorizationMiddleware 的雙重讀取邏輯複雜** → 不強求完全統一，保留 caller 層的組合邏輯
- **[風險] 既有 NetworkHelper 使用者不受影響** → 保留 `getClientIp(Request, array $trustedProxies)` 既有簽章，向後相容
