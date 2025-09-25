# 文章統計功能開發待辦清單

## 📅 專案資訊
- **功能名稱**：文章統計功能
- **開發分支**：feature/statistics-service
- **預計完成**：2025-09-21
- **開發人員**：開發團隊

## 🎯 開發順序與依賴關係

根據 DDD 架構原則，按照以下順序開發：
**Domain → Application → Infrastructure → Int#### ✅ T4.2 - 實作統計快取服務
**描述**：實作統計資料的快取機制
**預估時間**：4 小時
**依賴**：T4.1
**驗收標準**：
- [ ] `StatisticsCacheService` 類別
- [ ] 支援多層次快取策略
- [ ] 快取鍵命名規範統一
- [ ] 支援快取標籤管理
- [ ] 實作快取預熱機制
- [ ] 包含快取失效邏輯
- [ ] 執行 CI 檢查（PHP CS Fixer + PHPStan + PHPUnit）確認無錯誤
- [ ] 通過 PHPStan Level 10 檢查

#### ✅ T4.3 - 建立統計計算定時任務
**描述**：建立定期計算統計快照的背景任務
**預估時間**：3 小時
**依賴**：T4.2
**驗收標準**：
- [ ] `StatisticsCalculationCommand` 類別
- [ ] 支援不同統計週期（daily, weekly, monthly）
- [ ] 包含錯誤重試機制
- [ ] 記錄執行日誌
- [ ] 可手動觸發執行
- [ ] 支援並行安全執行
- [ ] 執行 CI 檢查（PHP CS Fixer + PHPStan + PHPUnit）確認無錯誤

#### ✅ T4.4 - 建立索引最佳化與查詢調整
**描述**：統計相關表的索引最佳化與SQL查詢調整
**預估時間**：4 小時
**依賴**：T4.3
**驗收標準**：
- [ ] 建立 posts 表統計查詢複合索引
- [ ] 測試索引效能改善效果
- [ ] 最佳化大量資料統計查詢
- [ ] 建立慢查詢監控
- [ ] 檢查查詢執行計劃
- [ ] 效能測試報告
- [ ] 執行 CI 檢查（PHP CS Fixer + PHPStan + PHPUnit）確認無錯誤

#### ✅ T4.5 - 建立統計監控與日誌系統
**描述**：建立統計功能的監控、日誌記錄與健康檢查
**預估時間**：5 小時
**依賴**：T4.4
**驗收標準**：
- [ ] `StatisticsMonitoringService` 類別
- [ ] 統計計算時間監控
- [ ] 快取命中率監控
- [ ] API 回應時間監控
- [ ] 錯誤率記錄與警告
- [ ] 統計健康檢查端點
- [ ] 日誌輪轉與保存策略
- [ ] 執行 CI 檢查（PHP CS Fixer + PHPStan + PHPUnit）確認無錯誤

#### 🔲 T4.6 - 建立統計資料回填指令
**描述**：建立一個指令，用於對歷史資料重新計算並生成統計快照
**預估時間**：3 小時
**依賴**：T4.3
**驗收標準**：
- [ ] `StatisticsRecalculationCommand` 類別
- [ ] 支援按統計類型和日期範圍進行回填
- [ ] 提供 `--force` 選項覆蓋現有快照
- [ ] 包含進度顯示和日誌記錄
- [ ] 指令可安全地重複執行
- [ ] 執行 CI 檢查（PHP CS Fixer + PHPStan + PHPUnit）確認無錯誤

#### 🔲 T4.7 - 整合領域事件分派器
**描述**：將統計領域事件整合到專案的事件分派機制中
**預估時間**：3 小時
**依賴**：T1.5
**驗收標準**：
- [ ] 建立 `PostViewed` 事件的監聽器，觸發非同步計數更新
- [ ] 建立 `StatisticsSnapshotCreated` 事件的監聽器，觸發快取失效或預熱
- [ ] 若專案尚無事件機制，則引入一個輕量級的事件分派器
- [ ] 事件處理邏輯有完整的測試
- [ ] 執行 CI 檢查（PHP CS Fixer + PHPStan + PHPUnit）確認無錯誤

#### 🔲 T4.8 - 建立統計功能設定檔
**描述**：建立並設定統計功能的獨立設定檔
**預估時間**：1 小時
**依賴**：T4.2
**驗收標準**：
- [ ] 在 `config/` 目錄下建立 `statistics.php`
- [ ] 將快取 TTL、排程時間、資料保存期限等配置移入此檔案
- [ ] 應用程式能正確讀取此設定檔
- [ ] 執行 CI 檢查（PHP CS Fixer + PHPStan + PHPUnit）確認無錯誤-

