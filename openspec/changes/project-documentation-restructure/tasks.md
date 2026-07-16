## 1. 建立 ADR（architecture-decision-records）

- [x] 1.1 建立 `docs/decisions/` 目錄與 README.md 索引
- [x] 1.2 撰寫 ADR-001: Statistics DTO Analyzer 萃取
- [x] 1.3 撰寫 ADR-002: PostRepository 拆分
- [x] 1.4 撰寫 ADR-003: IP 邏輯統一至 NetworkHelper
- [x] 1.5 撰寫 ADR-004: JWT Auth Strategy 模式
- [x] 1.6 撰寫 ADR-005: TagController 內嵌頁面萃取
- [x] 1.7 撰寫 ADR-006: Frontend Admin BasePage
- [x] 1.8 撰寫 ADR-007: 安全與效能審計修復

## 2. 補齊領域文件（domain-documentation）

- [x] 2.1 建立 `docs/domains/README.md` 領域總覽
- [x] 2.2 建立 `docs/domains/01-認證授權領域.md`
- [x] 2.3 建立 `docs/domains/02-文章領域.md`
- [x] 2.4 建立 `docs/domains/03-附件領域.md`
- [x] 2.5 建立 `docs/domains/04-統計領域.md`
- [x] 2.6 建立 `docs/domains/05-安全領域.md`
- [x] 2.7 建立 `docs/domains/06-設定領域.md`

## 3. 補齊架構設計文件（design-documents）

- [x] 3.1 建立 `docs/architecture/README.md` 設計文件入口
- [x] 3.2 建立 `docs/architecture/01-統計分析器模式.md`
- [x] 3.3 建立 `docs/architecture/02-IP萃取統一.md`
- [x] 3.4 建立 `docs/architecture/03-JWT授權策略.md`

## 4. 補齊前端架構文件（frontend-architecture）

- [x] 4.1 建立 `docs/frontend/README.md` 前端架構入口
- [x] 4.2 建立 `docs/frontend/01-架構總覽.md`（取代 FRONTEND_INTERFACE_DESIGN）
- [x] 4.3 建立 `docs/frontend/02-管理後台基底類別.md`
- [x] 4.4 建立 `docs/frontend/03-API模組模式.md`

## 5. 內容管理者指南（content-creator-guide）

- [x] 5.1 建立 `docs/guides/content-creators/` 目錄與 README.md
- [x] 5.2 建立 `docs/guides/content-creators/01-管理後台使用手冊.md`（從 FRONTEND_USER_GUIDE.md 改寫）

## 6. 文件改名與目錄 README（繁體中文 + 數字 prefix）

- [x] 6.1 為 `docs/architecture/` `docs/decisions/` `docs/domains/` `docs/frontend/` `docs/api/` `docs/runbooks/` 建立 README.md
- [x] 6.2 為 `docs/guides/admin/` `docs/guides/developer/` `docs/guides/frontend/` `docs/guides/deployment/` `docs/guides/content-creators/` 建立 README.md
- [x] 6.3 將 `docs/guides/admin/` 下 4 檔改名（ADMIN_MANUAL → 01-管理員手冊 等）
- [x] 6.4 將 `docs/guides/developer/` 下 8 檔改名（DEVELOPER_GUIDE → 01-開發者指南 等）
- [x] 6.5 將 `docs/guides/frontend/` 下 6 檔改名（PROJECT_OVERVIEW → 01-專案概述 等）
- [x] 6.6 將 `docs/guides/deployment/` 下 3 檔改名（DEPLOYMENT → 01-部署流程 等）
- [x] 6.7 將 `docs/api/` 下 7 檔改名（API_USAGE_GUIDE → 01-API使用指南 等）
- [x] 6.8 將 `docs/runbooks/` 下 3 檔改名（DEVELOPMENT → 01-開發環境 等）
- [x] 6.9 將 `docs/frontend/` 下 2 檔改名（ckeditor5-integration → 04-CKEditor整合 等）
- [x] 6.10 將 `docs/architecture/BACKEND_REFACTOR_2026-04.md` 改名為 `04-後端重構紀錄.md`

## 7. 文件歸檔

- [x] 7.1 歸檔 `docs/domains/shared/ROUTING_SYSTEM_*.md` x5 至 `archive/legacy/`
- [x] 7.2 歸檔 `docs/domains/shared/CACHE_TAGGING_SYSTEM_*.md` x4 至 `archive/legacy/`
- [x] 7.3 歸檔 `docs/domains/shared/DDD_ARCHITECTURE_DESIGN.md` 至 `archive/legacy/`
- [x] 7.4 歸檔 `docs/domains/shared/ARCHITECTURE_AUDIT.md` 至 `archive/legacy/`
- [x] 7.5 歸檔 `docs/guides/frontend/FRONTEND_INTERFACE_DESIGN_SPECIFICATION.md` 至 `archive/legacy/`
- [x] 7.6 歸檔 `docs/features/batch-delete-posts.md` 至 `archive/legacy/`
- [x] 7.7 歸檔 `docs/features/statistics.md` 至 `archive/legacy/`
- [x] 7.8 歸檔 `docs/testing/*.md` x3 至 `archive/reports/`
- [x] 7.9 確認 `docs/FRONTEND_USER_GUIDE.md` 已改名搬遷（見任務 5.2）

## 8. 文件合併

- [x] 8.1 將 `docs/SECURITY_HEADERS.md` 內容合併至 `docs/runbooks/03-安全設定.md`
- [x] 8.2 將 `docs/domains/auth/USER_ACTIVITY_LOGGING_*.md` x3 合併為單一精簡文件
- [x] 8.3 更新 `docs/domains/shared/MULTI_LAYER_CACHE_SYSTEM.md` 補充 CacheInterface 萃取、LayeredCacheDriver、TaggedCacheManager、CacheMonitor 等新功能

## 9. 更新現有文件

- [x] 9.1 更新 `README.md` — 改為 5 種 persona 引導入口（後端、前端、內容管理、系統管理、API 整合）
- [x] 9.2 更新 `docs/INDEX.md` — 加入 ADR、領域、前端、架構入口，表格加入「適合對象」欄位
- [x] 9.3 更新 `docs/DOCUMENTATION_GOVERNANCE.md` — 加入 ADR 維護規則與 reader persona 審查項目

## 10. 驗證

- [x] 10.1 確認 `README.md` 可引導 5 種 persona 到正確的文件路徑
- [x] 10.2 確認所有 OVERVIEW.md 包含 reader persona 標註（無 OVERVIEW.md 檔案存在，跳過）
- [x] 10.3 確認所有 ADR 遵循統一格式
- [x] 10.4 確認 archive 目錄仍可獨立存取
- [x] 10.5 驗證所有跨文件的交叉引用連結完整性（README.md 引用路徑均存在；INDEX.md 連結 ../README.md 及子目錄 README 均存在）
