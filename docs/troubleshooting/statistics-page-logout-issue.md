# 系統統計頁面登出問題 - 完整調查報告

## 問題描述
登入後訪問「系統統計」頁面時，會被導回登入頁面，用戶無法使用統計功能。

## 調查過程

### 第一次嘗試（錯誤方向）
**假設**：JWT 認證中介軟體的參數傳遞錯誤  
**修復**：移除 `AuthServiceProvider` 中傳遞給 `JwtAuthenticationMiddleware` 的多餘參數  
**結果**：問題仍然存在

### 第二次調查（找到根本原因）
**方法**：深入檢查日誌文件  
**發現**：
```log
✅ Token 驗證成功
繼續執行後續處理器...
❌ 認證驗證失敗: Entry "StatisticsQueryServiceInterface" cannot be resolved: 
   the class is not instantiable
```

**關鍵發現**：
1. JWT 認證中介軟體**實際上運作正常** ✅
2. Token 驗證**成功** ✅
3. 問題發生在**Controller 執行時** ❌
4. DI 容器無法解析 `StatisticsQueryServiceInterface` ❌

## 根本原因

### 技術分析

在 `StatisticsServiceProvider` 中：

```php
// ✅ 有這個（具體類別的綁定）
StatisticsQueryService::class => \DI\factory(function (...) {
    return new StatisticsQueryService(...);
}),

// ❌ 缺少這個（介面的綁定）
// StatisticsQueryServiceInterface::class => \DI\get(StatisticsQueryService::class),
```

### 依賴鏈

```
StatisticsChartController
    ↓ 需要
StatisticsVisualizationService
    ↓ 需要
StatisticsQueryAdapter
    ↓ 需要
StatisticsQueryServiceInterface ← 無法解析！
```

因為 `StatisticsQueryServiceInterface` 沒有綁定到 DI 容器，所以整個依賴鏈都無法建立。

### 為什麼會導致「登出」現象？

1. 用戶訪問 `/admin/statistics`
2. 前端 JS 呼叫統計 API：`/api/statistics/overview`
3. JWT 認證成功 ✅
4. 嘗試建立 `StatisticsChartController` 實例
5. DI 容器無法解析依賴 ❌
6. 拋出 500 錯誤
7. 前端收到錯誤，誤判為認證失敗
8. 導向登入頁面

## 解決方案

在 `StatisticsServiceProvider.php` 中新增介面綁定：

```php
// 綁定介面到實作
StatisticsQueryServiceInterface::class => \DI\get(StatisticsQueryService::class),
```

## 修復驗證

### 1. 日誌驗證
```log
✅ Token 驗證成功
繼續執行後續處理器...
=== JwtAuthenticationMiddleware::process END (SUCCESS) ===
```

### 2. E2E 測試驗證
```bash
✓ 應該能訪問「系統統計」頁面且不被導回登入頁 (2.6s)
```

### 3. 單元測試驗證
所有統計相關測試通過 ✅

### 4. 靜態分析驗證
PHPStan Level 10 無錯誤 ✅

## 學到的教訓

### 1. 不要假設問題的原因
- ❌ 看到「導回登入頁」就假設是認證問題
- ✅ 應該先檢查完整的日誌和錯誤訊息

### 2. DI 容器錯誤的症狀
- DI 容器無法解析依賴也會導致 500 錯誤
- 前端可能會誤判為認證失敗
- 症狀與認證問題相似但根本原因完全不同

### 3. 完整的介面綁定很重要
```php
// 不夠完整
Service::class => \DI\create(Service::class)

// 完整的綁定
Service::class => \DI\create(Service::class),
ServiceInterface::class => \DI\get(Service::class)
```

### 4. 日誌是關鍵
JWT 中介軟體的詳細日誌幫助我們快速定位問題：
```log
✅ 開始驗證
Token extracted: eyJ0eXAiOiJKV1Q...
開始驗證 Token...
✅ Token 驗證成功
繼續執行後續處理器...
❌ 認證驗證失敗: Entry "StatisticsQueryServiceInterface" cannot be resolved
```

## 相關提交

1. `342f8706` - fix(auth): 修正 JWT 認證中介軟體的服務提供者參數錯誤
   - 這個修復是有價值的，修正了真實的參數錯誤
   - 但不是導致統計頁面問題的根本原因

2. `a229e949` - test(e2e): 新增管理員側欄導航測試以防止認證重導向錯誤
   - 建立了完整的測試套件
   - 防止未來再次出現類似問題

3. `e62c30ed` - fix(di): 修正統計查詢服務的依賴注入綁定缺失 ⭐
   - **真正修復統計頁面問題的提交**

## 最終結論

問題的根本原因是 **DI 容器配置不完整**，不是認證問題。

修復方法：在 `StatisticsServiceProvider` 中新增 `StatisticsQueryServiceInterface` 的綁定。

這個案例告訴我們：
1. 症狀（被導回登入頁）不等於根本原因（認證失敗）
2. 深入的日誌分析是找到真相的關鍵
3. DDD 架構中，完整的介面綁定至關重要