## 📋 詳細待辦事項

### 階段 1：領域層設計 (Domain Layer)

#### ✅ T1.1 - 分析與設計統計領域模型
**描述**：設計統計相關的領域概念和模型
**預估時間**：4 小時
**狀態**：✅ 已完成
**驗收標準**：
- [x] 完成統計領域概念分析文件
- [x] 定義統計聚合根 (Aggregate Root)
- [x] 設計統計值物件 (Value Objects)
- [x] 確定統計領域事件 (Domain Events)
- [x] 執行 CI 檢查（PHP CS Fixer + PHPStan + PHPUnit）確認無錯誤
- [ ] 領域專家審核通過設計

#### ✅ T1.2 - 建立統計值物件 (Value Objects)
**描述**：建立統計相關的值物件
**預估時間**：3 小時
**依賴**：T1.1
**狀態**：✅ 已完成
**驗收標準**：
- [x] `StatisticsPeriod` 值物件（時間範圍）
- [x] `StatisticsMetric` 值物件（統計指標）
- [x] `SourceType` 值物件（來源類型）
- [x] 所有值物件都是 immutable
- [x] 包含完整的驗證邏輯
- [x] 執行 CI 檢查（PHP CS Fixer + PHPStan + PHPUnit）確認無錯誤
- [x] 通過 PHPStan Level 10 檢查

#### ✅ T1.3 - 建立統計實體 (Entities)
**描述**：建立統計快照實體
**預估時間**：4 小時
**狀態**：✅ 已完成
**依賴**：T1.2
**驗收標準**：
- [x] `StatisticsSnapshot` 實體正確建立
- [x] 包含唯一識別 (UUID)
- [x] 實作領域邏輯方法
- [x] 正確使用值物件
- [x] 包含領域不變條件 (Invariants)
- [x] 執行 CI 檢查（PHP CS Fixer + PHPStan + PHPUnit）確認無錯誤
- [x] 通過 PHPStan Level 10 檢查

#### ✅ T1.4 - 定義統計 Repository 介面
**描述**：定義統計資料存取的領域介面
**預估時間**：2 小時
**狀態**：✅ 已完成
**依賴**：T1.3
**驗收標準**：
- [x] `StatisticsRepositoryInterface` 介面定義完整
- [x] `PostStatisticsRepositoryInterface` 介面定義完整
- [x] `UserStatisticsRepositoryInterface` 介面定義完整
- [x] 方法簽名遵循領域語言
- [x] 包含完整的 DocBlock 註解
- [x] 介面設計符合 ISP 原則
- [x] 執行 CI 檢查（PHP CS Fixer + PHPStan + PHPUnit）確認無錯誤

#### ✅ T1.5 - 建立統計領域服務
**描述**：建立統計計算的核心領域服務
**預估時間**：6 小時
**狀態**：✅ 已完成
**依賴**：T1.4
**驗收標準**：
- [x] `StatisticsAggregationService` 領域服務
- [x] 支援文章、來源、使用者、內容（標籤/分類）、互動（留言）等多維度計算
- [x] 所有業務邏輯封裝在領域層
- [x] 不依賴基礎設施層
- [x] 包含完整的領域邏輯測試
- [x] 執行 CI 檢查（PHP CS Fixer + PHPStan + PHPUnit）確認無錯誤
- [x] 通過 PHPStan Level 10 檢查

---

### 階段 2：資料庫結構調整 (Infrastructure - Database)

#### ✅ T2.1 - 建立文章來源追蹤 Migration
**描述**：為 posts 表新增來源追蹤欄位
**預估時間**：2 小時
**狀態**：✅ 已完成
**驗收標準**：
- [x] Migration 檔案正確建立
- [x] 新增 `creation_source` 欄位 (enum)
- [x] 新增 `creation_source_detail` 欄位 (nullable text)
- [x] 建立適當的索引
- [x] 包含向下相容的預設值
- [x] Migration 可正確回滾
- [x] 通過本地測試環境驗證
- [x] 執行 CI 檢查（PHP CS Fixer + PHPStan + PHPUnit）確認無錯誤

