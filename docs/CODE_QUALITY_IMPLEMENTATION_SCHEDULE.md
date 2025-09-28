# 程式碼品質改善實作優先順序與時間表

## 🎯 實際數據與目標

**當前狀況** (根據實際分析結果):
- **PSR-4 合規率**: 81.79% (301/368 檔案)
- **現代 PHP 特性**: 已有良好基礎 (枚舉9個、match表達式70個、聯合型別119個、readonly屬性93個)
- **DDD 結構**: 100%完整性 (53個組件，但缺少聚合根設計)

**改善目標**:
- **PSR-4 合規率**: 81.79% → **95%+** (修復85個問題)
- **現代 PHP 特性**: 補強建構子屬性提升、屬性標籤、空安全運算子等0使用次數的特性
- **DDD 結構**: 在現有基礎上增加聚合根設計

---

## 📋 第一週：PSR-4 合規性修復 (優先級：🔥 最高)

### Day 1: 修復命名空間問題 (25個檔案)
**檔案清單**:
- ✅ `app/Shared/OpenApi/OpenApiConfig.php` - 添加命名空間
- ✅ `app/Shared/Helpers/functions.php` - 添加命名空間
- ✅ `app/Infrastructure/Config/container.php` - 添加命名空間
- ❌ `scripts/` 目錄下22個缺少命名空間的腳本

**預期效果**: PSR-4 合規率 +6-8% → 87-89%

### Day 2: 修復類別與檔案名稱不一致 (15個檔案)
**主要問題檔案**:
- ❌ `app/Application/Controllers/TestController.php` (HealthController 類別)
- ❌ `app/Application/Controllers/BaseController.php` (包含JsonFlag枚舉)
- ❌ `app/Infrastructure/Services/OutputSanitizer.php` (包含SanitizerMode枚舉)

**解決方案**:
1. 將枚舉提取到獨立檔案
2. 重新命名錯誤的類別或檔案

**預期效果**: PSR-4 合規率 +3-4% → 90-93%

### Day 3: 修復命名空間路徑不符問題 (45個檔案)
**主要問題**:
- ❌ `app/Application.php` - 根層級檔案的命名空間處理
- ❌ Scripts目錄的檔案結構重組

**預期效果**: PSR-4 合規率 +2-3% → 92-96%

---

## 📋 第二週：現代 PHP 特性補強 (優先級：🔥 高)

### Day 4-5: 型別宣告大規模修復 (200+ 函式)
**重點檔案**:
- ❌ 所有 DTO 類別的 `toArray()` 方法
- ❌ Contract 介面的方法
- ❌ `app/Application.php` 中的3個函式

**實作方式**: 建立自動化腳本掃描和修復

### Day 6: 建構子屬性提升重構 (15-20個類別)
**候選類別**:
- ❌ 所有 DTO 類別
- ❌ Value Object 類別
- ❌ 配置類別

**預期效果**: 減少樣板程式碼15-20%

### Day 7: 枚舉型別擴充 (新增12個枚舉)
**新增枚舉清單**:
1. ❌ `HttpStatusCode` (200, 401, 403, 404, 500等)
2. ❌ `CacheType` (MEMORY, REDIS, FILE等)
3. ❌ `LogLevel` (DEBUG, INFO, WARNING, ERROR等)
4. ❌ `DatabaseAction` (CREATE, READ, UPDATE, DELETE等)
5. ❌ `SecurityLevel` (LOW, MEDIUM, HIGH, CRITICAL等)
6. ❌ `ValidationRule` (REQUIRED, EMAIL, LENGTH等)
7. ❌ `EventType` (USER_ACTION, SYSTEM, ERROR等)
8. ❌ `ApiVersion` (V1, V2等)
9. ❌ `ContentType` (JSON, XML, HTML等)
10. ❌ `UserRole` (ADMIN, USER, GUEST等)
11. ❌ `PostType` (ARTICLE, NEWS, TUTORIAL等)
12. ❌ `NotificationType` (EMAIL, SMS, PUSH等)

