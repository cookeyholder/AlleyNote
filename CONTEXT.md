# AlleyNote 領域詞彙表

> 本文件記錄 AlleyNote 專案的共同語言（Ubiquitous Language）。
> 詞彙定義優先於程式碼實作，當發現不一致時應更新此文件或修正程式碼。

---

## 管理員 (Admin)

具備系統管理權限的使用者，能夠登入後台、管理文章、使用者與系統設定。
對應程式碼的 `users.role IN (admin, super_admin, system_admin)`。

**別名：** 後台使用者、內容管理者
**避免混淆：** 與「使用者 (User)」不同，管理員擁有後台存取權限

## 使用者 (User) / 訪客 (Visitor)

不具備管理權限的一般使用者，僅能瀏覽公開文章。
對應程式碼的 `users.role IN (user, guest)` 或未登入狀態。

**別名：** 讀者、一般使用者
**避免混淆：** 與「管理員 (Admin)」不同，使用者無法存取後台

## 使用者帳號 (User Account)

當不需要區分管理員與一般使用者時的抽象統稱。
對應程式碼的 `users` 資料表與 `UserId` 值物件（`App\Domains\Auth\ValueObjects\UserId`）。

---

## 公告 (Post)

系統的核心內容單元，由管理員建立、發布、編輯或封存。
對應程式碼的 `Post` 類別家族（`App\Domains\Post\Models\Post`、`PostRepository` 等）。

**避免混淆：** 不是「文章」或「貼文」。AlleyNote 是公告／佈告欄平台，統一使用「公告」

## Token (JWT)

AlleyNote 中所有的 Token 皆為 JSON Web Token (JWT)，使用 RS256 演算法簽章。
對應程式碼的 `JwtTokenService`、`TokenPair`、`FirebaseJwtProvider`。

**組成：**
- **Access Token**：短效（預設 1 小時），用於 API 請求認證
- **Refresh Token**：長效（預設 30 天），用於刷新 Access Token，存於 HttpOnly Cookie

**避免混淆：** 不存在非 JWT 的 Token 類型。所有 Token 操作都通過 `JwtTokenServiceInterface` 進行

---

## 認證 (Authentication)

確認使用者身份的過程。驗證 JWT Token 的有效性、檢查黑名單、比對角色時效。
對應程式碼的 `JwtAuthenticationMiddleware`、`AuthenticationService`。

**HTTP 狀態碼：** 401 Unauthorized
**核心問題：** 「你是誰？」

## 授權 (Authorization)

確認使用者是否有權限執行特定操作的過程。檢查角色、權限、資源擁有者、時間限制。
對應程式碼的 `JwtAuthorizationMiddleware`、`AuthorizationOrchestratorService`、5 個 Strategy 類別。

**HTTP 狀態碼：** 403 Forbidden
**核心問題：** 「你能做什麼？」

**避免混淆：** 認證失敗回傳 401，授權失敗回傳 403。兩者在中介層的處理鏈不同

---

## 角色 (Role)

RBAC 中的群組概念，是「一組權限的集合名稱」。
對應程式碼的 `users.role` 欄位，在 `AuthServiceProvider` 中以 `$rolePermissions` 映射表定義。

**內建角色：** `super_admin` > `admin` > `moderator` > `user` > `guest`
**用法：** 角色決定使用者的預設權限範圍。所有人共用同一套角色定義

## 權限 (Permission)

一個具體的 `resource.action` 字串，代表對某個資源執行某個操作的能力。
對應程式碼的 `user_permissions` 資料表與 `PermissionAuthorizationStrategy`。

**格式：** `{resource}.{action}`，例如 `posts.create`、`users.*`、`*`（全部允許）
**來源：** 角色繼承 + 直接指派

## 授權模型

AlleyNote 使用 RBAC + 直接權限覆寫的混合模型：

1. **角色權限 (Role-based)**：`RoleAuthorizationStrategy` 查詢角色→權限映射表（RBAC）
2. **直接權限 (Direct Permission)**：`PermissionAuthorizationStrategy` 查詢使用者直接指派的權限

兩者在 `AuthorizationOrchestratorService` 中依序執行，任一策略允許即短路回傳。
這讓管理員可以對特定使用者「額外開放」某項權限，而不需要變更其角色。

**避免混淆：** 此處的「授權模型」指 `docs/architecture/03-JWT授權策略.md` 中的 5 策略架構，
與 `AuthorizationService`（角色/權限 CRUD，資料庫存取）職責不同

---

## 統計快照 (StatisticsSnapshot)

統計資料的時間點快照，包含一段時間內的彙總數據。
對應程式碼的 `Statistics` 領域下兩個同名類別，職責不同：

### Entities\StatisticsSnapshot（領域實體）
- **路徑：** `App\Domains\Statistics\Entities\StatisticsSnapshot`
- **職責：** 建立統計快照、計算指標、驗證資料完整性、產生領域事件
- **特色：** 使用 `StatisticsPeriod` ValueObject、`DateTimeInterface`、有工廠方法 `create()`

### Models\StatisticsSnapshot（資料映射）
- **路徑：** `App\Domains\Statistics\Models\StatisticsSnapshot`
- **職責：** 從資料庫列映射為 PHP 物件，純資料容器
- **特色：** 無商業邏輯、僅 getter 與 `toArray()`

