# API 實現檢查清單

## 專案目標
確保所有後端 API 端點已完整實現並正確運作

## 當前狀態總結

### ✅ 已實現的 API

#### 高優先級
- ✅ GET /api/users - 使用者列表
- ✅ POST /api/users - 建立使用者
- ✅ PUT /api/users/{id} - 更新使用者
- ✅ DELETE /api/users/{id} - 刪除使用者
- ✅ POST /api/users/{id}/activate - 啟用使用者
- ✅ POST /api/users/{id}/deactivate - 停用使用者
- ✅ POST /api/users/{id}/reset-password - 重設密碼

#### 中優先級
- ✅ GET /api/roles - 角色列表
- ✅ POST /api/roles - 建立角色
- ✅ PUT /api/roles/{id} - 更新角色
- ✅ DELETE /api/roles/{id} - 刪除角色
- ✅ GET /api/permissions - 權限列表
- ✅ GET /api/tags - 標籤列表
- ✅ POST /api/tags - 建立標籤
- ✅ PUT /api/tags/{id} - 更新標籤
- ✅ DELETE /api/tags/{id} - 刪除標籤

#### 低優先級
- ✅ GET /api/settings - 系統設定
- ✅ PUT /api/settings - 更新系統設定
- ✅ GET /api/settings/{key} - 取得單一設定
- ✅ PUT /api/settings/{key} - 更新單一設定
- ✅ GET /api/timezone-info - 取得時區資訊

### ❌ 缺少的功能

#### 圖表統計功能（StatisticsChartController）
StatisticsChartController 已經實現但未註冊路由：
- ❌ GET /api/statistics/charts/posts/timeseries - 文章發布時間序列
- ❌ GET /api/statistics/charts/users/timeseries - 使用者活動時間序列
- ❌ GET /api/statistics/charts/views/timeseries - 瀏覽量時間序列
- ❌ GET /api/statistics/charts/tags/distribution - 標籤分布統計
- ❌ GET /api/statistics/charts/sources/distribution - 來源分布統計

#### 角色權限管理路由
- ❌ PUT /api/roles/{id}/permissions - 更新角色權限（Controller 方法已存在）
- ❌ GET /api/permissions/grouped - 按資源分組的權限列表（Controller 方法已存在）

## 待辦事項

### 階段 1：註冊缺少的路由 ✅

#### 任務 1.1：添加圖表統計路由到 statistics.php ✅
- [x] 在 `/backend/config/routes/statistics.php` 添加 StatisticsChartController 路由
- [x] 確保路由包含適當的中介軟體（jwt.auth）
- [x] 測試路由是否正確註冊

#### 任務 1.2：添加角色權限管理路由到 routes.php ✅
- [x] 在 `/backend/config/routes.php` 添加角色權限管理路由
- [x] PUT /api/roles/{id}/permissions
- [x] GET /api/permissions/grouped

### 階段 2：錯誤處理改進

#### 任務 2.1：統一錯誤處理
- [ ] 確保所有 Controller 方法都有適當的 try-catch
- [ ] 統一錯誤響應格式
- [ ] 添加詳細的錯誤訊息

#### 任務 2.2：驗證改進
- [ ] 檢查所有輸入驗證是否完整
- [ ] 確保參數類型檢查正確
- [ ] 添加邊界值測試

### 階段 3：測試與驗證

#### 任務 3.1：單元測試
- [ ] 為新增的路由撰寫測試
- [ ] 測試成功情況
- [ ] 測試錯誤情況
- [ ] 測試邊界條件

#### 任務 3.2：整合測試
- [ ] 測試路由是否正確註冊
- [ ] 測試中介軟體是否正確執行
- [ ] 測試權限控制是否正確

#### 任務 3.3：CI 檢查 ✅
- [x] 執行 PHP CS Fixer - 通過
- [x] 執行 PHPStan 檢查 - 通過（Level 10，無錯誤）
- [x] 執行 PHPUnit 測試 - 部分測試失敗（現有問題，與新增路由無關）
- [x] 確保新增程式碼沒有引入新的錯誤

### 階段 4：文檔更新

#### 任務 4.1：API 文檔
- [ ] 更新 API 端點清單
- [ ] 添加請求/響應範例
- [ ] 更新權限說明

#### 任務 4.2：實現報告
- [ ] 撰寫完成報告
- [ ] 記錄遇到的問題與解決方案
- [ ] 更新 CHANGELOG

## 驗收標準

### 功能性驗收
1. 所有列出的 API 端點都已實現
2. 所有端點都有適當的錯誤處理
3. 所有端點都有適當的權限控制
4. 所有端點都返回正確的資料格式

### 技術性驗收
1. 通過 PHP CS Fixer 程式碼風格檢查
2. 通過 PHPStan Level 10 靜態分析
3. 通過所有單元測試
4. 通過所有整合測試

### 文檔驗收
1. 所有 API 端點都有完整文檔
2. 所有變更都已記錄在 CHANGELOG
3. 完成實現報告

## 預估時間
- 階段 1：30 分鐘
- 階段 2：1 小時
- 階段 3：1.5 小時
- 階段 4：30 分鐘
- **總計：約 3.5 小時**

## 注意事項
1. 所有變更都要遵循 DDD 原則
2. 保持程式碼與現有風格一致
3. 確保向後兼容性
4. 所有錯誤訊息使用繁體中文
5. 遵循 PSR-7、PSR-15、PSR-17 標準