---

## 📋 第三週：DDD 聚合根設計 (優先級：🟡 中高)

### Day 8-9: 聚合根識別與設計
**候選聚合根**:
1. ❌ **Post 聚合** - 核心業務聚合
   - 實體: Post
   - 值物件: PostTitle, PostContent, PostSlug
   - 行為: publish(), archive(), updateContent()

2. ❌ **User 聚合** - 用戶管理聚合
   - 實體: User
   - 值物件: UserId, Email, Password
   - 行為: register(), updateProfile(), authenticate()

3. ❌ **Statistics 聚合** - 統計資料聚合
   - 實體: StatisticsSnapshot
   - 值物件: StatisticsPeriod, StatisticsMetric
   - 行為: calculate(), snapshot(), export()

### Day 10: 聚合邊界定義與實作
- ❌ 定義聚合間的通信協議
- ❌ 實作聚合根的行為方法
- ❌ 建立聚合持久化策略

---

## 📋 第四週：測試與優化 (優先級：🟡 中)

### Day 11-12: 測試覆蓋率建立
- ❌ 為所有重構的類別建立單元測試
- ❌ 為新的聚合根建立行為測試
- ❌ 為新的枚舉建立測試

### Day 13: 效能驗證與優化
- ❌ 執行效能基準測試
- ❌ 分析重構對效能的影響
- ❌ 必要時進行優化調整

### Day 14: 文件更新與代碼審查
- ❌ 更新技術文件
- ❌ 準備代碼審查材料
- ❌ 建立最佳實踐範例

---

## 🎯 具體執行步驟 (立即開始)

### 第一步: 修復最關鍵的PSR-4問題

**立即執行**: 修復3個高影響檔案
```bash
# 1. 修復 TestController 類別名稱問題
# 2. 提取 BaseController 中的 JsonFlag 枚舉
# 3. 提取 OutputSanitizer 中的 SanitizerMode 枚舉
```

**預期時間**: 2小時
**預期效果**: PSR-4 合規率 +2-3%

### 第二步: Scripts 目錄結構重組

**立即執行**: 重組 scripts 目錄結構
```bash
scripts/
├── lib/              # 共用類別
├── Analysis/         # 分析工具
├── Database/         # 資料庫工具
├── Deployment/       # 部署工具
├── Maintenance/      # 維護工具
└── Quality/          # 品質檢查工具
```

**預期時間**: 4小時
**預期效果**: PSR-4 合規率 +4-5%

### 第三步: 建立自動化型別宣告修復工具

**立即執行**: 建立腳本自動掃描和修復缺少型別宣告的函式
**預期時間**: 3小時
**預期效果**: 現代 PHP 特性採用率大幅提升

---

## ⚠️ 風險控制措施

### 高風險操作
1. **Scripts 目錄重組** - 可能影響 CI/CD 管道
2. **聚合根重構** - 可能影響資料存取邏輯
3. **大量型別宣告** - 可能引入型別錯誤

### 風險控制
1. **階段性提交** - 每完成一個小模組就提交
2. **測試先行** - 重構前先建立測試
3. **漸進式部署** - 分批次合併到主分支
4. **回滾準備** - 為每個重大變更準備回滾計劃

---

## 📈 成功衡量指標

**技術指標**:
- PSR-4 合規率: 81.79% → 95%+ (**+13.21%**)
- 型別宣告覆蓋率: 提升50%+
- 枚舉使用: 9個 → 21個 (**+133%**)
- 聚合根數量: 0個 → 3個
- 程式碼重複率: 降低20%+

**品質指標**:
- PHPStan Level 10: 100% 通過
- 測試覆蓋率: +10%
- 技術債務: 減少30%+

**開發效率指標**:
- 新功能開發時間: 減少15%
- Bug 修復時間: 減少20%
- 代碼審查效率: 提升25%

---

這個計劃提供了具體的、可執行的步驟來系統性地改善這三項程式碼品質指標，並且基於實際的分析數據制定了切實可行的目標。