#### ✅ T2.1.1 - 修改 Post 模型支援來源追蹤
**描述**：修改現有 Post 模型類別及 Repository 以支援來源追蹤
**預估時間**：2 小時
**依賴**：T2.1
**狀態**：✅ 已完成
**驗收標準**：
- [x] Post 模型新增 `getCreationSource()` 和 `getCreationSourceDetail()` 方法
- [x] Post 構造函式支援新欄位
- [x] PostRepository 新增按來源查詢的方法
- [x] 維持向下相容性
- [x] 通過所有現有測試
- [x] 執行 CI 檢查（PHP CS Fixer + PHPStan + PHPUnit）確認無錯誤
- [x] 通過 PHPStan Level 10 檢查

#### ✅ T2.2 - 建立統計快照表 Migration
**描述**：建立統計資料快照表
**預估時間**：3 小時
**依賴**：T2.1
**狀態**：✅ 已完成
**驗收標準**：
- [x] `statistics_snapshots` 表正確建立
- [x] 包含必要欄位（id, uuid, snapshot_type, period_type, period_start, period_end, statistics_data, created_at）
- [x] JSON 欄位支援複雜統計資料
- [x] 建立複合索引提升查詢效能
- [x] StatisticsSnapshot 模型和 Repository 介面建立完成
- [x] 通過本地測試環境驗證
- [x] 執行 CI 檢查（PHP CS Fixer + PHPStan + PHPUnit）確認無錯誤

#### ✅ T2.3 - 更新現有資料的來源資訊
**描述**：為現有文章資料設定預設來源
**預估時間**：1 小時
**依賴**：T2.1
**狀態**：✅ 已完成
**驗收標準**：
- [x] 所有現有文章設定預設來源為 'web'
- [x] 資料更新腳本可重複執行
- [x] 包含資料驗證邏輯
- [x] 備份機制確保資料安全
- [x] 執行日誌記錄操作結果
- [x] Migration 和獨立腳本雙重保障
- [x] 執行 CI 檢查（PHP CS Fixer + PHPStan + PHPUnit）確認無錯誤

#### ✅ T2.4 - PostRepository 支援來源查詢
**描述**：更新 PostRepository 介面和實作，新增按來源類型篩選的查詢方法
**預估時間**：3 小時
**依賴**：T2.1.1
**狀態**：✅ 已完成
**驗收標準**：
- [x] 擴充 PostRepositoryInterface，新增 5 個來源查詢方法
- [x] 實作 findByCreationSource() - 按來源類型查詢文章
- [x] 實作 getSourceDistribution() - 取得來源分佈統計
- [x] 實作 findByCreationSourceAndDetail() - 按來源和詳細資訊查詢
- [x] 實作 countByCreationSource() - 計算特定來源文章數
- [x] 實作 paginateByCreationSource() - 來源文章分頁查詢
- [x] 包含單元測試和整合測試
- [x] 通過 CI 檢查和 PHPStan Level 10 驗證

---

### 階段 3：應用層服務 (Application Layer)

#### ✅ T3.1 - 建立統計應用服務
**描述**：建立統計功能的應用層服務
**預估時間**：5 小時
**依賴**：T1.5, T2.2
**狀態**：✅ 已完成
**驗收標準**：
- [x] `StatisticsApplicationService` 類別
- [x] 協調多個領域服務
- [x] 處理應用層的事務邏輯
- [x] 包含完整的錯誤處理
- [x] 實作快取策略
- [x] 執行 CI 檢查（PHP CS Fixer + PHPStan + PHPUnit）確認無錯誤
- [x] 通過 PHPStan Level 10 檢查

#### ✅ T3.2 - 建立統計查詢服務
**描述**：專門處理統計查詢的應用服務
**預估時間**：4 小時
**依賴**：T3.1
**狀態**：✅ 已完成
**驗收標準**：
- [x] `StatisticsQueryService` 類別
- [x] 支援複雜的統計查詢
- [x] 實作查詢最佳化
- [x] 包含分頁支援
- [x] 查詢參數驗證
- [x] 執行 CI 檢查（PHP CS Fixer + PHPStan + PHPUnit）確認無錯誤
- [x] 通過 PHPStan Level 10 檢查

