# AlleyNote公布欄網站建置規格書

## 1. 專案概述

### 1.1 專案目標

建立一個現代化的公布欄網站系統，採用 PHP 8.4.5 與 SQLite3 資料庫，透過 Docker 容器化技術在 Debian Linux 12 上執行，並使用 NGINX 作為網頁伺服器。此系統將提供完善的帳號管理、文章發布、權限控制與內容安全機制。

### 1.2 專案範圍

- 使用者管理與身分驗證系統
- 單位與群組管理功能
- 公告文章管理系統
- 附件上傳與管理功能
- 內容分類與標籤系統
- IP 存取控制機制
- 權限管理系統

## 2. 技術架構

### 2.1 技術堆疊

- **後端框架**：PHP 8.4.5
- **資料庫**：SQLite3
- **網頁伺服器**：NGINX
- **容器化平台**：Docker 與 Docker Compose
- **作業系統**：Debian Linux 12
- **前端技術**：
  - HTML5, CSS3, JavaScript
  - Tailwind CSS
  - Vue.js 或 React (視需求選用)
- **編輯器**：最新版 CKEditor
- **身分驗證**：JWT (JSON Web Tokens)

### 2.2 開發環境規格

#### 2.2.1 DevContainer 開發環境

本專案採用 Visual Studio Code DevContainer 技術，確保開發環境的一致性與可攜性：

1. **基礎環境**
   - 基於 PHP 8.4.5 官方映像檔
   - Debian Linux 12 作業系統
   - Visual Studio Code 開發環境

2. **開發工具整合**
   - PHP Debug 擴充功能
   - PHP IntelliSense
   - SQLite 資料庫工具
   - Git 整合工具
   - Docker 整合工具

3. **開發流程支援**
   - 熱重載（Hot Reload）支援
   - Xdebug 3.x 除錯工具
   - 程式碼品質工具（PHP_CodeSniffer, PHPStan）
   - 自動化測試工具（PHPUnit）

4. **環境一致性**
   - 與生產環境相同的 PHP 版本和擴充功能
   - 標準化的開發依賴套件
   - 一致的程式碼格式化規則
   - 統一的資料庫結構

### 2.3 系統架構圖

```
┌─────────────────────────────────────────────────────┐
│                   使用者 (瀏覽器)                     │
└───────────────────────┬─────────────────────────────┘
                        │
                        ▼
┌─────────────────────────────────────────────────────┐
│                  NGINX 網頁伺服器                    │
└───────────────────────┬─────────────────────────────┘
                        │
                        ▼
┌─────────────────────────────────────────────────────┐
│                 PHP 8.4.5 應用伺服器                 │
│  ┌───────────┐   ┌───────────┐   ┌───────────┐      │
│  │ 使用者模組 │   │ 文章模組  │   │ 權限模組  │      │
│  └───────────┘   └───────────┘   └───────────┘      │
│  ┌───────────┐   ┌───────────┐   ┌───────────┐      │
│  │ 附件模組  │   │ IP管理模組 │   │ 單位模組  │      │
│  └───────────┘   └───────────┘   └───────────┘      │
└───────────────────────┬─────────────────────────────┘
                        │
                        ▼
┌─────────────────────────────────────────────────────┐
│                  SQLite3 資料庫                     │
└─────────────────────────────────────────────────────┘
```

## 3. 系統功能與模組

### 3.1 使用者管理系統

#### 3.1.1 帳號功能
- 使用者註冊
- 使用者登入/登出
- 密碼重設機制
- 雙因素身分驗證 (選配)
- 使用者個人資料管理

#### 3.1.2 單位管理
- 單位建立與管理
- 使用者可隸屬於一或多個單位
- 單位管理者權限設定

#### 3.1.3 群組與權限管理
- 使用者群組建立與管理
- 群組權限定義與設定
- 權限包含：
  - 發表文章權限
  - 刪除文章權限
  - 設定置頂文章權限
  - 設定附件數量權限
  - 管理使用者權限
  - 管理單位權限
  - IP 黑白名單管理權限

### 3.2 文章管理系統

#### 3.2.1 文章基本功能
- 文章建立、編輯、刪除
- 文章預覽功能
- 文章流水編號與 UUID 主鍵設計
- 文章置頂功能
- 文章草稿功能
- 文章排程發布功能
- 文章搜尋功能：
  - 提供搜尋框讓使用者以關鍵字搜尋
  - 可搜尋標題與內容中出現的關鍵字
  - 搜尋結果可依照日期、單位、分類進行排序
  - 支援多重搜尋條件組合

#### 3.2.2 文章內容資訊
- 文章標題
- 文章內容 (整合 CKEditor)
- 發文者帳號
- 發文者 IP 位址
- 發文日期時間
- 修改日期時間
- 觀看人數計數
- 分類標籤 (多選)

