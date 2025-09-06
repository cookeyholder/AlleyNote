# AlleyNote 測試錯誤分析報告

生成時間：2025-09-06 21:31:15
PHPStan 等級：Level 10

## 📊 摘要統計
- 測試檔案數：144
- 原始檔案數：268
- PHPStan 錯誤總數：0
- 有錯誤的檔案數：0
- 最常見錯誤類型：none
- 測試品質評分：70/100

## ⏱️ 預估修復時間
- 總預估時間：0 分鐘
- 簡單修復：0 個 (約 0 分鐘)
- 中等修復：0 個 (約 0 分鐘)
- 困難修復：0 個 (約 0 分鐘)

## 🔍 錯誤類型分佈

## 💡 重構建議
### 🔴 修正測試反模式
**優先級：** High
**描述：** 在 tests/Database/Seeds/UserActivityLogsSeederTest.php 中發現 high_cyclomatic_complexity 反模式
**建議行動：** 將複雜的測試分割成多個較簡單的測試方法

### 🔴 修正測試反模式
**優先級：** High
**描述：** 在 tests/Unit/Domains/Security/Services/ActivityLoggingServiceTest.php 中發現 high_cyclomatic_complexity 反模式
**建議行動：** 將複雜的測試分割成多個較簡單的測試方法

### 🔴 修正測試反模式
**優先級：** High
**描述：** 在 tests/Unit/Shared/Cache/Repositories/MemoryTagRepositoryTest.php 中發現 high_cyclomatic_complexity 反模式
**建議行動：** 將複雜的測試分割成多個較簡單的測試方法

### 🔴 修正測試反模式
**優先級：** High
**描述：** 在 tests/Unit/Shared/Cache/Services/CacheGroupManagerTest.php 中發現 high_cyclomatic_complexity 反模式
**建議行動：** 將複雜的測試分割成多個較簡單的測試方法

### 🔴 修正測試反模式
**優先級：** High
**描述：** 在 tests/Unit/Infrastructure/Auth/Repositories/TokenBlacklistRepositoryTest.php 中發現 high_cyclomatic_complexity 反模式
**建議行動：** 將複雜的測試分割成多個較簡單的測試方法

### 🔴 修正測試反模式
**優先級：** High
**描述：** 在 tests/Unit/Infrastructure/Auth/Repositories/RefreshTokenRepositoryTest.php 中發現 high_cyclomatic_complexity 反模式
**建議行動：** 將複雜的測試分割成多個較簡單的測試方法

### 🔴 修正測試反模式
**優先級：** High
**描述：** 在 tests/Unit/Validation/ValidatorTest.php 中發現 high_cyclomatic_complexity 反模式
**建議行動：** 將複雜的測試分割成多個較簡單的測試方法

### 🔴 修正測試反模式
**優先級：** High
**描述：** 在 tests/Integration/Shared/Cache/Services/TaggedCacheIntegrationTest.php 中發現 high_cyclomatic_complexity 反模式
**建議行動：** 將複雜的測試分割成多個較簡單的測試方法

### 🔴 修正測試反模式
**優先級：** High
**描述：** 在 tests/Integration/Api/V1/AuthEndpointTest.php 中發現 high_cyclomatic_complexity 反模式
**建議行動：** 將複雜的測試分割成多個較簡單的測試方法

### 🔴 修正測試反模式
**優先級：** High
**描述：** 在 tests/Integration/Application/Controllers/Admin/TagManagementControllerTest.php 中發現 high_cyclomatic_complexity 反模式
**建議行動：** 將複雜的測試分割成多個較簡單的測試方法

### 🔴 修正測試反模式
**優先級：** High
**描述：** 在 tests/Integration/FileSystemBackupTest.php 中發現 high_cyclomatic_complexity 反模式
**建議行動：** 將複雜的測試分割成多個較簡單的測試方法

### 🔴 修正測試反模式
**優先級：** High
**描述：** 在 tests/Integration/AttachmentUploadTest.php 中發現 high_cyclomatic_complexity 反模式
**建議行動：** 將複雜的測試分割成多個較簡單的測試方法

### 🔴 修正測試反模式
**優先級：** High
**描述：** 在 tests/manual/test_routing_performance.php 中發現 high_cyclomatic_complexity 反模式
**建議行動：** 將複雜的測試分割成多個較簡單的測試方法

### 🔴 修正測試反模式
**優先級：** High
**描述：** 在 tests/Performance/SimpleUserActivityLogPerformanceTest.php 中發現 high_cyclomatic_complexity 反模式
**建議行動：** 將複雜的測試分割成多個較簡單的測試方法

## 🚨 檢測到的反模式
### UserActivityLogsSeederTest.php
- redundant_type_check (嚴重程度：low)
- high_cyclomatic_complexity (嚴重程度：high)

### StatisticsCacheServiceTest.php
- redundant_type_check (嚴重程度：low)

### ActivityLoggingServiceTest.php
- high_cyclomatic_complexity (嚴重程度：high)

### JwtTokenServiceInterfaceTest.php
- excessive_assertion_usage (嚴重程度：medium)