#### ✅ T3.3 - 建立統計 DTO 類別
**描述**：建立統計資料傳輸物件
**預估時間**：3 小時
**狀態**：✅ 已完成
**依賴**：T3.2
**驗收標準**：
- [x] `StatisticsOverviewDTO` 類別
- [x] `PostStatisticsDTO` 類別
- [x] `SourceDistributionDTO` 類別
- [x] `UserStatisticsDTO` 類別（替代 UserActivityDTO）
- [x] `ContentInsightsDTO` 類別 (用於標籤、分類等)
- [x] 所有 DTO 包含驗證邏輯
- [x] 支援 JSON 序列化
- [x] 執行 CI 檢查（PHP CS Fixer + PHPStan + PHPUnit）確認無錯誤
- [x] 通過 PHPStan Level 10 檢查

---

### 階段 4：基礎設施層實作 (Infrastructure Layer)

#### ✅ T4.1 - 實作統計 Repository
**描述**：實作統計資料存取的具體類別
**預估時間**：6 小時
**依賴**：T1.4, T2.2
**狀態**：✅ 完成
**驗收標準**：
- [x] `StatisticsRepository` 類別實作
- [x] `PostStatisticsRepository` 類別實作
- [x] `UserStatisticsRepository` 類別實作
- [x] 在寫入時處理資料的 JSON 序列化，讀取時處理反序列化
- [x] 使用原生 SQL 最佳化效能
- [x] 包含完整的錯誤處理
- [x] 支援複雜的統計查詢
- [x] 實作統計 Repository 資料庫適配器（Cache、Transaction、Logging）
- [x] 建立 StatisticsDatabaseAdapterFactory 適配器工廠
- [x] 執行 CI 檢查（PHP CS Fixer + PHPStan + PHPUnit）確認無錯誤
- [x] 通過 PHPStan Level 10 檢查

#### ✅ T4.2 - 實作統計快取服務
**描述**：實作統計資料的快取機制
**預估時間**：4 小時
**依賴**：T4.1
**狀態**：✅ 完成
**驗收標準**：
- [x] `StatisticsCacheService` 類別
- [x] 支援多層次快取策略
- [x] 快取鍵命名規範統一
- [x] 支援快取標籤管理（statistics, overview, posts, users, popular, trends, sources, prewarmed）
- [x] 實作快取預熱機制（warmup 方法）
- [x] 包含快取失效邏輯（flushByTags, cleanup 方法）
- [x] 完整的統計資訊追蹤（getStats 方法）
- [x] 註冊到 DI 容器並自動注入
- [x] 執行 CI 檢查（PHP CS Fixer + PHPStan + PHPUnit）確認無錯誤
- [x] 通過 PHPStan Level 10 檢查

#### ✅ T4.3 - 建立統計計算定時任務
**描述**：建立定期計算統計快照的背景任務
**預估時間**：3 小時
**依賴**：T4.2
**狀態**：✅ 完成
**驗收標準**：
- [x] `StatisticsCalculationCommand` 類別
- [x] 支援不同統計週期（daily, weekly, monthly）
- [x] 包含錯誤重試機制
- [x] 記錄執行日誌
- [x] 可手動觸發執行
- [x] 支援並行安全執行
- [x] 建立完整的單元測試，涵蓋各種執行情境
- [x] 提供 CLI 執行腳本 `scripts/statistics-calculation.php`
- [x] 註冊到 DI 容器並自動注入
- [x] 執行 CI 檢查（PHP CS Fixer + PHPStan + PHPUnit）確認無錯誤
- [x] 通過 PHPStan Level 10 檢查

#### ✅ T4.4 - 建立索引最佳化與查詢調整
**描述**：統計相關表的索引最佳化與SQL查詢調整
**預估時間**：4 小時
**依賴**：T4.3
**狀態**：✅ 已完成
**驗收標準**：
- [x] 建立 posts 表統計查詢複合索引
- [x] 測試索引效能改善效果
- [x] 最佳化大量資料統計查詢
- [x] 建立慢查詢監控
- [x] 檢查查詢執行計劃
- [x] 效能測試報告
- [x] 執行 CI 檢查（PHP CS Fixer + PHPStan + PHPUnit）確認無錯誤

#### ✅ T4.5 - 建立統計監控與日誌系統
**描述**：建立統計功能的監控、日誌記錄與健康檢查
**預估時間**：5 小時
**依賴**：T4.4
**驗收標準**：
- [ ] `StatisticsMonitoringService` 類別
- [ ] 統計計算時間監控
- [ ] 快取命中率監控
- [ ] API 回應時間監控
- [ ] 錯誤率記錄與警告
- [ ] 統計健康檢查端點
- [ ] 日誌輪轉與保存策略

