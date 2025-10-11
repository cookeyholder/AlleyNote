# 時區功能實現計劃

## 目標
實現完整的時區管理系統，讓網站可以設定時區，所有時間顯示使用網站時區，資料庫儲存使用 RFC3339 格式（UTC）。

## RFC3339 格式說明
- 格式：`YYYY-MM-DDTHH:MM:SS±HH:MM` 或 `YYYY-MM-DDTHH:MM:SSZ`
- 範例：`2025-10-11T12:30:00+08:00` 或 `2025-10-11T04:30:00Z`
- Z 表示 UTC（+00:00）
- 資料庫儲存：統一使用 UTC（Z 結尾）
- 顯示：根據網站時區轉換

## 待辦清單

### 階段 1：資料庫準備
- [x] 1.1 檢查 settings 表結構 ✅
- [x] 1.2 創建初始時區設定記錄（預設 UTC+8 Asia/Taipei） ✅
- [ ] 1.3 創建資料庫遷移腳本（將現有時間格式轉為 RFC3339）

### 階段 2：後端核心功能
- [x] 2.1 創建時區輔助函數類 `TimezoneHelper.php` ✅
  - [x] UTC 轉網站時區 ✅
  - [x] 網站時區轉 UTC ✅
  - [x] RFC3339 格式驗證 ✅
  - [x] 獲取網站時區設定 ✅
  - [x] 時區偏移計算 ✅
  - [x] 常用時區列表 ✅
  - [x] 格式化顯示 ✅
- [x] 2.2 修改 SettingController ✅
  - [x] 添加 getTimezoneInfo() 方法 ✅
  - [x] 使用現有 API 獲取/更新時區 ✅
- [ ] 2.3 修改 PostController
  - [ ] index: 時間輸出轉換為網站時區
  - [ ] show: 時間輸出轉換為網站時區
  - [ ] store: 接收網站時區時間，轉換為 UTC 儲存
  - [ ] update: 接收網站時區時間，轉換為 UTC 儲存
- [ ] 2.4 修改 PostRepository
  - [ ] 儲存時間時使用 RFC3339 格式
  - [ ] 查詢時間時轉換格式

### 階段 3：前端核心功能
- [ ] 3.1 創建時區處理 JavaScript 模組 `timezoneUtils.js`
  - [ ] 獲取網站時區設定
  - [ ] UTC 轉網站時區顯示
  - [ ] 網站時區轉 UTC
  - [ ] 格式化時間顯示
- [ ] 3.2 修改系統設定頁面 `settings.js`
  - [ ] 添加時區選擇器（常用時區列表）
  - [ ] 載入當前時區設定
  - [ ] 儲存時區設定
  - [ ] 顯示當前時區的當前時間
- [ ] 3.3 修改文章編輯頁面 `postEditor.js`
  - [ ] datetime-local 輸入使用網站時區
  - [ ] 顯示時區提示
  - [ ] 提交時轉換為 UTC
- [ ] 3.4 修改所有顯示時間的頁面
  - [ ] home.js（首頁）
  - [ ] posts.js（文章列表）
  - [ ] post.js（文章詳情）
  - [ ] dashboard.js（儀表板）

### 階段 4：資料遷移
- [ ] 4.1 創建資料遷移腳本
  - [ ] 分析現有時間格式
  - [ ] 轉換為 RFC3339 格式（假設為 UTC+8）
  - [ ] 備份原始資料
  - [ ] 執行遷移
- [ ] 4.2 驗證遷移結果

### 階段 5：測試
- [ ] 5.1 後端單元測試
  - [ ] TimezoneHelper 測試
  - [ ] 時間轉換正確性測試
- [ ] 5.2 前端功能測試
  - [ ] 時區設定儲存
  - [ ] 時區切換
  - [ ] 文章發布時間正確性
- [ ] 5.3 整合測試
  - [ ] 不同時區下的文章發布
  - [ ] 時區切換後的時間顯示
  - [ ] 定時發布功能

### 階段 6：文檔
- [ ] 6.1 更新 README（時區設定說明）
- [ ] 6.2 創建時區使用指南
- [ ] 6.3 API 文檔更新

## 實現細節

### 時區列表（常用）
```javascript
const COMMON_TIMEZONES = [
  { value: 'UTC', label: 'UTC (協調世界時)', offset: '+00:00' },
  { value: 'Asia/Taipei', label: 'Asia/Taipei (台北時間 UTC+8)', offset: '+08:00' },
  { value: 'Asia/Tokyo', label: 'Asia/Tokyo (東京時間 UTC+9)', offset: '+09:00' },
  { value: 'Asia/Shanghai', label: 'Asia/Shanghai (上海時間 UTC+8)', offset: '+08:00' },
  { value: 'Asia/Hong_Kong', label: 'Asia/Hong_Kong (香港時間 UTC+8)', offset: '+08:00' },
  { value: 'America/New_York', label: 'America/New_York (紐約時間)', offset: '-05:00/-04:00' },
  { value: 'America/Los_Angeles', label: 'America/Los_Angeles (洛杉磯時間)', offset: '-08:00/-07:00' },
  { value: 'Europe/London', label: 'Europe/London (倫敦時間)', offset: '+00:00/+01:00' },
];
```

### 資料庫時間格式範例
```
儲存（RFC3339 UTC）：2025-10-11T04:30:00Z
顯示（UTC+8）：2025-10-11 12:30:00
```

### API 變更
```
GET /api/settings/timezone
Response: { "timezone": "Asia/Taipei", "offset": "+08:00" }

PUT /api/settings/timezone
Request: { "timezone": "Asia/Taipei" }
Response: { "success": true }
```

## 風險與注意事項
1. 現有資料遷移風險 - 需要備份
2. 時區轉換可能的精度損失
3. 夏令時處理（某些時區）
4. 前端瀏覽器時區與網站時區的區別
5. 定時發布功能需要重新測試

## 估計時間
- 階段 1: 30 分鐘
- 階段 2: 2 小時
- 階段 3: 2 小時
- 階段 4: 1 小時
- 階段 5: 1 小時
- 階段 6: 30 分鐘
- 總計：約 7 小時

## 進度追蹤
- 開始時間：2025-10-11
- 當前階段：階段 3 - 前端核心功能
- 完成進度：30%
- 最後更新：2025-10-11 12:20
