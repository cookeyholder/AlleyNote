## Why

AlleyNote 歷經 7 次重大重構後，程式碼架構已大幅演進（Statistics Analyzer、PostRepository 拆分、IP 統一、JWT Strategy 模式、BaseAdminPage 等），但 `docs/` 下的 69 份 Markdown 文件仍停留在重構前的狀態。

進一步調查發現三個關鍵問題：

1. **讀者對象不明確** — 文件沒有區分後端開發者、前端開發者、內容管理者、系統管理員、API 整合者五種角色，所有人看到同一份索引
2. **42% 的文件是 orphan** — 69 份中有 29 份沒有人知道該給誰看或該放在哪裡（domains/shared/ 下已廢棄的路由系統、快取標籤系統文件佔了 11 份）
3. **5 份文件與實際程式碼嚴重脫節** — FRONTEND_INTERFACE_DESIGN 有 6 處重大落差（圖示方案、CKEditor 版本、角色選單分離等），FRONTEND_USER_GUIDE 名稱誤導非技術管理者，SECURITY_HEADERS 孤立於根目錄

## What Changes

### 結構重整
- **5 種讀者分流**：INDEX.md 與 README.md 依後端開發者、前端開發者、內容管理者、系統管理員、API 整合者提供不同入口
- **README.md 作為目錄頁**：每個子目錄以 README.md 作為概覽入口，GitHub 瀏覽時自動顯示
- **繁體中文命名**：所有文件使用繁體中文檔名 + 數字 prefix（`01-統計分析器模式.md`），`ls` 排序即為閱讀順序

### 新建文件（約 27 份）
- `docs/decisions/` — 7 份 ADR + README.md 索引
- `docs/architecture/` — 3 份設計文件（Analyzer、IP、Auth Strategy）+ README.md
- `docs/domains/` — 6 份領域概述 + README.md
- `docs/frontend/` — 3 份架構文件（架構總覽、BaseAdminPage、API Modules）+ README.md
- `docs/guides/content-creators/` — 取代 FRONTEND_USER_GUIDE.md

### 文件改名搬遷（約 31 份）
- `guides/` 下 23 份 + README.md 改繁體中文名
- `api/` 下 7 份改繁體中文名
- `runbooks/` 下 3 份改繁體中文名
- `frontend/` 下 2 份改繁體中文名

### 文件取代
- FRONTEND_INTERFACE_DESIGN_SPECIFICATION.md → archive/，由 `frontend/` 架構文件取代

### 文件合併
- SECURITY_HEADERS.md → 合併至 `runbooks/03-安全設定.md`
- USER_ACTIVITY_LOGGING_*.md 3 檔合併為 1 份領域文件

### 文件歸檔（約 13 份）
- `domains/shared/` 下路由系統 5 檔、快取標籤系統 4 檔、DDD 設計、架構審計
- `features/` 下 2 檔
- `testing/` 下 3 檔

## Capabilities

### New Capabilities
- `architecture-decision-records`: 為 7 次重構建立 ADR，記錄決策背景、替代方案與取捨
- `domain-documentation`: 為 6 個 Bounded Context 建立領域概述文件
- `frontend-architecture`: 前端 SPA 架構、BaseAdminPage 生命週期、API Modules 模式說明
- `design-documents`: Architecture 層的 Analyzer/IP/Auth 三份設計文件
- `content-creator-guide`: 內容管理者使用手冊，取代 FRONTEND_USER_GUIDE.md

### Modified Capabilities
- `documentation-information-architecture`: 更新文件拓樸結構，加入 ADR/領域/前端/內容管理者分類，明確定義各目錄的閱讀對象，規範 README.md 為目錄頁、繁體中文命名規則
- `documentation-maintenance-governance`: 更新文件更新觸發規則，納入 ADR 維護要求與 reader persona 分流檢查

## Impact

### 新增目錄與檔案
- `docs/decisions/README.md` + 7 份 ADR
- `docs/architecture/README.md` + 3 份設計文件
- `docs/domains/README.md` + 6 份領域概述
- `docs/frontend/README.md` + 3 份架構文件
- `docs/guides/content-creators/README.md` + `01-管理後台使用手冊.md`

### 改名搬遷（繁體中文 + 數字 prefix）
- `docs/guides/admin/` 4 檔 → `01-管理員手冊.md` 等
- `docs/guides/developer/` 8 檔 → `01-開發者指南.md` 等
- `docs/guides/frontend/` 6 檔 → `01-專案概述.md` 等
- `docs/guides/deployment/` 3 檔 → `01-部署流程.md` 等
- `docs/api/` 7 檔 → `01-API使用指南.md` 等
- `docs/runbooks/` 3 檔 → `01-開發環境.md` 等
- `docs/frontend/` 2 檔 → `04-CKEditor整合.md`、`05-通知系統.md`
- `docs/architecture/BACKEND_REFACTOR_2026-04.md` → `04-後端重構紀錄.md`

### 更新現有文件
- `README.md` — 改為 5 種 persona 引導入口
- `docs/INDEX.md` — 更新入口導覽
- `docs/DOCUMENTATION_GOVERNANCE.md` — 加入 ADR 維護規則與 persona 審查項目

### 歸檔
- `docs/domains/shared/ROUTING_SYSTEM_*.md` x5 → `archive/legacy/`
- `docs/domains/shared/CACHE_TAGGING_SYSTEM_*.md` x4 → `archive/legacy/`
- `docs/domains/shared/DDD_ARCHITECTURE_DESIGN.md` → `archive/legacy/`
- `docs/domains/shared/ARCHITECTURE_AUDIT.md` → `archive/legacy/`
- `docs/guides/frontend/FRONTEND_INTERFACE_DESIGN_SPECIFICATION.md` → `archive/legacy/`
- `docs/features/batch-delete-posts.md` → `archive/legacy/`
- `docs/features/statistics.md` → `archive/legacy/`
- `docs/testing/*.md` x3 → `archive/reports/`
- `docs/FRONTEND_USER_GUIDE.md` → 改名搬至 `guides/content-creators/`
- `docs/SECURITY_HEADERS.md` → 合併至 `runbooks/`

### 相依檔案
- `openspec/specs/documentation-information-architecture/spec.md` — delta spec
- `openspec/specs/documentation-maintenance-governance/spec.md` — delta spec
- `docs/INDEX.md` — 所有連結需更新對應新檔名
- `README.md` — 所有連結需更新對應新檔名