#### 🔲 T4.6 - 建立統計資料回填指令
**描述**：建立一個指令，用於對歷史資料重新計算並生成統計快照
**預估時間**：3 小時
**依賴**：T4.3
**驗收標準**：
- [ ] `StatisticsRecalculationCommand` 類別
- [ ] 支援按統計類型和日期範圍進行回填
- [ ] 提供 `--force` 選項覆蓋現有快照
- [ ] 包含進度顯示和日誌記錄
- [ ] 指令可安全地重複執行

#### 🔲 T4.7 - 整合領域事件分派器
**描述**：將統計領域事件整合到專案的事件分派機制中
**預估時間**：3 小時
**依賴**：T1.5
**驗收標準**：
- [ ] 建立 `PostViewed` 事件的監聽器，觸發非同步計數更新
- [ ] 建立 `StatisticsSnapshotCreated` 事件的監聽器，觸發快取失效或預熱
- [ ] 若專案尚無事件機制，則引入一個輕量級的事件分派器
- [ ] 事件處理邏輯有完整的測試

#### 🔲 T4.8 - 建立統計功能設定檔
**描述**：建立並設定統計功能的獨立設定檔
**預估時間**：1 小時
**依賴**：T4.2
**驗收標準**：
- [ ] 在 `config/` 目錄下建立 `statistics.php`
- [ ] 將快取 TTL、排程時間、資料保存期限等配置移入此檔案
- [ ] 應用程式能正確讀取此設定檔

---

### 階段 5：介面層實作 (Interface Layer)

#### ✅ T5.1 - 建立統計查詢 API 控制器
**描述**：建立統計查詢的 RESTful API 端點
**預估時間**：4 小時
**依賴**：T4.7
**狀態**：✅ 已完成
**驗收標準**：
- [x] `StatisticsController` 類別
- [x] GET `/api/statistics/overview` 端點
- [x] GET `/api/statistics/posts` 端點
- [x] GET `/api/statistics/sources` 端點
- [x] GET `/api/statistics/users` 端點
- [x] GET `/api/statistics/popular` 端點
- [x] 完整的參數驗證（包含最大查詢範圍限制）
- [x] 標準化的回應格式
- [x] 完整的錯誤處理
- [x] 整合 `statistics:read` 權限驗證
- [x] 執行 CI 檢查（PHP CS Fixer + PHPStan + PHPUnit）確認無錯誤
- [x] 通過 PHPStan Level 10 檢查

#### ✅ T5.2 - 建立統計管理 API 控制器
**描述**：建立管理員專用的統計管理功能
**預估時間**：3 小時
**依賴**：T5.1
**狀態**：✅ 已完成
**驗收標準**：
- [x] `StatisticsAdminController` 類別
- [x] POST `/api/admin/statistics/refresh` 端點
- [x] DELETE `/api/admin/statistics/cache` 端點
- [x] GET `/api/admin/statistics/health` 端點（包含詳細檢查）
- [x] 管理員權限驗證
- [x] 操作活動日誌記錄
- [x] 執行 CI 檢查（PHP CS Fixer + PHPStan + PHPUnit）確認無錯誤
- [x] 通過 PHPStan Level 10 檢查

#### ✅ T5.3 - 更新 API 路由配置
**描述**：在路由配置中註冊統計相關端點
**預估時間**：1 小時
**依賴**：T5.2
**狀態**：✅ 已完成
**驗收標準**：
- [x] 統計查詢路由正確註冊
- [x] 統計管理路由正確註冊
- [x] 路由群組和中介軟體正確設定
- [x] API 版本控制正確實作
- [x] 路由測試通過
- [x] 執行 CI 檢查（PHP CS Fixer + PHPStan + PHPUnit）確認無錯誤

#### 🔲 T5.4 - 建立文章瀏覽數追蹤端點
**描述**：建立用於記錄文章瀏覽行為的 API 端點
**預估時間**：2 小時
**依賴**：T4.7
**驗收標準**：
- [ ] `PostViewController` 類別
- [ ] POST `/api/posts/{id}/view` 端點
- [ ] 呼叫此端點會發布 `PostViewed` 領域事件
- [ ] 包含速率限制防止濫用
- [ ] 回應時間極短（< 100ms）
- [ ] 執行 CI 檢查（PHP CS Fixer + PHPStan + PHPUnit）確認無錯誤

---

### 階段 6：測試實作 (Testing)

