## MODIFIED Requirements

### Requirement: 文件更新觸發規則
**變更**: 新增兩項觸發條件 — (1) 架構重構需對應更新 ADR，(2) 新增或修改 domain BC 需對應更新 OVERVIEW.md

#### Scenario: PR 涉及架構重構
- **WHEN** pull request 修改核心架構（如 Repository 拆分、策略模式導入）
- **THEN** 該 PR MUST 建立或更新對應的 `docs/decisions/ADR-*.md`

### Requirement: 文件審查清單
**變更**: 在審查清單中加入 reader persona 確認項目

#### Scenario: 審查者檢視觸發條件 PR
- **WHEN** 審查者評估符合文件觸發條件的 pull request
- **THEN** 審查者 MUST 確認範圍、正確性、連結有效性
- **AND** 審查者 MUST 確認文件的 reader persona 標註是否正確
