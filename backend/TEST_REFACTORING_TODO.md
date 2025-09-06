# AlleyNote 測試重構待辦清單

## 📋 專案概況
- **目標**: 完成 15 個高優先級測試重構項目
- **當前進度**: 4/15 完成 (26.7%)
- **測試品質評分**: 70/100 → 目標 85-90/100
- **總測試數**: 1457 個測試
- **總斷言數**: 6659 個斷言

## ✅ 已完成項目 (4/15)

- [x] **UserActivityLogsSeederTest.php** ✅
  - 完成時間: 2025-09-06
  - 重構方式: 分割複雜測試方法，增加 6 個輔助方法
  - 測試狀態: 12 個測試全部通過，45 個斷言

- [x] **ActivityLoggingServiceTest.php** ✅
  - 完成時間: 2025-09-06
  - 重構方式: 重構複雜回調為類方法，增加 3 個驗證方法
  - 測試狀態: 14 個測試全部通過，39 個斷言

- [x] **MemoryTagRepositoryTest.php** ✅
  - 完成時間: 2025-09-06
  - 重構方式: 提取測試設定與斷言邏輯，增加 4 個輔助方法
  - 測試狀態: 17 個測試全部通過，51 個斷言

- [x] **CacheGroupManagerTest.php** ✅
  - 完成時間: 2025-09-06
  - 重構方式: 簡化複雜回調函式為命名類方法，增加 4 個輔助方法
  - 測試狀態: 19 個測試全部通過，41 個斷言

## 🔄 待完成項目 (11/15)

### 🔴 第一階段：核心認證系統 (優先級: 極高)

- [ ] **1. TokenBlacklistRepositoryTest.php** 🎯 **[下一個項目]**
  - 位置: `tests/Unit/Infrastructure/Auth/Repositories/TokenBlacklistRepositoryTest.php`
  - 問題: high_cyclomatic_complexity 反模式
  - 預估時間: 15 分鐘
  - 重構策略: 分析複雜的 token 驗證邏輯，提取為獨立的驗證方法
  - 相關功能: JWT token 黑名單管理

- [ ] **2. RefreshTokenRepositoryTest.php**
  - 位置: `tests/Unit/Infrastructure/Auth/Repositories/RefreshTokenRepositoryTest.php`
  - 問題: high_cyclomatic_complexity 反模式
  - 預估時間: 15 分鐘
  - 重構策略: 簡化 refresh token 生命周期測試
  - 相關功能: JWT token 刷新機制

### 🟡 第二階段：核心驗證與快取 (優先級: 高)

- [ ] **3. ValidatorTest.php**
  - 位置: `tests/Unit/Validation/ValidatorTest.php`
  - 問題: high_cyclomatic_complexity 反模式
  - 預估時間: 20 分鐘
  - 重構策略: 分割複雜的驗證規則測試
  - 相關功能: 核心驗證邏輯

- [ ] **4. TaggedCacheIntegrationTest.php**
  - 位置: `tests/Integration/Shared/Cache/Services/TaggedCacheIntegrationTest.php`
  - 問題: high_cyclomatic_complexity 反模式
  - 預估時間: 20 分鐘
  - 重構策略: 簡化快取標籤整合測試
  - 相關功能: 快取系統核心

### 🟢 第三階段：API 與控制器 (優先級: 中)

- [ ] **5. AuthEndpointTest.php**
  - 位置: `tests/Integration/Api/V1/AuthEndpointTest.php`
  - 問題: high_cyclomatic_complexity 反模式
  - 預估時間: 25 分鐘
  - 重構策略: 分割 API 端點測試，提取請求驗證邏輯
  - 相關功能: 認證 API 端點

- [ ] **6. TagManagementControllerTest.php**
  - 位置: `tests/Integration/Application/Controllers/Admin/TagManagementControllerTest.php`
  - 問題: high_cyclomatic_complexity 反模式
  - 預估時間: 20 分鐘
  - 重構策略: 簡化標籤管理控制器測試
  - 相關功能: 標籤管理功能

### 🔵 第四階段：安全與檔案系統 (優先級: 中)

- [ ] **7. PasswordHashingTest.php**
  - 位置: `tests/Security/PasswordHashingTest.php`
  - 問題: high_cyclomatic_complexity 反模式
  - 預估時間: 15 分鐘
  - 重構策略: 分割密碼雜湊演算法測試
  - 相關功能: 密碼安全機制

- [ ] **8. FileSystemBackupTest.php**
  - 位置: `tests/Integration/FileSystemBackupTest.php`
  - 問題: high_cyclomatic_complexity 反模式
  - 預估時間: 20 分鐘
  - 重構策略: 簡化檔案系統備份測試流程
  - 相關功能: 系統備份機制

- [ ] **9. AttachmentUploadTest.php**
  - 位置: `tests/Integration/AttachmentUploadTest.php`
  - 問題: high_cyclomatic_complexity 反模式
  - 預估時間: 20 分鐘
  - 重構策略: 分割檔案上傳測試案例
  - 相關功能: 檔案上傳功能

### ⚡ 第五階段：效能測試 (優先級: 低)

- [ ] **10. test_routing_performance.php**
  - 位置: `tests/manual/test_routing_performance.php`
  - 問題: high_cyclomatic_complexity 反模式
  - 預估時間: 15 分鐘
  - 重構策略: 簡化路由效能測試邏輯
  - 相關功能: 路由效能監控

- [ ] **11. SimpleUserActivityLogPerformanceTest.php**
  - 位置: `tests/Performance/SimpleUserActivityLogPerformanceTest.php`
  - 問題: high_cyclomatic_complexity 反模式
  - 預估時間: 15 分鐘
  - 重構策略: 分割使用者活動日誌效能測試
  - 相關功能: 活動日誌效能

## 📊 進度追蹤

### 時間預估
- **已完成**: 4 項 (約 60 分鐘)
- **待完成**: 11 項 (約 200 分鐘)
- **總計**: 15 項 (約 260 分鐘 = 4.3 小時)

### 每階段目標
1. **第一階段完成後**: 進度 40% (6/15)，認證系統測試品質大幅提升
2. **第二階段完成後**: 進度 60% (9/15)，核心功能測試穩定性提升
3. **第三階段完成後**: 進度 80% (11/15)，API 層測試覆蓋率優化
4. **第四階段完成後**: 進度 93% (13/15)，安全性與檔案系統測試完善
5. **第五階段完成後**: 進度 100% (15/15)，效能測試全面優化

## 🎯 成功標準

每個重構項目必須達到以下標準：
- ✅ 所有測試保持通過狀態
- ✅ 保持或提升斷言覆蓋率
- ✅ 符合 PHPStan Level 10 標準
- ✅ 採用一致的 Extract Method 重構模式
- ✅ 程式碼可讀性顯著提升
- ✅ 循環複雜度明顯降低

## 📝 重構日誌

### 2025-09-06
- ✅ 完成第一階段測試重構 (4/15 項目)
- ✅ 建立重構待辦清單
- 🎯 準備開始第一項待辦：TokenBlacklistRepositoryTest.php

---

**下一步行動**: 開始重構 TokenBlacklistRepositoryTest.php
