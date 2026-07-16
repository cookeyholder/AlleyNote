## ADDED Requirements

### Requirement: 批次載入文章標籤消除 N+1
文章列表分頁查詢時，必須批次載入所有文章的標籤，而非對每篇文章逐筆查詢。批次載入應使用 `INNER JOIN post_tags` 搭配 `WHERE post_id IN (...)`。

#### Scenario: 分頁文章列表只執行 2 次查詢
- **WHEN** 前端請求文章列表（每頁 20 篇）
- **THEN** 後端執行 1 次分頁查詢取得文章
- **AND** 執行 1 次批次查詢取得所有文章的標籤
- **AND** 總資料庫查詢次數為 2 次（而非 21 次）

#### Scenario: 無文章時不執行標籤查詢
- **WHEN** 分頁查詢回傳 0 篇文章
- **THEN** 不執行 `getPostTags` 批次查詢
- **AND** 回傳空標籤對應

#### Scenario: 批次查詢結果正確對應文章
- **WHEN** 批次查詢回傳多篇文章的標籤
- **THEN** 每篇文章的標籤陣列正確對應
- **AND** 無標籤的文章回傳空陣列

### Requirement: 明確指定查詢欄位取代 SELECT *
資料庫查詢應明確指定需要的欄位名稱，避免使用 `SELECT *`。這可防止 `password_hash` 等敏感欄位意外暴露至 API 回應。

#### Scenario: UserRepository 不使用 SELECT *
- **WHEN** `UserRepository::paginate()` 執行使用者查詢
- **THEN** SELECT 語句明確列出欄位（`id, username, email, role, ...`）
- **AND** 不使用 `u.*` 或 `*`

### Requirement: 消除 tagsExist() TOCTOU 競態條件
`PostCrudRepository::assignTags()` 不應在事務中對標籤存在性做獨立預先檢查，應依賴外鍵約束保證關聯完整性。若標籤已被刪除，`INSERT` 因外鍵違例失敗時由事務回滾。

#### Scenario: 標籤在檢查後被刪除時事務回滾
- **WHEN** `assignTags()` 在事務中為文章關聯多個標籤
- **AND** 其中某個標籤在 `INSERT` 執行前已被其他連線刪除
- **THEN** 資料庫因外鍵約束擲回例外
- **AND** 事務回滾，文章與其餘標籤的關聯不寫入
- **AND** 呼叫端收到適當錯誤回應

#### Scenario: 有效標籤正常關聯
- **WHEN** `assignTags()` 為文章關聯有效標籤
- **THEN** 不執行額外的 `tagsExist()` 查詢
- **AND** `INSERT` 成功，外鍵約束通過
