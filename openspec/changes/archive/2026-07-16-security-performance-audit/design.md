## Context

AlleyNote 使用 JWT（RS256）進行認證與授權，Redis 做為快取與速率限制後端，SQLite 為資料庫。近期程式碼審計發現多項安全與效能問題，涵蓋憑證日誌外洩、授權撤銷延遲、Cookie 安全設定不足、Redis 容錯行為不安全、N+1 查詢效能、以及 CSP 供應鏈安全。這些問題分散在認證中介層、控制器、儲存庫與前端程式碼中。

## Goals / Non-Goals

**Goals:**
- 消除 JWT token 與公鑰資訊被寫入正式環境日誌的風險
- 使用者角色降級後，JWT token 在合理時間內失效（非立即但 < token TTL）
- 認證 Cookie 改為 SameSite=Strict，移除 localStorage refresh token 回退
- Redis 連線失敗時速率限制拒絕請求（fail-closed）而非放行
- 文章列表批次載入標籤，消除 N+1 查詢
- 消除 `tagsExist()` 的 TOCTOU 競態條件，依賴外鍵約束確保資料完整
- Tailwind CDN 加上 SRI integrity hash，CSP 規則補上 CDN 來源
- 後台標籤渲染使用安全方法防止 XSS
- CSRF token 提供補發端點

**Non-Goals:**
- 不完全重寫認證系統（如導入 BFF 模式）
- 不變更 JWT 演算法或金鑰管理方式
- 不重新設計速率限制機制（僅修改容錯行為）
- 不更換 Tailwind 載入方式（保留 CDN 但加上 SRI）

## Decisions

### D1: JWT 角色時效驗證採用 iat 比對 role_updated_at
**替代方案：** 縮短 access token TTL（15 分鐘）／每次請求查資料庫驗證角色
**選擇理由：** 縮短 TTL 需要前端配合 refresh token 邏輯變更，影響較大。iat 比對只需在認證中介層新增一次快取查詢，對效能的影響最低且向後相容

### D2: Redis 失效時回傳 allowed => false 而非 true
**替代方案：** 回退至 SQLite 或檔案式速率限制
**選擇理由：** 回退至 SQLite 需額外實作與測試，增加複雜度。Fail-closed 是最安全的預設行為，且 Redis 在生產環境中應有 HA 機制，短暫不可用時拒絕而非放行更合理

### D3: 批次載入標籤使用 INNER JOIN + GROUP BY 而非逐筆查詢
**替代方案：** 在 paginate() 中使用子查詢 / GROUP_CONCAT
**選擇理由：** INNER JOIN + GROUP BY 在 SQLite 中效能最佳，且不需修改回傳型別

### D4: SameSite=Strict 而非辨識頂層導覽
**替代方案：** 保留 Lax 但加上 CSRF 雙重提交 Cookie 模式
**選擇理由：** SameSite=Strict 在現代瀏覽器中支援度已達 95%+，設定簡單且效果明確。保留 CSRF token 作為縱深防禦

### D5: 移除 tagsExist() 預先檢查，依賴外鍵約束
**替代方案：** 在事務中加入 `SELECT ... FOR UPDATE` 鎖定標籤資料列
**選擇理由：** `tagsExist()` 的 `SELECT COUNT(*)` 與後續 `INSERT` 之間存在 TOCTOU 視窗，鎖定標籤表會降低並行性。外鍵約束 (`post_tags.tag_id REFERENCES tags.id`) 已保證資料完整性，`INSERT` 失敗時由事務回滾即可。移除預檢不僅消除競態，還能減少一次額外查詢

## Risks / Trade-offs

- **SameSite=Strict 可能中斷第三方嵌入** — 如果 AlleyNote 被嵌入 iframe 或作為 OAuth callback，Strict 會阻擋 Cookie。目前無此使用情境，但需記錄
- **批次載入標籤增加單次查詢複雜度** — 當文章數量極大時，IN (?,?,?) 的參數數量可能變多。建議限制每次批次大小
- **Redis fail-closed 可能造成 false positive 封鎖** — Redis 短暫中斷時，合法使用者可能被誤擋。建議搭配 Redis 連線健康檢查與 alert
