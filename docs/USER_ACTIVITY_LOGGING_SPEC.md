# 使用者行為紀錄功能開發規格書

## 📋 專案概述

### 1.1 功能目標
建立完整的使用者行為追蹤與稽核系統，記錄所有重要的使用者操作，支援安全分析、合規稽核和異常行為檢測。

### 1.2 業務需求
- **安全稽核**：追蹤所有安全相關操作
- **合規要求**：滿足資料保護法規的稽核要求
- **異常檢測**：自動識別可疑行為模式
- **效能監控**：分析系統使用模式
- **故障排除**：協助問題診斷

### 1.3 技術架構
- **DDD 架構**：整合至 Security Domain
- **資料庫**：SQLite3 with JSON support
- **快取**：支援快取機制減少資料庫負載
- **非同步處理**：支援批次和非同步記錄

---

## 🎯 功能需求規格

### 2.1 核心功能需求

#### FR-001: 基本行為記錄
**描述**：系統能夠記錄使用者的各種操作行為

**詳細需求**：
- 記錄使用者 ID、操作類型、目標資源、時間戳記
- 記錄 IP 位址、User Agent、請求路徑
- 記錄操作結果（成功/失敗）
- 支援匿名使用者操作記錄
- 記錄會話資訊用於追蹤

**驗收條件**：
- [x] 成功記錄登入操作
- [x] 成功記錄文章 CRUD 操作
- [x] 成功記錄附件操作
- [x] 成功記錄失敗操作（如登入失敗）
- [x] 記錄包含完整的上下文資訊

#### FR-002: 行為類型管理
**描述**：系統提供完整的行為類型定義和分類

**詳細需求**：
- 支援預定義的行為類型（使用 Enum）
- 行為類型包含分類、嚴重程度資訊
- 支援動態啟用/停用特定類型記錄
- 支援自訂描述和元數據
- 行為類型可擴充

**驗收條件**：
- [x] 所有預定義行為類型正確分類
- [x] 嚴重程度等級正確設定
- [x] 可動態控制記錄啟用狀態
- [x] 支援新增自訂行為類型

#### FR-003: 查詢和搜尋功能
**描述**：提供強大的查詢介面支援各種搜尋需求

**詳細需求**：
- 支援按使用者、時間範圍、行為類型查詢
- 支援全文搜尋（描述和元數據）
- 支援複合條件查詢
- 支援排序和分頁
- 支援匯出功能

**驗收條件**：
- [x] 使用者行為歷史查詢正常
- [x] 時間範圍查詢精確
- [x] 搜尋功能快速回應（<500ms）
- [x] 分頁功能正常
- [x] 複合條件查詢結果正確

#### FR-004: 統計分析功能
**描述**：提供行為統計和趨勢分析

**詳細需求**：
- 按類型、時間維度統計活動數量
- 識別熱門操作類型
- 檢測異常行為模式
- 生成使用者活動報告
- 支援圖表資料輸出

**驗收條件**：
- [x] 統計數據準確
- [x] 異常檢測靈敏度適中
- [x] 報告生成速度合理（<3秒）
- [x] 支援多種時間維度統計

### 2.2 非功能需求

#### NFR-001: 效能需求
- 單次記錄操作 < 50ms
- 查詢回應時間 < 500ms
- 支援每秒 100+ 記錄寫入
- 資料庫儲存最佳化

#### NFR-002: 可靠性需求
- 記錄成功率 > 99.9%
- 系統故障時不影響主要業務功能
- 支援記錄重試機制
- 資料完整性保證

#### NFR-003: 安全性需求
- 敏感資料加密儲存
- 存取權限控制
- 記錄不可篡改性
- 符合 GDPR 要求

#### NFR-004: 擴展性需求
- 支援水平擴展
- 支援資料分割
- 支援歸檔機制
- 支援外部日誌系統整合

---

## 🏗️ 技術規格

### 3.1 資料模型設計