#### 🔲 T6.1 - 建立統計領域服務單元測試
**描述**：為統計領域服務撰寫完整的單元測試
**預估時間**：6 小時
**依賴**：T1.5
**驗收標準**：
- [ ] `StatisticsCalculatorServiceTest` 類別
- [ ] `PostStatisticsServiceTest` 類別
- [ ] 測試覆蓋率 ≥ 95%
- [ ] 包含正常與異常情況測試
- [ ] 使用 Mock 物件隔離依賴
- [ ] 測試案例名稱清楚表達意圖
- [ ] 所有測試都能通過
- [ ] 執行 CI 檢查（PHP CS Fixer + PHPStan + PHPUnit）確認無錯誤

#### ✅ T6.2 - 建立統計 Repository 單元測試
**描述**：為統計 Repository 撰寫單元測試
**預估時間**：5 小時
**依賴**：T4.1
**驗收標準**：
- [ ] `StatisticsRepositoryTest` 類別
- [ ] `PostStatisticsRepositoryTest` 類別
- [ ] 測試覆蓋率 ≥ 95%
- [ ] 包含資料庫互動測試
- [ ] 使用測試資料庫
- [ ] 測試資料清理機制
- [ ] 所有測試都能通過
- [ ] 執行 CI 檢查（PHP CS Fixer + PHPStan + PHPUnit）確認無錯誤

#### ✅ T6.3 - 建立統計 API 整合測試
**描述**：為統計 API 撰寫整合測試
**預估時間**：4 小時
**依賴**：T5.2
**驗收標準**：
- [ ] `StatisticsApiTest` 類別
- [ ] `StatisticsAdminApiTest` 類別
- [ ] 測試所有 API 端點
- [ ] 包含認證和授權測試
- [ ] 測試回應格式和狀態碼
- [ ] 包含錯誤情況測試
- [ ] 所有測試都能通過
- [ ] 執行 CI 檢查（PHP CS Fixer + PHPStan + PHPUnit）確認無錯誤

#### ✅ T6.4 - 建立統計快取功能測試
**描述**：為統計快取機制撰寫功能測試
**預估時間**：3 小時
**依賴**：T4.2
**驗收標準**：
- [ ] `StatisticsCacheTest` 類別
- [ ] 測試快取命中和失效
- [ ] 測試快取預熱機制
- [ ] 測試快取標籤功能
- [ ] 包含並行存取測試
- [ ] 所有測試都能通過
- [ ] 執行 CI 檢查（PHP CS Fixer + PHPStan + PHPUnit）確認無錯誤

#### ✅ T6.5 - 建立統計效能測試
**描述**：為統計功能建立效能基準測試
**預估時間**：4 小時
**依賴**：T6.4
**驗收標準**：
- [ ] `StatisticsPerformanceTest` 類別
- [ ] 測試大量資料統計計算
- [ ] 測試並發 API 請求處理
- [ ] 測試快取效能表現
- [ ] 測試記憶體使用量
- [ ] 效能基準驗證
- [ ] 生成效能測試報告
- [ ] 執行 CI 檢查（PHP CS Fixer + PHPStan + PHPUnit）確認無錯誤

#### 🔲 T6.6 - 建立指令與事件功能測試
**描述**：為新增的指令和事件監聽器撰寫測試
**預估時間**：4 小時
**依賴**：T4.6, T4.7, T5.4
**驗收標準**：
- [ ] `StatisticsRecalculationCommandTest` 類別
- [ ] `PostViewedListenerTest` 類別
- [ ] `PostViewControllerTest` 類別
- [ ] 測試覆蓋率 ≥ 95%
- [ ] 所有測試都能通過
- [ ] 執行 CI 檢查（PHP CS Fixer + PHPStan + PHPUnit）確認無錯誤

---

### 階段 7：文件與部署 (Documentation & Deployment)

#### 🔲 T7.1 - 更新 Swagger API 文件
**描述**：為新的統計 API 建立 Swagger 文件
**預估時間**：2 小時
**依賴**：T5.2
**驗收標準**：
- [ ] 所有統計 API 端點包含在 Swagger 文件中
- [ ] 包含 `POST /api/posts/{id}/view` 端點
- [ ] 完整的請求/回應範例
- [ ] 參數說明和驗證規則
- [ ] 錯誤回應格式說明
- [ ] 認證要求說明
- [ ] 文件可正確產生和顯示
- [ ] 執行 CI 檢查（PHP CS Fixer + PHPStan + PHPUnit）確認無錯誤

