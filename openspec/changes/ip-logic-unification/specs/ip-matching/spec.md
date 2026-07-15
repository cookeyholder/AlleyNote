## ADDED Requirements

### Requirement: 支援完全比對
`isIpInRanges()` 方法 SHALL 支援 IP 字串的完全比對。

#### Scenario: 完全比對成功
- **WHEN** IP 為 `"1.2.3.4"` 且範圍清單包含 `"1.2.3.4"`
- **THEN** 回傳 `true`

#### Scenario: 完全比對失敗
- **WHEN** IP 為 `"1.2.3.4"` 且範圍清單僅包含 `"5.6.7.8"`
- **THEN** 回傳 `false`

### Requirement: 支援 CIDR 網路範圍比對（IPv4）
`isIpInRanges()` 方法 SHALL 支援 CIDR 格式（例如 `"10.0.0.0/8"`）的 IPv4 網路範圍比對。

#### Scenario: CIDR 比對成功
- **WHEN** IP 為 `"10.0.0.5"` 且範圍清單包含 `"10.0.0.0/8"`
- **THEN** 回傳 `true`

#### Scenario: CIDR 比對失敗
- **WHEN** IP 為 `"11.0.0.5"` 且範圍清單包含 `"10.0.0.0/8"`
- **THEN** 回傳 `false`

#### Scenario: CIDR 前綴長度為 0 時比對所有 IP
- **WHEN** IP 為 `"255.255.255.255"` 且範圍清單包含 `"0.0.0.0/0"`
- **THEN** 回傳 `true`

#### Scenario: 無效的 CIDR 格式回傳 false
- **WHEN** 範圍為 `"not-a-cidr/abc"`
- **THEN** 回傳 `false`

### Requirement: 支援萬用字元模式比對
`isIpInRanges()` 方法 SHALL 支援使用 `*` 的萬用字元模式比對（例如 `"192.168.*"`）。

#### Scenario: 萬用字元比對成功
- **WHEN** IP 為 `"192.168.1.100"` 且範圍清單包含 `"192.168.*"`
- **THEN** 回傳 `true`

#### Scenario: 萬用字元比對失敗
- **WHEN** IP 為 `"10.0.0.1"` 且範圍清單包含 `"192.168.*"`
- **THEN** 回傳 `false`

#### Scenario: 多段萬用字元比對
- **WHEN** IP 為 `"10.20.30.40"` 且範圍清單包含 `"10.*.*.*"`
- **THEN** 回傳 `true`

### Requirement: 公開的 isIpInRanges 方法
原有的 `isIpInRanges()` 與 `ipInNetwork()` 方法 SHALL 改為 `public`，讓外部可以直接呼叫。

#### Scenario: 公開方法可直接呼叫
- **WHEN** 外部程式碼呼叫 `NetworkHelper::isIpInRanges('1.2.3.4', ['1.2.3.4'])`
- **THEN** 回傳 `true`
