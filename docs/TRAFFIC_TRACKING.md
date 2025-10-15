# AlleyNote 流量追蹤機制說明

## 📊 概述

AlleyNote 使用事件驅動架構來記錄和統計文章瀏覽量，實現了高效能的流量追蹤系統。

## 🏗️ 架構組成

### 1. 資料庫表結構

#### `post_views` 表
儲存每一次文章瀏覽記錄：

```sql
CREATE TABLE IF NOT EXISTS post_views (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    post_id INTEGER NOT NULL,              -- 文章 ID
    user_id INTEGER,                       -- 使用者 ID（已登入使用者）
    ip_address VARCHAR(45),                -- 訪客 IP 地址
    user_agent TEXT,                       -- 瀏覽器 User-Agent
    referer TEXT,                          -- 來源頁面
    viewed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,  -- 瀏覽時間
    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
)
```

**索引**：
- `idx_post_views_post` - 加速按文章查詢
- `viewed_at` - 加速時間範圍查詢

#### `statistics_snapshots` 表
儲存定期聚合的統計快照（用於快速查詢）：

```sql
CREATE TABLE IF NOT EXISTS statistics_snapshots (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    uuid VARCHAR(36) NOT NULL UNIQUE,
    period_type VARCHAR(20) NOT NULL,      -- 時間粒度：hour, day, week, month
    period_start DATETIME NOT NULL,
    period_end DATETIME NOT NULL,
    data TEXT NOT NULL,                     -- JSON 格式的統計資料
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
)
```

### 2. 流量記錄流程

#### 前端觸發
當使用者瀏覽文章時，前端呼叫 API：

```javascript
// 前端程式碼範例（需要實作）
async function trackPostView(postId) {
  try {
    await fetch(`/api/posts/${postId}/view`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        referrer: document.referrer || null
      })
    });
  } catch (error) {
    console.error('Failed to track view:', error);
  }
}
```

**特點**：
- ✅ 非阻塞式調用，不影響頁面載入
- ✅ 允許匿名訪問（不需要 JWT token）
- ✅ 自動收集 IP、User-Agent、Referrer

#### 後端處理
**API 端點**：`POST /api/posts/{id}/view`

**處理流程**：
1. **驗證文章存在性**（PostViewController）
2. **收集瀏覽資訊**：
   - 使用者 ID（如果已登入）
   - IP 地址（支援代理服務器）
   - User-Agent
   - Referrer（來源頁面）
3. **觸發 PostViewed 事件**
4. **非同步處理**：
   - 寫入 `post_views` 表
   - 更新快取
   - 觸發其他相關處理

**程式碼位置**：
- 控制器：`backend/app/Application/Controllers/Api/V1/PostViewController.php`
- 事件：`backend/app/Domains/Statistics/Events/PostViewed.php`

### 3. 統計查詢 API

#### 流量趨勢圖表 API
**端點**：`GET /api/statistics/charts/views/timeseries`

**查詢參數**：
- `start_date` - 開始日期（YYYY-MM-DD）
- `end_date` - 結束日期（YYYY-MM-DD）
- `granularity` - 時間粒度（hour, day, week, month）

**回應格式**：
```json
{
  "success": true,
  "data": [
    {
      "date": "2025-10-01",
      "views": 150,
      "visitors": 85
    },
    {
      "date": "2025-10-02",
      "views": 200,
      "visitors": 120
    }
  ],
  "meta": {
    "start_date": "2025-10-01",
    "end_date": "2025-10-31",
    "granularity": "day"
  }
}
```

**程式碼位置**：
- `backend/app/Application/Controllers/Api/V1/StatisticsChartController.php`

#### 其他統計 API

1. **統計概覽**：`GET /api/statistics/overview`
   - 總文章數、活躍使用者、新使用者、總瀏覽量

2. **熱門文章**：`GET /api/statistics/popular`
   - 按瀏覽量排序的熱門文章列表

3. **使用者統計**：`GET /api/statistics/users`
   - 使用者活躍度、註冊趨勢

4. **來源統計**：`GET /api/statistics/sources`
   - 流量來源分析（Referrer 統計）

## 🔒 安全性設計

### 1. 速率限制
- 使用專用中介軟體 `post_view_rate_limit`
- 防止惡意刷流量

### 2. IP 追蹤
- 支援反向代理（X-Forwarded-For, X-Real-IP 等）
- 過濾內網 IP 和保留 IP
- 記錄真實的客戶端 IP

### 3. 隱私保護
- User-Agent 和 IP 僅用於統計
- 支援匿名瀏覽（user_id 可為 NULL）
- 可設定資料保留期限

## 📈 效能優化

### 1. 非同步處理
- 使用事件驅動架構
- PostViewed 事件異步處理
- API 回應時間 < 100ms

### 2. 查詢優化
- 資料庫索引優化
- 使用統計快照減少即時計算
- 快取熱門統計資料

### 3. 批次處理
- 定期聚合統計資料
- 創建 statistics_snapshots 快照
- 清理過期的詳細記錄

## 🛠️ 維護與管理

### 手動刷新統計
```bash
POST /api/admin/statistics/refresh
{
  "force_recalculate": true
}
```

### 清除統計快取
```bash
DELETE /api/admin/statistics/cache
```

### 系統健康檢查
```bash
GET /api/admin/statistics/health
```

## 📝 待實作項目

### 前端整合
目前前端**尚未實作**自動調用流量追蹤 API。需要在以下位置添加：

1. **文章詳情頁**（`frontend/js/pages/public/post.js`）：
   ```javascript
   // 在 renderPost 函數中添加
   async function renderPost(postId) {
     // ... 載入文章內容
     
     // 記錄瀏覽
     trackPostView(postId);
   }
   ```

2. **首頁文章卡片點擊**（可選）

### 統計功能增強

1. **即時統計**：
   - WebSocket 推送即時瀏覽數據
   - 即時訪客數量顯示

2. **進階分析**：
   - 訪客地理位置分布
   - 裝置類型統計（桌面/手機/平板）
   - 瀏覽器統計
   - 訪客停留時間

3. **報表匯出**：
   - CSV/Excel 格式匯出
   - PDF 報表生成
   - 定期郵件報表

## 🔍 調試與監控

### 檢查流量記錄
```sql
-- 查看最近的瀏覽記錄
SELECT * FROM post_views 
ORDER BY viewed_at DESC 
LIMIT 10;

-- 統計各文章瀏覽量
SELECT 
  p.title,
  COUNT(pv.id) as view_count,
  COUNT(DISTINCT pv.ip_address) as unique_visitors
FROM posts p
LEFT JOIN post_views pv ON p.id = pv.post_id
GROUP BY p.id
ORDER BY view_count DESC;
```

### 日誌記錄
- PostViewController 記錄所有錯誤到 error_log
- 包含處理時間（processing_time_ms）
- 記錄異常的 IP 或行為

## 📚 相關文件

- [統計領域設計](./STATISTICS_DOMAIN.md)（如果存在）
- [API 文件](./API_DOCUMENTATION.md)
- [資料庫架構](./DATABASE_SCHEMA.md)

## 🎯 總結

AlleyNote 的流量追蹤系統：
- ✅ 使用事件驅動架構，解耦且易於擴展
- ✅ 支援匿名和已登入使用者
- ✅ 高效能設計（< 100ms 回應時間）
- ✅ 完整的 IP 和來源追蹤
- ✅ 靈活的統計查詢 API
- ⚠️ **前端尚未整合**（需要添加 API 調用）