### RefreshTokenRepositoryInterfaceTest.php
- excessive_assertion_usage (嚴重程度：medium)
- too_many_test_methods (嚴重程度：medium)

### TokenBlacklistRepositoryInterfaceTest.php
- excessive_assertion_usage (嚴重程度：medium)
- too_many_test_methods (嚴重程度：medium)

### AuthenticationExceptionTest.php
- too_many_test_methods (嚴重程度：medium)

### TokenExpiredExceptionTest.php
- too_many_test_methods (嚴重程度：medium)

### RefreshTokenExceptionTest.php
- too_many_test_methods (嚴重程度：medium)

### TokenGenerationExceptionTest.php
- too_many_test_methods (嚴重程度：medium)

### AuthServiceTest.php
- redundant_type_check (嚴重程度：low)

### JwtTokenServiceTest.php
- too_many_test_methods (嚴重程度：medium)

### TokenBlacklistServiceTest.php
- too_many_test_methods (嚴重程度：medium)

### RefreshTokenTest.php
- too_many_test_methods (嚴重程度：medium)

### TokenPairTest.php
- too_many_test_methods (嚴重程度：medium)

### DeviceInfoTest.php
- excessive_assertion_usage (嚴重程度：medium)
- too_many_test_methods (嚴重程度：medium)

### JwtPayloadTest.php
- too_many_test_methods (嚴重程度：medium)

### TokenBlacklistEntryTest.php
- too_many_test_methods (嚴重程度：medium)

### CacheKeysTest.php
- too_many_test_methods (嚴重程度：medium)

### MemoryTagRepositoryTest.php
- high_cyclomatic_complexity (嚴重程度：high)

### CacheGroupManagerTest.php
- redundant_type_check (嚴重程度：low)
- high_cyclomatic_complexity (嚴重程度：high)

### CreatePostDTOTest.php
- too_many_test_methods (嚴重程度：medium)

### UpdatePostDTOTest.php
- too_many_test_methods (嚴重程度：medium)

### DTOValidationTest.php
- too_many_test_methods (嚴重程度：medium)

### FirebaseJwtProviderTest.php
- too_many_test_methods (嚴重程度：medium)

### TokenBlacklistRepositoryTest.php
- redundant_type_check (嚴重程度：low)
- too_many_test_methods (嚴重程度：medium)
- high_cyclomatic_complexity (嚴重程度：high)
- excessive_mocking (嚴重程度：medium)

### RefreshTokenRepositoryTest.php
- redundant_type_check (嚴重程度：low)
- too_many_test_methods (嚴重程度：medium)
- high_cyclomatic_complexity (嚴重程度：high)

### AttachmentServiceTest.php
- excessive_mocking (嚴重程度：medium)

### ValidationExceptionTest.php
- too_many_test_methods (嚴重程度：medium)

### ValidatorTest.php
- redundant_type_check (嚴重程度：low)
- too_many_test_methods (嚴重程度：medium)
- high_cyclomatic_complexity (嚴重程度：high)

### PasswordHashingTest.php
- redundant_type_check (嚴重程度：low)

### PostActivityLoggingTest.php
- excessive_mocking (嚴重程度：medium)

### TaggedCacheIntegrationTest.php
- high_cyclomatic_complexity (嚴重程度：high)

### ActivityLogApiIntegrationTest.php
- redundant_type_check (嚴重程度：low)

### PostControllerTest.php
- excessive_mocking (嚴重程度：medium)

### PostControllerTest.php
- excessive_mocking (嚴重程度：medium)

### AuthEndpointTest.php
- high_cyclomatic_complexity (嚴重程度：high)

### TagManagementControllerTest.php
- redundant_type_check (嚴重程度：low)
- high_cyclomatic_complexity (嚴重程度：high)
- excessive_mocking (嚴重程度：medium)

### FileSystemBackupTest.php
- high_cyclomatic_complexity (嚴重程度：high)

### AttachmentUploadTest.php
- high_cyclomatic_complexity (嚴重程度：high)

### test_multiple_routes.php
- deep_nesting (嚴重程度：medium)

### test_controller_simple.php
- redundant_type_check (嚴重程度：low)

### test_controller_integration.php
- redundant_type_check (嚴重程度：low)

### test_routing_performance.php
- high_cyclomatic_complexity (嚴重程度：high)

### test_middleware_system.php
- deep_nesting (嚴重程度：medium)

### SimpleUserActivityLogPerformanceTest.php
- high_cyclomatic_complexity (嚴重程度：high)
- deep_nesting (嚴重程度：medium)

## 📁 錯誤最多的檔案 (Top 10)

## 🎯 下一步行動建議
1. 優先修復高優先級的重構建議
2. 從錯誤數量最多的檔案開始修復
3. 專注於最常見的錯誤類型進行批量修復
4. 建立測試重構的 checklist 避免回歸
5. 考慮設定 PHPStan 基線檔案來管理逐步修復過程

---
*此報告由 AlleyNote 測試錯誤分析掃描器自動生成*
