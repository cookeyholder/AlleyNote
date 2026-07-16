## ADDED Requirements

### Requirement: NetworkHelper 提供無代理信任的 IP 提取
`getClientIpFromServerParams()` 方法 SHALL 僅從 `$serverParams` 提取 IP，不對 REMOTE_ADDR 進行代理信任判斷。呼叫端 SHALL 提供客製化的標頭優先順序陣列與 `filter_var` 旗標。

#### Scenario: 從 server params 提取 CF-Connecting-IP
- **WHEN** `$serverParams` 包含 `HTTP_CF_CONNECTING_IP` 且值為合法公開 IP
- **THEN** 回傳該 IP

#### Scenario: 從 server params 提取 X-Forwarded-For 第一個 IP
- **WHEN** `$serverParams` 包含 `HTTP_X_FORWARDED_FOR` 且值為 `"1.2.3.4, 5.6.7.8"`
- **THEN** 回傳 `"1.2.3.4"`

#### Scenario: 所有標頭皆無效時回傳 REMOTE_ADDR
- **WHEN** 所有設定標頭皆不存在或值為空，但 `REMOTE_ADDR` 為 `"10.0.0.1"`
- **THEN** 回傳 `"10.0.0.1"`

#### Scenario: 同時讀取 $serverParams 與 Request header line
- **WHEN** `$serverParams` 無對應值但 `$request->getHeaderLine()` 回傳有效 IP
- **THEN** 回傳該 IP（適用於 JwtAuthorizationMiddleware 模式）

### Requirement: NetworkHelper 提供私有範圍偵測的 IP 提取
`getClientIpWithPrivateCheck()` 方法 SHALL 檢查 REMOTE_ADDR 是否為私有/保留 IP 範圍，若是則信任轉發標頭中的 IP。

#### Scenario: REMOTE_ADDR 為私有 IP 時信任轉發標頭
- **WHEN** `REMOTE_ADDR` 為 `"192.168.1.1"` 且 `HTTP_X_FORWARDED_FOR` 包含 `"203.0.113.5"`
- **THEN** 回傳 `"203.0.113.5"`

#### Scenario: REMOTE_ADDR 為公開 IP 時不信任轉發標頭
- **WHEN** `REMOTE_ADDR` 為 `"203.0.113.1"` 且 `HTTP_X_FORWARDED_FOR` 包含 `"1.2.3.4"`
- **THEN** 回傳 `"203.0.113.1"`

#### Scenario: REMOTE_ADDR 為公開 IP 且無標頭時回傳本身
- **WHEN** `REMOTE_ADDR` 為 `"203.0.113.1"` 且無任何轉發標頭
- **THEN** 回傳 `"203.0.113.1"`

### Requirement: NetworkHelper 保留既有信任清單 IP 提取
原有的 `getClientIp()` 方法 SHALL 維持既有簽章與行為，僅在 REMOTE_ADDR 符合 `$trustedProxies` 清單時才信任轉發標頭。

#### Scenario: REMOTE_ADDR 在信任清單中時信任轉發標頭
- **WHEN** `$trustedProxies` 包含 `"10.0.0.0/8"`，`REMOTE_ADDR` 為 `"10.0.0.5"`，且 `HTTP_CF_CONNECTING_IP` 為 `"1.2.3.4"`
- **THEN** 回傳 `"1.2.3.4"`

#### Scenario: REMOTE_ADDR 不在信任清單中時回傳本身
- **WHEN** `$trustedProxies` 為 `["192.168.0.0/16"]`，`REMOTE_ADDR` 為 `"10.0.0.5"`
- **THEN** 回傳 `"10.0.0.5"`

### Requirement: NetworkHelper 支援自訂標頭優先順序
所有提取方法 SHALL 接受 `array $headerPriority` 參數，讓 caller 指定自己的標頭檢查順序與對應的 server params key。

#### Scenario: 自訂僅檢查 X-Real-IP
- **WHEN** `$headerPriority` 為 `['HTTP_X_REAL_IP']` 且該值為 `"5.6.7.8"`
- **THEN** 回傳 `"5.6.7.8"`
