# AlleyNote 統計功能使用指南

## 📊 功能概覽

AlleyNote 統計系統提供全面的資料分析功能，幫助您深入了解網站的使用情況和內容表現。

### 主要功能

- **📈 統計概覽**：整體網站數據概況
- **📝 文章統計**：文章發布和閱讀分析
- **🌐 來源分析**：流量來源追蹤
- **👥 使用者統計**：用戶活動分析
- **🔥 熱門內容**：最受歡迎內容排行
- **⚡ 即時快照**：實時數據監控

## 🚀 快速開始

### API 基礎資訊

- **基礎 URL**: `/api/statistics`
- **認證方式**: JWT Bearer Token
- **回應格式**: JSON
- **支援方法**: GET

### 基本使用範例

```bash
# 取得統計概覽
curl -H "Authorization: Bearer YOUR_JWT_TOKEN" \
     "http://localhost/api/statistics/overview?period_type=daily"

# 取得文章統計
curl -H "Authorization: Bearer YOUR_JWT_TOKEN" \
     "http://localhost/api/statistics/posts?start_date=2024-01-01&end_date=2024-01-31"

# 取得熱門內容
curl -H "Authorization: Bearer YOUR_JWT_TOKEN" \
     "http://localhost/api/statistics/popular?limit=10"
```

## 📋 API 端點詳細說明

### 1. 統計概覽 - `/api/statistics/overview`

**功能**：取得網站整體統計數據

**參數**：
- `period_type` (可選): `daily`|`weekly`|`monthly` - 統計期間類型
- `start_date` (可選): `YYYY-MM-DD` - 開始日期
- `end_date` (可選): `YYYY-MM-DD` - 結束日期

**回應範例**：
```json
{
  "success": true,
  "data": {
    "period": {
      "type": "daily",
      "start_date": "2024-01-01",
      "end_date": "2024-01-01"
    },
    "posts": {
      "total_count": 150,
      "published_count": 120,
      "draft_count": 30
    },
    "users": {
      "total_count": 500,
      "active_users": 80,
      "new_registrations": 15
    },
    "views": {
      "total_views": 12500,
      "unique_visitors": 3200,
      "average_views_per_post": 83.33
    }
  },
  "meta": {
    "generated_at": "2024-01-01T12:00:00Z",
    "cached": true
  }
}
```

### 2. 文章統計 - `/api/statistics/posts`

**功能**：分析文章發布和表現數據

**參數**：
- `period_type` (可選): 統計期間類型
- `start_date` (可選): 開始日期
- `end_date` (可選): 結束日期
- `source` (可選): `web`|`mobile`|`api` - 來源篩選

**回應範例**：
```json
{
  "success": true,
  "data": {
    "total_count": 150,
    "status_distribution": {
      "published": 120,
      "draft": 25,
      "archived": 5
    },
    "source_analysis": {
      "web": 100,
      "mobile": 40,
      "api": 10
    },
    "trends": {
      "growth_rate": 15.5,
      "average_daily": 4.8
    }
  }
}
```

### 3. 熱門內容 - `/api/statistics/popular`

**功能**：取得最受歡迎的內容列表

**參數**：
- `period_type` (可選): 統計期間類型
- `limit` (可選): 1-100 - 返回項目數量限制 (預設: 10)

**回應範例**：
```json
{
  "success": true,
  "data": [
    {
      "id": 123,
      "title": "熱門文章標題",
      "views": 1250,
      "rank": 1
    }
  ]
}
```

## 🔧 最佳實踐

### 1. 查詢最佳化

**選擇合適的時間範圍**：
```bash
# ✅ 好的做法 - 明確的日期範圍
curl "...?start_date=2024-01-01&end_date=2024-01-07"

# ❌ 避免 - 過大的日期範圍可能影響效能
curl "...?start_date=2023-01-01&end_date=2024-12-31"
```

**使用快取友善的查詢**：
```bash
# ✅ 利用預設的期間類型（有快取）
curl "...?period_type=daily"

# ✅ 使用標準時間範圍
curl "...?period_type=monthly&start_date=2024-01-01"
```

### 2. 錯誤處理

**處理 API 錯誤回應**：
```javascript
async function getStatistics() {
  try {
    const response = await fetch('/api/statistics/overview', {
      headers: {
        'Authorization': `Bearer ${token}`,
        'Content-Type': 'application/json'
      }
    });
    
    const data = await response.json();
    
    if (!data.success) {
      throw new Error(data.error.message);
    }
    
    return data.data;
  } catch (error) {
    console.error('統計資料取得失敗:', error.message);
    // 實施降級策略或顯示錯誤訊息
  }
}
```

### 3. 快取策略

**了解快取行為**：
- 概覽數據：快取 15 分鐘
- 熱門內容：快取 30 分鐘
- 文章統計：快取 1 小時
- 歷史數據：快取 24 小時

**利用 meta 資訊**：
```javascript
if (response.meta.cached) {
  console.log('資料來自快取，生成時間:', response.meta.generated_at);
}
```

## ⚡ 效能調整指南

### 1. 查詢效能優化

