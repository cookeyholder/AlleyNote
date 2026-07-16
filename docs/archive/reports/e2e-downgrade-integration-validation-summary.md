# 降級 E2E 對應 Integration 驗證摘要

## 目的

此文件整理「已降級（或部分降級）E2E」與「對應 Integration 測試」的承接關係，作為測試金字塔重構後的可追溯依據。

參考變更：

- `openspec/changes/refactor-e2e-to-integration-test-pyramid`
- `openspec/changes/complete-integration-coverage-for-downgraded-e2e`

## 對應矩陣

| 降級 E2E 能力/檔案                                                                                                                             | 降級內容                                                             | 對應 Integration 測試                                                                                                                                                                                      | 狀態        |
| ---------------------------------------------------------------------------------------------------------------------------------------------- | -------------------------------------------------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ----------- |
| `tests/e2e/tests/06-timezone.spec.js`                                                                                                          | 整個 suite 降級（`describe.skip`），時間轉換與儲存正確性交由後端驗證 | `backend/tests/Integration/Settings/SettingTimezoneIntegrationTest.php`                                                                                                                                    | ✅ 已補齊   |
| `tests/e2e/tests/08-password-security.spec.js`                                                                                                 | 規則矩陣案例（弱密碼/強密碼/使用者資訊關聯）改由後端規則驗證承接     | `backend/tests/Integration/Security/PasswordPolicyIntegrationTest.php`                                                                                                                                     | ✅ 已補齊   |
| `tests/e2e/tests/09-post-tags.spec.js` + `tests/e2e/tests/10-tag-management.spec.js` + `tests/e2e/tests/09-role-management.spec.js`            | 高資料依賴的標籤/角色關聯場景部分降級                                | `backend/tests/Integration/Auth/RoleTagRelationIntegrationTest.php`                                                                                                                                        | ✅ 已補齊   |
| `tests/e2e/tests/11-statistics.spec.js`                                                                                                        | 多數案例以 `test.skip` 避免資料集依賴造成不穩定                      | `backend/tests/Integration/Statistics/StatisticsRepositoryIntegrationTest.php`、`backend/tests/Integration/Statistics/PostStatisticsRepositoryIntegrationTest.php`（另有既有 statistics integration 套件） | ✅ 已覆蓋   |
| `tests/e2e/tests/13-batch-delete-posts.spec.js`                                                                                                | 保留核心流程，邊界/資料正確性案例降級                                | `backend/tests/Integration/Http/PostControllerTest.php`（刪除 API 行為）                                                                                                                                   | ✅ 已覆蓋   |
| `tests/e2e/tests/15-system-settings.spec.js` + `tests/e2e/tests/16-settings-integration.spec.js`                                               | 設定持久化、跨模組生效等高耦合案例降級                               | `backend/tests/Integration/Settings/SettingTimezoneIntegrationTest.php`                                                                                                                                    | ✅ 已補齊   |
| `tests/e2e/tests/15-post-detail.spec.js` + `tests/e2e/tests/17-ckeditor-features.spec.js` + `tests/e2e/tests/19-ckeditor-availability.spec.js` | 富文本安全與渲染正確性改由後端安全清理承接                           | `backend/tests/Integration/Security/RichTextSecurityIntegrationTest.php`                                                                                                                                   | ✅ 已補齊   |
| `tests/e2e/tests/14-admin-pages-comprehensive.spec.js`                                                                                         | 整體巡檢 suite 降級（`describe.skip`），避免重複導覽巡檢             | 不對應 integration（此類屬 UI 導覽 smoke，保留於 E2E 層）                                                                                                                                                  | ✅ 設計符合 |

## 本輪驗證命令與結果

在 `backend/` 執行：

```bash
php vendor/bin/phpunit \
  tests/Integration/Settings/SettingTimezoneIntegrationTest.php \
  tests/Integration/Security/PasswordPolicyIntegrationTest.php \
  tests/Integration/Auth/RoleTagRelationIntegrationTest.php \
  tests/Integration/Security/RichTextSecurityIntegrationTest.php \
  tests/Integration/Statistics/StatisticsRepositoryIntegrationTest.php \
  tests/Integration/Statistics/PostStatisticsRepositoryIntegrationTest.php \
  tests/Integration/Http/PostControllerTest.php
```

結果：

- `Tests: 45`
- `Assertions: 251`
- `Failures: 0`
- `PHPUnit Warnings: 1`（No code coverage driver available，非功能失敗）

## 結論

降級 E2E 的規則/資料正確性責任，已由對應 Integration 測試承接並通過驗證；E2E 可維持核心旅程 smoke 定位，降低環境資料耦合造成的 CI 波動。

## 測試分層準則

- 放在 E2E（`tests/e2e/tests`）
  - 使用者關鍵旅程：登入/登出、主要導覽、核心 CRUD 主流程。
  - 跨頁面互動與權限導向行為（例如未授權重導）。
  - Smoke 型 UI 可用性驗證（元素可見、流程可走通）。
- 放在 Integration（`backend/tests/Integration`）
  - 規則矩陣與資料正確性（密碼政策、時區轉換、統計聚合與排序）。
  - Repository/Service 層關聯一致性（角色/權限、標籤關聯、設定持久化）。
  - 富文本安全清理、允許標籤保留等非 UI 視覺性斷言。
- 判斷原則
  - 若失敗原因主要取決於資料狀態或商業規則，優先放 Integration。
  - 若失敗原因主要取決於瀏覽器互動與跨頁流程，放 E2E。
  - 同一能力避免在 E2E 重複做深度規則驗證，E2E 保留「流程通」即可。