**命名警告：** `Models\StatisticsSnapshot` 的命名是歷史遺留，實際職責接近 Record/DTO 而非 Model。
在新開發中應避免延伸此命名模式

---

## 統計類型 (StatisticsType)

統計快照的分類，決定快照要彙總哪種資料。
對應程式碼的 `StatisticsType` enum，定義了五種有效值：

| 值 | 用途 | 對應 ChartType |
|---|---|---|
| `overview` | 全站概覽數據 | Line |
| `posts` | 文章相關統計 | Bar |
| `users` | 使用者相關統計 | Bar |
| `sources` | 流量來源分析 | Pie |
| `popular` | 熱門內容排行 | Bar |

**避免混淆：**
- `trends` 不是統計類型——它是快照計算後的趨勢分析結果，用於 TTL 查詢與快取標籤
- 此枚舉對應 `StatisticsSnapshot` 的 `snapshot_type` 資料庫欄位

---

## 應用層 Service (Application Service)

負責編排多個領域 Service、管理快取、決定執行時機。不包含純領域邏輯。
對應程式碼的 `App\Application\Services\*`。

**例子：** `StatisticsApplicationService` — 編排彙總、計算趨勢、管理快取
**避免混淆：** 與 Domain Service 不同，Application Service 會處理 HTTP 請求的上下文（DTO、快取鍵）

## 領域層 Service (Domain Service)

負責單一領域職責的商業邏輯計算。不關心 HTTP、快取、外部系統。
對應程式碼的 `App\Domains\*\Services\*`。

**例子：** `StatisticsAggregationService`（彙總計算）、`StatisticsQueryService`（查詢邏輯）
**避免混淆：** 不含基礎設施邏輯，所有依賴都是領域層介面或 ValueObject

## 基礎設施層 Service (Infrastructure Service)

負責與外部系統互動的實作：檔案處理、匯出格式、第三方 API。
對應程式碼的 `App\Infrastructure\*\Services\*`。

**例子：** `StatisticsExportService`（CSV/JSON 匯出格式處理）

---

## 安全 (Security) Bounded Context

統一管理 AlleyNote 的所有安全機制：防護、偵測、日誌、速率限制。
對應程式碼的 `App\Domains\Security\*` + 相關 Middleware。

### 範疇

| 機制 | 領域層 | HTTP 入口 |
|------|--------|-----------|
| XSS 防護 | `XssProtectionService` | —（整合在各 Controller） |
| CSRF 保護 | `CsrfProtectionService` | `CsrfMiddleware` |
| 安全標頭 | `SecurityHeaderService` | `SecurityHeaderMiddleware` |
| 活動日誌 | `ActivityLoggingService` | —（注入在各 Service） |
| 可疑活動偵測 | `SuspiciousActivityDetector` | —（由 LoggingService 觸發） |
| IP 管理 | `IpService` | — |
| 錯誤處理 | `ErrorHandlerService` | — |
| 機密管理 | `SecretsManager` | — |
| 速率限制 | `RateLimitServiceInterface`（待建立） | `RateLimitMiddleware` |

### 目前缺口

`RateLimitMiddleware` 目前直接依賴 `Infrastructure\Services\RateLimitService`，
缺乏 Security BC 的抽象層。應新增 `Domains\Security\Contracts\RateLimitServiceInterface`，
讓 Middleware 依賴 Security BC 的介面而非 Infrastructure 實作。

---

## IP 在系統中的流動

AlleyNote 的 IP 處理經過 NetworkHelper 集中萃取後，流向三個目的地：

```
NetworkHelper（統一萃取來源）
    │
    ├──▶ IpService（允許/拒絕清單）
    │        ├── ip_lists 資料表（永久保留 IP 規則）
    │        └── 用途：管理員手動設定允許或封鎖某個 IP
    │
    ├──▶ RateLimitMiddleware（速率限制金鑰）
    │        ├── Redis key "rate_limit:{ip}"（暫存，60 秒 TTL）
    │        └── 用途：短時間視窗的請求頻率限制
    │
    └──▶ ActivityLoggingService（審計日誌）
             ├── user_activity_logs.ip_address（永久保留）
             ├── 用途：稽核軌跡、取證分析
             └── SuspiciousActivityDetector（讀取日誌分析，待接線）
```

### IP 儲存能否統一？

`ip_lists` 和 `user_activity_logs` 都存 IP，但目的不同：

| 表格 | 存的是 | 生命週期 | 誰管理 |
|------|--------|---------|--------|
| `ip_lists` | IP 規則（允許/封鎖） | 永久，手動管理 | 管理員 |
| `user_activity_logs` | IP 證據（誰做了什麼） | 永久，自動記錄 | 系統 |

不建議統一儲存——兩者的生命週期管理方式不同（規則可被管理員刪除，日誌不可刪除），混在一起會讓查詢邏輯變複雜。

### SuspiciousActivityDetector

具備完整的異常 IP 分析邏輯（失敗率尖峰、多使用者共享 IP、可疑範圍），
但目前沒有任何程式碼呼叫它。未來可透過以下方式接線：

- **排程命令**：定期掃描活動日誌，偵測異常 IP 後自動加入封鎖清單
- **即時觸發**：在 `ActivityLoggingService` 記錄特定事件後同步呼叫

對應程式碼的 `App\Domains\Security\Services\SuspiciousActivityDetector`。