#### ✅ T7.2 - 撰寫統計功能使用指南
**描述**：建立統計功能的使用和維護文件
**預估時間**：3 小時
**依賴**：T7.1
**驗收標準**：
- [ ] 功能概覽和使用場景
- [ ] API 使用範例和最佳實踐
- [ ] 效能調整指南
- [ ] 故障排除手冊
- [ ] 維護和監控建議
- [ ] 文件格式清晰易讀
- [ ] 執行 CI 檢查（PHP CS Fixer + PHPStan + PHPUnit）確認無錯誤

#### ✅ T7.3 - 建立資料庫遷移指南
**描述**：建立統計功能資料庫遷移的操作指南
**預估時間**：1 小時
**依賴**：T2.3
**驗收標準**：
- [ ] 遷移步驟詳細說明
- [ ] 資料備份建議
- [ ] 回滾程序說明
- [ ] 效能影響評估
- [ ] 生產環境部署檢查清單
- [ ] 執行 CI 檢查（PHP CS Fixer + PHPStan + PHPUnit）確認無錯誤

#### ✅ T7.4 - 建立統計功能操作手冊
**描述**：建立統計功能的日常維護和故障處理手冊
**預估時間**：3 小時
**依賴**：T7.3
**驗收標準**：
- [ ] 統計功能日常監控指南
- [ ] 快取管理操作手冊
- [ ] 常見問題與解決方案
- [ ] 統計資料异常處理程序
- [ ] 性能調整建議
- [ ] 緊急情況應對流程
- [ ] 執行 CI 檢查（PHP CS Fixer + PHPStan + PHPUnit）確認無錯誤

---

### 階段 8：品質保證與最佳化 (Quality Assurance)

#### 🔲 T8.1 - 執行完整的程式碼品質檢查
**描述**：確保所有新增程式碼符合專案品質標準
**預估時間**：2 小時
**依賴**：T6.4
**驗收標準**：
- [ ] 通過 PHP CS Fixer 檢查
- [ ] 通過 PHPStan Level 10 分析
- [ ] 程式碼覆蓋率 ≥ 90%
- [ ] 沒有程式碼異味（Code Smells）
- [ ] 符合 PSR 標準
- [ ] 執行 CI 檢查（PHP CS Fixer + PHPStan + PHPUnit）確認無錯誤

#### ✅ T8.2 - 效能測試與最佳化
**描述**：進行統計功能的效能測試和最佳化
**預估時間**：4 小時
**依賴**：T8.1
**驗收標準**：
- [ ] API 回應時間 < 2 秒
- [ ] 支援並行查詢測試
- [ ] 記憶體使用量合理
- [ ] 資料庫查詢最佳化
- [ ] 快取命中率 ≥ 80%
- [ ] 效能測試報告完整
- [ ] 執行 CI 檢查（PHP CS Fixer + PHPStan + PHPUnit）確認無錯誤

#### ✅ T8.3 - 安全性審查
**描述**：對統計功能進行安全性審查
**預估時間**：2 小時
**依賴**：T8.2
**驗收標準**：
- [ ] 權限控制正確實作
- [ ] 輸入驗證完整
- [ ] 防止 SQL 注入
- [ ] 敏感資料保護
- [ ] 審計日誌完整
- [ ] 安全測試通過
- [ ] 執行 CI 檢查（PHP CS Fixer + PHPStan + PHPUnit）確認無錯誤

---

## 📊 進度追蹤

### 完成狀態
- [x] 階段 1：領域層設計 (5/5) ✅ 完成
- [x] 階段 2：資料庫結構調整 (4/4) ✅ 完成
- [x] 階段 3：應用層服務 (3/3) ✅ 完成
- [ ] 階段 4：基礎設施層實作 (5/8) 🚧 進行中
- [ ] 階段 5：介面層實作 (3/4) 🚧 進行中
- [ ] 階段 6：測試實作 (0/6)
- [ ] 階段 7：文件與部署 (0/4)
- [ ] 階段 8：品質保證與最佳化 (0/3)

### 總體進度
**18/41 項任務完成 (43.9%)**

**🎉 第一階段（領域層設計）已完成！**
**🎉 第二階段（資料庫結構調整）已完成！**
**🎉 第三階段（應用層服務）已完成！**
**🚀 第四階段（基礎設施層實作）進行中：已完成 T4.1-T4.5**
**🚀 第五階段（介面層實作）進行中：已完成 T5.1-T5.3**

