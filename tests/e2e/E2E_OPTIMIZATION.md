# E2E 測試優化記錄

## 優化日期
2025-10-15

## 問題分析

### 執行時間過長
- 總共 77 個測試案例（60 個通過，17 個跳過）
- 20 個測試文件
- 平均每個測試約 20-30 秒（包括 retry 時間）
- 總執行時間約 25-30 分鐘

### Retry 機制
原配置：
```javascript
retries: process.env.CI ? 2 : 0  // CI 環境重試 2 次
actionTimeout: 10000              // 10 秒
navigationTimeout: 30000          // 30 秒
```

### Retry 發生的可能原因
1. **超時問題**：操作或頁面導航超過配置的時間限制
2. **元素選擇器問題**：元素未及時出現或選擇器不正確
3. **網路延遲**：API 請求回應時間不穩定
4. **頁面渲染延遲**：非同步內容載入較慢
5. **測試環境不穩定**：GitHub Actions runner 性能波動

## 優化方案

### 1. 減少 Retry 次數
```javascript
retries: process.env.CI ? 1 : 0  // 從 2 次降為 1 次
```
**理由**：
- 每次 retry 都會重新執行整個測試
- 2 次 retry 意味著最多執行 3 次（原始 + 2次重試）
- 降為 1 次可減少約 30% 的重試時間
- 仍保留一次重試機會以應對偶發性問題

### 2. 增加超時時間
```javascript
timeout: 60000,                  // 新增：每個測試最長 60 秒
actionTimeout: 15000,             // 從 10秒 增加到 15秒
navigationTimeout: 40000,         // 從 30秒 增加到 40秒
```
**理由**：
- 增加超時時間可減少因等待時間不足導致的不必要 retry
- GitHub Actions runner 可能比本地環境慢
- 頁面導航和資料載入需要更多時間緩衝

### 3. 保持其他配置
```javascript
fullyParallel: false,            // 維持循序執行，避免測試間衝突
workers: 1,                      // 維持單 worker，確保測試穩定性
```
**理由**：
- 測試間有資料依賴（使用共享資料庫）
- 並行執行可能導致測試不穩定
- 單 worker 雖然較慢但更可靠

## 預期效果

### 時間節省
- **Retry 減少**：假設 10% 測試需要 retry
  - 原本：10 個測試 × 2 次 retry × 30 秒 = 600 秒 (10 分鐘)
  - 優化後：10 個測試 × 1 次 retry × 30 秒 = 300 秒 (5 分鐘)
  - **節省約 5 分鐘**

- **超時減少**：更長的超時時間可能減少 5-10% 的不必要 retry
  - **額外節省約 1-2 分鐘**

### 總預期
- 優化前：約 25-30 分鐘
- 優化後：約 18-23 分鐘
- **總節省：約 20-25% 的執行時間**

## 進一步優化建議

如果仍需要加快測試速度，可考慮：

### 1. 選擇性並行執行
將獨立的測試分組並行執行：
```javascript
// 針對特定測試文件啟用並行
fullyParallel: true,
workers: 2,
```

### 2. 優化測試本身
- 減少不必要的 `page.waitForTimeout()`
- 使用更精確的選擇器
- 合併相似的測試案例
- 使用 `page.waitForLoadState('networkidle')` 替代固定等待

### 3. 分離測試集
將測試分為快速和慢速兩組：
```yaml
# .github/workflows/e2e-tests-fast.yml (關鍵路徑)
# .github/workflows/e2e-tests-slow.yml (定期執行)
```

### 4. 使用測試分片
```javascript
// 將測試分散到多個 worker
workers: 3,
shard: { total: 3, current: 1 }
```

## 監控指標

需要追蹤以下指標以評估優化效果：

1. **總執行時間**：E2E workflow 完整執行時間
2. **Retry 次數**：每次執行中發生 retry 的測試數量
3. **Retry 原因**：分析哪些測試經常 retry 及原因
4. **失敗率**：優化後是否影響測試成功率

## 回滾計劃

如果優化導致測試不穩定：

1. **恢復 retry 次數**：
   ```javascript
   retries: process.env.CI ? 2 : 0
   ```

2. **調整超時時間**：
   ```javascript
   actionTimeout: 20000,
   navigationTimeout: 50000,
   ```

3. **記錄失敗原因**：分析是配置問題還是測試本身問題

## 後續工作

1. ✅ 修改 playwright.config.js
2. ⏳ 提交並推送到遠端
3. ⏳ 等待 CI 執行完成
4. ⏳ 分析執行日誌，找出哪些測試在 retry
5. ⏳ 根據 retry 日誌進一步優化特定測試
6. ⏳ 更新 SKIPPED_TESTS.md，修復跳過的測試
