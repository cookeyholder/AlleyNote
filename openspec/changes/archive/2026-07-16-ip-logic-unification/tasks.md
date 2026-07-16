## 1. NetworkHelper 擴充

- [ ] 1.1 將 `isIpInRanges()` 改為 `public static`，補上萬用字元 (`*`) 比對邏輯
- [ ] 1.2 將 `ipInNetwork()` 改為 `public static`
- [ ] 1.3 實作 `getClientIpFromServerParams(Request, array $headerPriority, int $filterFlags, bool $iterateAllIps = false, string $fallback = '127.0.0.1')` 方法
- [ ] 1.4 實作 `getClientIpWithPrivateCheck(Request, array $headerPriority, bool $iterateAllIps = false, string $fallback = '127.0.0.1')` 方法
- [ ] 1.5 實作 `maskIpAddress(string $ip): string` 方法（從 DeviceInfo 搬移）
- [ ] 1.6 保留既有 `getClientIp()` 與 `getTrustedProxies()` 簽章不變

## 2. 行為比對測試計畫（Behavior Comparison Test Plan）

為確保每個 caller 取代後行為完全一致，定義以下測試矩陣，每項需在 NetworkHelper 方法轉換前後比對相同輸入是否產出相同輸出：

| Caller | 測試場景 | 預期行為 |
|---|---|---|
| JwtAuthorizationMiddleware | CF header 有效，X-Forwarded-For 為私有 IP | 應取 CF（server params 含 `NO_PRIV_RANGE` 跳過私有） |
| JwtAuthorizationMiddleware | CF header 無效，hasHeader 有值 | server params 跳過後，hasHeader 不帶旗標應捕獲（這是 JWT 特有的雙重來源行為） |
| JwtAuthorizationMiddleware | 萬用字元模式 `192.168.*` 比對 `192.168.1.1` | 應比對成功 |
| JwtAuthorizationMiddleware | 萬用字元模式 `10.0.*.*` 比對 `10.0.5.100` | 應比對成功 |
| JwtAuthorizationMiddleware | CIDR 模式 `10.0.0.0/8` 比對 `10.0.0.1` | 應比對成功 |
| JwtAuthorizationMiddleware | 無轉發標頭，僅 REMOTE_ADDR | 回退 `'127.0.0.1'` |
| RateLimitMiddleware | REMOTE_ADDR 為私有 IP（如 192.168.1.1） | 應信任代理標頭，取 X-Forwarded-For 中的公開 IP |
| RateLimitMiddleware | REMOTE_ADDR 為公開 IP（如 8.8.8.8） | 不應信任任何標頭，回退 `$remoteAddr` |
| RateLimitMiddleware | REMOTE_ADDR 為私有，但所有轉發標頭皆為私有 IP | 回退 `$remoteAddr` |
| PostViewRateLimitMiddleware | X-Forwarded-For 含 `203.0.113.1, 10.0.0.1, 192.168.1.1` | 應迭代所有 IP 並取 `203.0.113.1`（第一個公開 IP，不一定是第一個） |
| PostViewRateLimitMiddleware | X-Forwarded-For 全部私有，但 X-Real-IP 為公開 | 應取 X-Real-IP 的值 |
| PostViewRateLimitMiddleware | 所有標頭無有效公開 IP，REMOTE_ADDR 為 10.0.0.5 | 回退 `10.0.0.5` |
| StatisticsAdminController | CF header 有效，X-Forwarded-For 也有值 | 應取 CF（最高優先） |
| StatisticsAdminController | 無任何轉發標頭，REMOTE_ADDR 未設定 | 回退 `'unknown'` |
| CSPReportController | 同 StatisticsAdminController 測試場景 | 行為應完全相同 |

- [ ] 2.1 完成行為比對測試計畫，驗證每個 caller 取代前後的輸出完全一致

## 3. 撰寫 NetworkHelper 單元測試

- [ ] 3.1 測試 `getClientIpFromServerParams()`：各標頭優先順序、無效 IP 回退、`$iterateAllIps=true/false`、自訂 `$fallback`、無標頭時回退
- [ ] 3.2 測試 `getClientIpWithPrivateCheck()`：私有範圍信任、公開範圍不信任、邊界案例（127.0.0.1、10.x.x.x、172.16-31.x.x、192.168.x.x）
- [ ] 3.3 測試 `getClientIp()`：信任清單比對（CIDR + 萬用字元）、非信任來源回退、空信任清單
- [ ] 3.4 測試 `isIpInRanges()`：完全比對、CIDR 比對、萬用字元比對、混合清單、無匹配
- [ ] 3.5 測試 `ipInNetwork()`：IPv4 CIDR 正確/錯誤比對、無效 CIDR、邊界長度 `/0`、`/32`
- [ ] 3.6 測試 `maskIpAddress()`：IPv4、IPv6 簡寫、IPv6 完整、無效 IP

## 4. 取代 JwtAuthorizationMiddleware 的內聯實作

- [ ] 4.1 將 `getClientIpAddress()` 中 `$serverParams` 部分改為呼叫 `getClientIpFromServerParams()`（保留 `$request->hasHeader()` 雙重來源邏輯與原始標頭順序）
- [ ] 4.2 將 `isIpInList()` + `ipMatches()` 改為呼叫 `isIpInRanges()`
- [ ] 4.3 確認 `checkIpBasedAccess()` 行為不變

## 5. 取代 RateLimitMiddleware 的內聯實作

- [ ] 5.1 將 `getRealClientIP()` 改為呼叫 `getClientIpWithPrivateCheck()`（保留原始標頭順序）
- [ ] 5.2 確認 `process()` 中使用的 IP 行為不變

## 6. 取代 PostViewRateLimitMiddleware 的內聯實作

- [ ] 6.1 將 `getRealClientIP()` 改為呼叫 `getClientIpFromServerParams()`（`$iterateAllIps=true`，保留迭代所有 IP 的行為與原始標頭順序）
- [ ] 6.2 確認 `checkRateLimit()` 中使用的 IP 行為不變

## 7. 取代 StatisticsAdminController 的內聯實作

- [ ] 7.1 將 `getClientIpAddress()` 改為呼叫 `getClientIpFromServerParams()`（`$fallback='unknown'`，保留原始標頭順序）
- [ ] 7.2 確認 `logAdminAction()` 中的 IP 行為不變

## 8. 取代 CSPReportController 的內聯實作

- [ ] 8.1 將 `getClientIP()` 改為呼叫 `getClientIpFromServerParams()`（`$fallback='unknown'`，保留原始標頭順序）
- [ ] 8.2 確認 `logViolation()` 與 `checkForAlert()` 中的 IP 行為不變

## 9. 更新 DeviceInfo 的 IP 遮罩

- [ ] 9.1 將 `maskIpAddress()` 改為委託 `NetworkHelper::maskIpAddress($this->ipAddress)`（傳入實例的 `$ipAddress` 屬性）
- [ ] 9.2 確認 `toSummary()` 與 `toString()` 輸出不變
- [ ] 9.3 確認 `$this->ipAddress` 可正確傳遞給靜態方法

## 10. 最終驗證

- [ ] 10.1 執行行為比對測試（Task 2.1），確認每個 caller 取代前後輸出完全一致
- [ ] 10.2 執行 `composer test` 確認所有既有測試通過
- [ ] 10.3 執行 `composer check-all` 確認無靜態分析與程式碼風格問題