#### 3.1.1 user_activity_logs 資料表
```sql
CREATE TABLE user_activity_logs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    uuid VARCHAR(36) UNIQUE NOT NULL,
    user_id INTEGER NULL,
    session_id VARCHAR(128) NULL,
    action_type VARCHAR(50) NOT NULL,
    action_category VARCHAR(30) NOT NULL,
    target_type VARCHAR(50) NULL,
    target_id VARCHAR(50) NULL,
    status VARCHAR(20) DEFAULT 'success',
    description TEXT NULL,
    metadata JSON NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    request_method VARCHAR(10) NULL,
    request_path VARCHAR(500) NULL,
    created_at DATETIME NOT NULL,
    occurred_at DATETIME NOT NULL,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);
```

#### 3.1.2 索引設計
- `uuid` (UNIQUE)
- `user_id, occurred_at`
- `action_category, action_type`
- `status`
- `ip_address`
- `occurred_at`

### 3.2 領域模型設計

#### 3.2.1 核心實體
- **UserActivityLog**: 活動記錄實體
- **ActivityType**: 行為類型值物件
- **ActivityCategory**: 行為分類值物件
- **ActivitySeverity**: 嚴重程度值物件

#### 3.2.2 領域服務
- **ActivityLoggingService**: 記錄服務
- **ActivityAnalysisService**: 分析服務
- **SuspiciousActivityDetector**: 異常檢測服務

#### 3.2.3 基礎設施服務
- **ActivityLogRepository**: 資料存取
- **ActivityLogCache**: 快取服務
- **ActivityLogQueue**: 非同步處理

### 3.3 API 設計

#### 3.3.1 記錄 API
```php
// 基本記錄
POST /api/v1/activity-logs
{
    "action_type": "auth.login.success",
    "user_id": 123,
    "description": "使用者登入成功",
    "metadata": {
        "login_method": "password",
        "remember_me": true
    }
}

// 批次記錄
POST /api/v1/activity-logs/batch
{
    "logs": [
        { /* log 1 */ },
        { /* log 2 */ }
    ]
}
```

#### 3.3.2 查詢 API
```php
// 查詢使用者活動
GET /api/v1/activity-logs/users/{userId}
?category=authentication
&limit=50
&offset=0
&start_time=2025-08-01T00:00:00Z
&end_time=2025-08-31T23:59:59Z

// 搜尋活動
GET /api/v1/activity-logs/search
?q=login
&category=authentication
&status=failed
&sort=occurred_at
&order=desc
```

#### 3.3.3 統計 API
```php
// 活動統計
GET /api/v1/activity-logs/statistics
?start_time=2025-08-01T00:00:00Z
&end_time=2025-08-31T23:59:59Z
&group_by=category

// 可疑活動
GET /api/v1/activity-logs/suspicious
?threshold=10
&time_window=1h
```

---

## 🧪 測試規格

### 4.1 單元測試

#### 4.1.1 領域模型測試
- **ActivityTypeTest**: 測試行為類型枚舉
- **CreateActivityLogDTOTest**: 測試 DTO 建立和驗證
- **ActivityCategoryTest**: 測試分類功能

#### 4.1.2 服務層測試
- **ActivityLoggingServiceTest**: 測試記錄服務
- **ActivityAnalysisServiceTest**: 測試分析服務
- **SuspiciousActivityDetectorTest**: 測試異常檢測

#### 4.1.3 資料存取層測試
- **ActivityLogRepositoryTest**: 測試資料操作
- **ActivityLogCacheTest**: 測試快取功能

### 4.2 整合測試

#### 4.2.1 API 整合測試
- **ActivityLogControllerTest**: 測試 API 端點
- **AuthenticationLoggingTest**: 測試認證日誌整合
- **PostActivityLoggingTest**: 測試文章操作日誌

#### 4.2.2 業務流程測試
- **UserRegistrationLoggingTest**: 使用者註冊流程
- **PostManagementLoggingTest**: 文章管理流程
- **SecurityIncidentLoggingTest**: 安全事件流程

### 4.3 效能測試

#### 4.3.1 負載測試
- 並發記錄效能測試
- 大量查詢效能測試
- 資料庫負載測試