#### 3.2.3 附件管理
- 多檔案上傳功能
- 附件數量限制：
  - 可由管理員針對每個單位設定允許的附件數量上限
  - 設定為 0 表示完全禁止附件上傳功能
  - 當單位附件數量設定為 0 時，該單位的所有文章將無法上傳附件
  - 前端介面將自動隱藏附件上傳相關控制項
  - API 將阻擋任何附件上傳請求
- 附件預覽功能
- 附件安全性檢查
- 檔案格式限制，僅允許上傳：
  - 圖片：JPG/JPEG、PNG
  - 文件：PDF
  - 辦公文件：EXCEL (.xlsx, .xls)、WORD (.docx, .doc)、ODT (.odt)
- 檔案大小限制，可由管理員設定

#### 3.2.4 分類標籤系統
- 標籤建立與管理
- 多層級分類系統
- 標籤搜尋功能

### 3.3 IP 管理系統

#### 3.3.1 IP 黑白名單
- IP 黑名單管理
- IP 白名單管理
- IP 範圍設定 (CIDR 表示法)
- IP 封鎖規則設定

#### 3.3.2 存取控制
- IP 位址記錄與追蹤
- 存取記錄日誌
- 異常存取偵測

### 3.4 系統管理功能

#### 3.4.1 系統設定
- 網站基本設定
- 文章設定 (預設置頂數量、首頁顯示數量等)
- 附件設定 (預設允許數量、檔案大小限制、允許檔案類型等)
- 時區設定 (管理員可以設定系統時區，影響所有日期時間顯示)

#### 3.4.2 系統監控
- 系統活動日誌
- 使用者活動監控
- 效能監控

## 4. 資料庫設計

### 4.1 資料庫實體關係圖 (ERD)

```
┌────────────┐      ┌────────────┐       ┌────────────┐
│   Users    │      │   Groups   │       │ Permissions │
├────────────┤      ├────────────┤       ├────────────┤
│ id         │      │ id         │       │ id         │
│ uuid       │◄─┐   │ uuid       │   ┌──►│ uuid       │
│ username   │  │   │ name       │   │   │ name       │
│ email      │  │   │ description│   │   │ description│
│ password   │  │   └──────┬─────┘   │   └────────────┘
│ created_at │  │          │         │          ▲
│ updated_at │  │          ▼         │          │
└────────────┘  │   ┌────────────┐   │   ┌──────┴─────┐
        ▲       │   │ User_Group │   │   │ Group_Perm │
        │       │   ├────────────┤   │   ├────────────┤
        │       │   │ user_id    │   │   │ group_id   │
┌───────┴────┐  │   │ group_id   │   │   │ perm_id    │
│ User_Unit  │  │   └────────────┘   │   └────────────┘
├────────────┤  │                    │
│ user_id    │──┘                    │
│ unit_id    │                       │
└────┬───────┘                       │
     │                               │
     ▼                               │
┌────────────┐                       │
│   Units    │                       │
├────────────┤                       │
│ id         │                       │
│ uuid       │                       │
│ name       │                       │
│ description│                       │
└────────────┘                       │
        ▲                            │
        │                            │
┌───────┴────┐    ┌────────────┐     │
│ IP_Lists   │    │   Posts    │     │
├────────────┤    ├────────────┤     │
│ id         │    │ id         │     │
│ uuid       │    │ uuid       │◄────┘
│ ip_address │    │ seq_number │
│ type       │    │ title      │
│ unit_id    │    │ content    │
└────────────┘    │ user_id    │
                  │ user_ip    │
                  │ created_at │
                  │ updated_at │
                  │ views      │
                  │ is_pinned  │
                  └─────┬──────┘
                        │
          ┌─────────────┼─────────────┐
          ▼             ▼             ▼
   ┌────────────┐ ┌────────────┐ ┌────────────┐
   │ Post_Tags  │ │ Attachments│ │ Post_Views │
   ├────────────┤ ├────────────┤ ├────────────┤
   │ post_id    │ │ id         │ │ id         │
   │ tag_id     │ │ uuid       │ │ uuid       │
   └──────┬─────┘ │ post_id    │ │ post_id    │
          │       │ filename   │ │ user_id    │
          │       │ filepath   │ │ user_ip    │
          ▼       │ filesize   │ │ view_date  │
   ┌────────────┐ └────────────┘ └────────────┘
   │    Tags    │
   ├────────────┤
   │ id         │
   │ uuid       │
   │ name       │
   │ parent_id  │
   └────────────┘
```

### 4.2 資料表詳細設計

#### 4.2.0 主鍵設計原則

本系統中所有資料表皆採用雙主鍵設計：
1. **UUID**：作為資料表的邏輯主鍵，格式為 RFC 4122 v4 標準的 UUID 字串，用於外部識別與 API 操作
2. **ID**：整數型自增欄位，作為資料表的技術主鍵，用於內部關聯與效能優化

此雙主鍵設計的優勢：
- 提高資料庫安全性，避免使用連續整數 ID 時的資料洩漏風險
- 便於分散式系統的資料同步與整合
- 支援多租戶架構的未來擴展
- 資料遷移時保持一致性
- 整數型 ID 可維持關聯表效能

