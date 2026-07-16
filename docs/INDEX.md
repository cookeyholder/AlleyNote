# AlleyNote 文件索引

此檔為專案主動態文件的 canonical 入口。

## 適合對象

| 目錄 | 說明 | 適合對象 |
|------|------|----------|
| `decisions/` | 架構決策記錄 (ADR) | 後端開發者、架構師 |
| `domains/` | 領域概述與設計 | 後端開發者 |
| `architecture/` | 設計文件與模式說明 | 後端開發者 |
| `frontend/` | 前端架構與實作 | 前端開發者 |
| `guides/content-creators/` | 內容管理者操作手冊 | 內容管理者 |
| `guides/admin/` | 系統管理員指南 | 系統管理員 |
| `guides/developer/` | 開發者指南 | 後端開發者 |
| `guides/deployment/` | 部署與維運 | 系統管理員 |
| `api/` | API 文件 | API 整合者 |
| `runbooks/` | 開發與維運 runbook | 開發者、管理員 |
| `archive/` | 歷史歸檔文件 | 所有人 |

## Canonical 文件

| 主題 | 唯一可信來源 |
|------|-------------|
| 專案概覽 | [README.md](../README.md) |
| 本機安裝／開發流程 | [runbooks/01-開發環境.md](runbooks/01-開發環境.md) |
| 架構設計與決策 | [architecture/README.md](architecture/README.md) |
| 前端架構 | [frontend/README.md](frontend/README.md) |
| API 使用與參考 | [api/README.md](api/README.md) |
| 文件治理與審查清單 | [DOCUMENTATION_GOVERNANCE.md](DOCUMENTATION_GOVERNANCE.md) |
| 歷史紀錄 | [archive/README.md](archive/README.md) |

## 文件盤點（2026-04 整併）

| 路徑 | 分類 | 目的地／說明 |
|------|------|-------------|
| `README.md` | Canonical | 僅保留專案概覽，細節統一導向 `docs/INDEX.md` |
| `QUICK_START.md` | Canonical | 保留為精簡啟動頁，內容與 runbook 對齊 |
| `docs/runbooks/DEVELOPMENT.md` | Canonical | 本機開發／執行／測試唯一來源 |
| `docs/architecture/BACKEND_REFACTOR_2026-04.md` | Canonical | PR #78 架構重構後技術決策與擴充邊界唯一來源 |
| `docs/runbooks/CI_CD.md` | Canonical | CI 模擬與故障排查唯一來源 |
| `docs/runbooks/SECURITY.md` | Canonical | 安全標頭與密碼安全說明整併後唯一來源 |
| `docs/api/README.md` | Canonical | API 文件入口 |
| `docs/DOCUMENTATION_GOVERNANCE.md` | Canonical | 更新觸發規則、審查清單、OpenSpec 連動規範 |
| `docs/README.md` | Merge Target | 精簡成入口導引，避免重複維護 |
| `docs/STATISTICS_API_SPEC.md` | Archive | 已移至 `docs/archive/legacy-2026-04/` |
| `docs/STATISTICS_PAGE_README.md` | Archive | 已移至 `docs/archive/legacy-2026-04/` |
| `docs/STATISTICS_ENHANCEMENT_SUMMARY.md` | Archive | 已移至 `docs/archive/legacy-2026-04/` |
| `docs/TRAFFIC_TRACKING.md` | Archive | 已移至 `docs/archive/legacy-2026-04/` |
| `docs/PASSWORD_SECURITY.md` | Archive | 已移至 `docs/archive/legacy-2026-04/` |
| `docs/user-guide/password-security.md` | Archive | 已移至 `docs/archive/legacy-2026-04/` |
| `docs/CODE_REVIEW_REPORT*.md` | Archive | 已移至 `docs/archive/reports/code-review/` |
| `docs/implementation-summary-settings-integration.md` | Archive | 已移至 `docs/archive/legacy-2026-04/` |

## OpenSpec 連動規範

對每個 active OpenSpec change：

1. 在 proposal／design／tasks 標示受影響 canonical 文件。
2. 在同一實作範圍內同步更新對應文件。
3. 任務標記完成前，先驗證連結與指令可用性。

本次整併對應 change：
- `openspec/changes/consolidate-project-documentation`
