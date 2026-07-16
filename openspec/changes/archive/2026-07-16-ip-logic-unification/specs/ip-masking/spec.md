## ADDED Requirements

### Requirement: NetworkHelper 提供 IP 遮罩方法
`maskIpAddress()` 方法 SHALL 接受 IP 字串，回傳遮罩後的版本。IPv4 隱藏最後一段為 `xxx`，IPv6 保留前 4 段後以 `xxxx` 取代。

#### Scenario: IPv4 遮罩
- **WHEN** 輸入為 `"192.168.1.100"`
- **THEN** 回傳 `"192.168.1.xxx"`

#### Scenario: IPv6 簡寫格式遮罩
- **WHEN** 輸入為 `"2001:db8::1"`
- **THEN** 回傳 `"2001:db8::xxxx"`

#### Scenario: IPv6 完整格式遮罩
- **WHEN** 輸入為 `"2001:db8:1234:5678:abcd:ef01:2345:6789"`
- **THEN** 回傳 `"2001:db8:1234:5678::xxxx"`

#### Scenario: 無效 IP 回傳部分遮罩
- **WHEN** 輸入為 `"invalid-ip"`
- **THEN** 回傳結尾 `"xxxx"` 的遮罩字串

### Requirement: DeviceInfo 委託 NetworkHelper 進行遮罩
`DeviceInfo::maskIpAddress()` 方法 SHALL 改為呼叫 `NetworkHelper::maskIpAddress()`，保持完全相同行為。

#### Scenario: DeviceInfo 遮罩行為一致
- **WHEN** `DeviceInfo` 的 `toSummary()` 呼叫內部遮罩
- **THEN** 結果與直接呼叫 `NetworkHelper::maskIpAddress()` 相同
