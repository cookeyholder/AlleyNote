# PHPStan Level 10 錯誤分析報告

## 總覽
- **總錯誤數**: 1281 個錯誤
- **受影響檔案**: 267 個檔案
- **錯誤嚴重程度**: Level 10 (最高嚴格度)

## 錯誤分類與優先級

### 高優先級 (影響基礎功能)

#### 1. Array 型別規格缺失 (missingType.iterableValue) - 約 300+ 錯誤
**影響檔案**:
- `Application/DTOs/Statistics/*` (所有統計相關 DTO)
- `Domains/Auth/DTOs/*` (認證相關 DTO)
- `Infrastructure/Http/*` (HTTP 處理)
- `Shared/Cache/*` (快取系統)

**問題**: 陣列參數和屬性缺少明確的值型別規格
```php
// 錯誤示例
array $data  // 缺少值型別
// 正確示例
array<string, mixed> $data
```

#### 2. 參數型別不匹配 (argument.type) - 約 200+ 錯誤
**影響檔案**:
- `Application/Middleware/JwtAuthorizationMiddleware.php` (29 錯誤)
- `Domains/Auth/Services/*` (認證服務)
- `Infrastructure/Repositories/*` (資料庫層)

**問題**: 方法呼叫時參數型別不符合預期

#### 3. 回傳型別不匹配 (return.type) - 約 150+ 錯誤
**影響檔案**:
- `SourceDistributionDTO.php` (36 錯誤)
- `StatisticsOverviewDTO.php`
- Repository 層檔案

### 中優先級 (型別安全改善)

#### 4. 混合型別存取 (property.nonObject, method.nonObject) - 約 100+ 錯誤
**影響檔案**:
- `SourceDistributionDTO.php` (多個 property.nonObject 錯誤)
- `Infrastructure/Routing/*`

#### 5. 空值合併運算子問題 (nullCoalesce.offset) - 約 50+ 錯誤
**影響檔案**:
- `StatisticsController.php` (2 錯誤)
- `Infrastructure/Routing/*`

### 低優先級 (最佳化提醒)

#### 6. 總是為真/假的條件檢查 (function.alreadyNarrowedType) - 約 100+ 錯誤
**影響檔案**:
- 各種檔案中的 `is_array()`, `is_string()` 等檢查

#### 7. 不可達程式碼 (deadCode.unreachable) - 約 10+ 錯誤

## 重點修復檔案

### 最高優先級
1. **Application/DTOs/Statistics/SourceDistributionDTO.php** (36 錯誤)
2. **Application/Middleware/JwtAuthorizationMiddleware.php** (29 錯誤)
3. **Application/DTOs/Statistics/StatisticsOverviewDTO.php** (16 錯誤)

### 系統核心檔案
4. **Infrastructure/Http/ServerRequest.php** (大量型別規格問題)
5. **Infrastructure/Routing/Core/Router.php** (路由系統)
6. **Shared/Validation/Validator.php** (驗證系統)

## 修復策略

### 階段 1: 基礎型別修復 (1-2 天)
- 修復所有 `missingType.iterableValue` 錯誤
- 專注於 DTO 類別的陣列型別規格

### 階段 2: 參數型別對齊 (1-2 天)
- 修復 `argument.type` 錯誤
- 確保方法呼叫的型別一致性

### 階段 3: 回傳型別規範 (1 天)
- 修復 `return.type` 錯誤
- 統一回傳型別規格

### 階段 4: 細節最佳化 (1 天)
- 處理空值檢查和條件判斷
- 清理不必要的程式碼

## 自動化修復腳本需求

1. **Array Type Fixer**: 自動添加陣列型別規格
2. **Parameter Type Aligner**: 修復參數型別不匹配
3. **Return Type Standardizer**: 統一回傳型別格式

## 風險評估

- **低風險**: Array 型別規格添加
- **中風險**: 參數型別修改可能影響現有邏輯
- **高風險**: 回傳型別變更可能破壞相依性

## 建議執行順序

1. 先備份當前工作狀態
2. 建立測試腳本驗證修復效果
3. 分批次修復，每次提交前執行完整測試
4. 優先修復 DTO 和基礎結構類別
5. 最後處理業務邏輯層的細節調整
