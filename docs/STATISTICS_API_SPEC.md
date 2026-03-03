# 統計功能 API 規格書

> 本文件列出統計功能所需的所有 API 端點，包括已實作和待實作的端點

## API 端點狀態

### ✅ 已實作的端點

1. **統計概覽** - `GET /api/statistics/overview`
   - 參數: `start_date`, `end_date`
   - 回傳: 總文章數、活躍使用者、新使用者、總瀏覽量

2. **文章統計** - `GET /api/statistics/posts`
   - 參數: 時間範圍參數
   - 回傳: 文章相關統計資料

3. **來源統計** - `GET /api/statistics/sources`
   - 參數: 時間範圍參數
   - 回傳: 流量來源分布

4. **使用者統計** - `GET /api/statistics/users`
   - 參數: 時間範圍參數
   - 回傳: 使用者活動統計

5. **熱門內容** - `GET /api/statistics/popular`
   - 參數: `start_date`, `end_date`, `limit`
   - 回傳: 熱門文章列表

6. **瀏覽量時間序列** - `GET /api/statistics/charts/views/timeseries`
   - 參數: `start_date`, `end_date`
   - 回傳: 瀏覽量趨勢資料

7. **登入失敗統計** - `GET /api/v1/activity-logs/login-failures`
   - 參數: `start_date`, `end_date`, `limit`
   - 回傳: 登入失敗次數、失敗帳號列表、趨勢資料

### ❌ 需要實作/修正的端點

#### 1. 統計概覽端點需要完整實作

**端點**: `GET /api/statistics/overview`

**目前問題**: 可能未完整實作或權限設定有問題

**請求參數**:
```json
{
  "start_date": "2025-01-01",  // 開始日期 (YYYY-MM-DD)
  "end_date": "2025-01-31"      // 結束日期 (YYYY-MM-DD)
}
```

**回應格式**:
```json
{
  "success": true,
  "data": {
    "total_posts": 150,        // 總文章數 (指定時間範圍內)
    "active_users": 45,        // 活躍使用者數
    "new_users": 12,           // 新使用者數
    "total_views": 8500        // 總瀏覽量
  }
}
```

**驗收標準**:
- [ ] 正確計算指定時間範圍內的統計資料
- [ ] 支援不同時間範圍 (日/週/月)
- [ ] 已登入使用者可以存取
- [ ] 回傳格式符合規格

---

#### 2. 熱門文章端點需要完整實作

**端點**: `GET /api/statistics/popular`

**目前問題**: 可能返回 404 或權限錯誤

**請求參數**:
```json
{
  "start_date": "2025-01-01",  // 開始日期
  "end_date": "2025-01-31",    // 結束日期
  "limit": "10"                 // 限制回傳數量
}
```

**回應格式**:
```json
{
  "success": true,
  "data": [
    {
      "id": 123,
      "title": "文章標題",
      "views": 1250,
      "slug": "article-slug",
      "published_at": "2025-01-15T10:30:00Z"
    }
  ]
}
```

**驗收標準**:
- [ ] 依瀏覽量排序回傳熱門文章
- [ ] 支援時間範圍篩選
- [ ] 支援數量限制參數
- [ ] 已登入使用者可以存取
- [ ] 回傳格式符合規格

---

#### 3. 流量時間序列端點需要完整實作

**端點**: `GET /api/statistics/charts/views/timeseries`

**目前問題**: 可能返回 404 或資料格式不正確

**請求參數**:
```json
{
  "start_date": "2025-01-01",  // 開始日期
  "end_date": "2025-01-31"     // 結束日期
}
```

**回應格式**:
```json
{
  "success": true,
  "data": [
    {
      "date": "2025-01-01",
      "views": 850,
      "visitors": 320
    },
    {
      "date": "2025-01-02",
      "views": 920,
      "visitors": 350
    }
  ]
}
```

**驗收標準**:
- [ ] 依日期分組回傳瀏覽量和訪客數
- [ ] 支援不同時間粒度 (時/日/週/月)
- [ ] 已登入使用者可以存取
- [ ] 資料格式適合用於 Chart.js 繪圖
- [ ] 回傳格式符合規格

---

#### 4. 登入失敗統計端點需要完整實作

**端點**: `GET /api/v1/activity-logs/login-failures`

**目前問題**: 可能返回 404、401 或資料結構不完整

**請求參數**:
```json
{
  "start_date": "2025-01-01T00:00:00Z",  // 開始時間 (ISO 8601)
  "end_date": "2025-01-31T23:59:59Z",    // 結束時間 (ISO 8601)
  "limit": "10"                           // 限制帳號列表數量
}
```

**回應格式**:
```json
{
  "success": true,
  "data": {
    "total": 156,              // 總失敗次數
    "accounts": [              // 失敗最多的帳號
      {
        "username": "user123",
        "email": "user@example.com",
        "count": 25
      }
    ],
    "trend": [                 // 時間趨勢資料
      {
        "date": "2025-01-01",
        "count": 12
      },
      {
        "date": "2025-01-02",
        "count": 8
      }
    ]
  }
}
```