#### 4.2.1 日期時間格式規範

本系統中所有的日期時間欄位必須採用 RFC 3339 格式儲存。RFC 3339 是一種國際標準的日期時間表示法，格式為：
```
YYYY-MM-DDThh:mm:ss.sssZ
```

其中：
- `YYYY-MM-DD` 為日期部分
- `T` 為日期和時間的分隔符號
- `hh:mm:ss.sss` 為時間部分，包含小時、分鐘、秒及毫秒
- `Z` 表示時區，可以是 `Z` (UTC 時間) 或 `+/-hh:mm` 格式的時區偏移

範例：
- `2025-04-10T15:30:00Z` (UTC 時間)
- `2025-04-10T23:30:00+08:00` (台北時間，UTC+8)

採用此標準有以下好處：
- 符合國際化規範
- 方便排序與比較
- 提供明確的時區資訊
- 與大多數現代程式語言及資料庫相容
- 支援 REST API 中的時間序列化

#### 4.2.2 使用者相關資料表

**users (使用者資料表)**
```
- id：整數，自增主鍵
- uuid：文字，UUID 格式的唯一識別碼
- username：文字，使用者名稱，唯一
- email：文字，電子郵件，唯一
- password：文字，加密後的密碼
- status：整數，帳號狀態 (1=啟用，0=停用)
- last_login：日期時間，最後登入時間
- created_at：日期時間，建立時間
- updated_at：日期時間，更新時間
```

**units (單位資料表)**
```
- id：整數，自增主鍵
- uuid：文字，UUID 格式的唯一識別碼
- name：文字，單位名稱
- description：文字，單位描述
- created_at：日期時間，建立時間
- updated_at：日期時間，更新時間
```

**user_unit (使用者-單位關聯資料表)**
```
- user_id：整數，外鍵參照 users.id
- unit_id：整數，外鍵參照 units.id
- created_at：日期時間，建立時間
```

**groups (群組資料表)**
```
- id：整數，自增主鍵
- uuid：文字，UUID 格式的唯一識別碼
- name：文字，群組名稱
- description：文字，群組描述
- created_at：日期時間，建立時間
- updated_at：日期時間，更新時間
```

**user_group (使用者-群組關聯資料表)**
```
- user_id：整數，外鍵參照 users.id
- group_id：整數，外鍵參照 groups.id
- created_at：日期時間，建立時間
```

**permissions (權限資料表)**
```
- id：整數，自增主鍵
- uuid：文字，UUID 格式的唯一識別碼
- name：文字，權限名稱
- description：文字，權限描述
- created_at：日期時間，建立時間
- updated_at：日期時間，更新時間
```

**group_permission (群組-權限關聯資料表)**
```
- group_id：整數，外鍵參照 groups.id
- permission_id：整數，外鍵參照 permissions.id
- created_at：日期時間，建立時間
```

#### 4.2.3 文章相關資料表

**posts (文章資料表)**
```
- id：整數，自增主鍵
- uuid：文字，UUID 格式的唯一識別碼，主鍵
- seq_number：整數，流水編號，唯一
- title：文字，文章標題
- content：文字，文章內容
- user_id：整數，外鍵參照 users.id
- user_ip：文字，發文者 IP 位址
- views：整數，觀看次數
- is_pinned：布林值，是否置頂
- status：整數，文章狀態 (1=發布，0=草稿)
- publish_date：日期時間，發布日期
- created_at：日期時間，建立時間
- updated_at：日期時間，更新時間
```

**tags (標籤資料表)**
```
- id：整數，自增主鍵
- uuid：文字，UUID 格式的唯一識別碼
- name：文字，標籤名稱
- parent_id：整數，父標籤 ID，可為 NULL
- created_at：日期時間，建立時間
- updated_at：日期時間，更新時間
```

**post_tag (文章-標籤關聯資料表)**
```
- post_id：整數，外鍵參照 posts.id
- tag_id：整數，外鍵參照 tags.id
- created_at：日期時間，建立時間
```

**attachments (附件資料表)**
```
- id：整數，自增主鍵
- uuid：文字，UUID 格式的唯一識別碼
- post_id：整數，外鍵參照 posts.id
- filename：文字，檔案原始名稱
- filepath：文字，檔案儲存路徑
- filesize：整數，檔案大小 (位元組)
- filetype：文字，檔案類型
- downloads：整數，下載次數
- created_at：日期時間，建立時間
- updated_at：日期時間，更新時間
```

**post_views (文章觀看記錄表)**
```
- id：整數，自增主鍵
- uuid：文字，UUID 格式的唯一識別碼
- post_id：整數，外鍵參照 posts.id
- user_id：整數，外鍵參照 users.id，可為 NULL
- user_ip：文字，訪問者 IP
- view_date：日期時間，觀看時間
```

#### 4.2.4 IP 管理相關資料表

