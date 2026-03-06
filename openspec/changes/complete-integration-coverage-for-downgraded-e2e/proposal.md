## Why

前一階段已將多個高環境依賴 E2E 套件降級（skip/smoke），以降低 flaky 與 false-negative。
本次需要把被降級案例的核心驗證責任完整補到整合測試，避免測試金字塔出現覆蓋缺口。

## What Changes

- 建立「降級 E2E → 整合測試」一對一補齊策略。
- 新增整合測試覆蓋以下責任：
  - 時區轉換與設定持久化（對應 `06-timezone`, `16-settings-integration`, `15-system-settings`）
  - 密碼規則矩陣（對應 `08-password-security` 中被降級規則驗證）
  - 統計資料聚合與熱門排序（對應 `11-statistics`）
  - 標籤/角色/權限關聯一致性（對應 `09-post-tags`, `09-role-management`, `10-tag-management`）
  - 富文本安全清洗與允許標籤渲染（對應 `15-post-detail`, `17-ckeditor-features`, `19-ckeditor-availability`）

## Capabilities

### Modified Capabilities

- `e2e-test-strategy`: 由「降級策略」擴展為「降級後整合測試補齊閉環」。

## Impact

- 影響範圍：`backend/tests/Integration/**`。
- 成果目標：降級 E2E 不再承擔規則驗證，且所有被降級能力均有整合測試承接。