#### 4.3.2 容量測試
- 大量資料查詢測試
- 長期運行穩定性測試
- 記憶體使用量測試

---

## 📊 驗收標準

### 5.1 功能驗收標準

#### AC-001: 基本記錄功能
**Given** 使用者執行登入操作  
**When** 系統記錄該操作  
**Then** 
- 活動記錄成功儲存到資料庫
- 記錄包含正確的使用者 ID、行為類型、時間戳記
- 記錄包含 IP 位址和 User Agent
- 記錄狀態為 "success"

#### AC-002: 失敗操作記錄
**Given** 使用者登入失敗  
**When** 系統記錄該失敗操作  
**Then**
- 記錄狀態為 "failed"
- 包含失敗原因描述
- 包含嘗試的使用者名稱（如適用）
- 觸發安全警報（如超過閾值）

#### AC-003: 查詢功能
**Given** 系統有多筆活動記錄  
**When** 管理員查詢特定使用者的活動  
**Then**
- 返回該使用者的所有相關記錄
- 結果按時間降序排列
- 支援分頁顯示
- 查詢時間 < 500ms

#### AC-004: 統計分析
**Given** 系統運行一段時間有足夠資料  
**When** 生成活動統計報告  
**Then**
- 顯示各類型活動的數量統計
- 顯示活躍時段分析
- 識別異常活動模式
- 報告生成時間 < 3秒

### 5.2 效能驗收標準

#### PC-001: 記錄效能
- 單次記錄操作完成時間 < 50ms
- 批次記錄 100 筆 < 200ms
- 記錄失敗率 < 0.1%

#### PC-002: 查詢效能
- 簡單查詢（單一條件）< 100ms
- 複合查詢（多重條件）< 500ms
- 統計查詢 < 1000ms

#### PC-003: 併發效能
- 支援 100 個併發記錄請求
- 支援 50 個併發查詢請求
- 系統回應時間不超過平時的 150%

### 5.3 安全驗收標準

#### SC-001: 資料完整性
- 記錄資料不可被篡改
- 記錄刪除需要特殊權限
- 敏感資料適當加密或遮罩

#### SC-002: 存取控制
- 只有授權使用者可查看活動記錄
- 使用者只能查看自己的記錄（除非有特殊權限）
- 管理員操作被完整記錄

#### SC-003: 隱私保護
- 符合 GDPR 資料保護要求
- 支援資料匿名化
- 支援資料刪除請求

---

## 🔧 實作考量

### 6.1 資料保留政策
- **一般活動記錄**：保留 90 天
- **安全相關記錄**：保留 1 年
- **重要稽核記錄**：保留 3 年
- **自動歸檔機制**：每月執行

### 6.2 效能最佳化
- **索引策略**：針對常用查詢建立複合索引
- **分割策略**：按時間分割大型資料表
- **快取策略**：熱門查詢結果快取 5 分鐘
- **非同步處理**：非關鍵記錄使用佇列處理

### 6.3 監控告警
- **記錄失敗率**：超過 1% 發送警報
- **查詢效能**：超過 1 秒發送警報
- **異常活動**：自動檢測並即時通知
- **儲存空間**：使用率超過 80% 警報

### 6.4 災難復原
- **定期備份**：每日完整備份
- **增量備份**：每小時增量備份
- **復原測試**：月度復原演練
- **多地備份**：關鍵資料異地備份

---

## 📈 成功指標

### 7.1 業務指標
- **記錄完整性**：> 99.9%
- **查詢回應時間**：< 500ms
- **系統可用性**：> 99.5%
- **資料準確性**：100%

### 7.2 技術指標
- **程式碼覆蓋率**：> 90%
- **效能基準通過率**：100%
- **安全測試通過率**：100%
- **文件完整性**：100%

### 7.3 使用者體驗指標
- **管理介面回應時間**：< 1 秒
- **報告生成時間**：< 5 秒
- **搜尋結果相關性**：> 95%
- **介面操作直觀性**：使用者滿意度 > 4.0/5.0