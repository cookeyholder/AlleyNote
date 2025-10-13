# 統計功能修復進度報告

> 📅 更新時間：2025-10-13  
> 📌 本報告追蹤統計頁面問題的修復進度

---

## 🐛 已發現的問題

### 1. StatisticsQueryService DI 配置錯誤 ✅ 已修復
- **錯誤**: `No entry or class found for 'db'`
- **原因**: `StatisticsServiceProvider` 使用 `$container->get('db')` 而非 `$container->get(PDO::class)`
- **修復**: 
  ```php
  // 修改前
  $db = $container->get('db');
  
  // 修改後
  $db = $container->get(PDO::class);
  ```
- **檔案**: `backend/app/Domains/Statistics/Providers/StatisticsServiceProvider.php`
- **狀態**: ✅ 已修復並通過 CI 測試

### 2. `/api/statistics/popular` 返回 500 錯誤 ❌ 待修復
- **錯誤**: 內部伺服器錯誤
- **API**: `GET /api/statistics/popular?start_date=2025-10-01&end_date=2025-10-13`
- **回應**:
  ```json
  {
    "success": false,
    "error": {
      "type": "internal_error",
      "message": "取得熱門內容統計失敗"
    }
  }
  ```
- **影響**: 熱門文章統計無法顯示
- **優先級**: 高
- **狀態**: ❌ 待調查

### 3. `/api/v1/activity-logs/login-failures` 返回 404 ❌ 待修復
- **錯誤**: 路由不存在
- **API**: `GET /api/v1/activity-logs/login-failures?start_date=2025-10-01&end_date=2025-10-13`
- **前端呼叫位置**: `frontend/js/pages/admin/statistics.js` 第 158 行
- **影響**: 登入失敗統計無法顯示
- **可能解決方案**:
  1. 實作該 API 端點
  2. 修改前端改用其他 API
  3. 使用模擬資料
- **優先級**: 中
- **狀態**: ❌ 待處理

### 4. `/api/statistics/charts/views/timeseries` 返回 401 ❌ 待修復
- **錯誤**: 認證驗證失敗
- **API**: `GET /api/statistics/charts/views/timeseries?start=2025-10-06&end=2025-10-13`
- **原因**: 可能的原因
  1. Token 在前幾個 API 呼叫中被標記為無效
  2. 中介軟體執行順序問題
  3. 該 controller 的 DI 配置錯誤（類似問題 1）
- **影響**: 流量趨勢圖表無法顯示
- **優先級**: 高
- **狀態**: ❌ 待調查

### 5. 訪問統計頁面後使用者被登出 ❌ 待修復
- **現象**: 點擊「系統統計」後，頁面會短暫顯示，然後自動導向首頁並登出
- **可能原因**: 
  1. 某個 API 錯誤導致前端認為 token 無效
  2. 錯誤處理邏輯問題
  3. 路由守衛誤判
- **影響**: 無法正常使用統計頁面
- **優先級**: 最高
- **狀態**: ❌ 待調查

---

## 🧪 測試結果

### 後端 API 測試
- ✅ `GET /api/statistics/overview` - 成功 (200)
- ❌ `GET /api/statistics/popular` - 失敗 (500)
- ❌ `GET /api/v1/activity-logs/login-failures` - 失敗 (404)
- ❌ `GET /api/statistics/charts/views/timeseries` - 失敗 (401)

### 前端 E2E 測試
- ❌ 系統統計頁面 › 應該顯示統計頁面標題
- ❌ 系統統計頁面 › 應該顯示統計卡片
- ❌ 系統統計頁面 › 應該顯示流量趨勢圖表
- ❌ 系統統計頁面 › 應該顯示熱門文章列表
- ❌ 其他統計相關測試

### CI 狀態
- ✅ PHP CS Fixer: 0/555 需修復
- ✅ PHPStan Level 10: 0 errors
- ✅ PHPUnit: 2,225 tests, 9,251 assertions (52 skipped)

---

## 📝 下一步行動計劃

### Phase 1: 調查錯誤原因 (當前階段)
- [ ] 1.1 檢查 `StatisticsController::getPopular` 的實作
- [ ] 1.2 檢查日誌找出 500 錯誤的詳細堆疊
- [ ] 1.3 驗證 `StatisticsChartController` 的 DI 配置
- [ ] 1.4 檢查 activity logs 路由配置

### Phase 2: 修復 API 端點
- [ ] 2.1 修復 `/api/statistics/popular` 的錯誤
- [ ] 2.2 實作或修正 `/api/v1/activity-logs/login-failures`
- [ ] 2.3 修復 `/api/statistics/charts/views/timeseries` 的認證問題
- [ ] 2.4 驗證所有 API 正常工作

### Phase 3: 修復前端問題
- [ ] 3.1 改善錯誤處理，避免自動登出
- [ ] 3.2 為失敗的 API 提供降級方案（模擬資料）
- [ ] 3.3 測試統計頁面完整流程

### Phase 4: 測試驗證
- [ ] 4.1 執行 E2E 測試
- [ ] 4.2 手動測試所有功能
- [ ] 4.3 確保 CI 全部通過

---

## 🔗 相關文件
- [系統狀態報告](./CURRENT_STATUS_REPORT.md)
- [統計功能 API 規格](./STATISTICS_API_SPEC.md)
- [統計功能待辦清單](./STATISTICS_TODO.md)
- [統計功能實作報告](./STATISTICS_IMPLEMENTATION_REPORT.md)

---

## 📊 修復進度

- ✅ 已修復: 1/5 (20%)
- ❌ 待修復: 4/5 (80%)

**整體狀態**: 🔴 進行中

---

> 💡 **備註**: 主要問題集中在 API 端點的錯誤處理和 DI 配置，修復後統計功能應可正常運作