**ip_lists (IP 黑白名單資料表)**
```
- id：整數，自增主鍵
- uuid：文字，UUID 格式的唯一識別碼
- ip_address：文字，IP 位址或 CIDR 範圍
- type：整數，名單類型 (1=白名單，0=黑名單)
- unit_id：整數，外鍵參照 units.id，可為 NULL
- description：文字，說明
- created_at：日期時間，建立時間
- updated_at：日期時間，更新時間
```

### 4.3 索引設計

為提升系統效能，建議在以下欄位建立索引：

- users 表：username, email, uuid
- posts 表：uuid, seq_number, user_id, publish_date
- post_tag 表：post_id, tag_id
- attachments 表：post_id
- ip_lists 表：ip_address, type
- user_unit 表：user_id, unit_id
- user_group 表：user_id, group_id
- group_permission 表：group_id, permission_id

## 5. API 設計

### 5.1 REST API 端點設計

#### 5.1.1 使用者相關 API

```
- POST   /api/auth/register        - 註冊新使用者
- POST   /api/auth/login           - 使用者登入
- POST   /api/auth/logout          - 使用者登出
- GET    /api/auth/user            - 獲取目前使用者資訊
- PUT    /api/auth/user            - 更新使用者資訊
- POST   /api/auth/password/reset  - 重設密碼
```

#### 5.1.2 單位與群組相關 API

```
- GET    /api/units                - 獲取所有單位
- POST   /api/units                - 建立新單位
- GET    /api/units/{id}           - 獲取單位詳情
- PUT    /api/units/{id}           - 更新單位
- DELETE /api/units/{id}           - 刪除單位
- GET    /api/units/{id}/users     - 獲取單位內使用者

- GET    /api/groups               - 獲取所有群組
- POST   /api/groups               - 建立新群組
- GET    /api/groups/{id}          - 獲取群組詳情
- PUT    /api/groups/{id}          - 更新群組
- DELETE /api/groups/{id}          - 刪除群組
- GET    /api/groups/{id}/users    - 獲取群組內使用者
- GET    /api/groups/{id}/permissions - 獲取群組權限
- PUT    /api/groups/{id}/permissions - 設定群組權限
```

#### 5.1.3 文章相關 API

```
- GET    /api/posts                - 獲取文章列表
- POST   /api/posts                - 建立新文章
- GET    /api/posts/{id}           - 獲取文章詳情
- PUT    /api/posts/{id}           - 更新文章
- DELETE /api/posts/{id}           - 刪除文章
- PUT    /api/posts/{id}/pin       - 置頂/取消置頂文章
- GET    /api/posts/{id}/attachments - 獲取文章附件
- GET    /api/tags/{tag_id}/posts  - 獲取特定標籤的文章列表
- GET    /api/tags/{tag_id}/posts/recent/{limit} - 獲取特定標籤的近期 n 篇文章
```

#### 5.1.4 附件相關 API

```
- POST   /api/attachments          - 上傳附件
- GET    /api/attachments/{id}     - 下載附件
- DELETE /api/attachments/{id}     - 刪除附件
```

#### 5.1.5 標籤相關 API

```
- GET    /api/tags                 - 獲取所有標籤
- POST   /api/tags                 - 建立新標籤
- PUT    /api/tags/{id}            - 更新標籤
- DELETE /api/tags/{id}            - 刪除標籤
```

#### 5.1.6 IP 管理相關 API

```
- GET    /api/ip-lists             - 獲取 IP 黑白名單
- POST   /api/ip-lists             - 新增 IP 規則
- DELETE /api/ip-lists/{id}        - 刪除 IP 規則
```

### 5.2 API 安全性設計

- 所有 API 採用 JWT (JSON Web Token) 進行身分驗證
- API 請求限流 (Rate Limiting) 機制
- CSRF 防護
- 請求參數驗證
- API 權限檢查
- 全站使用 HTTPS

## 6. 使用者介面設計

### 6.1 主要頁面與功能

- 首頁/公告列表頁
- 文章詳細頁面
- 使用者登入/註冊頁面
- 使用者個人資料頁面
- 文章發布/編輯頁面
- 管理者控制台
  - 使用者管理
  - 單位管理
  - 群組與權限管理
  - 文章管理
  - 標籤管理
  - IP 黑白名單管理
  - 系統設定

### 6.2 使用者體驗特色

- 響應式設計，支援桌面與行動裝置
- 無障礙設計 (WCAG 2.1 AA 標準)
- 黑暗模式支援
- 快速搜尋功能
- 分頁與篩選功能
- 文章瀏覽記錄
- 使用者通知系統

### 6.3 CKEditor 整合

- 整合最新版 CKEditor
- 自訂工具列按鈕
- 圖片上傳與管理功能
- 表格建立與編輯
- 程式碼區塊語法高亮
- 內容模板功能
- 支援 HTML 清理

### 6.4 附件管理介面

#### 6.4.1 全站附件管理功能

