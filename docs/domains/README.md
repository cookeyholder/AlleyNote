# 領域總覽

> 本目錄記錄 AlleyNote 的 6 個 Bounded Context（BC），
> 說明各自職責、核心檔案、依賴方向與適合讀者。

| # | BC | 職責 | 適合讀者 |
|---|----|------|----------|
| [01](01-認證授權領域.md) | **Auth** — 認證與授權 | JWT 認證、RBAC + 直接權限授權、密碼管理、角色/權限 CRUD | 後端開發者、安全工程師 |
| [02](02-文章領域.md) | **Post** — 公告 | 公告 CRUD、標籤管理、內容審查、快取失效、搜尋 | 後端開發者、內容管理者 |
| [03](03-附件領域.md) | **Attachment** — 附件 | 檔案上傳、MIME 驗證、儲存與關聯公告 | 後端開發者 |
| [04](04-統計領域.md) | **Statistics** — 統計 | 分析器模式（Analyzer）、統計快照、彙總查詢、匯出 | 後端開發者、資料分析師 |
| [05](05-安全領域.md) | **Security** — 安全 | XSS 防護、CSRF、安全標頭、活動日誌、速率限制、可疑行為偵測 | 後端開發者、安全工程師 |
| [06](06-設定領域.md) | **Setting** — 設定 | 系統設定 key-value 存取 | 後端開發者 |

## 依賴方向

```
Auth  ──→  Shared (ValueObjects)
  ↑
Post ──→ Auth (Session/權限查詢)
Post ──→ Security (活動日誌)
Post ──→ Shared (快取介面)
  ↑
Attachment ──→ Shared (儲存抽象)
  ↑
Statistics ──→ Post (公告資料來源)
Statistics ──→ Shared (快取/事件)
  ↑
Security ──→ Shared (日誌/事件)
  ↑
Setting（無領域依賴）
```

所有 BC 共用 `Shared` 層的 ValueObjects 與基礎設施抽象介面。

## Reader Persona

- **後端開發者**：需要理解 BC 邊界、Service 職責、Repository 模式
- **安全工程師**：關注 Auth / Security BC 的防護機制與缺口
- **內容管理者**：關注 Post BC 的公告生命週期
- **資料分析師**：關注 Statistics BC 的彙總邏輯與分析器