**驗收標準**:
- [ ] 正確計算登入失敗總次數
- [ ] 回傳失敗最多的帳號列表
- [ ] 提供時間趨勢資料用於繪圖
- [ ] 支援時間範圍篩選
- [ ] 需要適當的權限控制 (管理員或有統計權限的使用者)
- [ ] 回傳格式符合規格

---

## 權限設定問題

### 問題描述
部分 API 端點返回 401 Unauthorized，表示權限設定可能過於嚴格或未正確配置。

### 需要檢查的項目
- [ ] 確認 JWT 中介軟體正確運作
- [ ] 確認統計相關端點的權限設定
- [ ] 檢查是否需要特定角色或權限
- [ ] 測試不同角色使用者的存取權限

### 建議權限設定
```
- 統計概覽: 所有已登入使用者可存取
- 熱門文章: 所有已登入使用者可存取  
- 流量統計: 所有已登入使用者可存取
- 登入失敗統計: 僅管理員或有統計權限的使用者可存取
- 管理功能 (刷新/清除快取): 僅管理員可存取
```

---

## 前端呼叫方式參考

### 時間範圍轉換邏輯
```javascript
// 前端計算時間範圍的方式
const endDate = new Date();
const startDate = new Date();

if (timeRange === 'day') {
  startDate.setDate(startDate.getDate() - 1);
} else if (timeRange === 'week') {
  startDate.setDate(startDate.getDate() - 7);
} else if (timeRange === 'month') {
  startDate.setDate(startDate.getDate() - 30);
}

const params = {
  start_date: startDate.toISOString().split('T')[0],
  end_date: endDate.toISOString().split('T')[0]
};
```

### API 呼叫範例
```javascript
// 載入統計概覽
const response = await apiClient.get('/statistics/overview', { 
  params: { 
    start_date: '2025-01-01', 
    end_date: '2025-01-31' 
  } 
});

// 載入熱門文章
const response = await apiClient.get('/statistics/popular', { 
  params: { 
    start_date: '2025-01-01', 
    end_date: '2025-01-31',
    limit: '10'
  } 
});

// 載入流量趨勢
const response = await apiClient.get('/statistics/charts/views/timeseries', { 
  params: { 
    start_date: '2025-01-01', 
    end_date: '2025-01-31' 
  } 
});

// 載入登入失敗統計
const response = await apiClient.get('/v1/activity-logs/login-failures', { 
  params: { 
    start_date: '2025-01-01T00:00:00Z', 
    end_date: '2025-01-31T23:59:59Z',
    limit: '10'
  } 
});
```

---

## 測試要求

### 單元測試
- [ ] 統計概覽端點測試
- [ ] 熱門文章端點測試
- [ ] 流量時間序列端點測試
- [ ] 登入失敗統計端點測試
- [ ] 權限控制測試
- [ ] 參數驗證測試

### 整合測試
- [ ] 完整的統計頁面載入流程
- [ ] 時間範圍切換功能
- [ ] 不同使用者角色的存取測試
- [ ] 錯誤處理和降級處理

### E2E 測試
- [ ] 登入後瀏覽統計頁面
- [ ] 切換不同時間範圍
- [ ] 檢視圖表資料
- [ ] 刷新統計資料

---

## 注意事項

1. **日期格式**:
   - 使用 ISO 8601 格式
   - 統一使用 UTC 時區
   - 支援只傳日期 (YYYY-MM-DD) 或完整時間戳

2. **效能考量**:
   - 大量資料查詢應該使用快取
   - 考慮使用資料庫索引優化查詢
   - 複雜統計可考慮背景處理

3. **錯誤處理**:
   - 提供清楚的錯誤訊息
   - 前端應該有降級處理 (使用模擬資料或顯示錯誤提示)
   - 記錄詳細的錯誤日誌

4. **資料一致性**:
   - 確保統計資料與實際資料一致
   - 定期刷新統計快取
   - 提供手動刷新功能

---

## 相關檔案

### 後端
- `/backend/config/routes/statistics.php` - 統計路由定義
- `/backend/config/routes/activity-logs.php` - 活動日誌路由定義
- `/backend/src/Application/Controllers/Api/V1/StatisticsController.php`
- `/backend/src/Application/Controllers/Api/V1/StatisticsChartController.php`
- `/backend/src/Application/Controllers/Api/V1/ActivityLogController.php`

### 前端
- `/frontend/js/pages/admin/statistics.js` - 統計頁面主要邏輯
- `/frontend/js/api/modules/statistics.js` - 統計 API 客戶端
- `/frontend/admin/statistics.html` - 統計頁面 HTML

---

## 更新記錄

- 2025-10-12: 建立 API 規格書，列出所有需要實作的端點
