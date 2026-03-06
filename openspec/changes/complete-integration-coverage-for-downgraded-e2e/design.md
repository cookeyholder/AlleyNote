## Context

E2E 已聚焦於核心旅程，但規則型驗證仍需有後端層級的穩定保護。現有整合測試已涵蓋部分 statistics/security，仍缺少完整「降級對應矩陣」。

## Goals

- 為所有已降級 E2E 能力建立可重現、低環境耦合的整合測試。
- 讓測試責任邊界具備可追溯性（E2E smoke + integration correctness）。

## Non-Goals

- 不恢復已降級 E2E。
- 不引入新的產品功能。

## Strategy

1. **按能力補齊，不按頁面補齊**
   - 以服務/Repository/安全服務整合測試承接規則。

2. **建立對應矩陣**
   - `timezone/settings` → `SettingManagementService + TimezoneHelper`
   - `password-security` → `PasswordValidationService`
   - `statistics` → 現有 statistics integration 補強排序與範圍驗證
   - `tag/role/permissions` → `RoleRepository + TagRepository` 關聯整合
   - `ckeditor/post-detail` → `XssProtectionService` 富文本安全與允許標籤

3. **避免不必要 HTTP/中介層依賴**
   - 優先測 service/repository 的行為正確性，降低環境噪音。

## Validation

- 針對新測試檔執行 `phpunit --filter` 精準驗證。
- 確保新增測試可在 `IntegrationTestCase` 的 in-memory DB 下獨立運行。
