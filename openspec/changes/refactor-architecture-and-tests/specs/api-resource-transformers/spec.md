## ADDED Requirements

### Requirement: Standard API Resource Transformation
系統必須提供一個統一的機制將 Domain 模型轉換為 API 回應格式，並支援額外中繼資料（如統計數據）的合併。

#### Scenario: Transform Post with Statistics
- **WHEN** 控制器傳遞一個 `Post` 物件與瀏覽統計資料給 `PostResource`
- **THEN** 系統回傳一個包含 `id`, `title`, `views`, `unique_visitors` 等欄位的標準化陣列