**批次查詢**：
```bash
# 一次取得多個統計數據
curl "/api/statistics/overview" & \
curl "/api/statistics/posts" & \
curl "/api/statistics/popular" &
wait
```

**分頁查詢**：
```bash
# 對大量數據使用適當的限制
curl "/api/statistics/popular?limit=20"
```

### 2. 前端最佳化

**資料快取**：
```javascript
class StatisticsCache {
  constructor(ttl = 300000) { // 5 分鐘
    this.cache = new Map();
    this.ttl = ttl;
  }
  
  get(key) {
    const item = this.cache.get(key);
    if (item && Date.now() - item.timestamp < this.ttl) {
      return item.data;
    }
    return null;
  }
  
  set(key, data) {
    this.cache.set(key, { data, timestamp: Date.now() });
  }
}
```

**延遲載入**：
```javascript
// 只在需要時載入統計數據
const lazyLoadStats = async (component) => {
  if (component.isVisible()) {
    const stats = await getStatistics();
    component.render(stats);
  }
};
```

## 🔍 故障排除手冊

### 常見問題

#### 1. 認證失敗
**錯誤**: `401 Unauthorized`
**解決方案**:
- 檢查 JWT Token 是否有效
- 確認 Token 包含必要的權限
- 檢查 Token 是否過期

#### 2. 參數錯誤
**錯誤**: `400 Bad Request`
**解決方案**:
- 檢查日期格式 (YYYY-MM-DD)
- 確認 period_type 值正確
- 檢查數值參數範圍

#### 3. 資料不一致
**問題**: 統計數據與預期不符
**排查步驟**:
1. 檢查時區設定
2. 確認查詢期間範圍
3. 檢查快取狀態
4. 查看伺服器日誌

#### 4. 效能問題
**問題**: API 回應緩慢
**優化方法**:
1. 縮小查詢範圍
2. 使用適當的快取策略
3. 避免並發大量請求
4. 檢查資料庫索引

### 日誌分析

**開啟詳細日誌**：
```bash
# 查看統計 API 日誌
docker-compose exec web tail -f storage/logs/statistics.log

# 查看快取日誌
docker-compose exec web tail -f storage/logs/cache.log
```

**日誌示例**：
```
[2024-01-01 12:00:00] statistics.INFO: 統計概覽 API 請求 
  {"method":"GET","uri":"/api/statistics/overview","query_params":{"period_type":"daily"}}

[2024-01-01 12:00:01] cache.INFO: 快取命中 
  {"key":"statistics_overview_daily_2024-01-01","hit":true}
```

## 🔧 維護和監控建議

### 1. 監控指標

**關鍵效能指標**：
- API 回應時間 (< 200ms)
- 快取命中率 (> 80%)
- 錯誤率 (< 1%)
- 並發請求數

**監控設定**：
```bash
# 設定 API 監控
curl -X POST "http://monitoring-service/alerts" \
  -d '{
    "name": "statistics_api_response_time",
    "threshold": 200,
    "unit": "ms"
  }'
```

### 2. 定期維護

**每日任務**：
- 檢查 API 回應時間
- 清理過期快取
- 備份統計快照

**每週任務**：
- 分析使用模式
- 優化慢查詢
- 更新統計預算算

**每月任務**：
- 歷史資料歸檔
- 效能評估報告
- 容量規劃檢討

### 3. 備份策略

**資料備份**：
```bash
# 每日備份統計快照
mysqldump statistics_snapshots > backup/stats_$(date +%Y%m%d).sql

# 快取資料備份
redis-cli --rdb backup/cache_$(date +%Y%m%d).rdb
```

**恢復程序**：
```bash
# 恢復統計資料
mysql < backup/stats_20240101.sql

# 重建快取
php artisan statistics:cache:warmup
```

## 📚 進階用法

### 1. 自訂時間範圍

```javascript
// 自訂週期查詢
const getCustomPeriodStats = async (startDate, endDate) => {
  const params = new URLSearchParams({
    start_date: startDate,
    end_date: endDate,
    period_type: 'custom'
  });
  
  return fetch(`/api/statistics/overview?${params}`);
};
```

### 2. 資料匯出

```javascript
// 匯出統計報告
const exportStatistics = async (format = 'json') => {
  const stats = await getStatistics();
  
  if (format === 'csv') {
    return convertToCSV(stats);
  }
  
  return stats;
};
```

### 3. 即時統計

```javascript
// WebSocket 即時統計
const ws = new WebSocket('ws://localhost/statistics/realtime');
ws.onmessage = (event) => {
  const realtimeStats = JSON.parse(event.data);
  updateDashboard(realtimeStats);
};
```

## 🤝 支援與回饋

如果您在使用統計功能時遇到問題或有改進建議，請：

1. 查看 [API 文件](http://localhost/api/docs/ui)
2. 檢查 [故障排除手冊](#故障排除手冊)
3. 提交 [GitHub Issue](https://github.com/your-repo/issues)
4. 聯繫技術支援團隊

---

*最後更新：2024-12-19*  
*版本：1.0.0*