本系統提供完整的附件管理介面，供管理員集中管理所有文章的附件：

1. **全站附件總覽**
   - 以表格形式呈現所有附件資訊
   - 顯示文章標題、附件數量、檔案名稱、檔案大小 (KB)
   - 檔案類型圖示視覺識別
   - 附件上傳日期與下載次數

2. **統計與空間管理**
   - 顯示全站附件總數量
   - 顯示全站附件總佔用空間 (MB/GB)
   - 依文件類型的檔案數量與空間分析圖表
   - 依上傳時間的檔案數量趨勢圖

3. **批次操作功能**
   - 批次刪除選定的附件
   - 批次轉移附件至其他文章
   - 批次下載多個附件 (ZIP 壓縮)
   - 批次重新命名 (增加前綴/後綴)

#### 6.4.2 文章附件管理

1. **附件列表展示**
   - 文章標題下方顯示其所有附件
   - 清晰顯示每個附件的檔案大小 (KB)
   - 檔案類型圖示與預覽縮圖 (適用於圖片)
   - 上傳者資訊與上傳時間

2. **操作功能**
   - 直接從管理介面刪除特定附件
   - 附件預覽功能 (適用於圖片、PDF)
   - 附件直接下載功能
   - 附件描述編輯

3. **空間管理**
   - 顯示該文章附件總數量
   - 顯示該文章附件總佔用空間 (KB/MB)
   - 文章附件配額設定與使用率顯示
   - 當附件數量設定為 0 時：
     - 隱藏所有附件上傳相關控制項
     - 顯示「此單位不允許附件上傳」的提示訊息
     - 保留既有附件的檢視功能（如果之前上傳過的話）
     - 管理者可以刪除既有附件，但無法新增

#### 6.4.3 搜尋與篩選功能

1. **附件搜尋**
   - 依檔案名稱搜尋
   - 依檔案類型搜尋
   - 依上傳時間範圍搜尋
   - 依檔案大小範圍搜尋

2. **附件排序**
   - 依檔案大小排序
   - 依上傳時間排序
   - 依下載次數排序
   - 依檔案名稱排序

### 6.5 文章排序與篩選功能

#### 6.5.1 文章排序機制

系統提供多種文章排序選項，以滿足不同使用場景需求：

1. **依觀看數排序**
   - 提供依文章觀看次數排序的功能 (由高至低/由低至高)
   - 可結合時間範圍設定 (最近一週/一個月/一年內最熱門文章)
   - 熱門文章自動標示
   - 觀看數成長趨勢圖表化顯示

2. **其他排序選項**
   - 依發布日期排序
   - 依最後更新日期排序
   - 依標題字母順序排序
   - 依附件數量排序

#### 6.5.2 排序功能應用場景

1. **前台應用**
   - 首頁「熱門文章」區塊
   - 分類頁面內排序選項
   - 搜尋結果頁面排序選項

2. **後台應用**
   - 管理者統計分析
   - 熱門內容報表產生
   - 文章閱讀分析

#### 6.5.3 API 支援

1. **排序參數**
   - `sort` 參數：指定排序欄位 (views, publish_date, update_date, title)
   - `order` 參數：指定排序方向 (asc, desc)
   - `period` 參數：指定時間範圍 (day, week, month, year, all)

2. **範例請求**
   ```
   GET /api/posts?sort=views&order=desc&period=month&limit=10
   ```
   獲取最近一個月內觀看次數最多的 10 篇文章

3. **回應範例**
   ```json
   {
     "data": [
       {
         "id": "550e8400-e29b-41d4-a716-446655440001",
         "title": "重要公告：系統更新",
         "views": 2547,
         "publish_date": "2025-04-01T08:30:00+08:00",
         "author": "系統管理員",
         "summary": "系統將於本週進行重要更新...",
         "tags": ["系統公告", "更新"]
       },
       // ...更多文章
     ],
     "meta": {
       "total": 145,
       "per_page": 10,
       "current_page": 1,
       "last_page": 15
     }
   }
   ```

## 7. 部署架構

### 7.1 Docker 容器配置

```
version: '3.8'

services:
  nginx:
    image: nginx:latest
    ports:
      - "80:80"
      - "443:443"
    volumes:
      - ./nginx/conf.d:/etc/nginx/conf.d
      - ./ssl:/etc/nginx/ssl
      - ./public:/var/www/html/public
      - ./storage/app/public:/var/www/html/public/storage
    depends_on:
      - php
    networks:
      - app-network
      
  php:
    build:
      context: ./docker/php
      dockerfile: Dockerfile
    volumes:
      - .:/var/www/html
      - ./docker/php/php.ini:/usr/local/etc/php/php.ini
    networks:
      - app-network
      
  composer:
    image: composer:latest
    volumes:
      - .:/app
    command: install
    networks:
      - app-network

networks:
  app-network:
    driver: bridge
```

### 7.2 Dockerfile 配置 (PHP)