### 預估工作量
- **總預估時間**：123 小時
- **已完成時間**：43 小時
- **剩餘時間**：80 小時
- **建議開發週期**：4-5 週
- **每日開發時間**：6-8 小時

---

## 🚨 風險與注意事項

### 技術風險
1. **效能風險**：大量資料統計可能影響系統效能
   - **緩解措施**：實作快取機制和定期預計算。瀏覽數等高頻寫入操作採用非同步處理。
2. **資料一致性**：快照資料可能與即時資料不同步
   - **緩解措施**：設計合理的更新頻率和手動重新整理機制。
3. **事件驅動複雜度**：引入事件機制會增加系統的複雜度。
   - **緩解措施**：從輕量級實作開始，並確保有足夠的日誌和監控來追蹤事件流。

### 業務風險
1. **需求變更**：統計需求可能在開發過程中變更
   - **緩解措施**：保持設計彈性，定期與需求方確認
2. **資料隱私**：統計可能洩露敏感使用者資訊
   - **緩解措施**：實作適當的資料匿名化、權限控制和匿名化閾值。

### 開發風險
1. **開發週期**：功能複雜度可能超出預期
   - **緩解措施**：分階段開發，優先實作核心功能
2. **測試覆蓋**：複雜的統計邏輯難以完全測試
   - **緩解措施**：採用 TDD 方法，重點測試關鍵業務邏輯

---

## 📈 最新進展記錄

### 2025年9月25日 - T5.3 更新 API 路由配置完成
- ✅ 建立 `/backend/config/routes/statistics.php` 統計路由配置檔案
- ✅ 註冊 StatisticsController 的 5 個查詢端點（GET /api/statistics/{overview,posts,sources,users,popular}）
- ✅ 註冊 StatisticsAdminController 的 3 個管理端點（POST /refresh, DELETE /cache, GET /health）
- ✅ 設定適當的中介軟體：jwt.auth（查詢API）、jwt.auth + jwt.authorize（管理API）
- ✅ 更新 RoutingServiceProvider 將統計路由加入路由載入清單
- ✅ 建立完整的路由整合測試 StatisticsRoutingTest（13個測試方法）
- ✅ 通過 PHPStan Level 10、PHP CS Fixer、PHPUnit 全部品質檢查（1855個測試通過）
- 🎯 總體進度提升至 43.9%（18/41 項任務完成）

### 2025年9月25日 - T5.2 統計管理 API 控制器完成
- ✅ 成功實作 StatisticsAdminController 類別，包含 3 個管理端點
- ✅ 實作 POST /api/admin/statistics/refresh 手動刷新統計資料端點
- ✅ 實作 DELETE /api/admin/statistics/cache 清除統計快取端點
- ✅ 實作 GET /api/admin/statistics/health 系統健康檢查端點
- ✅ 整合管理員權限驗證（admin.*, statistics.*, statistics.admin）
- ✅ 添加完整的活動日誌記錄功能（使用者、IP、操作詳情）
- ✅ 添加完整的 OpenAPI 3.0 文件註解和 API 說明
- ✅ 通過 PHPStan Level 10、PHP CS Fixer、PHPUnit 全部品質檢查
- 🎯 總體進度提升至 41.5%（17/41 項任務完成）

### 2025年9月25日 - T5.1 統計查詢 API 控制器完成
- ✅ 成功實作 StatisticsController 類別，包含 5 個統計查詢端點
- ✅ 實作完整的參數驗證機制（日期格式、範圍檢查、枚舉值驗證）
- ✅ 整合 statistics.read 權限驗證和標準化錯誤處理
- ✅ 添加完整的 OpenAPI 3.0 文件註解
- ✅ 通過 PHPStan Level 10、PHP CS Fixer、PHPUnit 全部品質檢查

---

## �📝 決策記錄

### 設計決策
1. **選擇快照機制**：採用統計快照而非即時計算，以提升效能
2. **DDD 架構**：嚴格遵循 DDD 分層架構，確保代碼品質
3. **快取策略**：採用 Redis 多層次快取，支援快取標籤管理
4. **事件驅動**：引入領域事件處理高頻操作（如瀏覽數）和模組解耦。

### 技術決策
1. **資料庫設計**：使用 JSON 欄位儲存複雜統計資料，提升彈性
2. **API 設計**：遵循 RESTful 原則，支援標準化回應格式
3. **測試策略**：採用 TDD 方法，確保測試覆蓋率 ≥ 90%
