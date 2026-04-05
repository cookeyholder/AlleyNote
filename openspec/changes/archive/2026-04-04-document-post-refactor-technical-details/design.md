## Context

PR #78 已將後端核心架構切換為 `ExceptionRegistry + ApiExceptionInterface`、`ApiResource`、以及 `ApiTestCase` DSL。雖然程式行為與測試已完成重構，但目前文件主體仍以部署與操作為主，缺少「為何這樣設計、如何正確擴充、哪些反模式必須避免」的技術細節。此落差會讓新貢獻者難以理解重構成果，也提高後續變更破壞一致性的風險。

## Goals / Non-Goals

**Goals:**
- 將重構後的核心技術決策轉為 canonical 文件中的固定章節與入口。
- 在治理規範中定義重大重構的文件更新門檻，避免「程式碼已改、文件未跟上」。
- 讓 PR reviewer 可依明確清單驗證技術文件是否足夠支撐維護。

**Non-Goals:**
- 不在此 change 重新設計 backend 架構或 API 行為。
- 不新增 CI 工具鏈或自動化 lint 規則（僅先定義治理要求與人工審查準則）。

## Decisions

### 1. 以既有文件 capability 擴充，而非新增獨立 capability
- **Decision**: 將需求落在 `documentation-information-architecture` 與 `documentation-maintenance-governance`。
- **Rationale**: 需求本質是「文件結構 + 維護規範」演進，沿用既有能力可避免規格分散。
- **Alternative considered**: 新建 `post-refactor-tech-docs` capability。此作法會增加維護面，且與既有文件治理邊界重疊。

### 2. 技術細節採「最小必填矩陣」
- **Decision**: 對重大重構文件定義四類必填內容：架構邊界、例外處理策略、資料轉換策略、測試 DSL/隔離策略。
- **Rationale**: 以結構化最小集合降低遺漏，並讓 reviewer 能明確核對。
- **Alternative considered**: 僅要求「更新相關文件」的寬鬆描述。此作法可執行性弱，審查標準不一致。

### 3. OpenSpec 任務需綁定實際 canonical 路徑
- **Decision**: 在 tasks 要求列出具體更新檔案（如 `README.md`, `docs/runbooks/DEVELOPMENT.md`, `docs/architecture/*`）。
- **Rationale**: 讓 change 完成條件可追蹤、可驗證，並降低「口頭承諾有更新」的模糊空間。
- **Alternative considered**: 只在 PR 描述標註文件更新。此作法不易在 OpenSpec 流程中持續追蹤。

## Risks / Trade-offs

- **[Risk]** 文件章節增多，可能提升短期維護成本。  
  **Mitigation**: 使用最小必填矩陣與 canonical 入口，避免任意擴張。
- **[Risk]** 規範定義過於抽象，審查者仍無法一致判斷。  
  **Mitigation**: 在 spec scenario 以 MUST/WHEN/THEN 固化驗收條件。
- **[Trade-off]** 本 change 不導入自動化檢查，短期仍依賴 reviewer。  
  **Mitigation**: 先建立可執行規範，後續再評估導入 docs-check 自動化。

## Migration Plan

1. 更新 OpenSpec 規格（本 change）。
2. 依新規格補齊 canonical 文件內容與導覽入口。
3. 在後續重構 PR 套用新審查清單，確認流程可運作。
4. 若執行上發現負擔過高，再調整必填矩陣細節。

## Open Questions

- `docs/architecture/` 是否採單一 `backend-refactor.md`，或分拆為多篇（exceptions/resources/testing）？
- 是否需要在 PR template 新增「重大重構技術文件更新」固定欄位？