```dockerfile
FROM php:8.4.5-fpm

# 安裝相依套件
RUN apt-get update && apt-get install -y \
    libzip-dev \
    zip \
    unzip \
    git \
    curl \
    libicu-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    sqlite3 \
    libsqlite3-dev

# 安裝 PHP 擴充功能
RUN docker-php-ext-install pdo pdo_sqlite zip exif pcntl
RUN docker-php-ext-configure gd --with-freetype --with-jpeg
RUN docker-php-ext-install gd

# 安裝 Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 設定工作目錄
WORKDIR /var/www/html

# 設定權限
RUN chown -R www-data:www-data /var/www/html
```

### 7.3 NGINX 配置

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name example.com www.example.com;
    return 301 https://$host$request_uri;
}

server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name example.com www.example.com;
    
    ssl_certificate /etc/nginx/ssl/fullchain.pem;
    ssl_certificate_key /etc/nginx/ssl/privkey.pem;
    
    root /var/www/html/public;
    index index.php;
    
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    location ~ \.php$ {
        fastcgi_pass php:9000;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
    
    location ~ /\.(?!well-known).* {
        deny all;
    }
    
    location ~* \.(jpg|jpeg|gif|png|css|js|ico|xml)$ {
        expires 5d;
    }
    
    # 大型檔案上傳設定
    client_max_body_size 50M;
}
```

## 8. 安全與監控

### 8.1 資料安全

- 密碼使用 Argon2id 演算法加密
- 敏感資料加密儲存
- 定期資料庫備份
- 資料庫查詢防注入處理

### 8.2 應用程式安全

- 輸入資料驗證與清理
- XSS 防護
- CSRF 防護
- SQL 注入防護
- 檔案上傳安全性檢查
- 會話 (Session) 安全設定
- 適當的錯誤處理與日誌記錄

### 8.3 伺服器安全

- 限制 Docker 容器資源使用
- 最小權限原則
- 系統元件定期更新
- 防火牆設定
- 定期安全性掃描

### 8.4 IP 存取控制實作

- IP 黑名單阻擋機制
- IP 白名單允許機制
- IP 地區封鎖 (選配)
- DDOS 防護措施 (選配)

### 8.5 效能監控與最佳化

#### 8.5.1 效能監控架構

本系統採用多層次的效能監控架構，確保系統穩定運行並及時發現潛在問題：

1. **伺服器層級監控**
   - CPU 使用率監控：使用 Prometheus + Node Exporter
   - 記憶體使用率監控：記錄總體、使用中、快取、可用記憶體
   - 磁碟 I/O 效能：監控讀寫速度、IOPS 與延遲
   - 網路流量監控：追蹤進出流量、連線數、封包丟失率

2. **應用程式層級監控**
   - PHP-FPM 狀態監控：工作者程序數量、請求隊列長度
   - 請求執行時間追蹤：使用 Xdebug 或 Blackfire 分析
   - SQL 查詢效能分析：記錄緩慢查詢並提供最佳化建議
   - 異常與錯誤率追蹤：分類與統計各類型錯誤

3. **使用者體驗監控**
   - 頁面載入時間追蹤：使用 Real User Monitoring (RUM)
   - API 回應時間監控：記錄每個端點的平均、最大、最小回應時間
   - 前端效能指標：追蹤 First Contentful Paint、Time to Interactive 等指標

#### 8.5.2 監控工具與技術

1. **監控套件整合**
   - **Prometheus**：開源的監控與警報工具
   - **Grafana**：資料視覺化與儀表板平台
   - **cAdvisor**：容器資源使用分析
   - **Blackfire.io**：PHP 效能分析工具 (選配)

2. **效能資料收集**
   - 使用 StatsD 收集自訂指標
   - 使用標準 HTTP 標頭追蹤請求流程
   - 實作分散式追蹤系統 (OpenTelemetry)

3. **警報系統**
   - 多階段警報機制：警告、嚴重、緊急
   - 通知管道：電子郵件、SMS、專用通訊聊天室
   - 自動擴展機制觸發設定 (未來擴展)

#### 8.5.3 效能最佳化策略

1. **資料庫最佳化**
   - 索引效能分析與調整
   - 查詢快取策略
   - 分頁與批次處理大型資料集

2. **應用程式快取**
   - 實作 Redis 或 Memcached 快取層
   - 快取策略：頁面快取、API 回應快取、資料快取
   - 智能快取失效機制

3. **靜態資源最佳化**
   - 檔案壓縮與合併
   - 使用內容分發網路 (CDN)
   - 瀏覽器快取策略設定

4. **程式碼最佳化指南**
   - 使用非同步處理長時間任務
   - 延遲載入技術應用
   - 批次處理資料庫操作

### 8.6 系統日誌管理

#### 8.6.1 日誌架構與分類

本系統採用多層次的日誌管理架構，確保系統活動能被完整記錄並方便後續分析：

1. **系統日誌分類**
   - **系統運行日誌**：記錄系統啟動、關閉、容器狀態變更等資訊
   - **安全性日誌**：記錄登入嘗試、權限變更、敏感操作等安全相關事件
   - **錯誤日誌**：記錄應用程式錯誤、異常、警告等問題
   - **效能日誌**：記錄系統效能資訊，如請求處理時間、資源使用率
   - **存取日誌**：記錄 API 端點存取、資源使用情況

2. **日誌層級**
   - **DEBUG**：用於開發階段的詳細除錯資訊
   - **INFO**：一般操作資訊，確認系統正常運作
   - **WARNING**：不會立即影響系統運作但需要注意的警告
   - **ERROR**：發生錯誤但不影響主要功能的事件
   - **CRITICAL**：嚴重錯誤，可能導致系統部分功能無法使用

#### 8.6.2 日誌管理技術實作

1. **日誌格式標準化**
   - 採用結構化日誌格式 (JSON)
   - 每筆日誌包含：時間戳記、日誌層級、來源服務、事件類型、詳細訊息、關聯ID
   - 符合 RFC 5424 Syslog 協定標準
   - 範例：
     ```json
     {
       "timestamp": "2025-04-10T15:30:00.123Z",
       "level": "INFO",
       "service": "auth-service",
       "event_type": "user.login",
       "message": "使用者登入成功",
       "correlation_id": "550e8400-e29b-41d4-a716-446655440000",
       "user_id": "user-123",
       "ip_address": "192.168.1.1",
       "user_agent": "Mozilla/5.0..."
     }
     ```

2. **日誌收集與儲存**
   - **實時收集**：使用 Filebeat 收集容器與應用程式日誌
   - **集中處理**：透過 Logstash 進行日誌處理與轉換
   - **搜索引擎**：使用 Elasticsearch 儲存與索引日誌資料
   - **長期儲存**：重要日誌自動歸檔至 S3 相容儲存服務
   - **日誌輪替**：自動化日誌檔案輪替，避免磁碟空間耗盡

3. **日誌分析工具**
   - **視覺化儀表板**：使用 Kibana 建立自訂日誌分析儀表板
   - **異常偵測**：設定機器學習模型偵測異常模式
   - **自動化報表**：定期產生系統健康度與安全報表
   - **關聯分析**：透過關聯 ID 追蹤跨服務請求流程

#### 8.6.3 日誌管理策略

1. **保存政策**
   - 完整日誌保存 90 天
   - 安全性審計日誌保存 1 年
   - 重要系統事件日誌保存 3 年
   - 壓縮並加密歸檔儲存超過保存期限的日誌資料

2. **存取控制**
   - 系統管理員可查看所有日誌
   - 安全審計人員可查看安全性日誌
   - 開發人員可查看非敏感操作日誌
   - 所有日誌存取行為都會被記錄

3. **日誌監控與警報**
   - 設定關鍵詞與條件觸發即時警報
   - 多種警報管道：電子郵件、SMS、聊天室通知
   - 自動化回應機制：當偵測到特定攻擊模式時自動採取防護措施

4. **合規與稽核**
   - 支援 GDPR 相關日誌處理要求
   - 提供內建的日誌完整性驗證機制
   - 支援定期日誌稽核報表產生

### 8.7 CI/CD 架構規劃

#### 8.7.1 CI/CD 基本架構

本系統採用現代化的 CI/CD 流程，確保開發、測試與部署過程的自動化、一致性與可靠性：

1. **版本控制**
   - 使用 Git 作為版本控制系統
   - 採用 GitFlow 或 GitHub Flow 分支管理策略
   - 使用 GitHub 或 GitLab 託管程式碼

2. **持續整合 (CI)**
   - 每次程式碼提交自動觸發建構與測試
   - 平行執行單元測試、整合測試、靜態程式碼分析
   - 產生程式碼品質報告與測試覆蓋率報告

3. **持續部署 (CD)**
   - 自動化部署至開發、測試、預上線與正式環境
   - 基於 Docker 容器的一致性部署
   - 支援藍綠部署與金絲雀發布策略

#### 8.7.2 CI/CD 流程詳細說明

1. **開發工作流程**
   - 開發人員從主分支建立功能分支
   - 完成開發後提交合併請求 (Pull Request)
   - 自動執行程式碼審查與測試
   - 通過審查後合併至主分支

2. **自動化測試階段**
   - **靜態分析**：使用 PHP_CodeSniffer 與 PHPStan 進行程式碼品質分析
   - **單元測試**：使用 PHPUnit 執行單元測試
   - **整合測試**：測試元件之間的互動
   - **端對端測試**：使用 Cypress 或 Selenium 進行瀏覽器自動化測試
   - **安全性測試**：使用 OWASP ZAP 等工具進行安全性掃描

3. **部署流程**
   - **環境建構**：使用 Terraform 或其他基礎架構即程式碼 (IaC) 工具建置環境
   - **配置管理**：使用 Ansible 進行系統配置
   - **容器協調**：使用 Docker Compose 於測試環境部署，可擴展至 Kubernetes
   - **資料庫遷移**：自動執行結構變更與初始資料填充
   - **健康檢查**：部署後自動檢查系統可用性

#### 8.7.3 CI/CD 工具鏈

1. **核心工具**
   - **GitLab CI/CD** 或 **GitHub Actions**：工作流程自動化
   - **Docker Registry**：容器映像檔儲存庫
   - **SonarQube**：程式碼品質與安全性分析
   - **PHPUnit**：單元測試框架
   - **Cypress**：前端自動化測試

2. **監控與通知**
   - 整合 Slack 或 Microsoft Teams 進行部署通知
   - 與監控系統整合，提供部署前後的系統健康狀態比較
   - 自動產生部署報告與變更日誌

#### 8.7.4 部署策略

1. **環境階段**
   - **開發環境** (DEV)：開發人員持續整合與功能驗證
   - **測試環境** (QA)：由測試人員進行系統測試
   - **預上線環境** (Staging)：模擬正式環境，進行最終驗證
   - **正式環境** (Production)：使用者存取的實際系統

2. **部署模式**
   - **藍綠部署**：準備兩組相同環境，一個作為正式服務，另一個進行更新
   - **金絲雀發布**：逐步將流量導向新版本，監控系統指標
   - **回滾機制**：發生異常時能迅速回復至先前穩定版本

3. **發布管理**
   - 使用版本標籤進行發布追蹤
   - 維護詳細的變更日誌
   - 實施發布審批流程，控制重要環境的部署權限

## 12. 外部網站整合

### 12.1 公布欄作為子功能的架構設計

公布欄系統設計為可獨立運行，同時也能作為其他網站的子功能進行整合。整合機制主要透過以下方式實現：

#### 12.1.1 API 整合機制

1. **RESTful API 設計**
   - 所有 API 端點遵循 RESTful 設計原則
   - 回應格式統一為 JSON，支援 JSONP 跨域呼叫
   - 提供完整的 API 文件與互動式測試頁面 (Swagger UI)

2. **API 驗證與權限**
   - 支援 API Key 認證機制
   - OAuth2.0 授權流程支援
   - 支援 CORS 設定，允許指定網域的跨域請求

3. **快速整合套件**
   - 提供多種程式語言的客戶端函式庫：
     ```
     - PHP 客戶端函式庫
     - JavaScript/TypeScript 客戶端函式庫
     - Python 客戶端函式庫
     ```
   - 預建的整合元件：
     ```
     - 最新公告元件 (Vue/React/原生 JS)
     - 熱門公告元件
     - 公告搜尋元件
     ```

#### 12.1.2 外部網站呼叫範例

**JavaScript 客戶端使用範例**:
```javascript
// 初始化公布欄客戶端
const bulletinBoard = new BulletinBoardClient({
  apiKey: 'your-api-key',
  baseUrl: 'https://bulletin-api.example.com'
});

