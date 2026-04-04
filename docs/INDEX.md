# AlleyNote 文件索引

此檔為專案主動態文件的 canonical 入口。

## Canonical 文件

| 主題 | 唯一可信來源 |
|---|---|
| 專案概覽 | [README.md](../README.md) |
| 本機安裝／開發流程 | [docs/runbooks/DEVELOPMENT.md](runbooks/DEVELOPMENT.md) |
| 重構後後端技術細節（2026-04） | [docs/architecture/BACKEND_REFACTOR_2026-04.md](architecture/BACKEND_REFACTOR_2026-04.md) |
| CI/CD 與品質關卡 | [docs/runbooks/CI_CD.md](runbooks/CI_CD.md) |
| 安全控制與維運 | [docs/runbooks/SECURITY.md](runbooks/SECURITY.md) |
| API 使用與參考 | [docs/api/README.md](api/README.md) |
| 統計功能與流量追蹤 | [docs/features/statistics.md](features/statistics.md) |
| 文件治理與審查清單 | [docs/DOCUMENTATION_GOVERNANCE.md](DOCUMENTATION_GOVERNANCE.md) |
| 歷史紀錄 | [docs/archive/README.md](archive/README.md) |

## 文件盤點（2026-04 整併）

| 路徑 | 分類 | 目的地／說明 |
|---|---|---|
| `README.md` | Canonical | 僅保留專案概覽，細節統一導向 `docs/INDEX.md` |
| `QUICK_START.md` | Canonical | 保留為精簡啟動頁，內容與 runbook 對齊 |
| `docs/runbooks/DEVELOPMENT.md` | Canonical | 本機開發／執行／測試唯一來源 |
| `docs/architecture/BACKEND_REFACTOR_2026-04.md` | Canonical | PR #78 架構重構後技術決策與擴充邊界唯一來源 |
| `docs/runbooks/CI_CD.md` | Canonical | CI 模擬與故障排查唯一來源 |
| `docs/runbooks/SECURITY.md` | Canonical | 安全標頭與密碼安全說明整併後唯一來源 |
| `docs/api/README.md` | Canonical | API 文件入口 |
| `docs/features/statistics.md` | Canonical | 統計與流量追蹤整併後唯一來源 |
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