// 取得最新5篇公告
bulletinBoard.getPosts({ limit: 5, sort: 'publish_date' })
  .then(posts => {
    // 處理並顯示公告資料
    renderLatestPosts(posts);
  });

// 取得指定標籤的熱門公告
bulletinBoard.getPostsByTag('公告', { limit: 3, sort: 'views' })
  .then(posts => {
    // 處理標籤文章資料
    renderTagPosts(posts);
  });
```

**PHP 客戶端使用範例**:
```php
// 初始化公布欄客戶端
$bulletinBoard = new BulletinBoardClient([
  'apiKey' => 'your-api-key',
  'baseUrl' => 'https://bulletin-api.example.com'
]);

// 取得最新5篇公告
$latestPosts = $bulletinBoard->getPosts(['limit' => 5, 'sort' => 'publish_date']);

// 在網頁中顯示
foreach ($latestPosts as $post) {
  echo "<div class='post'>";
  echo "<h3><a href='{$post->url}'>{$post->title}</a></h3>";
  echo "<p>{$post->summary}</p>";
  echo "</div>";
}
```

#### 12.1.3 頁面嵌入與單一登入

1. **iframe 嵌入**
   - 提供可配置的 iframe 嵌入功能
   - 支援自適應高度調整
   - 支援主題樣式繼承

2. **單一登入集成**
   - 支援 SAML 2.0 身分驗證
   - 支援 JWT 令牌傳遞
   - OAuth2.0 授權委託

3. **深層連結**
   - 支援直接連結至特定文章、類別或搜尋結果
   - URL 參數通過主網站傳遞至公布欄子系統

### 12.2 外部公告顯示設定

1. **樣式適應**
   - 提供可自訂 CSS 變數以適應主網站樣式
   - 支援暗色/亮色模式自動切換
   - 回應式設計，適應不同容器尺寸

2. **元件設定選項**
   - 顯示欄位自訂 (標題、摘要、日期、作者、標籤)
   - 文章數量限制
   - 自動輪播選項
   - 排序方式選擇 (最新、最熱門、指定標籤)